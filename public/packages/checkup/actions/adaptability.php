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
if(!in_array('tidy', $loaded_extensions)) {
    throw new Exception('Dependency is missing: tidy', QN_ERROR_INVALID_CONFIG);
}

list($context) = [ $providers['context'] ];

list($report_id) = [ $params['report_id'] ];


// check received report ID validity
$report = Report::search(['id', '=', $report_id])->read(['id', 'date', 'domain', 'content'])->first();
if(!$report) throw new Exception('Unknown report', QN_ERROR_INVALID_PARAM); 

// retrieve URL
$domain = $report['domain'];
$url = 'http://'.$domain;

// search for ADA results for given report
$category = TestCategory::search(['name', '=', 'ADA'])
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

    $content = $report['content'];

    $tidy = new Tidy();
    $tidy->parseString($content, ['doctype' => '<!DOCTYPE HTML>']);

    preg_match_all('/^(?:line (\d+) column (\d+) - )?(\S+): (?:\[((?:\d+\.?){4})]:)?(.*?)$/m', $tidy->errorBuffer, $tidy_errors, PREG_SET_ORDER);

    // responsive CSS frameworks
    $evidences = [
        'materializecss'    =>  ['materialize'], // materialize.min.js
        'bulma.io'          =>  ['bulma'],       // bulma.min.cjs
        'zurb foundation'   =>  ['foundation'],  // foundation.min.js
        'bootstrap'         =>  ['bootstrap'],   // bootstrap.min.css
        'cssgrids'          =>  ['cssgrids'],    // cssgrids-min.css
        'uikit'             =>  ['uikit'],       // uikit.min.js
        'semantic-ui'       =>  ['semantic']     // semantic.min.css
    ];

    foreach($evidences as $clues) {
        foreach($clues as $clue) {
            if(preg_match('/^.*('.$clue.').*\.css.*$/miU', $content)) {
                $responsive = true;
                break 2;
            }
        }
    }

    if(!$responsive) {
        
        $evidences = [
            "bootstrap"         => ['col-xs-', 'col-sm-', 'col-md-', 'col-lg-', 'col-xl-' ],
            "bulma"             => ['is-full', 'is-offset', 'is-half', 'is-3', 'is-6', 'is-12' ],
            "zurb foundation"   => ['show-for-', 'hide-for-', 'align-self-', 'grid-padding-']
        ];        

        foreach($evidences as $tested_framework => $clues) {
            foreach($clues as $clue) {
                if(strpos($content, $clue) !== false) {
                    $responsive = true;
                    break 2;
                }
            }
        }            
    }

    $result = [
        'HTML_ERRORS'          =>  count($tidy_errors),
        'JS_ERRORS'            =>  intval(false),
        'CSS_ERRORS'           =>  intval(false),
        'RESPONSIVE'           =>  intval($responsive)
    ];

    $category = TestCategory::search(['name', '=', 'ADA'])
                            ->read(['tests_ids'])
                            ->first();

    $tests = Test::search(['id', 'in', $category['tests_ids']])
                 ->read(['id', 'name'])
                 ->get();

    foreach($tests as $test_id => $test ) {
        $value = $result[$test['name']];

        switch($test['name']) {
            case 'RESPONSIVE':
                $pass = (bool) $value;
                break;
            case 'HTML_ERRORS':
            case 'CSS_ERRORS':
            case 'JS_ERRORS':
                $pass = !((bool) $value);
                break;
        }
        Result::create(['report_id' => $report_id, 'test_id' => $test_id, 'value' => $value, 'pass' => $pass]);
    }
}

$context->httpResponse()
        ->status(201)
        ->send();