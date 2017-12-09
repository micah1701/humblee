<?php 

/**
 * Set which configuration file to use
 * 
 * this could be done progromatically like:
 * $environment = ($_SERVER['HTTP_HOST'] == "my-domain.com") ? 'env_production.php' : 'env_dev.php';
 *
 */
$environment  = 'env_production.php';

$_ENV['config'] = require_once $environment;

// encryption key (used for simple encryption)
define("ENCRYPT_KEY", md5("HowManyProgrammersDoesItTakeToChangeALightbulb?None,thatsAHardwareIssue") ); // once set, NEVER EVER CHANGE THIS 

// all $_SESSION info used by the framework is in a sub arrayof $_SESSION
define("session_key","humblee"); // example usage: $_SESSION[session_key][user_id];

// tables used in this application
define("_table_pages","humblee_pages");
define("_table_content","humblee_content");
define("_table_content_types","humblee_content_types");
define("_table_templates","humblee_templates");
define("_table_users","humblee_users");
define("_table_roles","humblee_roles");
define("_table_user_roles","humblee_user_roles");
define("_table_validation","humblee_validation");
define("_table_accesslog","humblee_accesslog");

// encryption variables
define("ENCRYPT_CYPHER", MCRYPT_RIJNDAEL_256);
define("ENCRYPT_MODE",   MCRYPT_MODE_CBC);
define('ENCRYPT_EOT','___EOT'); // an "end of transfer" delimiter to append to the data before encrypting (a fix for .docx and .xlsx files)