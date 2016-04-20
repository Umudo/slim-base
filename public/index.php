<?php

require __DIR__ . '/../vendor/autoload.php';

spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $base_dir = __DIR__ . '/../App/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        // no, move to the next registered autoloader
        return false;
    }

    $relative_class = substr($class, $len);

    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    // if the file exists, require it
    if (file_exists($file)) {
        require $file;

        return true;
    }

    return false;
});

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

\App\Helper\Container::setContainer($app->getContainer());

// Register middleware
require __DIR__ . '/../config/middleware.php';
// Register routes
require __DIR__ . '/../config/routes.php';

\App\Base\ConnectionManager::setContainer();

$app->run();