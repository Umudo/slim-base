<?php

$app->add(function (Psr\Http\Message\RequestInterface $request, Psr\Http\Message\ResponseInterface $response, callable $next) {
    $uri = $request->getUri();
    $path = $uri->getPath();

    $path_trimmed = preg_replace('/\/{2,}$/', '/', $path); //Prevent multiple redirects

    if ($path != '/' && substr($path_trimmed, -1) == '/') {
        // permanently redirect paths with a trailing slash
        // to their non-trailing counterpart
        $uri = $uri->withPath(substr($path_trimmed, 0, -1));
        return $response->withRedirect((string)$uri, 301); //exists in \Slim\Http\Response
    }

    return $next($request, $response);
});

$app->add(new \App\Middleware\SessionMiddleware());