<?php
/*
    Some Rights Reserved, Cedric Francoys, 2019, Brussels
    Licensed under GNU GPL 3 license <http://www.gnu.org/licenses/>
*/
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


list($context) = [ $providers['context'] ];

list($report_id) = [ $params['report_id'] ];


// check received report ID validity
$report = Report::search(['id', '=', $report_id])->read(['id', 'date', 'domain', 'url', 'content'])->first();
if(!$report) throw new Exception('Unknown report', QN_ERROR_INVALID_PARAM); 

// retrieve URL
$domain = $report['domain'];
$url = $report['url'];
$content = $report['content'];

// search for VIS results for given report
$category = TestCategory::search(['name', '=', 'VIS'])
                        ->read(['tests_ids'])
                        ->first();

$results = Result::search([['report_id', '=', $report_id], ['test_id', 'in', $category['tests_ids']]])
                   ->read([
                       'value', 
                       'pass', 
                       'test_id' => ['name', 'description']
                   ])
                   ->get();

// if report hasn't already been processed yet, run checks
if( count($results) == 0 ) {

    // init values to be retrieved
    $index_count = 0;
    $indexed = false;
    $deep_scan = false;

    $result = [];

    $evidences = [
        'META_HTML'         => ['<title>', 'description'],
        'META_SCHEMA'       => ['itemscope', 'itemtype', 'itemprop'],
        'META_OPENGRAPH'    => ['og:'],
        'FAVICON'           => ['shortcut icon'/* , 'apple-touch-icon'*/]
    ];

    foreach($evidences as $evidence => $clues) {
        $result[$evidence] = true;
        foreach($clues as $clue) {                
            if(strpos($content, $clue) === false) {
                $result[$evidence] = false;
                break;
            }
        }
    }

    

    // check for sitemap and robots.txt presence
    $files = [
        'SITEMAP'   => ['sitemap', 'sitemap.txt', 'sitemap.xml'],
        'ROBOTS'    => ['robots.txt']
    ];

    foreach($files as $evidence => $clues) {
        $result[$evidence] = false;
        foreach($clues as $clue) {                
            $remote = @fopen ($url.'/'.$clue, "rb");
            if ($remote && strpos($http_response_header[0], '200') !== false) {
                $result[$evidence] = true;
                break;        
            }           
        }
    }


    // use CSE to retrieve indexed pages from website
    $searchRequest = new HttpRequest('/customsearch/v1', ['Host' => 'www.googleapis.com:443']);
    $response = $searchRequest
                ->setBody([
                    'key' => 'AIzaSyCDSjNGcEMMhUmfoVYduwhJaUoWE4chECA',
                    'cx'  => '003384885074762145397:zcwurjmt7se',
                    'q'   => 'site:'.$domain
                ])->send();

    $data = $response->body();
    $index_count = $data['searchInformation']['totalResults'];

    $searchRequest = new HttpRequest('/search', ['Host' => 'webcache.googleusercontent.com']);

    // is the website indexed ?
    $indexed = false;
    $response = $searchRequest
                ->setBody([
                    'q'   => 'cache:'.$domain
                ])->send();

    if(intval($response->status()) == 200) $indexed = true;

    if(!$indexed) {
        // not indexed
    }
    else {
        if(!$index_count) {
            // indexed but in sandbox or preliminary stage
        }
        else {
            // indexed and deep scan enabled
            $deep_scan = true;
        }
    }

  
    
    $result['INDEXED'] = $indexed;
    $result['INDEX_COUNT'] = $index_count;
    $result['INDEX_DEEP_SCAN'] = $deep_scan;

    $category = TestCategory::search(['name', '=', 'VIS'])->read(['tests_ids'])->first();
    $tests = Test::search(['id', 'in', $category['tests_ids']])->read(['id', 'name'])->get();

    foreach($tests as $test_id => $test ) {
        $value = $result[$test['name']];

        switch($test['name']) {
            case 'META_HTML':
            case 'META_SCHEMA':
            case 'META_OPENGRAPH':
            case 'FAVICON':
            case 'ROBOTS':
            case 'SITEMAP':
            case 'INDEXED':
            case 'INDEX_DEEP_SCAN':
            case 'INDEX_COUNT':
                $pass = (bool) $value;
                break;
        }

        Result::create(['report_id' => $report_id, 'test_id' => $test_id, 'value' => $value, 'pass' => $pass]);
    }     
}

$context->httpResponse()
        ->status(201)
        ->send();