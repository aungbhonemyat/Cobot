<?php
use App\Core\Router;
use App\Core\Request;

$router = new Router();

// Web routes
$router->register('get', '/', function () {
    header('Location: /items');
    exit;
});

$router->register('get', '/items', 'ItemController@index');

return $router;
