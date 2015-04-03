<?php

namespace Router;

class PathGenerator
{
    /**
     * @var Router
     */
    private $router;

    /**
     * @var null
     */
    private $domain;

    public function __construct(Router $router, $domain = null)
    {

        $this->router = $router;
        $this->domain = $domain;
    }

    /**
     * @return Router
     */
    public function getRouter()
    {
        return $this->router;
    }

    /**
     * @param Router $router
     *
     * @return $this
     */
    public function setRouter(Router $router)
    {
        $this->router = $router;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getDomain()
    {
        return $this->domain;
    }

    /**
     * @param string $domain
     *
     * @return $this
     */
    public function setDomain($domain)
    {
        $this->domain = $domain;

        return $this;
    }

    /**
     * @return string
     */
    public function __invoke()
    {

        return call_user_func_array([$this, 'generate'], func_get_args());
    }

    /**
     * @param string $routeName
     * @param array  $arguments
     * @param bool   $absolute
     *
     * @return string
     */
    public function generate(
        $routeName,
        array $arguments = [],
        $absolute = false
    ) {

        $path = $this->getRouter()->getPathFor($routeName, $arguments);

        return $absolute ? $this->getDomain() . $path : $path;
    }
}
