<?php session_start();
exit("hello! welcome to humblee");

define('MVC_ONLY',true); //scripts in the "controllers" and "models" directory should require this value be set before running

define('framework_start_time', microtime(true)); // for tracking exc time

ini_set('display_errors',1);
error_reporting(E_ALL);

date_default_timezone_set('America/New_York');

/**
 * set the server directory that this application is stored in.
 * if applicaiton is in the root directory, set to "/"
 */
$_app_server_path = rtrim(str_replace('\\', '/', dirname(__FILE__)),"/")."/"; // eg: "/home/ubuntu/workspace/mjm-web/"
$web_app_path = str_replace(rtrim($_SERVER['DOCUMENT_ROOT'],"/"),'',$_app_server_path); // eg: "/mjm-web/" or just "/" if in root
define('_app_path',$web_app_path); 
define('_app_server_path',$_app_server_path);

/**
 * include required models and controllers
 */
require $_app_server_path.'core/config.php'; //define table name vars, database connection and encryption info
require $_app_server_path.'core/libs/idiorm.php';  // idiorm class for database management
// require $_app_server_path.'core/libs/paris.php'; // paris relational mapping add-on to idiorm (optional)
require $_app_server_path.'core/core.php';


/**
 * auto load class files when called with the Core::auto_load($class) function
 */
spl_autoload_register(array('Core','auto_load')); 

/**
 * database connection data (used by idiorm.php class) 
 * values are stored in config.php
 */
ORM::configure('mysql:host='. _db_host .';dbname=' ._db_name);
ORM::configure('username', _db_username);
ORM::configure('password', _db_password);

/**
 * route to a specified controller based on URI
 * if the uri matches a pre-defined route use its coorisponding controller
 * otherwise, include the default controller
*/

$_uri_parts = explode("/",ltrim(Core::getURI(),"/")); 
$_called_controller = strtolower($_uri_parts[0]);

switch ($_called_controller) {
	
	case "request" : // controller for processing custom "AJAX" requests.  Second parameter of URI is "action" function
    	$controller = new Controller_Request;
		
		if( isset($_uri_parts[1]) && $_uri_parts[1] != "" ){	
			$function_name = $_uri_parts[1];
			$controller->$function_name();
		}else{
			$controller->index();
		}
	break;
    
    case "admin" : // controller for admin specific functions for outside the site's template
		
		$controller = new Core_Controller_Admin;
		if( isset($_uri_parts[1]) && $_uri_parts[1] != "" ){	
			$function_name = $_uri_parts[1];
			$controller->$function_name();
		}else{
			$controller->index();
		}	
	break;
	
	case "core-request" : // controller for processing core CMS "AJAX" requests.  Second parameter of URI is "action" function

		$controller = new Core_Controller_Request;		
		if( isset($_uri_parts[1]) && $_uri_parts[1] != "" ){	
			$function_name = $_uri_parts[1];
			$controller->$function_name();
		}else{
			$controller->index();
		}
	break;
	
	case "user" : // controller "user" actions, like loggin in, registering for acess, and updating profile

		$controller = new Core_Controller_User;
		if( isset($_uri_parts[1]) && $_uri_parts[1] != "" ){	
			$function_name = $_uri_parts[1];
			$controller->$function_name();
		}else{
			$controller->index();
		}
	break;
	
	default : // everything should run through the template controller unless a custom controller is specified above

		$controller = new Core_Controller_Template;
		$controller->index();
}