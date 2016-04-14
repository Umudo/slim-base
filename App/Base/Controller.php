<?php

namespace App\Base;


use Interop\Container\ContainerInterface;
use Psr\Http\Message\{
    ServerRequestInterface, ResponseInterface
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

    public function __construct(ContainerInterface $container, ServerRequestInterface $request, ResponseInterface $response)
    {
        $this->container = $container;
        $this->request = $request;
        $this->response = $response;
    }
}