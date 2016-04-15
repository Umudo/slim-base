<?php
return [
    'settings' => [
        //Slim settings
        'displayErrorDetails'               => true, //set false in production
        'determineRouteBeforeAppMiddleware' => true, //When true, the route is calculated before any middleware is executed. This means that you can inspect route parameters in middleware if you need to.
        'production'                        => false,
        'default_controller'                => 'Home',
        'default_action'                    => 'index',
        'action_suffix'                     => 'Action',

        //PHP-View settings
        'renderer'                          => [
            'views_path'         => __DIR__ . '/../views/',
            'template_file_name' => 'template.php'
        ],

        //Monolog settings
        'logger'                            => [
            'name'            => 'trails-app',
            'path'            => __DIR__ . '/../logs/log', //this includes the filename, so the file created would be named log-YYYY-MM-DD
            'maxFiles'        => 14,
            'minimumLogLevel' => \Monolog\Logger::DEBUG //Change to NOTICE on production
        ],

        //Database configurations
        'db'                                => [
            'mongo' => [
                'default' => [
                    'host'          => 'mongodb://localhost',
                    'port'          => 27017,
                    'username'      => '',
                    'password'      => '',
                    'uriOptions'    => [
                        'connectTimeoutMS' => 2000,
                        'socketTimeoutMS'  => 60000,
                    ],
                    'driverOptions' => [
                        'typeMap' => [
                            'array'    => 'array',
                            'document' => 'array',
                            'root'     => 'array'
                        ]
                    ],
                    'database'      => 'databaseName'
                ]
            ],
            
            'redis' => [
                'default' => [
                    'host'       => '127.0.0.1',
                    'port'       => 6379,
                    'persistent' => true,
                    'timeout'    => 2,
                    'connect'    => true, //constructor will connect if true, otherwise you need to call connect()
                    'password'   => ''
                ]
            ]
        ]
    ]
];