<?php
namespace Router;

use Exception;
use OutOfBoundsException;
use Router\DataCollection\RouteCollection;
use Router\Exceptions\DispatchHaltedException;
use Router\Exceptions\HttpException;
use Router\Exceptions\HttpExceptionInterface;
use Router\Exceptions\LockedResponseException;
use Router\Exceptions\RegularExpressionCompilationException;
use Router\Exceptions\RoutePathCompilationException;
use Router\Exceptions\UnhandledException;

class Router
{
    const ROUTE_NAMESPACE = true;
    const ROUTE_NO_NAMESPACE = false;

    /**
     * The regular expression used to compile and match URL's
     *
     * @const string
     */
    const ROUTE_COMPILE_REGEX = '`(\\\?(?:/|\.|))(\[([^:\]]*+)(?::([^:\]]*+))?\])(\?|)`';

    /**
     * The regular expression used to escape the non-named param section of a route URL
     *
     * @const string
     */
    const ROUTE_ESCAPE_REGEX = '`(?<=^|\])[^\]\[\?]+?(?=\[|$)`';

    /**
     * Dispatch route output handling
     * Don't capture anything. Behave as normal.
     *
     * @const int
     */
    const DISPATCH_NO_CAPTURE = 0;

    /**
     * Dispatch route output handling
     * Capture all output and return it from dispatch
     *
     * @const int
     */
    const DISPATCH_CAPTURE_AND_RETURN = 1;

    /**
     * Dispatch route output handling
     * Capture all output and replace the response body with it
     *
     * @const int
     */
    const DISPATCH_CAPTURE_AND_REPLACE = 2;

    /**
     * Dispatch route output handling
     * Capture all output and prepend it to the response body
     *
     * @const int
     */
    const DISPATCH_CAPTURE_AND_PREPEND = 3;

    /**
     * Dispatch route output handling
     * Capture all output and append it to the response body
     *
     * @const int
     */
    const DISPATCH_CAPTURE_AND_APPEND = 4;

    /**
     * Collection of the routes to match on dispatch
     *
     * @var RouteCollection
     */
    protected $routes;

    /**
     * The Route factory object responsible for creating Route instances
     *
     * @var AbstractRouteFactory
     */
    protected $route_factory;

    /**
     * An array of error callback callables
     *
     * @var array[callable]
     */
    protected $errorCallbacks = array();

    /**
     * An array of HTTP error callback callables
     *
     * @var array[callable]
     */
    protected $httpErrorCallbacks = array();

    /**
     * An array of callbacks to call after processing the dispatch loop
     * and before the response is sent
     *
     * @var array[callable]
     */
    protected $afterFilterCallbacks = array();

    /**
     * The Request object passed to each matched route
     *
     * @var Request
     * @deprecated
     */
    protected $request;

    /**
     * @var
     */
    protected $psrRequest;

    /**
     * The Response object passed to each matched route
     *
     * @var Response
     */
    protected $response;

    /**
     * A generic variable passed to each matched route
     *
     * @var mixed
     */
    protected $app;

    /**
     * Constructor
     *
     * Create a new Router instance with optionally injected dependencies
     * This DI allows for easy testing, object mocking, or class extension
     *
     * @param mixed $app An object passed to each route callback, defaults to an App instance
     * @param RouteCollection $routes Collection object responsible for containing all route instances
     * @param AbstractRouteFactory $route_factory A factory class responsible for creating Route instances
     */
    public function __construct(
        $app = null,
        RouteCollection $routes = null,
        AbstractRouteFactory $route_factory = null
    )
    {
        // Instanciate and fall back to defaults
        //$this->app = $app ?: new App();
        $this->routes = $routes ?: new RouteCollection();
        $this->route_factory = $route_factory ?: new RouteFactory();
    }

    /**
     * @return RouteCollection
     */
    public function getRoutes()
    {
        return $this->routes;
    }

    /**
     * @param RouteCollection $routes
     * @return $this
     */
    public function setRoutes(RouteCollection $routes)
    {
        $this->routes = $routes;
        return $this;
    }

    /**
     * @return PsrRequest
     */
    public function getRequest()
    {
        if (null === $this->psrRequest) {
            $this->psrRequest = (new RequestController())->createRequestFromGlobals();
        }
        return $this->psrRequest;
    }

    /**
     * @param PsrRequest $request
     * @return $this
     */
    public function setRequest(PsrRequest $request)
    {
        $this->psrRequest = $request;
        return $this;
    }

    /**
     * @param Request $request
     * @return $this|Request
     * @deprecated
     */
    public function request(Request $request = null)
    {
        if (null === $request) {
            return $this->request;
        }
        $this->request = $request;
        return $this;
    }

    /**
     * @return Response
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * @param Response $response
     * @return $this
     */
    public function setResponse(Response $response)
    {
        $this->response = $response;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getApp()
    {
        return $this->app;
    }

    /**
     * @param mixed $app
     * @return $this
     */
    public function setApp($app)
    {
        $this->app = $app;
        return $this;
    }

    /**
     * Parse our extremely loose argument order of our "respond" method and its aliases
     *
     * This method takes its arguments in a loose format and order.
     * The method signature is simply there for documentation purposes, but allows
     * for the minimum of a callback to be passed in its current configuration.
     *
     * @see Klein::respond()
     * @param mixed $args An argument array. Hint: This works well when passing "func_get_args()"
     * @named string | array $method   HTTP Method to match
     * @named string $path             Route URI path to match
     * @named callable $callback       Callable callback method to execute on route match
     * @return array                    A named parameter array containing the keys: 'method', 'path', and 'callback'
     */
    protected function parseLooseArgumentOrder(array $args)
    {
        // Get the arguments in a very loose format
        $callback = array_pop($args);
        $path = array_pop($args);
        $method = array_pop($args);

        // Return a named parameter array
        return array(
            'method' => $method,
            'path' => $path,
            'callback' => $callback,
        );
    }

    /**
     * Add a new route to be matched on dispatch
     *
     * Essentially, this method is a standard "Route" builder/factory,
     * allowing a loose argument format and a standard way of creating
     * Route instances
     *
     * This method takes its arguments in a very loose format
     * The only "required" parameter is the callback (which is very strange considering the argument definition order)
     *
     * <code>
     * $router = new Router();
     *
     * $router->respond( function() {
     *     echo 'this works';
     * });
     * $router->respond( '/endpoint', function() {
     *     echo 'this also works';
     * });
     * $router->respond( 'POST', '/endpoint', function() {
     *     echo 'this also works!!!!';
     * });
     * </code>
     *
     * @param string | array $method HTTP Method to match
     * @param string $path Route URI path to match
     * @param callable $callback Callable callback method to execute on route match
     * @param bool $useNamespace
     * @return Route
     * todo: namespace should be OOP and extendable/customizable, while keeping the simplicity
     * of the current automated namespace system
     */
    public function respond($method, $path = '*', $callback = null, $useNamespace = self::ROUTE_NAMESPACE)
    {
        // Get the arguments in a very loose format
        $arguments = func_get_args();
        if (isset($arguments[3])) {
            unset($arguments[3]);
        }
        extract(
            $this->parseLooseArgumentOrder($arguments),
            EXTR_OVERWRITE
        );

        $route = $this->route_factory->build($callback, $path, $method, true, null, $useNamespace);

        $this->routes->add($route);

        return $route;
    }

    /**
     * Collect a set of routes under a common namespace
     *
     * The routes may be passed in as either a callable (which holds the route definitions),
     * or as a string of a filename, of which to "include" under the Router router scope
     *
     * <code>
     * $router = new Router();
     *
     * $router->with('/users', function($router) {
     *     $router->respond( '/', function() {
     *         // do something interesting
     *     });
     *     $router->respond( '/[i:id]', function() {
     *         // do something different
     *     });
     * });
     *
     * $router->with('/cars', __DIR__ . '/routes/cars.php');
     * </code>
     *
     * @param string $namespace The namespace under which to collect the routes
     * @param callable | string[filename] $routes   The defined routes to collect under the namespace
     * @return void
     */
    public function with($namespace, $routes)
    {
        $previous = $this->route_factory->getNamespace();

        $this->route_factory->appendNamespace($namespace);

        if (is_callable($routes)) {
            if (is_string($routes)) {
                $routes($this);
            } else {
                call_user_func($routes, $this);
            }
        } else {
            require $routes;
        }

        $this->route_factory->setNamespace($previous);
    }

    /**
     * Dispatch the request to the appropriate route(s)
     *
     * Dispatch with optionally injected dependencies
     * This DI allows for easy testing, object mocking, or class extension
     *
     * @param Request $request The request object to give to each callback
     * @param AbstractResponse $response The response object to give to each callback
     * @param boolean $send_response Whether or not to "send" the response after the last route has been matched
     * @param int $capture Specify a DISPATCH_* constant to change the output capturing behavior
     * @return void|string
     */
    public function dispatch(
        Request $request = null,
        AbstractResponse $response = null,
        $send_response = true,
        $capture = self::DISPATCH_NO_CAPTURE
    )
    {
        // Set/Initialize our objects to be sent in each callback
        $this->request = $request ?: Request::createFromGlobals();
        $this->response = $response ?: new Response();

        // Prepare any named routes
        $this->routes->prepareNamed();


        // Grab some data from the request
        $uri = $this->request->pathname();
        $req_method = $this->request->method();

        // Set up some variables for matching
        $skip_num = 0;
        $matched = $this->routes->cloneEmpty(); // Get a clone of the routes collection, as it may have been injected
        $methods_matched = array();
        $params = array();
        $apc = function_exists('apc_fetch');

        ob_start();

        try {
            foreach ($this->routes as $route) {
                // Are we skipping any matches?
                if ($skip_num > 0) {
                    $skip_num--;
                    continue;
                }

                // Grab the properties of the route handler
                $method = $route->getMethod();
                $path = $route->getPath();
                $count_match = $route->getCountMatch();

                // Keep track of whether this specific request method was matched
                $method_match = null;

                // Was a method specified? If so, check it against the current request method
                if (is_array($method)) {
                    foreach ($method as $test) {
                        if (strcasecmp($req_method, $test) === 0) {
                            $method_match = true;
                        } elseif (strcasecmp($req_method, 'HEAD') === 0
                            && (strcasecmp($test, 'HEAD') === 0 || strcasecmp($test, 'GET') === 0)
                        ) {

                            // Test for HEAD request (like GET)
                            $method_match = true;
                        }
                    }

                    if (null === $method_match) {
                        $method_match = false;
                    }
                } elseif (null !== $method && strcasecmp($req_method, $method) !== 0) {
                    $method_match = false;

                    // Test for HEAD request (like GET)
                    if (strcasecmp($req_method, 'HEAD') === 0
                        && (strcasecmp($method, 'HEAD') === 0 || strcasecmp($method, 'GET') === 0)
                    ) {

                        $method_match = true;
                    }
                } elseif (null !== $method && strcasecmp($req_method, $method) === 0) {
                    $method_match = true;
                }

                // If the method was matched or if it wasn't even passed (in the route callback)
                $possible_match = (null === $method_match) || $method_match;

                // ! is used to negate a match
                if (isset($path[0]) && $path[0] === '!') {
                    $negate = true;
                    $i = 1;
                } else {
                    $negate = false;
                    $i = 0;
                }

                // Check for a wildcard (match all)
                if ($path === '*') {
                    $match = true;

                } elseif (($path === '404' && $matched->isEmpty() && count($methods_matched) <= 0)
                    || ($path === '405' && $matched->isEmpty() && count($methods_matched) > 0)
                ) {

                    // Warn user of deprecation
                    trigger_error(
                        'Use of 404/405 "routes" is deprecated. Use $klein->onHttpError() instead.',
                        E_USER_DEPRECATED
                    );
                    // TODO: Possibly remove in future, here for backwards compatibility
                    $this->onHttpError($route);

                    continue;

                } elseif (isset($path[$i]) && $path[$i] === '@') {
                    // @ is used to specify custom regex

                    $match = preg_match('`' . substr($path, $i + 1) . '`', $uri, $params);

                } else {
                    // Compiling and matching regular expressions is relatively
                    // expensive, so try and match by a substring first

                    $expression = null;
                    $regex = false;
                    $j = 0;
                    $n = isset($path[$i]) ? $path[$i] : null;

                    // Find the longest non-regex substring and match it against the URI
                    while (true) {
                        if (!isset($path[$i])) {
                            break;
                        } elseif (false === $regex) {
                            $c = $n;
                            $regex = $c === '[' || $c === '(' || $c === '.';
                            if (false === $regex && false !== isset($path[$i + 1])) {
                                $n = $path[$i + 1];
                                $regex = $n === '?' || $n === '+' || $n === '*' || $n === '{';
                            }
                            if (false === $regex && $c !== '/' && (!isset($uri[$j]) || $c !== $uri[$j])) {
                                continue 2;
                            }
                            $j++;
                        }
                        $expression .= $path[$i++];
                    }

                    try {
                        // Check if there's a cached regex string
                        if (false !== $apc) {
                            $regex = apc_fetch("route:$expression");
                            if (false === $regex) {
                                $regex = $this->compileRoute($expression);
                                apc_store("route:$expression", $regex);
                            }
                        } else {
                            $regex = $this->compileRoute($expression);
                        }
                    } catch (RegularExpressionCompilationException $e) {
                        throw RoutePathCompilationException::createFromRoute($route, $e);
                    }

                    $match = preg_match($regex, $uri, $params);
                }

                if (isset($match) && $match ^ $negate) {
                    if ($possible_match) {
                        if (!empty($params)) {
                            /**
                             * URL Decode the params according to RFC 3986
                             * @link http://www.faqs.org/rfcs/rfc3986
                             *
                             * Decode here AFTER matching as per @chriso's suggestion
                             * @link https://github.com/chriso/klein.php/issues/117#issuecomment-21093915
                             */
                            $params = array_map('rawurldecode', $params);

                            $this->getRequest()->setAttributes($params);
                            $this->request->paramsNamed()->merge($params);
                        }

                        // Handle our response callback
                        try {
                            $this->handleRouteCallback($route, $matched, $methods_matched);

                        } catch (DispatchHaltedException $e) {
                            switch ($e->getCode()) {
                                case DispatchHaltedException::SKIP_THIS:
                                    continue 2;
                                    break;
                                case DispatchHaltedException::SKIP_NEXT:
                                    $skip_num = $e->getNumberOfSkips();
                                    break;
                                case DispatchHaltedException::SKIP_REMAINING:
                                    break 2;
                                default:
                                    throw $e;
                            }
                        }

                        if ($path !== '*') {
                            $count_match && $matched->add($route);
                        }
                    }

                    // Don't bother counting this as a method match if the route isn't supposed to match anyway
                    if ($count_match) {
                        // Keep track of possibly matched methods
                        $methods_matched = array_merge($methods_matched, (array)$method);
                        $methods_matched = array_filter($methods_matched);
                        $methods_matched = array_unique($methods_matched);
                    }
                }
            }

            // Handle our 404/405 conditions
            if ($matched->isEmpty() && count($methods_matched) > 0) {
                // Add our methods to our allow header
                $this->response->header('Allow', implode(', ', $methods_matched));

                if (strcasecmp($req_method, 'OPTIONS') !== 0) {
                    throw HttpException::createFromCode(405);
                }
            } elseif ($matched->isEmpty()) {
                throw HttpException::createFromCode(404);
            }

        } catch (HttpExceptionInterface $e) {
            // Grab our original response lock state
            $locked = $this->response->isLocked();

            // Call our http error handlers
            $this->httpError($e, $matched, $methods_matched);

            // Make sure we return our response to its original lock state
            if (!$locked) {
                $this->response->unlock();
            }

        } catch (Exception $e) {
            $this->error($e);
        }

        try {
            if ($this->response->chunked) {
                $this->response->chunk();

            } else {
                // Output capturing behavior
                switch ($capture) {
                    case self::DISPATCH_CAPTURE_AND_RETURN:
                        $buffed_content = null;
                        if (ob_get_level()) {
                            $buffed_content = ob_get_clean();
                        }
                        return $buffed_content;
                        break;
                    case self::DISPATCH_CAPTURE_AND_REPLACE:
                        if (ob_get_level()) {
                            $this->response->body(ob_get_clean());
                        }
                        break;
                    case self::DISPATCH_CAPTURE_AND_PREPEND:
                        if (ob_get_level()) {
                            $this->response->prepend(ob_get_clean());
                        }
                        break;
                    case self::DISPATCH_CAPTURE_AND_APPEND:
                        if (ob_get_level()) {
                            $this->response->append(ob_get_clean());
                        }
                        break;
                    case self::DISPATCH_NO_CAPTURE:
                    default:
                        if (ob_get_level()) {
                            ob_end_flush();
                        }
                }
            }

            // Test for HEAD request (like GET)
            if (strcasecmp($req_method, 'HEAD') === 0) {
                // HEAD requests shouldn't return a body
                $this->response->body('');

                if (ob_get_level()) {
                    ob_clean();
                }
            }
        } catch (LockedResponseException $e) {
            // Do nothing, since this is an automated behavior
        }

        // Run our after dispatch callbacks
        $this->callAfterDispatchCallbacks();

        if ($send_response && !$this->response->isSent()) {
            $this->response->send();
        }
    }

    /**
     * Compiles a route string to a regular expression
     *
     * @param string $route The route string to compile
     * @return void
     */
    protected function compileRoute($route)
    {
        // First escape all of the non-named param (non [block]s) for regex-chars
        if (preg_match_all(static::ROUTE_ESCAPE_REGEX, $route, $escape_locations, PREG_SET_ORDER)) {
            foreach ($escape_locations as $locations) {
                $route = str_replace($locations[0], preg_quote($locations[0]), $route);
            }
        }

        // Now let's actually compile the path
        if (preg_match_all(static::ROUTE_COMPILE_REGEX, $route, $matches, PREG_SET_ORDER)) {
            $match_types = array(
                'i' => '[0-9]++',
                'a' => '[0-9A-Za-z]++',
                'h' => '[0-9A-Fa-f]++',
                's' => '[0-9A-Za-z-_]++',
                '*' => '.+?',
                '**' => '.++',
                '' => '[^/]+?'
            );

            foreach ($matches as $match) {
                list($block, $pre, $inner_block, $type, $param, $optional) = $match;

                if (isset($match_types[$type])) {
                    $type = $match_types[$type];
                }
                // Older versions of PCRE require the 'P' in (?P<named>)
                $pattern = '(?:'
                    . ($pre !== '' ? $pre : null)
                    . '('
                    . ($param !== '' ? "?P<$param>" : null)
                    . $type
                    . '))'
                    . ($optional !== '' ? '?' : null);

                $route = str_replace($block, $pattern, $route);
            }
        }

        $regex = "`^$route$`";

        // Check if our regular expression is valid
        $this->validateRegularExpression($regex);

        return $regex;
    }

    /**
     * Validate a regular expression
     *
     * This simply checks if the regular expression is able to be compiled
     * and converts any warnings or notices in the compilation to an exception
     *
     * @param string $regex The regular expression to validate
     * @throws RegularExpressionCompilationException If the expression can't be compiled
     * @access private
     * @return boolean
     */
    private function validateRegularExpression($regex)
    {
        $error_string = null;

        // Set an error handler temporarily
        set_error_handler(
            function ($errno, $errstr) use (&$error_string) {
                $error_string = $errstr;
            },
            E_NOTICE | E_WARNING
        );

        if (false === preg_match($regex, null) || !empty($error_string)) {
            // Remove our temporary error handler
            restore_error_handler();

            throw new RegularExpressionCompilationException(
                $error_string,
                preg_last_error()
            );
        }

        // Remove our temporary error handler
        restore_error_handler();

        return true;
    }

    /**
     * Get the path for a given route
     *
     * This looks up the route by its passed name and returns
     * the path/url for that route, with its URL params as
     * placeholders unless you pass a valid key-value pair array
     * of the placeholder params and their values
     *
     * If a pathname is a complex/custom regular expression, this
     * method will simply return the regular expression used to
     * match the request pathname, unless an optional boolean is
     * passed "flatten_regex" which will flatten the regular
     * expression into a simple path string
     *
     * This method, and its style of reverse-compilation, was originally
     * inspired by a similar effort by Gilles Bouthenot (@gbouthenot)
     *
     * @link https://github.com/gbouthenot
     * @param string $route_name The name of the route
     * @param array $params The array of placeholder fillers
     * @param boolean $flatten_regex Optionally flatten custom regular expressions to "/"
     * @throws OutOfBoundsException     If the route requested doesn't exist
     * @return string
     */
    public function getPathFor($route_name, array $params = null, $flatten_regex = true)
    {
        // First, grab the route
        $route = $this->routes->get($route_name);

        // Make sure we are getting a valid route
        if (null === $route) {
            throw new OutOfBoundsException('No such route with name: ' . $route_name);
        }

        $path = $route->getPath();

        if (preg_match_all(static::ROUTE_COMPILE_REGEX, $path, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                list($block, $pre, $inner_block, $type, $param, $optional) = $match;

                if (isset($params[$param])) {
                    $path = str_replace($block, $pre . $params[$param], $path);
                } elseif ($optional) {
                    $path = str_replace($block, '', $path);
                }
            }

        } elseif ($flatten_regex && strpos($path, '@') === 0) {
            // If the path is a custom regular expression and we're "flattening", just return a slash
            $path = '/';
        }

        return $path;
    }

    /**
     * Handle a route's callback
     *
     * This handles common exceptions and their output
     * to keep the "dispatch()" method DRY
     *
     * @param Route $route
     * @param RouteCollection $matched
     * @param int $methods_matched
     * @return void
     */
    protected function handleRouteCallback(Route $route, RouteCollection $matched, $methods_matched)
    {
        // Handle the callback
        $returned = call_user_func(
            $route->getCallback(), // Instead of relying on the slower "invoke" magic
            $this->request,
            $this->response,
            $this->app,
            $this, // Pass the Router instance
            $matched,
            $methods_matched
        );

        if ($returned instanceof AbstractResponse) {
            $this->response = $returned;
        } else {
            // Otherwise, attempt to append the returned data
            try {
                $this->response->append($returned);
            } catch (LockedResponseException $e) {
                // Do nothing, since this is an automated behavior
            }
        }
    }

    /**
     * Adds an error callback to the stack of error handlers
     *
     * @param callable $callback The callable function to execute in the error handling chain
     * @return boolean|void
     */
    public function onError($callback)
    {
        $this->errorCallbacks[] = $callback;
    }

    /**
     * Routes an exception through the error callbacks
     *
     * @param Exception $err The exception that occurred
     * @throws UnhandledException   If the error/exception isn't handled by an error callback
     * @return void
     */
    protected function error(Exception $err)
    {
        $type = get_class($err);
        $msg = $err->getMessage();

        if (count($this->errorCallbacks) > 0) {
            foreach (array_reverse($this->errorCallbacks) as $callback) {
                if (is_callable($callback)) {
                    if (is_string($callback)) {
                        $callback($this, $msg, $type, $err);

                        return;
                    } else {
                        call_user_func($callback, $this, $msg, $type, $err);

                        return;
                    }
                }
            }
        } else {
            $this->response->code(500);
            throw new UnhandledException($msg, $err->getCode(), $err);
        }

        // Lock our response, since we probably don't want
        // anything else messing with our error code/body
        $this->response->lock();
    }

    /**
     * Adds an HTTP error callback to the stack of HTTP error handlers
     *
     * @param callable $callback The callable function to execute in the error handling chain
     * @return void
     */
    public function onHttpError($callback)
    {
        $this->httpErrorCallbacks[] = $callback;
    }

    /**
     * Handles an HTTP error exception through our HTTP error callbacks
     *
     * @param HttpExceptionInterface $http_exception The exception that occurred
     * @param RouteCollection $matched The collection of routes that were matched in dispatch
     * @param array $methods_matched The HTTP methods that were matched in dispatch
     * @return void
     */
    protected function httpError(HttpExceptionInterface $http_exception, RouteCollection $matched, $methods_matched)
    {
        if (!$this->response->isLocked()) {
            $this->response->code($http_exception->getCode());
        }

        if (count($this->httpErrorCallbacks) > 0) {
            foreach (array_reverse($this->httpErrorCallbacks) as $callback) {
                if ($callback instanceof Route) {
                    $this->handleRouteCallback($callback, $matched, $methods_matched);
                } elseif (is_callable($callback)) {
                    if (is_string($callback)) {
                        $callback(
                            $http_exception->getCode(),
                            $this,
                            $matched,
                            $methods_matched,
                            $http_exception
                        );
                    } else {
                        call_user_func(
                            $callback,
                            $http_exception->getCode(),
                            $this,
                            $matched,
                            $methods_matched,
                            $http_exception
                        );
                    }
                }
            }
        }

        // Lock our response, since we probably don't want
        // anything else messing with our error code/body
        $this->response->lock();
    }

    /**
     * Adds a callback to the stack of handlers to run after the dispatch
     * loop has handled all of the route callbacks and before the response
     * is sent
     *
     * @param callable $callback The callable function to execute in the after route chain
     * @return void
     */
    public function afterDispatch($callback)
    {
        $this->afterFilterCallbacks[] = $callback;
    }

    /**
     * Runs through and executes the after dispatch callbacks
     *
     * @return void
     */
    protected function callAfterDispatchCallbacks()
    {
        try {
            foreach ($this->afterFilterCallbacks as $callback) {
                if (is_callable($callback)) {
                    if (is_string($callback)) {
                        $callback($this);

                    } else {
                        call_user_func($callback, $this);

                    }
                }
            }
        } catch (Exception $e) {
            $this->error($e);
        }
    }

    /**
     * OPTIONS alias for "respond()"
     *
     * @see Klein::respond()
     * @param string $path
     * @param callable $callback
     * @return Route
     */
    public function options($path = '*', $callback = null)
    {
        // Options the arguments in a very loose format
        extract(
            $this->parseLooseArgumentOrder(func_get_args()),
            EXTR_OVERWRITE
        );

        return $this->respond('OPTIONS', $path, $callback);
    }

    /**
     * HEAD alias for "respond()"
     *
     * @see Klein::respond()
     * @param string $path
     * @param callable $callback
     * @return Route
     */
    public function head($path = '*', $callback = null)
    {
        // Get the arguments in a very loose format
        extract(
            $this->parseLooseArgumentOrder(func_get_args()),
            EXTR_OVERWRITE
        );

        return $this->respond('HEAD', $path, $callback);
    }

    /**
     * GET alias for "respond()"
     *
     * @see Klein::respond()
     * @param string $path
     * @param callable $callback
     * @return Route
     */
    public function get($path = '*', $callback = null)
    {
        // Get the arguments in a very loose format
        extract(
            $this->parseLooseArgumentOrder(func_get_args()),
            EXTR_OVERWRITE
        );

        return $this->respond('GET', $path, $callback);
    }

    /**
     * POST alias for "respond()"
     *
     * @see Klein::respond()
     * @param string $path
     * @param callable $callback
     * @return Route
     */
    public function post($path = '*', $callback = null)
    {
        // Get the arguments in a very loose format
        extract(
            $this->parseLooseArgumentOrder(func_get_args()),
            EXTR_OVERWRITE
        );

        return $this->respond('POST', $path, $callback);
    }

    /**
     * PUT alias for "respond()"
     *
     * @see Klein::respond()
     * @param string $path
     * @param callable $callback
     * @return Route
     */
    public function put($path = '*', $callback = null)
    {
        // Get the arguments in a very loose format
        extract(
            $this->parseLooseArgumentOrder(func_get_args()),
            EXTR_OVERWRITE
        );

        return $this->respond('PUT', $path, $callback);
    }

    /**
     * DELETE alias for "respond()"
     *
     * @see Klein::respond()
     * @param string $path
     * @param callable $callback
     * @return Route
     */
    public function delete($path = '*', $callback = null)
    {
        // Get the arguments in a very loose format
        extract(
            $this->parseLooseArgumentOrder(func_get_args()),
            EXTR_OVERWRITE
        );

        return $this->respond('DELETE', $path, $callback);
    }

    /**
     * PATCH alias for "respond()"
     *
     * PATCH was added to HTTP/1.1 in RFC5789
     *
     * @link http://tools.ietf.org/html/rfc5789
     * @see Klein::respond()
     * @param string $path
     * @param callable $callback
     * @return Route
     */
    public function patch($path = '*', $callback = null)
    {
        // Get the arguments in a very loose format
        extract(
            $this->parseLooseArgumentOrder(func_get_args()),
            EXTR_OVERWRITE
        );

        return $this->respond('PATCH', $path, $callback);
    }
}
