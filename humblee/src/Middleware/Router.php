<?php

declare(strict_types=1);

namespace Humblee\Middleware;

use Humblee\Foundation\Core;

class Router implements Contract
{
    public function handle(Package $package): void
    {
        $uri_parts         = Core::getURIparts();
        $called_controller = (count($uri_parts) > 0) ? strtolower($uri_parts[0]) : '';

        switch ($called_controller) {
            case 'request': // Application-level AJAX requests — second URI segment is the action method
                $controller = new \App\Controller\Request;
                if (isset($uri_parts[1]) && $uri_parts[1] !== '') {
                    $function_name = $uri_parts[1];
                    $controller->$function_name();
                } else {
                    $controller->index();
                }
                break;

            case 'admin': // Admin panel controller
                $controller = new \Humblee\Controller\Admin;
                if (isset($uri_parts[1]) && $uri_parts[1] !== '') {
                    $function_name = $uri_parts[1];
                    $controller->$function_name();
                } else {
                    $controller->index();
                }
                break;

            case 'core-request': // Core CMS AJAX requests — second URI segment is the action method
                $controller = new \Humblee\Controller\Request;
                if (isset($uri_parts[1]) && $uri_parts[1] !== '') {
                    $function_name = $uri_parts[1];
                    $controller->$function_name();
                } else {
                    $controller->index();
                }
                break;

            case 'user': // User auth actions: login, register, profile, password reset
                $controller = new \Humblee\Controller\User;
                if (isset($uri_parts[1]) && $uri_parts[1] !== '') {
                    $function_name = $uri_parts[1];
                    $controller->$function_name();
                } else {
                    $controller->index();
                }
                break;

            case 'media': // Serve files from /storage with auth and encryption support
                $controller = new \Humblee\Controller\Media;
                $controller->index();
                break;

            default: // All standard site pages — resolves URL to a page record in the DB
                $controller = new \Humblee\Controller\Template;
                $controller->index();
        }
    }
}
