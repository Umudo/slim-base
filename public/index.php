<?php

require __DIR__ . '/../vendor/autoload.php';

date_default_timezone_set('Europe/Istanbul');
setlocale(LC_ALL, 'en_US.UTF-8');
mb_internal_encoding("UTF-8");
error_reporting(E_ALL ^ E_NOTICE ^ E_DEPRECATED);

$settings = require __DIR__ . '/../config/settings.php';
$app = new \Slim\App($settings);

register_shutdown_function(function () use ($app) {
    $error = error_get_last();

    if ($error && in_array($error['type'], array(E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR), true)) {
        /** @var \Monolog\Logger $logger */
        $logger = $app->getContainer()->get('logger');
        $message = $error['message'];
        unset($error['message']);
        $logger->critical($message, $error);
    }

    if (function_exists("fastcgi_finish_request")) {
        fastcgi_finish_request();
    }

    /* @var \App\Queue\JobQueue $jobQueue */
    $jobQueue = $app->getContainer()->get("jobQueue");
    $jobQueue->pushJobs();
});

// Set up dependencies
require __DIR__ . '/../config/dependencies.php';

//Set up DIC Helper
\App\Helper\Container::setContainer($app->getContainer());
\App\Base\ConnectionManager::setContainer();

// Register middleware
require __DIR__ . '/../config/middleware.php';
// Register routes
require __DIR__ . '/../config/routes.php';

$app->run();