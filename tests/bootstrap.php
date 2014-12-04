<?php

// Set a default timezone in case the user has not defined one
date_default_timezone_set('UTC');

// Set some configuration values
ini_set('session.use_cookies', 0);      // Don't send headers when testing sessions
ini_set('session.cache_limiter', '');   // Don't send cache headers when testing sessions

$vendor = realpath(__DIR__ . '/../vendor');

if (file_exists($vendor . "/autoload.php")) {
    require $vendor . "/autoload.php";
} else {
    $vendor = realpath(__DIR__ . '/../../../');
    if (file_exists($vendor . "/autoload.php")) {
        require $vendor . "/autoload.php";
    } else {
        throw new Exception("Unable to load dependencies");
    }
}

// Load our functions bootstrap
require __DIR__ . '/functions-bootstrap.php';
