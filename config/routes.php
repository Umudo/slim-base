<?php

$app->map(['GET', 'POST'], '/[{controller}]', function (Slim\Http\Request $request, Slim\Http\Response $response, $args) {
    // $this here is the Dependency Injection Container.

    $settings = $this->get('settings');
    $controller_name = empty($args['controller']) ? $settings['default_controller'] : $args['controller'];
    $controller_name = ucwords(strtolower($controller_name));

    $valid_name_regex = '/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/';

    /** @var Psr\Log\LoggerInterface $logger */
    $logger = $this->get('logger');

    if (!empty($controller_name) && !preg_match($valid_name_regex, $controller_name)) {
        $logger->info('`' . __FILE__ . '` on line ' . __LINE__ . ": Bad controller name `{$controller_name}`");
        $notFoundHandler = $this->get('notFoundHandler');

        return $notFoundHandler($request, $response);
    }

    $full_class_name = '\App\Controller\\' . $controller_name;

    if (empty($controller_name)) { //If empty, try with default controller.
        $full_class_name = 'App\Controller\\' . ucwords(strtolower($settings['default_controller']));
    }

    if (!class_exists($full_class_name)) {
        $logger->info('`' . __FILE__ . '` on line ' . __LINE__ . ": Bad controller name `{$controller_name}`");
        $notFoundHandler = $this->get('notFoundHandler');

        return $notFoundHandler($request, $response);
    }

    //Class is found, continue:
    $class = new $full_class_name($this, $request, $response);

    if (!$class instanceof \App\Base\Controller) {
        $logger->info("`{$controller_name}` is not an instance of \\App\\Base\\Controller");
        $notFoundHandler = $this->get('notFoundHandler');

        return $notFoundHandler($request, $response);
    }

    $full_default_action_name = $settings['default_action'] . $settings['action_suffix'];
    if (!method_exists($class, $full_default_action_name)) {
        $logger->info('`' . __FILE__ . '` on line ' . __LINE__ . ": Bad action name `{$full_default_action_name}`");
        $notFoundHandler = $this->get('notFoundHandler');

        return $notFoundHandler($request, $response);
    }

    $action_response = call_user_func([$class, $full_default_action_name]); //Parameters should be used with $request->getAttribute()

    if ($action_response instanceof \Psr\Http\Message\ResponseInterface) {
        $response = $action_response;
    } elseif (is_string($action_response)) {
        $response->getBody()->write($action_response);
    } elseif (is_array($action_response)) {
        if ($class->wantsJson()) {
            $response = $response->withJson($action_response);
        } else {
            $response->getBody()->write(json_encode($action_response));
        }
    } else {
        if (empty($response->getBody()->getSize())) {
            if ($settings['production']) {
                $response->getBody()->write('');
            } else {
                $response->getBody()->write('`' . __FILE__ . '` on line ' . __LINE__ . ": Return type is not valid in `{$full_class_name}` `{$full_default_action_name}`");
            }
        }
    }

    return $response;
});

$app->map(['GET', 'POST'], '/{controller}/{action}[/{parameters:.+}]', function (Slim\Http\Request $request, Slim\Http\Response $response, $args) {
    // $this here is the Dependency Injection Container.

    $controller_name = ucwords(strtolower($args['controller']));
    $action_name = $args['action'];
    $valid_name_regex = '/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/';

    /** @var Psr\Log\LoggerInterface $logger */
    $logger = $this->get('logger');

    $settings = $this->get('settings');

    if (!empty($controller_name) && !preg_match($valid_name_regex, $controller_name)) {
        $logger->info('`' . __FILE__ . '` on line ' . __LINE__ . ": Bad controller name `{$controller_name}`");
        $notFoundHandler = $this->get('notFoundHandler');

        return $notFoundHandler($request, $response);
    }

    $full_class_name = '\App\Controller\\' . $controller_name;
    if (!class_exists($full_class_name)) {
        $logger->info('`' . __FILE__ . '` on line ' . __LINE__ . ": Bad controller name `{$controller_name}`");
        $notFoundHandler = $this->get('notFoundHandler');

        return $notFoundHandler($request, $response);
    }

    $class = new $full_class_name($this, $request, $response);

    if (!$class instanceof \App\Base\Controller) {
        $logger->info("`{$controller_name}` is not an instance of \\App\\Base\\Controller");
        $notFoundHandler = $this->get('notFoundHandler');

        return $notFoundHandler($request, $response);
    }

    if (!empty($action_name) && !preg_match($valid_name_regex, $action_name)) {
        $logger->info('`' . __FILE__ . '` on line ' . __LINE__ . ": Bad action name `{$action_name}`");
        $notFoundHandler = $this->get('notFoundHandler');

        return $notFoundHandler($request, $response);
    }

    $full_action_name = $action_name . $settings['action_suffix'];
    if (empty($action_name) || !method_exists($class, $full_action_name)) {
        $logger->info('`' . __FILE__ . '` on line ' . __LINE__ . ": Bad action name `{$action_name}`");
        $notFoundHandler = $this->get('notFoundHandler');

        return $notFoundHandler($request, $response);
    }

    $params = [];
    if (!empty($args['parameters'])) {
        $params = explode('/', $args['parameters']);
    }

    $action_response = call_user_func_array([$class, $full_action_name], $params);

    if ($action_response instanceof \Psr\Http\Message\ResponseInterface) {
        $response = $action_response;
    } elseif (is_string($action_response)) {
        $response->getBody()->write($action_response);
    } elseif (is_array($action_response)) {
        if ($class->wantsJson()) {
            $response = $response->withJson($action_response);
        } else {
            $response->getBody()->write(json_encode($action_response));
        }
    } else {
        if (empty($response->getBody()->getSize())) {
            if ($settings['production']) {
                $response->getBody()->write('');
            } else {
                $response->getBody()->write('`' . __FILE__ . '` on line ' . __LINE__ . ": Return type is not valid in `{$full_class_name}` `{$full_action_name}`");
            }
        }
    }

    return $response;
});
