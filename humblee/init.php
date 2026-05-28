<?php

// Show or suppress errors
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Include required configuration and Composer autoloader
require_once _app_server_path.'humblee/configuration/config.php';
require_once _app_server_path.'humblee/vendor/autoload.php';

use Humblee\Foundation\Core;

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

/**
 * Route to a specified controller based on URI.
 * If the URI matches a pre-defined route, use its corresponding controller;
 * otherwise fall through to the Template controller which resolves pages from the DB.
 */

$_uri_parts = Core::getURIparts();
$_called_controller = (count($_uri_parts) > 0) ? strtolower($_uri_parts[0]) : '';

switch ($_called_controller)
{
    case "request": // Application-level AJAX requests — second URI segment is the action method
        $controller = new \App\Controller\Request;

        if (isset($_uri_parts[1]) && $_uri_parts[1] != "")
        {
            $function_name = $_uri_parts[1];
            $controller->$function_name();
        }
        else
        {
            $controller->index();
        }
    break;

    case "admin": // Admin panel controller
        $controller = new \Humblee\Controller\Admin;

        if (isset($_uri_parts[1]) && $_uri_parts[1] != "")
        {
            $function_name = $_uri_parts[1];
            $controller->$function_name();
        }
        else
        {
            $controller->index();
        }
    break;

    case "core-request": // Core CMS AJAX requests — second URI segment is the action method
        $controller = new \Humblee\Controller\Request;

        if (isset($_uri_parts[1]) && $_uri_parts[1] != "")
        {
            $function_name = $_uri_parts[1];
            $controller->$function_name();
        }
        else
        {
            $controller->index();
        }
    break;

    case "user": // User auth actions: login, register, profile, password reset
        $controller = new \Humblee\Controller\User;

        if (isset($_uri_parts[1]) && $_uri_parts[1] != "")
        {
            $function_name = $_uri_parts[1];
            $controller->$function_name();
        }
        else
        {
            $controller->index();
        }
    break;

    case "media": // Serve files from /storage with auth and encryption support
        $controller = new \Humblee\Controller\Media;
        $controller->index();
    break;

    default: // All standard site pages — resolves URL to a page record in the DB
        $controller = new \Humblee\Controller\Template;
        $controller->index();
}
