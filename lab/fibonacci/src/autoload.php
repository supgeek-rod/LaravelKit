<?php

spl_autoload_register(function ($classname) {
    require_once __DIR__ . '/' . $classname . '.php';
});
