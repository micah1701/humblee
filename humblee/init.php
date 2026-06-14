<?php

// Show or suppress errors
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Include required configuration and Composer autoloader
require_once _app_server_path . 'humblee/configuration/config.php';
require_once _app_server_path . 'humblee/vendor/autoload.php';

use Humblee\Foundation\Core;
use Humblee\Middleware\Kernel;

// Set default timezone
date_default_timezone_set($_ENV['config']['timezone']);

// Database connection (used by Idiorm ORM)
$_rdbms = $_ENV['config']['RDBMS'] ?? 'mysql';
if ($_rdbms === 'pgsql') {
    $_dsn_schema = (isset($_ENV['config']['db_schema']) && $_ENV['config']['db_schema'] !== '')
        ? ";options='--search_path=" . $_ENV['config']['db_schema'] . "'"
        : '';
    ORM::configure('pgsql:host=' . $_ENV['config']['db_host'] . ';dbname=' . $_ENV['config']['db_name'] . $_dsn_schema);
} else {
    ORM::configure('mysql:host=' . $_ENV['config']['db_host'] . ';dbname=' . $_ENV['config']['db_name']);
}
ORM::configure('username', $_ENV['config']['db_username']);
ORM::configure('password', $_ENV['config']['db_password']);

Kernel::boot();
