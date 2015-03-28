<?php

namespace Router;

class RouterApplication
{
    /**
     * @var Router
     */
    private $router;

    /**
     * @param Router $router
     */
    public function __construct(Router $router)
    {
        $this->router = $router;
    }

    /**
     * @param RouterInterface $router
     */
    public function add(RouterInterface $router)
    {
        $router->init();
    }

    public function dispatch()
    {
        $this->router->dispatch($this->router->request());
    }
}
