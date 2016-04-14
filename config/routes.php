<?php

$app->map(['GET', 'POST'], '/[{controller}]', function (Psr\Http\Message\ServerRequestInterface $request, Psr\Http\Message\ResponseInterface $response, $args) {
    // $this here is the Dependency Injection Container.

    $settings = $this->get('settings');
    $controller_name = empty($args['controller']) ? $settings['default_controller'] : $args['controller'];
    $controller_name = ucwords(strtolower($controller_name));

    $valid_name_regex = '/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/';

    /** @var Psr\Log\LoggerInterface $logger */
    $logger = $this->get('logger');

    if (!empty($controller_name) && !preg_match($valid_name_regex, $controller_name)) {
        $logger->info("`" . __FILE__ . "` on line " . __LINE__ . ": Bad controller name `{$controller_name}`");
        $notFoundHandler = $this->get('notFoundHandler');

        return $notFoundHandler($request, $response);
    }

    $full_class_name = '\App\Controller\\' . $controller_name;

    if (empty($controller_name)) { //If empty, try with default controller.
        $full_class_name = 'App\Controller\\' . ucwords(strtolower($settings['default_controller']));
    }

    if (!class_exists($full_class_name)) {
        $logger->info("`" . __FILE__ . "` on line " . __LINE__ . ": Bad controller name `{$controller_name}`");
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
        $logger->info("`" . __FILE__ . "` on line " . __LINE__ . ": Bad action name `{$full_default_action_name}`");
        $notFoundHandler = $this->get('notFoundHandler');

        return $notFoundHandler($request, $response);
    }

    return call_user_func([$class, $full_default_action_name]); //Parameters should be used with $request->getAttribute()
});

$app->map(['GET', 'POST'], '/{controller}/{action}[/{parameters:.+}]', function (Psr\Http\Message\ServerRequestInterface $request, Psr\Http\Message\ResponseInterface $response, $args) {
    // $this here is the Dependency Injection Container.

    $controller_name = ucwords(strtolower($args['controller']));
    $action_name = $args['action'];
    $valid_name_regex = '/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/';

    /** @var Psr\Log\LoggerInterface $logger */
    $logger = $this->get('logger');

    $settings = $this->get('settings');

    if (!empty($controller_name) && !preg_match($valid_name_regex, $controller_name)) {
        $logger->info("`" . __FILE__ . "` on line " . __LINE__ . ": Bad controller name `{$controller_name}`");
        $notFoundHandler = $this->get('notFoundHandler');

        return $notFoundHandler($request, $response);
    }

    $full_class_name = '\App\Controller\\' . $controller_name;
    if (!class_exists($full_class_name)) {
        $logger->info("`" . __FILE__ . "` on line " . __LINE__ . ": Bad controller name `{$controller_name}`");
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
        $logger->info("`" . __FILE__ . "` on line " . __LINE__ . ": Bad action name `{$action_name}`");
        $notFoundHandler = $this->get('notFoundHandler');

        return $notFoundHandler($request, $response);
    }

    $full_action_name = $action_name . $settings['action_suffix'];
    if (empty($action_name) || !method_exists($class, $full_action_name)) {
        $logger->info("`" . __FILE__ . "` on line " . __LINE__ . ": Bad action name `{$action_name}`");
        $notFoundHandler = $this->get('notFoundHandler');

        return $notFoundHandler($request, $response);
    }

    return call_user_func_array([$class, $full_action_name], $args['parameters'] ?? array());
});