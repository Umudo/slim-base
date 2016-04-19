<?php

require __DIR__.'/../vendor/autoload.php';

spl_autoload_register(function($class) {
    $prefix = 'App\\';
    $base_dir = __DIR__.'/../App/';

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

//Initialize the container
$app = new \Slim\App($settings);

require __DIR__ . '/../config/dependencies.php';

\App\Helper\Container::setContainer($app->getContainer());
\App\Base\ConnectionManager::setContainer();