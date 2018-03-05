<?php
define('include_only','scripts checking for this definition should die() if not set');

/**
 * Set which configuration file to use
 *
 * this could be done progromatically like:
 * $environment = ($_SERVER['HTTP_HOST'] == "my-domain.com") ? 'env_production.php' : 'env_dev.php';
 *
 */
$environment  = 'env_dev.php';

// load the environment set above
$_ENV['config'] = require_once $environment;

// all $_SESSION info used by the framework is in a sub array of $_SESSION
// default value is "humblee", eg $_SESSION['humblee']['my_variable'];
define("session_key","humblee"); // example usage: echo $_SESSION[session_key]['user_id'];

// tables used in this application
define("_table_pages","humblee_pages");
define("_table_content","humblee_content");
define("_table_content_p13n","humblee_content_p13n");
define("_table_content_types","humblee_content_types");
define("_table_templates","humblee_templates");
define("_table_users","humblee_users");
define("_table_roles","humblee_roles");
define("_table_user_roles","humblee_user_roles");
define("_table_validation","humblee_validation");
define("_table_accesslog","humblee_accesslog");
define("_table_media","humblee_media");
define("_table_media_folders","humblee_media_folders");