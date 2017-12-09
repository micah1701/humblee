<?php 
/**
 * Humblee - A humble PHP Framework and CMS
 * 
 * @package Humblee
 * @author	Micah J. Murray <micah@sixeightinteractive.com>
 * @link    https://humblee.io
 */

session_start();
define('_humblee_start_time', microtime(true)); // for tracking execution time
define('_app_server_path', realpath(__DIR__ . '/..') ."/"); // eg: "/home/ubuntu/workspace/myapp/" (note: the '/..' drops the "public" folder off the end )
define('_app_path', str_replace(rtrim($_SERVER['DOCUMENT_ROOT'],"/"), '', _app_server_path)); // eg: "/myapp/" or just "/" if in root); 

require_once(_app_server_path .'humblee/init.php');