<?php
namespace App\Core;

class Request
{
    public static function path(): string
    {
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        return rtrim($uri, '/') ?: '/';
    }

    public static function method(): string
    {
        return strtolower($_SERVER['REQUEST_METHOD']);
    }

    public static function input(string $key, $default = null)
    {
        return $_REQUEST[$key] ?? $default;
    }

    public static function all(): array
    {
        return array_merge($_GET, $_POST);
    }
}
