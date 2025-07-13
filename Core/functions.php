<?php

function dd($value){
echo '<pre>';
    var_dump($value);
    echo '</pre>';

die();
}

function urlIs($value) {
return $_SERVER['REQUEST_URI'] === $value;
}

 function abort($code = 404)
{
    http_response_code($code);

    require base_path("resources/views/{$code}.php");
    die();
}

function authorize($condition, $status = 403){
    if (!$condition) {
        abort($status);
    }
}

function  base_path($path)
{
    return BASE_PATH . $path;
}

function view($path, $attributes = [])
{
    extract($attributes);
    return require base_path('resources/views/' . $path);
}

function redirect($path)
{
    header("Location: {$path}");
    exit();
}

function old($key, $default = '')
{
    return Core\Session::get('old')[$key] ?? $default;
}