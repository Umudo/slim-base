<?php

$container = $app->getContainer();

$container['view'] = function ($c) {
    /**
     * @var \Slim\Container $c
     * @var array           $settings
     */

    $settings = $c->get('settings')['renderer'];

    return new \Slim\Views\PhpRenderer($settings['views_path']);
};

$container['logger'] = function ($c) {
    /**
     * @var \Slim\Container $c
     * @var \Monolog\Logger $logger
     * @var array           $settings
     */

    $settings = $c->get('settings')['logger'];
    $logger = new \Monolog\Logger($settings['name']);

    $logger->pushProcessor(new \Monolog\Processor\WebProcessor());
    $logger->pushProcessor(new \Monolog\Processor\MemoryUsageProcessor());
    $logger->pushProcessor(new \Monolog\Processor\MemoryPeakUsageProcessor());

    $rotating_file_handler = new \Monolog\Handler\RotatingFileHandler($settings['path'], $settings['maxFiles'], $settings['minimumLogLevel']);
    $rotating_file_handler->setFormatter(new \Monolog\Formatter\LineFormatter(\Monolog\Formatter\LineFormatter::SIMPLE_FORMAT . PHP_EOL, null, true, false)); //Enable inline line breaks
    $logger->pushHandler($rotating_file_handler);

    return $logger;
};

$container['jobQueue'] = function ($c) {
    /**
     * @var \Slim\Container $c
     * @var array           $settings
     */
    $options = $c->get('settings')["jobQueue"];

    $options['pathToPhp'] = $c->get('settings')['pathToPhp'];

    return new \App\Queue\JobQueue($options, $c);
};

$container['errorHandler'] = function ($c) {
    return function (Psr\Http\Message\ServerRequestInterface $request, Psr\Http\Message\ResponseInterface $response, Throwable $error) use ($c) {
        /**
         * @var \Slim\Container $c
         * @var \Monolog\Logger $logger
         */

        $logger = $c->get('logger');

        $text = sprintf('Type: %s' . PHP_EOL, get_class($error));

        if (($code = $error->getCode())) {
            $text .= sprintf('Code: %s' . PHP_EOL, $code);
        }

        if (($message = $error->getMessage())) {
            $text .= sprintf('Message: %s' . PHP_EOL, htmlentities($message));
        }

        if (($file = $error->getFile())) {
            $text .= sprintf('File: %s' . PHP_EOL, $file);
        }

        if (($line = $error->getLine())) {
            $text .= sprintf('Line: %s' . PHP_EOL, $line);
        }

        if (($trace = $error->getTraceAsString())) {
            $text .= sprintf('Trace: %s', $trace);
        }

        if ($error instanceof \Error) {
            $logger->error($text);
        } else if ($error instanceof \Exception) {
            $logger->warning($text);
        } else {
            $logger->notice($text);
        }

        $errorHandler = new \Slim\Handlers\PhpError($c->get('settings')['displayErrorDetails']);

        return $errorHandler($request, $response, $error);
    };
};

$container['phpErrorHandler'] = function ($c) {
    /**
     * @var Slim\Container $c
     */
    return $c->get('errorHandler');
};

$container['mongo-default'] = function ($c) {
    /**
     * @var Slim\Container $c
     */
    $settings = $c->get('settings')["db"]["mongo"]["default"];

    $mongo = new \App\Connection\Mongo($settings['host'], $settings['port'], $settings['uriOptions'], $settings['driverOptions']);

    if (!empty($settings['database'])) {
        $mongo->selectDatabase($settings['database']);
    }

    return $mongo;
};

$container['redis-default'] = function ($c) {
    /**
     * @var Slim\Container $c
     */
    $settings = $c->get('settings')["db"]["redis"]["default"];

    $redis = new \App\Connection\Redis($settings);

    return $redis->getClient();
};

$container['mysql-default'] = function ($c) {
    /**
     * @var Slim\Container $c
     */
    $settings = $c->get('settings')["db"]["mysql"]["default"];

    $dbhost = $settings['host'];
    $dbport = $settings['port'];
    $dbuser = $settings['user'];
    $dbpass = $settings['pass'];
    $database = $settings['database'];

    $db = new \App\Connection\ExtendedPDO("mysql:host=$dbhost;port=$dbport;dbname=$database;charset=utf8", $dbuser, $dbpass, array(PDO::ATTR_ERRMODE => PDO::ERRMODE_SILENT));
    $db->query("SET NAMES utf8");

    return $db;
};
