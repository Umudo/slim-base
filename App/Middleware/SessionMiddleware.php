<?php

namespace App\Middleware;

use Psr\Http\Message\{
    ServerRequestInterface, ResponseInterface
};

use \App\Session;

final class SessionMiddleware
{
    protected $options = [
        'name'                          => 'appName',
        'lifetime'                      => 7200,
        'path'                          => null,
        'domain'                        => null,
        'secure'                        => false,
        'httponly'                      => true,
        'updateLifetimeWithEachRequest' => true
    ];


    public function __construct($options = [])
    {
        $this->options = array_merge($this->options, $options);
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        $this->start($request);
        $this->checkSession($request);

        $response = $next($request, $response);

        if (isset($this->options["updateLifetimeWithEachRequest"]) && $this->options["updateLifetimeWithEachRequest"] === true) {
            //Update session cookie lifetime.
            setcookie(session_name(), session_id(), time() + $this->options["lifetime"], $this->options["path"], $this->options["domain"], $this->options["secure"], $this->options["httponly"]);
        }

        return $response;
    }

    private function checkSession(ServerRequestInterface $request)
    {
        $userAgent = Session::get("userAgent");

        if (hash('sha256', $request->getHeader("HTTP_USER_AGENT")[0]) !== $userAgent) {
            Session::destroy();

            $this->start($request);
            Session::regenerate(true);
        }

    }

    private function start(ServerRequestInterface $request)
    {
        if (session_status() == PHP_SESSION_ACTIVE) {
            return;
        }

        $current = session_get_cookie_params();

        $this->options["lifetime"] = (int)($this->options['lifetime'] ?? $current['lifetime']);
        $this->options["path"] = $this->options['path'] ?? $current['path'];
        $this->options["domain"] = $this->options['domain'] ?? $current['domain'];
        $this->options["secure"] = (bool)$this->options['secure'];
        $this->options["httponly"] = (bool)$this->options['httponly'];
        session_set_cookie_params($this->options["lifetime"], $this->options["path"], $this->options["domain"], $this->options["secure"], $this->options["httponly"]); //Initialize session cookie. 
        session_name($this->options["name"]);
        session_cache_limiter(false); //http://docs.slimframework.com/#Sessions
        session_start();

        Session::set('userAgent', hash('sha256', $request->getHeader("HTTP_USER_AGENT")[0]));
    }

}