<?php

define('ROOT_PATH', __DIR__);
// Include the composer autoloader
require_once dirname(__FILE__) . '/vendor/autoload.php';

use PHPFastCGI\FastCGIDaemon\ApplicationFactory;
use PHPFastCGI\FastCGIDaemon\Http\RequestInterface;
use PHPFastCGI\FastCGIDaemon\Http\Request as FastCGIRequest;
use Symfony\Component\HttpFoundation\Request;

FastCGIRequest::setUploadDir(ROOT_PATH.'/tmp');

$app = new Silex\Application;
$app['debug'] = true;
$app->get('/', function() {
    return file_get_contents(__DIR__.'/test.html');
});

$app->post('/post', function(Request $request) {
    $files = $request->files->all();
    $uploaded_files = [];
    foreach ($files as $file) {
        $uploaded_files[] = $file->getPathname();
        @copy($file->getPathname(), ROOT_PATH.'/tmp/' . $file->getClientOriginalName());
    }
    $data = [
        'post' => $request->request->all(),
        'files' => $uploaded_files
    ];
    return json_encode($data);
});

// A simple kernel. This is the core of your application
$kernel = function (RequestInterface $request) use ($app) {
    $symfony_request = $request->getHttpFoundationRequest();
    $symfony_response = $app->handle($symfony_request);
    $app->terminate($symfony_request, $symfony_response);

    return $symfony_response;
};

// Create your Symfony console application using the factory
$application = (new ApplicationFactory)->createApplication($kernel);

// Run the Symfony console application
$application->run();
