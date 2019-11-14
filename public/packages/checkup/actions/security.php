<?php
/*
    Some Rights Reserved, Cedric Francoys, 2019, Brussels
    Licensed under GNU GPL 3 license <http://www.gnu.org/licenses/>
*/
use qinoa\http\HttpUriHelper;
use qinoa\http\HttpRequest;
use checkup\Report;
use checkup\Result;
use checkup\Test;
use checkup\TestCategory;

list($params, $providers) = announce([
    'description'   => "Runs security checks.",
    'response'      => [
        'content-type'  => 'application/json',
        'charset'       => 'UTF-8',
        'accept-origin' => '*'
    ],
    'params'        => [
        'report_id' => [
            'description'   => 'identifier of the report this test should be assigned to.',
            'type'          => 'integer',
            'required'      => true,            
            'min'           => 1
        ]        
    ],
    'providers'     => ['context']
]);

if(!in_array('curl', get_loaded_extensions())) {
    throw new Exception('Dependency is missing: cURL', QN_ERROR_INVALID_CONFIG);
}

list($context) = [ $providers['context'] ];

list($report_id) = [ $params['report_id'] ];


// check received report ID validity
$report = Report::search(['id', '=', $report_id])->read(['id', 'date', 'domain', 'url', 'content'])->first();
if(!$report) throw new Exception('Unknown report', QN_ERROR_INVALID_PARAM); 

// retrieve URL
$domain = $report['domain'];
$url = $report['url'];
$content = $report['content'];

// search for SEC results for given report
$category = TestCategory::search(['name', '=', 'SEC'])
                        ->read(['tests_ids'])
                        ->first();

$results = Result::search([['report_id', '=', $report_id], ['test_id', 'in', $category['tests_ids']]])
                   ->read(['value', 'pass', 'test_id' => ['name', 'description']])
                   ->get();

// if report hasn't already been processed yet, run checks
if( count($results) == 0 ) {

    // init values to be retrieved
    $ssl = false;
    $cms = false;
    $cms_brand = 'unknown';
    $tree_secure = true;
    $js_vulnerabilities = [];    


    if( strpos($url, 'https') === 0) {
        $ssl = true;
    }

    // find call to external dependencies referred in the HTML
    preg_match_all('/<[script].*src=[\'"]([^"]*)[\'"].*\/?>/iU', $content, $matches);
    foreach($matches[1] as $uri) {
        // blah-[version](.min).js
        // blah(.min).js?ver=[version]        
        $dependency = str_replace(['.min', '.js'], '', basename(HttpUriHelper::getPath($uri)));
        
        $query = HttpUriHelper::getQuery($uri);
        if(stripos($query, 'ver=') !== false) {
            list($ver, $version) = explode('=', $query);
        }
        else {
            if(preg_match ( '/-([0-9.]*)\./i' , $uri, $res)) {
                $version = $res[1];
                $dependency = str_replace('-'.$version, '', $dependency);
            }
            else continue;
        }
        // check for known vulnerabilities
        if(strcasecmp ($dependency, 'jQuery') == 0) {            
            if(version_compare($version, '3.3.0') < 0) $js_vulnerabilities[] = ['dependency' => $dependency, 'version' => $version];
            // https://snyk.io/vuln/npm:jquery?lh=$version
        }

    }

    $evidences = [
        'wordpress' => ['wordpress', 'wp-content', 'wp-includes', '.w.org', '.wp.com', 'wp.me'],
        'drupal'    => ['drupal'],
        'joomla'    => ['joomla', '/media/jui/'],
        'typo3'     => ['t3js', 't3o'],
        'spip'      => ['SPIP', 'spip.php'],
        'wix'       => ['Wix.com Website Builder', 'X-Wix-Meta-Site-Id']        
    ];        

    foreach($evidences as $tested_cms => $clues) {
        foreach($clues as $clue) {
            if(strpos($content, $clue) !== false) {
                $cms_brand = $tested_cms;
                $cms = true;
                break 2;
            }
        }
    }

    // security checks specific to each CMS 
    $evidences = [
        'wordpress'    => ['/wp-config.php', '/wp-content/', '/wp-content/uploads/'],
        'drupal'       => ['/authorize.php', '/cron.php', '/install.php', '/upgrade.php'],
        'joomla'       => ['/images', '/tmp', '/cache'],
        'spip'         => ['/config', '/ecrire/auth', '/ecrire/plugins']
    ];

    if(isset($evidences[$cms_brand])) {
        foreach($evidences[$cms_brand] as $test) {
            $request = new HttpRequest($test, ['Host' => $domain]);
            $response = $request->send();
            if(intval($response->status()) != 403) {
                $tree_secure = false;
                break;
            }

        }
    }

    // store results
    $result = [
        'CMS_EVIDENCE'          =>  intval($cms),
        'CMS_BRAND'             =>  $cms_brand,
        'SSL'                   =>  intval($ssl),
        'JS_VULNERABILITY'      =>  count($js_vulnerabilities),
        'TREE_SECURE'           =>  intval($tree_secure),
        'JS_VULNERABILITIES'    =>  $js_vulnerabilities
    ];

    $category = TestCategory::search(['name', '=', 'SEC'])->read(['tests_ids'])->first();
    $tests = Test::search(['id', 'in', $category['tests_ids']])->read(['id', 'name'])->get();

    foreach($tests as $test_id => $test ) {
        $value = $result[$test['name']];

        switch($test['name']) {
            case 'TREE_SECURE':
            case 'SSL':
                $pass = (bool) $value;
                break;
            case 'CMS_BRAND':
                $pass = ($value == 'unknown');            
                break;
            case 'CMS_EVIDENCE':                
            case 'JS_VULNERABILITY':
                $pass = !((bool) $value);
                break;
        }

        Result::create(['report_id' => $report_id, 'test_id' => $test_id, 'value' => $value, 'pass' => $pass]);
    } 
}

$context->httpResponse()
        ->status(201)
        ->send();