<?php

$container = $app->getContainer();

$container['renderer'] = function($c) {
    $settings = $c->get['settings']['renderer'];
    return new \Slim\Views\PhpRenderer($settings['views_path']);
};

$container['logger'] = function($c) {
    $settings = $c->get['settings']['logger'];
    $logger = new \Monolog\Logger($settings['name']);
    $rotating_file_handler = new \Monolog\Handler\RotatingFileHandler($settings['path'], $settings['maxFiles'], $settings['minimumLogLevel']);
    $logger->pushHandler($rotating_file_handler);
    if ($c->get['settings']['production'] === false) {
        $browser_handler = new \Monolog\Handler\BrowserConsoleHandler($settings['minimumLogLevel']);
        $browser_handler->setFormatter(new \Monolog\Formatter\HtmlFormatter());
        $logger->pushHandler($browser_handler);
    }
    return $logger;
};
