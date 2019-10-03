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
    'description'   => "Returns results (if any) for a given category.",
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
        ],
        'category' => [
            'description'   => 'mnemonic of the category of the results to return (PERF, SEC, ...).',
            'type'          => 'string',
            'required'      => true
        ]
    ],
    'providers'     => ['context', 'adapt']
]);


list($context, $adapter) = [ $providers['context'], $providers['adapt'] ];

list($report_id, $category) = [ $params['report_id'], $params['category'] ];


// check received report ID validity
$report = Report::search(['id', '=', $report_id])->read(['id', 'date', 'domain'])->first();
if(!$report) throw new Exception('Unknown report', QN_ERROR_INVALID_PARAM); 

// init HTTP response status
$status = 200;

// search for category results for given report
$category = TestCategory::search(['name', '=', $category])
                        ->read(['tests_ids'])
                        ->first();
                        
$results = Result::search([['report_id', '=', $report_id], ['test_id', 'in', $category['tests_ids']]])
                 ->read(['value', 'pass', 'test_id' => ['name', 'description', 'type']])
                 ->get();

// if report has already been processed, fetch data 
if( count($results) ) {

    // serve saved data
    $result = []; 

    foreach($results as $res_id => $res_item) {
        $test_name = $res_item['test_id']['name'];
        $result[$test_name] = [
            "value"         => $adapter->adapt($res_item['value'], $res_item['test_id']['type']),
            "pass"          => $res_item['pass'],
            "description"   => $res_item['test_id']['description']
        ];

    }

}
// if there's no result yet, set status to NOT FOUND
else {
    $status = 404;
    $result = [];
}


// send HTTP response
$context->httpResponse()
        ->status($status)
        ->body($result)
        ->send();