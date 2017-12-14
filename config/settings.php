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
        'pathToPhp'                         => defined('PHP_BINARY') ? escapeshellarg(PHP_BINARY) : '/usr/local/bin/php',

        'session' => [
            //Session settings
            'name'                          => 'appName',
            'lifetime'                      => 7200,
            'path'                          => null,
            'domain'                        => null,
            'secure'                        => false,
            'httponly'                      => true,
            'updateLifetimeWithEachRequest' => true,
        ],

        'renderer' => [
            //PHP-View settings
            'views_path'         => __DIR__ . '/../views/',
            'template_file_name' => 'template.php',
        ],

        'logger' => [
            //Monolog settings
            'name'            => 'appName',
            'path'            => __DIR__ . '/../logs/log', //this includes the filename, so the file created would be named log-YYYY-MM-DD
            'maxFiles'        => 14,
            'minimumLogLevel' => \Monolog\Logger::DEBUG //Change to NOTICE on production
        ],

        'jobQueue' => [
            'enabled'              => true,
            'redisInstanceName'    => 'default',
            'runFor'               => 60,
            'maxJobFetch'          => 50,
            'minCronCount'         => 1,
            'maxCronCount'         => 2,
            'consumerCronFileName' => 'jobQueueConsumer.php',
        ],

        'taskRunner' => [
            'enabled'           => true,
            'redisInstanceName' => 'default',
        ],

        'db' => [
            //Database configurations
            'mongo' => [
                'default' => [
                    'host'          => 'mongodb://localhost:27017',
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
                            'root'     => 'array',
                        ],
                    ],
                    'database'      => 'databaseName',
                ],
            ],

            'redis' => [
                'default' => [
                    'host'       => '127.0.0.1',
                    'port'       => 6379,
                    'persistent' => true,
                    'timeout'    => 2,
                    'password'   => '',
                ],
            ],

            'mysql' => [
                'default' => [
                    'host'     => '127.0.0.1',
                    'port'     => 3306,
                    'user'     => '',
                    'pass'     => '',
                    'database' => '',
                ],
            ],
        ],
    ],
];
