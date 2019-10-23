<?php
/*
    Some Rights Reserved, Cedric Francoys, 2019, Brussels
    Licensed under GNU GPL 3 license <http://www.gnu.org/licenses/>
*/
use qinoa\http\HttpUriHelper;
use checkup\Report;

list($params, $providers) = announce([
    'description'   => "Returns a report ID to be used for given URL/domain. If necessary, creates a new empty report and retrieves root page content.",
    'response'      => [
        'content-type'  => 'application/json',
        'charset'       => 'UTF-8',
        'accept-origin' => '*'
    ],
    'params'        => [
        'url' =>  [
            'description'   => 'URL at which the website to check can be reached.',
            'type'          => 'string', 
            'required'      => true
        ]
    ],
    'providers'     => ['context']
]);

if(!in_array('curl', get_loaded_extensions())) {
    throw new Exception('Dependency is missing: cURL', QN_ERROR_INVALID_CONFIG);
}

list($context) = [ $providers['context'] ];

list($url) = [ $params['url'] ];

// sanitize URL
if(strpos($url, 'http') !== 0) {
    $url = 'http://'.$url;
}
// retrieve domain from URL
$domain = HttpUriHelper::getHost($url);

// search for a previously created report for given domain 
$report = Report::search(['domain', 'ilike', $domain], ['sort'  => ['created' => 'desc']])
                ->read(['id', 'created', 'domain'])
                ->first();

// if report is younger than 24h, use that one
if( $report && (time()-$report['created']) <= 24 *60 * 60 ) {
    $report_id = $report['id'];
}
// if there is no previous report or it is older than 24h, create a new one
else {

    // init cURL
    $ch = curl_init($url); 
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST  => "GET",        
        CURLOPT_POST           => false,        
        CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 6.1; rv:8.0) Gecko/20100101 Firefox/8.0',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER         => true,    
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_ENCODING       => "UTF-8",      
        CURLOPT_AUTOREFERER    => true,     // set referer on redirect
        CURLOPT_CONNECTTIMEOUT => 120,      // timeout on connect
        CURLOPT_TIMEOUT        => 120,      // timeout on response
        CURLOPT_MAXREDIRS      => 5         // stop after 5 redirects
    ]);
    // request given URL
    $content = curl_exec($ch);
    if(!$content) throw new Exception("Unable to fetch content from requested location:".curl_error($ch), QN_ERROR_UNKNOWN);
    $info = curl_getinfo($ch);
    $url = $info['url'];
    // normalize URL (add trailing slash if missing)
    if(substr($url, -1) != '/') $url .= '/';
    curl_close($ch);

   
    $report = Report::create([
                                'domain'    => $domain, 
                                'url'       => $url, 
                                'content'   => $content
                            ])->first();

    $report_id = $report['id'];
}

// send HTTP response
$context->httpResponse()
        ->status(200)
        ->body([
            'report_id' => (int) $report_id
        ])
        ->send();