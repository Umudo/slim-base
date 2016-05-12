<?php

$app->add(new \App\Middleware\SessionMiddleware($app->getContainer()->get('settings')['session']));

$app->add(function (Psr\Http\Message\ServerRequestInterface $request, Psr\Http\Message\ResponseInterface $response, callable $next) {
    $uri = $request->getUri();
    $path = $uri->getPath();

    $path_trimmed = preg_replace('/\/{2,}$/', '/', $path); //Prevent multiple redirects

    if ($path != '/' && substr($path_trimmed, -1) == '/') {
        // permanently redirect paths with a trailing slash
        // to their non-trailing counterpart
        $uri = $uri->withPath(substr($path_trimmed, 0, -1));

        return $response->withStatus(301)->withHeader('Location', (string)$uri); //exists in \Slim\Http\Response
    }

    $basePath = $uri->getBaseUrl();

    $check_cn_forward_header = $request->getHeader("HTTP_X_FORWARDED_PROTO");

    if (!empty($check_cn_forward_header) && $check_cn_forward_header[0] == "https") {
        $basePath = preg_replace('/http/i', $check_cn_forward_header[0], $basePath, 1);
    }

    \App\Helper\Uri::setBasePath($basePath);

    return $next($request, $response);
});