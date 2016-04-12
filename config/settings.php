<?php
return [
    'settings' => [
        //Slim settings
        'displayErrorDetails' => true, //set false in production
        'determineRouteBeforeAppMiddleware' => true, //When true, the route is calculated before any middleware is executed. This means that you can inspect route parameters in middleware if you need to.
        'production' => false,

        //PHP-View settings
        'renderer' => [
            'views_path' => __DIR__.'../views/',
            'template_file_name' => 'template.php'
        ],

        //Monolog settings
        'logger' => [
            'name' => 'trails-app',
            'path' => __DIR__.'/../logs',
            'maxFiles' => 14,
            'minimumLogLevel' => \Monolog\Logger::DEBUG //Change to NOTICE on production
        ]
    ]
];