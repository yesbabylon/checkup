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
    'description'   => "Runs performance checks.",
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



// search for PER results for given report
$category = TestCategory::search(['name', '=', 'PER'])
                        ->read(['tests_ids'])
                        ->first();    
$results = Result::search([['report_id', '=', $report_id], ['test_id', 'in', $category['tests_ids']]])
                   ->read(['value', 'pass', 'test_id' => ['name', 'description']])
                   ->get();

function get_content_length($header) {
    $content_length = 0;
    foreach($header as $line) {
        if(stripos($line, 'Content-Length') !== false) {
            $parts = explode(':', $line);
            $content_length = intval($parts[1]);
            break;
        }
    }
    return $content_length;
}

// if report hasn't already been processed yet, run checks
if( count($results) == 0 ) {


    // init values to be retrieved
    $total_requests = 0;
    $cache = true;
    $compress = false;
    $keepalive = false;
    $time = 0;
    $first_byte = 0;
    $weight = 0;
    $shared = false;
    $shared_host = 'unknown';
 
    // retrieve primary IP address
    $primary_ip = gethostbyname($domain);

    $start = microtime(true);
    // connect to the targeted URL
    $header = get_headers($url);
    // compute delay to first byte
    $first_byte = microtime(true) - $start;

    $start = microtime(true);
    $data = file_get_contents($url);
    $time = microtime(true)-$start;

    // request and fetch relevant resources referred in the HTML
    $pattern = '/<[img|script].*src="([^"]*)".*\/?>/iU';    
    preg_match_all($pattern, $content, $matches);

    $uris = [];
    foreach($matches[1] as $uri) {
        $uris[$uri] = true;
    }
    // normalize URL (make all absolute)
    $uris = array_map( function($a) use($url) {
        if(stripos($a, 'http') !== 0) {
            if($a[0] == '/') $a = substr($a, 1);
            $a = $url.$a;
        }
        return $a;
    }, array_keys($uris));

    // get total weight
    for($i = 0, $total_requests = count($uris); $i < $total_requests; ++$i) {
        $uri = $uris[$i];

        $start = microtime(true);
        // request resource (ignore faults)
        if($head = get_headers($uri)) {
            $time += microtime(true)-$start;
            $size = get_content_length($head);
            $weight += $size;
        }
    }

    $ipv6 = [ 
        // OVH shared hosts
        "2001:41d0:1:1b00:213:186:33:2"=>"cluster002.hosting.ovh.net", "2001:41d0:1:1b00:188:165:7:2"=>"cluster002.hosting.ovh.net", "2001:41d0:1:1b00:94:23:79:2"=>"cluster002.hosting.ovh.net", "2001:41d0:1:1b00:87:98:255:2"=>"cluster002.hosting.ovh.net", "2001:41d0:1:1b00:94:23:64:2"=>"cluster002.hosting.ovh.net", "2001:41d0:1:1b00:87:98:231:2"=>"cluster002.hosting.ovh.net", "2001:41d0:1:1b00:87:98:239:2"=>"cluster002.hosting.ovh.net", "2001:41d0:1:1b00:94:23:175:2"=>"cluster002.hosting.ovh.net", "2001:41d0:1:1b00:94:23:151:2"=>"cluster002.hosting.ovh.net", "2001:41d0:1:1b00:188:165:143:2"=>"cluster002.hosting.ovh.net", "2001:41d0:1:1b00:188:165:31:2"=>"cluster002.hosting.ovh.net", "2001:41d0:1:1b00:87:98:247:2"=>"cluster002.hosting.ovh.net", "2001:41d0:1:1b00:213:186:33:4"=>"cluster003.hosting.ovh.net", "2001:41d0:1:1b00:188:165:7:4"=>"cluster003.hosting.ovh.net", "2001:41d0:1:1b00:94:23:79:4"=>"cluster003.hosting.ovh.net", "2001:41d0:1:1b00:87:98:255:4"=>"cluster003.hosting.ovh.net", "2001:41d0:1:1b00:94:23:64:4"=>"cluster003.hosting.ovh.net", "2001:41d0:1:1b00:87:98:231:4"=>"cluster003.hosting.ovh.net", "2001:41d0:1:1b00:87:98:239:4"=>"cluster003.hosting.ovh.net", "2001:41d0:1:1b00:94:23:175:4"=>"cluster003.hosting.ovh.net", "2001:41d0:1:1b00:94:23:151:4"=>"cluster003.hosting.ovh.net", "2001:41d0:1:1b00:188:165:143:4"=>"cluster003.hosting.ovh.net", "2001:41d0:1:1b00:188:165:31:4"=>"cluster003.hosting.ovh.net", "2001:41d0:1:1b00:87:98:247:4"=>"cluster003.hosting.ovh.net", "2001:41d0:1:1b00:213:186:33:16"=>"cluster005.hosting.ovh.net", "2001:41d0:1:1b00:188:165:7:16"=>"cluster005.hosting.ovh.net", "2001:41d0:1:1b00:94:23:79:16"=>"cluster005.hosting.ovh.net", "2001:41d0:1:1b00:87:98:255:16"=>"cluster005.hosting.ovh.net", "2001:41d0:1:1b00:94:23:64:16"=>"cluster005.hosting.ovh.net", "2001:41d0:1:1b00:87:98:231:16"=>"cluster005.hosting.ovh.net", "2001:41d0:1:1b00:87:98:239:16"=>"cluster005.hosting.ovh.net", "2001:41d0:1:1b00:94:23:175:16"=>"cluster005.hosting.ovh.net", "2001:41d0:1:1b00:94:23:151:16"=>"cluster005.hosting.ovh.net", "2001:41d0:1:1b00:188:165:143:16"=>"cluster005.hosting.ovh.net", "2001:41d0:1:1b00:188.165.31.16"=>"cluster005.hosting.ovh.net", "2001:41d0:1:1b00:87:98:247:16"=>"cluster005.hosting.ovh.net", "2001:41d0:1:1b00:213:186:33:17"=>"cluster006.hosting.ovh.net", "2001:41d0:1:1b00:188:165:7:17"=>"cluster006.hosting.ovh.net", "2001:41d0:1:1b00:94:23:79:17"=>"cluster006.hosting.ovh.net", "2001:41d0:1:1b00:87:98:255:17"=>"cluster006.hosting.ovh.net", "2001:41d0:1:1b00:94:23:64:17"=>"cluster006.hosting.ovh.net", "2001:41d0:1:1b00:87:98:231:17"=>"cluster006.hosting.ovh.net", "2001:41d0:1:1b00:87:98:239:17"=>"cluster006.hosting.ovh.net", "2001:41d0:1:1b00:94:23:175:17"=>"cluster006.hosting.ovh.net", "2001:41d0:1:1b00:94:23:151:17"=>"cluster006.hosting.ovh.net", "2001:41d0:1:1b00:188:165:143:17"=>"cluster006.hosting.ovh.net", "2001:41d0:1:1b00:188:165:31:17"=>"cluster006.hosting.ovh.net", "2001:41d0:1:1b00:87:98:247:17"=>"cluster006.hosting.ovh.net", "2001:41d0:1:1b00:213:186:33:18"=>"cluster007.hosting.ovh.net", "2001:41d0:1:1b00:188:165:7:18"=>"cluster007.hosting.ovh.net", "2001:41d0:1:1b00:94:23:79:18"=>"cluster007.hosting.ovh.net", "2001:41d0:1:1b00:87:98:255:18"=>"cluster007.hosting.ovh.net", "2001:41d0:1:1b00:94:23:64:18"=>"cluster007.hosting.ovh.net", "2001:41d0:1:1b00:87:98:231:18"=>"cluster007.hosting.ovh.net", "2001:41d0:1:1b00:87:98:239:18"=>"cluster007.hosting.ovh.net", "2001:41d0:1:1b00:94:23:175:18"=>"cluster007.hosting.ovh.net", "2001:41d0:1:1b00:94:23:151:18"=>"cluster007.hosting.ovh.net", "2001:41d0:1:1b00:188:165:143:18"=>"cluster007.hosting.ovh.net", "2001:41d0:1:1b00:188:165:31:18"=>"cluster007.hosting.ovh.net", "2001:41d0:1:1b00:87:98:247:18"=>"cluster007.hosting.ovh.net", "2001:41d0:1:1b00:213:186:33:19"=>"cluster010.hosting.ovh.net", "2001:41d0:1:1b00:188:165:7:19"=>"cluster010.hosting.ovh.net", "2001:41d0:1:1b00:94:23:79:19"=>"cluster010.hosting.ovh.net", "2001:41d0:1:1b00:87:98:255:19"=>"cluster010.hosting.ovh.net", "2001:41d0:1:1b00:94:23:64:19"=>"cluster010.hosting.ovh.net", "2001:41d0:1:1b00:87:98:231:19"=>"cluster010.hosting.ovh.net", "2001:41d0:1:1b00:87:98:239:19"=>"cluster010.hosting.ovh.net", "2001:41d0:1:1b00:94:23:175:19"=>"cluster010.hosting.ovh.net", "2001:41d0:1:1b00:94:23:151:19"=>"cluster010.hosting.ovh.net", "2001:41d0:1:1b00:188:165:143:19"=>"cluster010.hosting.ovh.net", "2001:41d0:1:1b00:188:165:31:19"=>"cluster010.hosting.ovh.net", "2001:41d0:1:1b00:87:98:247:19"=>"cluster010.hosting.ovh.net", "2001:41d0:1:1b00:213:186:33:40"=>"cluster011.hosting.ovh.net", "2001:41d0:1:1b00:188:165:7:40"=>"cluster011.hosting.ovh.net", "2001:41d0:1:1b00:94:23:79:40"=>"cluster011.hosting.ovh.net", "2001:41d0:1:1b00:87:98:255:40"=>"cluster011.hosting.ovh.net", "2001:41d0:1:1b00:94:23:64:40"=>"cluster011.hosting.ovh.net", "2001:41d0:1:1b00:87:98:231:40"=>"cluster011.hosting.ovh.net", "2001:41d0:1:1b00:87:98:239:40"=>"cluster011.hosting.ovh.net", "2001:41d0:1:1b00:94:23:175:40"=>"cluster011.hosting.ovh.net", "2001:41d0:1:1b00:94:23:151:40"=>"cluster011.hosting.ovh.net", "2001:41d0:1:1b00:188:165:143:40"=>"cluster011.hosting.ovh.net", "2001:41d0:1:1b00:188:165:31:40"=>"cluster011.hosting.ovh.net", "2001:41d0:1:1b00:87:98:247:40"=>"cluster011.hosting.ovh.net", "2001:41d0:1:1b00:213:186:33:48"=>"cluster012.hosting.ovh.net", "2001:41d0:1:1b00:188:165:7:48"=>"cluster012.hosting.ovh.net", "2001:41d0:1:1b00:94:23:79:48"=>"cluster012.hosting.ovh.net", "2001:41d0:1:1b00:87:98:255:48"=>"cluster012.hosting.ovh.net", "2001:41d0:1:1b00:94:23:64:48"=>"cluster012.hosting.ovh.net", "2001:41d0:1:1b00:87:98:231:48"=>"cluster012.hosting.ovh.net", "2001:41d0:1:1b00:87:98:239:48"=>"cluster012.hosting.ovh.net", "2001:41d0:1:1b00:94:23:175:48"=>"cluster012.hosting.ovh.net", "2001:41d0:1:1b00:94:23:151:48"=>"cluster012.hosting.ovh.net", "2001:41d0:1:1b00:188:165:143:48"=>"cluster012.hosting.ovh.net", "2001:41d0:1:1b00:188:165:31:48"=>"cluster012.hosting.ovh.net", "2001:41d0:1:1b00:87:98:247:48"=>"cluster012.hosting.ovh.net", "2001:41d0:1:1b00:213:186:33:24"=>"cluster013.hosting.ovh.net", "2001:41d0:1:1b00:188:165:7:24"=>"cluster013.hosting.ovh.net", "2001:41d0:1:1b00:94:23:79:24"=>"cluster013.hosting.ovh.net", "2001:41d0:1:1b00:87:98:255:24"=>"cluster013.hosting.ovh.net", "2001:41d0:1:1b00:94:23:64:24"=>"cluster013.hosting.ovh.net", "2001:41d0:1:1b00:87:98:231:24"=>"cluster013.hosting.ovh.net", "2001:41d0:1:1b00:87:98:239:24"=>"cluster013.hosting.ovh.net", "2001:41d0:1:1b00:94:23:175:24"=>"cluster013.hosting.ovh.net", "2001:41d0:1:1b00:94:23:151:24"=>"cluster013.hosting.ovh.net", "2001:41d0:1:1b00:188:165:143:24"=>"cluster013.hosting.ovh.net", "2001:41d0:1:1b00:188:165:31:24"=>"cluster013.hosting.ovh.net", "2001:41d0:1:1b00:87:98:247:24"=>"cluster013.hosting.ovh.net", "2001:41d0:1:1b00:213:186:33:87"=>"cluster014.hosting.ovh.net", "2001:41d0:1:1b00:188:165:7:87"=>"cluster014.hosting.ovh.net", "2001:41d0:1:1b00:94:23:79:87"=>"cluster014.hosting.ovh.net", "2001:41d0:1:1b00:87:98:255:87"=>"cluster014.hosting.ovh.net", "2001:41d0:1:1b00:94:23:64:87"=>"cluster014.hosting.ovh.net", "2001:41d0:1:1b00:87:98:231:87"=>"cluster014.hosting.ovh.net", "2001:41d0:1:1b00:87:98:239:87"=>"cluster014.hosting.ovh.net", "2001:41d0:1:1b00:94:23:175:87"=>"cluster014.hosting.ovh.net", "2001:41d0:1:1b00:94:23:151:87"=>"cluster014.hosting.ovh.net", "2001:41d0:1:1b00:188:165:143:87"=>"cluster014.hosting.ovh.net", "2001:41d0:1:1b00:188:165:31:87"=>"cluster014.hosting.ovh.net", "2001:41d0:1:1b00:87:98:247:87"=>"cluster014.hosting.ovh.net", "2001:41d0:1:1b00:213:186:33:3"=>"cluster015.hosting.ovh.net", "2001:41d0:1:1b00:188:165:7:3"=>"cluster015.hosting.ovh.net", "2001:41d0:1:1b00:94:23:79:3"=>"cluster015.hosting.ovh.net", "2001:41d0:1:1b00:87:98:255:3"=>"cluster015.hosting.ovh.net", "2001:41d0:1:1b00:94:23:64:3"=>"cluster015.hosting.ovh.net", "2001:41d0:1:1b00:87:98:231:3"=>"cluster015.hosting.ovh.net", "2001:41d0:1:1b00:87:98:239:3"=>"cluster015.hosting.ovh.net", "2001:41d0:1:1b00:94:23:175:3"=>"cluster015.hosting.ovh.net", "2001:41d0:1:1b00:94:23:151:3"=>"cluster015.hosting.ovh.net", "2001:41d0:1:1b00:188:165:143:3"=>"cluster015.hosting.ovh.net", "2001:41d0:1:1b00:188:165:31:3"=>"cluster015.hosting.ovh.net", "2001:41d0:1:1b00:87:98:247:3"=>"cluster015.hosting.ovh.net", "2001:41d0:1:1b00:213:186:33:50"=>"cluster017.hosting.ovh.net", "2001:41d0:1:1b00:188:165:7:50"=>"cluster017.hosting.ovh.net", "2001:41d0:1:1b00:94:23:79:50"=>"cluster017.hosting.ovh.net", "2001:41d0:1:1b00:87:98:255:50"=>"cluster017.hosting.ovh.net", "2001:41d0:1:1b00:94:23:64:50"=>"cluster017.hosting.ovh.net", "2001:41d0:1:1b00:87:98:231:50"=>"cluster017.hosting.ovh.net", "2001:41d0:1:1b00:87:98:239:50"=>"cluster017.hosting.ovh.net", "2001:41d0:1:1b00:94:23:175:50"=>"cluster017.hosting.ovh.net", "2001:41d0:1:1b00:94:23:151:50"=>"cluster017.hosting.ovh.net", "2001:41d0:1:1b00:188:165:143:50"=>"cluster017.hosting.ovh.net", "2001:41d0:1:1b00:188:165:31:50"=>"cluster017.hosting.ovh.net", "2001:41d0:1:1b00:87:98:247:50"=>"cluster017.hosting.ovh.net", "2001:41d0:301::20"=>"cluster020.hosting.ovh.net", "2001:41d0:301:3::20"=>"cluster020.hosting.ovh.net", "2001:41d0:301:2::20"=>"cluster020.hosting.ovh.net", "2001:41d0:301:12::20"=>"cluster020.hosting.ovh.net", "2001:41d0:301:11::20"=>"cluster020.hosting.ovh.net", "2001:41d0:301:4::20"=>"cluster020.hosting.ovh.net", "2001:41d0:301:5::20"=>"cluster020.hosting.ovh.net", "2001:41d0:301:6::20"=>"cluster020.hosting.ovh.net", "2001:41d0:301:7::20"=>"cluster020.hosting.ovh.net", "2001:41d0:301:8::20"=>"cluster020.hosting.ovh.net", "2001:41d0:301:9::20"=>"cluster020.hosting.ovh.net", "2001:41d0:301:1::20"=>"cluster020.hosting.ovh.net", "2001:41d0:301::21"=>"cluster021.hosting.ovh.net", "2001:41d0:301:6::21"=>"cluster021.hosting.ovh.net", "2001:41d0:301:2::21"=>"cluster021.hosting.ovh.net", "2001:41d0:301:12::21"=>"cluster021.hosting.ovh.net", "2001:41d0:301:11::21"=>"cluster021.hosting.ovh.net", "2001:41d0:301:4::21"=>"cluster021.hosting.ovh.net", "2001:41d0:301:5::21"=>"cluster021.hosting.ovh.net", "2001:41d0:301:6::21"=>"cluster021.hosting.ovh.net", "2001:41d0:301:7::21"=>"cluster021.hosting.ovh.net", "2001:41d0:301:8::21"=>"cluster021.hosting.ovh.net", "2001:41d0:301:9::21"=>"cluster021.hosting.ovh.net", "2001:41d0:301:1::21"=>"cluster021.hosting.ovh.net", "2001:41d0:301::23"=>"cluster023.hosting.ovh.net", "2001:41d0:301:3::23"=>"cluster023.hosting.ovh.net", "2001:41d0:301:2::23"=>"cluster023.hosting.ovh.net", "2001:41d0:301:12::23"=>"cluster023.hosting.ovh.net", "2001:41d0:301:11::23"=>"cluster023.hosting.ovh.net", "2001:41d0:301:4::23"=>"cluster023.hosting.ovh.net", "2001:41d0:301:5::23"=>"cluster023.hosting.ovh.net", "2001:41d0:301:6::23"=>"cluster023.hosting.ovh.net", "2001:41d0:301:7::23"=>"cluster023.hosting.ovh.net", "2001:41d0:301:8::23"=>"cluster023.hosting.ovh.net", "2001:41d0:301:9::23"=>"cluster023.hosting.ovh.net", "2001:41d0:301:1::23"=>"cluster023.hosting.ovh.net", "2001:41d0:301::24"=>"cluster024.hosting.ovh.net", "2001:41d0:301:3::24"=>"cluster024.hosting.ovh.net", "2001:41d0:301:2::24"=>"cluster024.hosting.ovh.net", "2001:41d0:301:12::24"=>"cluster024.hosting.ovh.net", "2001:41d0:301:11::24"=>"cluster024.hosting.ovh.net", "2001:41d0:301:4::24"=>"cluster024.hosting.ovh.net", "2001:41d0:301:5::24"=>"cluster024.hosting.ovh.net", "2001:41d0:301:6::24"=>"cluster024.hosting.ovh.net", "2001:41d0:301:7::24"=>"cluster024.hosting.ovh.net", "2001:41d0:301:8::24"=>"cluster024.hosting.ovh.net", "2001:41d0:301:9::24"=>"cluster024.hosting.ovh.net", "2001:41d0:301:1::24"=>"cluster024.hosting.ovh.net", "2001:41d0:301::25"=>"cluster025.hosting.ovh.net", "2001:41d0:301:3::25"=>"cluster025.hosting.ovh.net", "2001:41d0:301:2::25"=>"cluster025.hosting.ovh.net", "2001:41d0:301:12::2"=>"cluster025.hosting.ovh.net", "2001:41d0:301:11::25"=>"cluster025.hosting.ovh.net", "2001:41d0:301:4::25"=>"cluster025.hosting.ovh.net", "2001:41d0:301:5::25"=>"cluster025.hosting.ovh.net", "2001:41d0:301:6::25"=>"cluster025.hosting.ovh.net", "2001:41d0:301:7::25"=>"cluster025.hosting.ovh.net", "2001:41d0:301:8::25"=>"cluster025.hosting.ovh.net", "2001:41d0:301:9::25"=>"cluster025.hosting.ovh.net", "2001:41d0:301:1::25"=>"cluster025.hosting.ovh.net", "2001:41d0:301::26"=>"cluster026.hosting.ovh.net", "2001:41d0:301:3::26"=>"cluster026.hosting.ovh.net", "2001:41d0:301:2::26"=>"cluster026.hosting.ovh.net", "2001:41d0:301:12::26"=>"cluster026.hosting.ovh.net", "2001:41d0:301:11::26"=>"cluster026.hosting.ovh.net", "2001:41d0:301:4::26"=>"cluster026.hosting.ovh.net", "2001:41d0:301:5::26"=>"cluster026.hosting.ovh.net", "2001:41d0:301:6::26"=>"cluster026.hosting.ovh.net", "2001:41d0:301:7::26"=>"cluster026.hosting.ovh.net", "2001:41d0:301:8::26"=>"cluster026.hosting.ovh.net", "2001:41d0:301:9::26"=>"cluster026.hosting.ovh.net", "2001:41d0:301:1::26"=>"cluster026.hosting.ovh.net", "2001:41d0:301::27"=>"cluster027.hosting.ovh.net", "2001:41d0:301:3::27"=>"cluster027.hosting.ovh.net", "2001:41d0:301:2::27"=>"cluster027.hosting.ovh.net", "2001:41d0:301:12::27"=>"cluster027.hosting.ovh.net", "2001:41d0:301:11::27"=>"cluster027.hosting.ovh.net", "2001:41d0:301:4::27"=>"cluster027.hosting.ovh.net", "2001:41d0:301:5::27"=>"cluster027.hosting.ovh.net", "2001:41d0:301:6::27"=>"cluster027.hosting.ovh.net", "2001:41d0:301:7::27"=>"cluster027.hosting.ovh.net", "2001:41d0:301:8::27"=>"cluster027.hosting.ovh.net", "2001:41d0:301:9::27"=>"cluster027.hosting.ovh.net", "2001:41d0:301:1::27"=>"cluster027.hosting.ovh.net" 
    ];


    // we got an IPv6
    if(strpos($primary_ip, ':')) {
        $shared_host = 'not available';
        if(isset($ipv6[$primary_ip])) {
            $shared = true;
            $shared_host = $ipv6[$primary_ip];
        }    
    }
    else {
        // get reverse PTR
        $host_domain = gethostbyaddr ( $primary_ip );
        // check for evidences
        if(strpos($host_domain, 'cluster') !== false && strpos($host_domain, 'ovh.net') !== false) {
            $shared = true;
            $shared_host = $host_domain;
        }
    }

    // determine if cache is enabled or not    
    if( strpos($header, 'private') 
        || strpos($header, 'no-store')
        || strpos($header, 'no-cache') ) {
        $cache = false;
    }
    if( strpos($header, 'gzip') ) {
        $compress = true;
    }
    if( strpos($header, 'keep-alive') ) { 
        $keepalive = true;
    }


    $result = [
        'FIRST_BYTE'        =>  round($first_byte, 2),
        'TOTAL_TIME'        =>  round($time, 2),
        'TOTAL_WEIGHT'      =>  round($weight/(1024*1024), 2),
        'TOTAL_REQUESTS'    =>  $total_requests,
        'COMPRESSION'       =>  intval($compress),
        'SHARED_HOSTING'    =>  intval($shared),
        'SHARED_HOST'       =>  $shared_host,    
        'KEEP_ALIVE'        =>  intval($keepalive),
        'CACHE'             =>  intval($cache)
    ];

    $category = TestCategory::search(['name', '=', 'PER'])->read(['tests_ids'])->first();
    $tests = Test::search(['id', 'in', $category['tests_ids']])->read(['id', 'name'])->get();

    foreach($tests as $test_id => $test ) {
        $value = $result[$test['name']];

        switch($test['name']) {
            case 'FIRST_BYTE':
                $pass = $value < 0.5;
                break;
            case 'TOTAL_TIME':
                $pass = $value < 3.0;
                break;
            case 'TOTAL_WEIGHT':
                $pass = $value < 4.0;
                break;
            case 'TOTAL_REQUESTS':
                $pass = $value < 25;
                break;
            case 'SHARED_HOST':                
                $pass = ($value == 'unknown');
                break;
            case 'COMPRESSION':
            case 'KEEP_ALIVE':
            case 'CACHE':
                $pass = (bool) $value;
                break;
            case 'SHARED_HOSTING':
                $pass = !((bool) $value);
                break;
        }

        Result::create(['report_id' => $report_id, 'test_id' => $test_id, 'value' => $value, 'pass' => $pass]);
    }    
}



// send HTTP response
$context->httpResponse()
        ->status(201)
        ->send();