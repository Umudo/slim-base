<?php

namespace App\Middleware;

use Psr\Http\Message\{
    ServerRequestInterface, ResponseInterface
};

use \App\Session;

final class SessionMiddleware
{
    protected $options = [
        'name'     => 'appName',
        'lifetime' => 7200,
        'path'     => null,
        'domain'   => null,
        'secure'   => false,
        'httponly' => true,
    ];


    public function __construct($options = [])
    {
        $this->options = array_merge($this->options, $options);
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        $this->start();
        $this->checkSession();

        return $next($request, $response);
    }

    private function checkSession()
    {
        $userAgent = Session::get("userAgent");
        if (hash('sha256', $_SERVER['HTTP_USER_AGENT']) !== $userAgent) {
            Session::destroy();

            $this->start();
            Session::regenerate(true);
        }

    }

    private function start()
    {
        if (session_status() == PHP_SESSION_ACTIVE) {
            return;
        }

        $options = $this->options;
        $current = session_get_cookie_params();

        $lifetime = (int)($options['lifetime'] ?? $current['lifetime']);
        $path = $options['path'] ?? $current['path'];
        $domain = $options['domain'] ?? $current['domain'];
        $secure = (bool)$options['secure'];
        $httponly = (bool)$options['httponly'];
        session_set_cookie_params($lifetime, $path, $domain, $secure, $httponly);
        session_name($options['name']);
        session_cache_limiter(false); //http://docs.slimframework.com/#Sessions
        session_start();

        Session::set('HTTP_USER_AGENT', hash('sha256', $_SERVER['HTTP_USER_AGENT']));
    }

}