<?php
require_once QN_BASEDIR.'/vendor/swiftmailer/swiftmailer/lib/swift_required.php';

use \Swift_SmtpTransport as Swift_SmtpTransport;
use \Swift_Message as Swift_Message;
use \Swift_Mailer as Swift_Mailer;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use qinoa\html\HTMLToText;
use checkup\Report;
use checkup\Result;
use checkup\Test;
use checkup\TestCategory;

list($params, $providers) = announce([
    'description'   => "Sends an email requesting full report.",
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
        'email' => [
            'description'   => 'email address to which the report must be sent.',
            'type'          => 'string',
            'required'      => true,            
            'pattern'       => '/^[^@\s]+@[^@\s]+$/'
        ]        
    ],
    'constants'     => ['EMAIL_SMTP_HOST', 'EMAIL_SMTP_PORT', 'EMAIL_SMTP_ACCOUNT_USERNAME', 'EMAIL_SMTP_ACCOUNT_PASSWORD', 'EMAIL_SMTP_ACCOUNT_DISPLAYNAME'], 
    'providers'     => ['context', 'adapt']
]);

list($context, $adapter) = [ $providers['context'], $providers['adapt'] ];

list($report_id, $email) = [ $params['report_id'], $params['email'] ];

// check received report ID validity
$report = Report::search(['id', '=', $report_id])
                ->read(['id', 'date', 'domain', 'url', 'content'])
                ->first();
                
if(!$report) throw new Exception('Unknown report', QN_ERROR_INVALID_PARAM); 

// read all results at once (from all categories)      
$results = Result::search([['report_id', '=', $report_id]])
                 ->read([
                            'value', 
                            'pass', 
                            'test_id'       => [
                                'name', 
                                'description', 
                                'type', 
                                'category_id'
                            ],
                        ])
                 ->get();

usort($results, function($a, $b) {
    return strcmp($a['test_id']['name'], $b['test_id']['name']);
});

// fetch existing categories
$categories = TestCategory::search()
                          ->read(['name', 'title', 'description'])
                          ->get();


// create a dataset mapping categories with related results
foreach($results as $result_id => $result) {
    if(!isset($categories[$result['test_id']['category_id']]['results'])) {
        $categories[$result['test_id']['category_id']]['results'] = [];
    }
    $categories[$result['test_id']['category_id']]['results'][] = $result;
}


$template = 'packages/checkup/views/templates/report.html.twig';

if(!file_exists($template)) {
    throw new \Exception('template file is missing', QN_ERROR_INVALID_CONFIG);
}

$loader = new FilesystemLoader(dirname($template));
$twig = new Environment($loader, ['cache' => '../cache']);


try {
    $html = $twig->render(
                            basename($template), 
                            [
                                'domain'        => $report['domain'], 
                                'url'           => $report['url'],
                                'categories'    => $categories
                            ]
                         );
} 
catch (\Twig_Error $e) {
    throw new \Exception('unable to compile template', QN_ERROR_UNKNOWN);
} 


$transport = new Swift_SmtpTransport(EMAIL_SMTP_HOST, EMAIL_SMTP_PORT, 'ssl');

$transport->setUsername(EMAIL_SMTP_ACCOUNT_USERNAME)
          ->setPassword(EMAIL_SMTP_ACCOUNT_PASSWORD);                   
                    
$message = new Swift_Message();
$message->setTo($email)
        ->setFrom([EMAIL_SMTP_ACCOUNT_USERNAME => EMAIL_SMTP_ACCOUNT_DISPLAYNAME])
        ->setSubject('Digital Facile - Rapport détaillé de '.$report['url'])
        ->setBody(HTMLToText::convert($html))
        ->addPart($html, 'text/html');

$mailer = new Swift_Mailer($transport);
$result = $mailer->send($message);

$context->httpResponse()
        ->status(201)
        ->send();