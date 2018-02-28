<?php

// Show or suppress errors
ini_set('display_errors',1);
error_reporting(E_ALL);

// Include required controllers
require_once _app_server_path.'humblee/configuration/config.php';
require_once _app_server_path.'humblee/vendor/autoload.php'; // to load composer files
require_once _app_server_path.'humblee/controllers/core.php';

// Set default timezone
date_default_timezone_set($_ENV['config']['timezone']);

// Auto load class files when called with the Core::auto_load($class) function
spl_autoload_register(array('Core','auto_load'));

// Database connection data (used by idiorm.php class)
use \j4mie\idiorm; // include the idiorm ORM class
ORM::configure('mysql:host='. $_ENV['config']['db_host'] .';dbname=' .$_ENV['config']['db_name']);
ORM::configure('username', $_ENV['config']['db_username']);
ORM::configure('password', $_ENV['config']['db_password']);

//if using Twilio, include the class
use Twilio\Rest\Client;

//if using Markdown, include the class
use Michelf\Markdown;

/**
 * Route to a specified controller based on URI
 * if the uri matches a pre-defined route use its corresponding controller
 * otherwise, include the default controller
 */

$_uri_parts = Core::getURIparts();
$_called_controller = (count($_uri_parts) > 0) ? strtolower($_uri_parts[0]) : '';

switch ($_called_controller)
{
	case "request" : // controller for processing custom "AJAX" requests.  Second parameter of URI is "action" function
    	$controller = new Controller_Request;

		if( isset($_uri_parts[1]) && $_uri_parts[1] != "" )
		{
			$function_name = $_uri_parts[1];
			$controller->$function_name();
		}
		else
		{
			$controller->index();
		}

	break;

    case "admin" : // controller for admin specific functions for outside the site's template

		$controller = new Core_Controller_Admin;
		if( isset($_uri_parts[1]) && $_uri_parts[1] != "" )
		{
			$function_name = $_uri_parts[1];
			$controller->$function_name();
		}
		else
		{
			$controller->index();
		}

	break;

	case "core-request" : // controller for processing core CMS "AJAX" requests.  Second parameter of URI is "action" function

		$controller = new Core_Controller_Request;
		if( isset($_uri_parts[1]) && $_uri_parts[1] != "" )
		{
			$function_name = $_uri_parts[1];
			$controller->$function_name();
		}
		else
		{
			$controller->index();
		}

	break;

	case "user" : // controller "user" actions, like loggin in, registering for acess, and updating profile

		$controller = new Core_Controller_User;
		if( isset($_uri_parts[1]) && $_uri_parts[1] != "" )
		{
			$function_name = $_uri_parts[1];
			$controller->$function_name();
		}
		else
		{
			$controller->index();
		}

	break;

	case "media" : // controller for reading files out of /storage folder

		$controller = new Core_Controller_Media;
		$controller->index();
	break;

	default : // everything should run through the template controller unless a custom controller is specified above

		$controller = new Core_Controller_Template;
		$controller->index();
}