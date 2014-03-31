<?php
define('LIB', implode(DIRECTORY_SEPARATOR, [realpath(dirname(__FILE__)), 'lib']));

date_default_timezone_set('UTC');
spl_autoload_register(function ($className) {
        $path = implode(DIRECTORY_SEPARATOR, [
                LIB,
                str_replace('\\', DIRECTORY_SEPARATOR, $className) . '.php'
            ]);
        if (file_exists($path)) {
            require_once $path;
        }
    });