<?php

$container = $app->getContainer();

$container['renderer'] = function ($c) {
    $settings = $c->get('settings')['renderer'];
    return new \Slim\Views\PhpRenderer($settings['views_path']);
};

$container['logger'] = function ($c) {
    $settings = $c->get('settings')['logger'];
    $logger = new \Monolog\Logger($settings['name']);

    \Monolog\ErrorHandler::register($logger);

    $rotating_file_handler = new \Monolog\Handler\RotatingFileHandler($settings['path'], $settings['maxFiles'], $settings['minimumLogLevel']);
    $rotating_file_handler->setFormatter(new \Monolog\Formatter\LineFormatter(\Monolog\Formatter\LineFormatter::SIMPLE_FORMAT . PHP_EOL, null, true, false)); //Enable inline line breaks
    $logger->pushHandler($rotating_file_handler);

    if ($c->get('settings')['production'] === false) {
        $browser_handler = new \Monolog\Handler\BrowserConsoleHandler($settings['minimumLogLevel']);
        $browser_handler->setFormatter(new \Monolog\Formatter\HtmlFormatter());
        $logger->pushHandler($browser_handler);
    }

    return $logger;
};

$container['errorHandler'] = function ($c) {
    return function (Psr\Http\Message\RequestInterface $request, Psr\Http\Message\ResponseInterface $response, Throwable $error) use ($c) {
        /** @var \Psr\Log\LoggerInterface $logger */
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

        $errorHandler = new \Slim\Handlers\PhpError(true); //Includes Throwable support

        return $errorHandler($request, $response, $error);
    };
};
