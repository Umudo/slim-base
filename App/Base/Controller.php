<?php

namespace App\Base;


use Psr\Container\ContainerInterface;
use Psr\Http\Message\{
    ResponseInterface, ServerRequestInterface
};

abstract class Controller
{
    /**
     * DI Container
     *
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var ServerRequestInterface
     */
    protected $request;

    /**
     * @var ResponseInterface
     */
    protected $response;

    protected $data;

    public function __construct(ContainerInterface $container, ServerRequestInterface $request, ResponseInterface $response)
    {
        $this->container = $container;
        $this->request = $request;
        $this->response = $response;
        $this->data = [];
    }

    public function wantsJson()
    {
        $acceptHeaders = $this->request->getHeader('Accept');

        return $this->request->getHeaderLine('X-Requested-With') === 'XMLHttpRequest' || (!empty($acceptHeaders) && preg_match('/application\/.*\+?json/', $acceptHeaders[0]));
    }

    protected function renderView(ResponseInterface $response, string $filename)
    {
        return $this->container->get('view')->render($response, $filename, $this->data);
    }
}