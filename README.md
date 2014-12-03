# Router

[![Build Status](https://img.shields.io/travis/gabrielbull/router/master.svg?style=flat)](https://travis-ci.org/gabrielbull/router)
[![Latest Stable Version](http://img.shields.io/packagist/v/gabrielbull/router.svg?style=flat)](https://packagist.org/packages/gabrielbull/router)
[![Total Downloads](https://img.shields.io/packagist/dm/gabrielbull/router.svg?style=flat)](https://packagist.org/packages/gabrielbull/router)
[![License](https://img.shields.io/packagist/l/gabrielbull/router.svg?style=flat)](https://packagist.org/packages/gabrielbull/router)

* Flexible regular expression routing (inspired by [Sinatra](http://www.sinatrarb.com/))
* A set of [boilerplate methods](#api) for rapidly building web apps
* Almost no overhead => [2500+ requests/second](https://gist.github.com/878833)

## Getting started

1. PHP 5.6.x is required
2. Install Router using [Composer](#composer-installation) (recommended) or manually
3. Setup [URL rewriting](https://gist.github.com/874000) so that all requests are handled by **index.php**
4. (Optional) Throw in some [APC](http://pecl.php.net/package/APC) for good measure

## Composer Installation

1. Get [Composer](http://getcomposer.org/)
2. Require Router with `php composer.phar require gabrielbull/router`
3. Install dependencies with `php composer.phar install`

## Example

*Hello World* - Obligatory hello world example

```php
<?php
require_once __DIR__ . '/vendor/autoload.php';

$router = new \Router\Router();

$router->respond('GET', '/hello-world', function () {
    return 'Hello World!';
});

$router->dispatch();
```

*Example 1* - Respond to all requests

```php
$router->respond(function () {
    return 'All the things';
});
```

*Example 2* - Named parameters

```php
$router->respond('/[:name]', function ($request) {
    return 'Hello ' . $request->name;
});
```

*Example 3* - [So RESTful](http://bit.ly/g93B1s)

```php
$router->respond('GET', '/posts', $callback);
$router->respond('POST', '/posts', $callback);
$router->respond('PUT', '/posts/[i:id]', $callback);
$router->respond('DELETE', '/posts/[i:id]', $callback);
$router->respond('OPTIONS', null, $callback);

// To match multiple request methods:
$router->respond(array('POST','GET'), $route, $callback);

// Or you might want to handle the requests in the same place
$router->respond('/posts/[create|edit:action]?/[i:id]?', function ($request, $response) {
    switch ($request->action) {
        //
    }
});
```

*Example 4* - Sending objects / files

$router->respond('/report/latest', function ($request, $response) {
    $response->file('/tmp/cached_report.zip');
});
```

## Route namespaces

```php
$router->with('/users', function () use ($router) {

    $router->respond('GET', '/?', function ($request, $response) {
        // Show all users
    });

    $router->respond('GET', '/[:id]', function ($request, $response) {
        // Show a single user
    });

});

foreach(array('projects', 'posts') as $controller) {
    // Include all routes defined in a file under a given namespace
    $router->with("/$controller", "controllers/$controller.php");
}
```

Included files are run in the scope of Router (`$router`) so all Router
methods/properties can be accessed with `$this`

_Example file for: "controllers/projects.php"_
```php
// Routes to "/projects/?"
$this->respond('GET', '/?', function ($request, $response) {
    // Show all projects
});
```

## Routing

**[** *match_type* **:** *param_name* **]**

Some examples

    *                    // Match all request URIs
    [i]                  // Match an integer
    [i:id]               // Match an integer as 'id'
    [a:action]           // Match alphanumeric characters as 'action'
    [h:key]              // Match hexadecimal characters as 'key'
    [:action]            // Match anything up to the next / or end of the URI as 'action'
    [create|edit:action] // Match either 'create' or 'edit' as 'action'
    [*]                  // Catch all (lazy)
    [*:trailing]         // Catch all as 'trailing' (lazy)
    [**:trailing]        // Catch all (possessive - will match the rest of the URI)
    .[:format]?          // Match an optional parameter 'format' - a / or . before the block is also optional

Some more complicated examples

    /posts/[*:title][i:id]     // Matches "/posts/this-is-a-title-123"
    /output.[xml|json:format]? // Matches "/output", "output.xml", "output.json"
    /[:controller]?/[:action]? // Matches the typical /controller/action format

**Note** - *all* routes that match the request URI are called - this
allows you to incorporate complex conditional logic such as user
authentication or view layouts. e.g. as a basic example, the following
code will wrap other routes with a header and footer

```php
$router->respond('*', function ($request, $response, $service) { $service->render('header.phtml'); });
//other routes
$router->respond('*', function ($request, $response, $service) { $service->render('footer.phtml'); });
```

Routes automatically match the entire request URI. If you need to match
only a part of the request URI or use a custom regular expression, use the `@` operator. If you need to
negate a route, use the `!` operator

```php
// Match all requests that end with '.json' or '.csv'
$router->respond('@\.(json|csv)$', ...

// Match all requests that _don't_ start with /admin
$router->respond('!@^/admin/', ...
```

## API

Below is a list of the public methods in the common classes you will most likely use. For a more formal source
of class/method documentation, please see the [PHPdoc generated documentation](http://chriso.github.io/klein.php/docs/).

```php
$request->
    id($hash = true)                    // Get a unique ID for the request
    paramsGet()                         // Return the GET parameter collection
    paramsPost()                        // Return the POST parameter collection
    paramsNamed()                       // Return the named parameter collection
    cookies()                           // Return the cookies collection
    server()                            // Return the server collection
    headers()                           // Return the headers collection
    files()                             // Return the files collection
    body()                              // Get the request body
    params()                            // Return all parameters
    params($mask = null)                // Return all parameters that match the mask array - extract() friendly
    param($key, $default = null)        // Get a request parameter (get, post, named)
    isSecure()                          // Was the request sent via HTTPS?
    ip()                                // Get the request IP
    userAgent()                         // Get the request user agent
    uri()                               // Get the request URI
    pathname()                          // Get the request pathname
    method()                            // Get the request method
    method($method)                     // Check if the request method is $method, i.e. method('post') => true
    query($key, $value = null)          // Get, add to, or modify the current query string
    <param>                             // Get / Set (if assigned a value) a request parameter

$response->
    protocolVersion($protocol_version = null)       // Get the protocol version, or set it to the passed value
    body($body = null)                              // Get the response body's content, or set it to the passed value
    status()                                        // Get the response's status object
    headers()                                       // Return the headers collection
    cookies()                                       // Return the cookies collection
    code($code = null)                              // Return the HTTP response code, or set it to the passed value
    prepend($content)                               // Prepend a string to the response body
    append($content)                                // Append a string to the response body
    isLocked()                                      // Check if the response is locked
    requireUnlocked()                               // Require that a response is unlocked
    lock()                                          // Lock the response from further modification
    unlock()                                        // Unlock the response
    sendHeaders($override = false)                  // Send the HTTP response headers
    sendCookies($override = false)                  // Send the HTTP response cookies
    sendBody()                                      // Send the response body's content
    send()                                          // Send the response and lock it
    isSent()                                        // Check if the response has been sent
    chunk($str = null)                              // Enable response chunking (see the wiki)
    header($key, $value = null)                     // Set a response header
    cookie($key, $value = null, $expiry = null)     // Set a cookie
    cookie($key, null)                              // Remove a cookie
    noCache()                                       // Tell the browser not to cache the response
    redirect($url, $code = 302)                     // Redirect to the specified URL
    dump($obj)                                      // Dump an object
    file($path, $filename = null)                   // Send a file
    json($object, $jsonp_prefix = null)             // Send an object as JSON or JSONP by providing padding prefix
```
