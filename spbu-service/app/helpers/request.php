<?php

function getRequestPath()
{
    $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    return rtrim($uri, '/') ?: '/';
}

function getRequestMethod()
{
    return $_SERVER['REQUEST_METHOD'];
}