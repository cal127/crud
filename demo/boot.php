<?php

// composer
$composer_autoload_file = __DIR__ . '/vendor/autoload.php';

if (!file_exists($composer_autoload_file)) {
    throw new Exception('You need to run "composer install"!');
}

require_once __DIR__ . '/vendor/autoload.php';


// models
require_once __DIR__ . '/models.php';

// db & sess
$pdo = new PDO('mysql:host=localhost;dbname=crudexample;charset=utf8', 'root', 'root');
ORM::set_db($pdo);
session_start();
