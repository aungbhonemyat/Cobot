<?php
require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/Core/Request.php';
require_once __DIR__ . '/../app/Core/Router.php';

spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    if (strpos($class, $prefix) !== 0) return;
    $rel = substr($class, strlen($prefix));
    $path = __DIR__ . '/../app/' . str_replace('\\', '/', $rel) . '.php';
    $path = str_replace('/', DIRECTORY_SEPARATOR, $path);
    if (file_exists($path)) {
        require_once $path;
    }
});

// load helpers and db
require_once __DIR__ . '/../app/helpers.php';

$router = require_once __DIR__ . '/../app/routes.php';
$router->dispatch();
