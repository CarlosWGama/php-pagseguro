<?php

function __autoload($namespace) {
    $class = @end(explode('\\', $namespace));
    if (file_exists(dirname(__FILE__).'/../src/' . $class . '.php'))
    require dirname(__FILE__).'/../src/' . $class . '.php';
}