<?php
/*
    Some Rights Reserved, Cedric Francoys, 2019, Brussels
    Licensed under GNU GPL 3 license <http://www.gnu.org/licenses/>
*/
use checkup\Report;
use checkup\Result;
use checkup\Test;
use checkup\TestCategory;

list($params, $providers) = announce([
    'description'   => "Runs integratbility checks (email related settings).",
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

list($url, $report_id) = [ $params['url'], $params['report_id'] ];


// check received report ID validity
$report = Report::search(['id', '=', $report_id])->read(['id', 'date', 'domain', 'url'])->first();
if(!$report) throw new Exception('Unknown report', QN_ERROR_INVALID_PARAM); 

// retrieve URL
$domain = $report['domain'];
$url = 'http://'.$domain;

// search for INT results for given report
$category = TestCategory::search(['name', '=', 'INT'])
                        ->read(['tests_ids'])
                        ->first();    
$results = Result::search([['report_id', '=', $report_id], ['test_id', 'in', $category['tests_ids']]])
                   ->read(['value', 'pass', 'test_id' => ['name', 'description']])
                   ->get();

// if report hasn't already been processed yet, run checks
if( count($results) == 0 ) {

    // init values to be retrieved
    $reverse_present = false;
    $reverse_consistent = false;
    $spf_published = false;
    $dmarc_published = false;
    $mx_published = false;
    
        
    $ip_address = 'unknown';
    $mx_server = 'unknown';
    $mx_pri_min = 10000;
    
    // retrieve A records
    $dns_records = dns_get_record($domain, DNS_A);
    foreach($dns_records as $record) {
        $ip_address = $record['ip'];
        break;
    }
    // retrieve TXT records
    $dns_records = dns_get_record($domain, DNS_TXT);
    foreach($dns_records as $record) {
        if(stripos($record['txt'], 'v=SPF') !== false) {
            $spf_published = true;
        }
        if(stripos($record['txt'], 'v=DMARC') !== false) {
            $dmarc_published = true;
        }
    }
    // retrieve MX records
    $dns_records = dns_get_record($domain, DNS_MX);
    foreach($dns_records as $record) {
        if($record['pri'] < $mx_pri_min) {
            $mx_pri_min = $record['pri'];
            $mx_server = $record['target'];
        }
    }
    // retrieve reverse PTR
    if( ($reverse_host = gethostbyaddr( $ip_address )) == $ip_address) {
        $reverse_host = 'unknown';
    }
    
    if($mx_server != 'unknown') {
        $mx_published = true;
    }
    if($reverse_host != 'unknown') {
        $reverse_present = true;
    }
    if($reverse_host == $domain) {
        $reverse_consistent = true;
    }
    
    $result = [
        'MX_PUBLISHED'          => intval($mx_published),
        'REVERSE_PRESENT'       => intval($reverse_present),
        'REVERSE_CONSISTENT'    => intval($reverse_consistent),
        'SPF_PUBLISHED'         => intval($spf_published),
        'DMARC_PUBLISHED'       => intval($dmarc_published)
    ];

    $category = TestCategory::search(['name', '=', 'INT'])->read(['tests_ids'])->first();
    $tests = Test::search(['id', 'in', $category['tests_ids']])->read(['id', 'name'])->get();

    foreach($tests as $test_id => $test ) {
        $value = $result[$test['name']];

        switch($test['name']) {
            case 'MX_PUBLISHED':
            case 'REVERSE_PRESENT':
            case 'REVERSE_CONSISTENT':
            case 'SPF_PUBLISHED':                                    
            case 'DMARC_PUBLISHED':
                $pass = (bool) $value;
                break;
        }

        Result::create(['report_id' => $report_id, 'test_id' => $test_id, 'value' => $value, 'pass' => $pass]);
    }     
}

$context->httpResponse()
        ->status(201)
        ->send();