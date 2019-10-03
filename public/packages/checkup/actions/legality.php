<?php
/*
    Some Rights Reserved, Cedric Francoys, 2019, Brussels
    Licensed under GNU GPL 3 license <http://www.gnu.org/licenses/>
*/
use qinoa\http\HttpUriHelper;
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

$loaded_extensions = get_loaded_extensions();
if(!in_array('curl', $loaded_extensions)) {
    throw new Exception('Dependency is missing: cURL', QN_ERROR_INVALID_CONFIG);
}

list($context) = [ $providers['context'] ];

list($report_id) = [ $params['report_id'] ];


// check received report ID validity
$report = Report::search(['id', '=', $report_id])->read(['id', 'date', 'domain'])->first();
if(!$report) throw new Exception('Unknown report', QN_ERROR_INVALID_PARAM); 

// retrieve URL
$domain = $report['domain'];
$url = 'http://'.$domain;

// search for LEG results for given report
$category = TestCategory::search(['name', '=', 'LEG'])
                        ->read(['tests_ids'])
                        ->first();    
$results = Result::search([['report_id', '=', $report_id], ['test_id', 'in', $category['tests_ids']]])
                   ->read(['value', 'pass', 'test_id' => ['name', 'description']])
                   ->get();

// if report hasn't already been processed yet, run checks
if( count($results) == 0 ) {

    // init values to be retrieved
    $tidy_errors = 0;
    $responsive = false;

    // cURL options (same for all calls)
    $options = [
        CURLOPT_CUSTOMREQUEST  => "GET",        
        CURLOPT_POST           => false,        
        CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 6.1; rv:8.0) Gecko/20100101 Firefox/8.0',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER         => true,    
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_ENCODING       => "",       // handle all encodings
        CURLOPT_AUTOREFERER    => true,     // set referer on redirect
        CURLOPT_CONNECTTIMEOUT => 120,      // timeout on connect
        CURLOPT_TIMEOUT        => 120,      // timeout on response
        CURLOPT_MAXREDIRS      => 5         // stop after 5 redirects
    ];

    // init cURL
    $ch = curl_init($url); 
    curl_setopt_array($ch, $options);

    // request given URL
    if($content = curl_exec($ch)) {
        // connect to the targeted URL
        $info = curl_getinfo($ch);

        // cookie consent
        $evidences = [
            'cookie-secure'     =>  ['cdn.cookie-secure.com'], // https://cdn.cookie-secure.com
            'cokiebot'          =>  ['consent.cookiebot.com'], // https://consent.cookiebot.com
            'cookie-consent'    =>  ['cookieconsent.insites.com'], // https://cookieconsent.insites.com
            'tarteaucitron'     =>  ['tarteaucitron.js'], // tarteaucitron.js
            'quantcast-GDPR-CMP'=>  ['quantcast.mgr.consensu.org'], // https://quantcast.mgr.consensu.org
            'trustarc-CCM'      =>  ['consent.truste.com/notice'], // https://consent.truste.com/notice
            'other'             =>  ['/privacy-cookie-declaration', '/cookies', '/cookie-consent', 'cookie-notice']
        ];

        foreach($evidences as $clues) {
            foreach($clues as $clue) {
                if(strpos($content, $clue) !== false) {
                    $cookies = true;
                    break 2;
                }
            }
        }
        
        // legal notice
        $evidences = [
            "legal" => ['/mentions-legales', '/legal' ]
        ];        

        foreach($evidences as $tested_cms => $clues) {
            foreach($clues as $clue) {
                if(strpos($content, $clue) !== false) {
                    $legal = true;
                    break 2;
                }
            }
        }
        
        // terms notice
        $evidences = [
            "terms" => ['/conditions', '/terms', '/cgv', '/conditions-generales' ]
        ];        

        foreach($evidences as $tested_cms => $clues) {
            foreach($clues as $clue) {
                if(strpos($content, $clue) !== false) {
                    $terms = true;
                    break 2;
                }
            }
        }

                
        // privacy notice
        $evidences = [
            "privacy" => ['/donnees-personnelles', '/privacy', '/privacy-policy', '/politique-de-confidentialite', '/gdpr-consent' ]
        ];        

        foreach($evidences as $tested_cms => $clues) {
            foreach($clues as $clue) {
                if(strpos($content, $clue) !== false) {
                    $privacy = true;
                    break 2;
                }
            }
        }
        
    }
    else {
        throw new Exception("Unable to fetch content from requested location:".curl_error($ch), QN_ERROR_UNKNOWN);
    }

    curl_close($ch);    

    $result = [
        'COOKIES_CONSENT'   =>  intval($cookies),
        'LEGAL_NOTICE'      =>  intval($legal),
        'TERMS'             =>  intval($terms),
        'PRIVACY'           =>  intval($privacy)
    ];

    $category = TestCategory::search(['name', '=', 'LEG'])->read(['tests_ids'])->first();
    $tests = Test::search(['id', 'in', $category['tests_ids']])->read(['id', 'name'])->get();

    foreach($tests as $test_id => $test ) {
        $value = $result[$test['name']];

        switch($test['name']) {
            case 'COOKIES_CONSENT':
            case 'LEGAL_NOTICE':
            case 'TERMS':
            case 'PRIVACY':                                    
                $pass = (bool) $value;
                break;
        }
        Result::create(['report_id' => $report_id, 'test_id' => $test_id, 'value' => $value, 'pass' => $pass]);
    }
}

$context->httpResponse()
        ->status(201)
        ->send();