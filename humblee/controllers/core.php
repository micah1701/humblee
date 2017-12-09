<?php

class Core {
	
	/**
	 * This function will (attempt to) automatically include the path to a given class
	 * 
	 * For this to work, the classes have to follow a specific naming convension:
	 * 
	 * The files must be placed in the appropriate "controllers" or "models" folders within the "application" or "core"
	 * and must be called with the name 'Model_Classname'  or 'Controller_Classname'
	 * furthermore, since the core classes of the framework are stored outside of the application folder, they must be prepended 
	 * e.g.  'Core_Model_Classname' or 'Core_Controller_Classname'
	 */
	public static function auto_load($class)
	{
		$class_parts = explode("_",$class);
		
		$parent_folder = ( strtolower($class_parts[0]) === "core" ) ? "humblee/" : "application/";
		
		if( strtolower($class_parts[0]) === "controller" ||  count($class_parts) === 3 && strtolower($class_parts[1]) === "controller")
		{
			$class_folder = "controllers/";
		}
		elseif( strtolower($class_parts[0]) ==="model" || count($class_parts) === 3 && strtolower($class_parts[1]) ==="model")
		{
			$class_folder = "models/";
		}
		elseif( strtolower($class_parts[0]) == "draw") // the "Draw" controller breaks convention to make is static
		{
			$parent_folder = "humblee/";
			$class_folder = "controllers/";
		}
		else
		{
			return false;
		}	
		
		$filename = strtolower(array_pop($class_parts));
		$path = _app_server_path. $parent_folder . $class_folder . $filename .'.php';
		
		if(!is_file( $path))
		{
			return false;
		}
		require_once $path;
	}
	
	/**
 	 * Return String of URL Path (everything after domain.tld but not including additional params) 
	 *
	 */
	public static function getURI()
    {
		if(isset($_SERVER['PATH_INFO']))
        { 
			$_path_info = $_SERVER['PATH_INFO']; 
		
		}
        elseif(isset($_SERVER['ORIG_PATH_INFO']))
        {
			$_path_info = $_SERVER['ORIG_PATH_INFO'];
 		}
        else
        {
			$_path_info = $_SERVER['REQUEST_URI'];
		}
		
		if (substr($_path_info, 0, strlen(_app_path)) == _app_path) {
  			$_path_info = substr($_path_info, strlen(_app_path));
		} 

        if(substr($_path_info,0,9) == "index.php")
        {
            $_path_info = ltrim($_path_info, "index.php");   // index.php might be there, drop that too
        }
   		
		$uri = (!isset($_path_info) || $_path_info == "" || $_path_info == "public") ? "" : ltrim($_path_info,"/");
		return $uri;
		
        // depending on server configuration, you might need to do this instead		
        // $_path_info = preg_split("/\?|\&/",$_path_info); // check for ? or & in url  
        // return (!isset($_path_info[0]) || $_path_info[0] == "") ? "" : ltrim($_path_info[0],"/");
    }
    
    /**
 	 * Return ARRAY of URI parts
 	 * eg www.mydomain.com/dir1/dir2/dir3 returns array(0=>'dir1',1=>'dir2',2=>'dir3');
	 *
	 */
	public static function getURIparts()
	{
		$_uri_parts = explode("/",ltrim(Core::getURI(),"/"));
		if($_uri_parts[0] == "public")
		{
			array_shift($_uri_parts);
		}
		return $_uri_parts;
	}

	/**
	 * return the contents of a page
	 *
	 * $path (str) path to file
	 * $view_variables (optional array) variables to be utilized by the included file array($var="value", $var2="value2") 
	 *
	 */
	public static function view($path,$view_variables=false)
    {
		if (is_file($path))
        {
        	ob_start();
        	
			if($view_variables)
            {
				extract($view_variables);
			}
			
			include $path;
    	   	return ob_get_clean();
    	}
    	return false;	
	}
	
	/**
	 * helper function used by Core::auth() to cache roles in user's SESSION
	 */
	private static function cacheUserRoles()
	{
		$roles = ORM::for_table( _table_user_roles)
					->distinct()->select('role_id')
					->select('name')
					->join( _table_roles, array( _table_user_roles.'.role_id','=', _table_roles.'.id'))
					->where('user_id',$_SESSION[session_key]['user_id'])
					->find_many();
		
		if(!$roles)
		{
			$_SESSION[session_key]['has_roles'] = null;
			unset($_SESSION[session_key]['has_roles']);
			return false;
		}
					
		foreach($roles as $role)
		{
			$_SESSION[session_key]['has_roles'][$role->role_id] = $role->name;
		}
		
		return $_SESSION[session_key]['has_roles'];
	}
	
	/**
	 * Check if user has given role
	 *
	 * $required_roles	MIXED
	 *						INT 	looks for role by ID
	 *						STRING 	looks for role by name
	 *					or an ARRAY of INT/STRING to match "any" roles
	 *
	 * can be an array as well
	 */
	public static function auth($required_roles)
    {
		
		//make sure the user is logged in; they should have a user_id set in their session
		if(!isset($_SESSION[session_key]['user_id']))
        { 
            return false; 
        }
        
        //make sure a require role (or roles) were sent. If just one value was sent, make it an array
        if(!is_array($required_roles))
        {
        	$required_roles = array($required_roles);
        }
        
        //check if user's roles have been cached to their SESSION. if not, do so now
        if(!isset($_SESSION[session_key]['has_roles']) || !$_SESSION[session_key]['has_roles'] || !is_array($_SESSION[session_key]['has_roles']))
		{
			$has_roles = Core::cacheUserRoles();
			
			if(!$has_roles)
			{
				return false;
			}
		}
        
        //go through all the requested roles to check. if any are true, return true
        foreach($required_roles as $required_role)
        {
			if((is_numeric($required_role) && array_key_exists($required_role,$_SESSION[session_key]['has_roles'])) || in_array($required_role,$_SESSION[session_key]['has_roles']) )
			{
				return true;
			}
        }

		//if no roles matched, return false
        return false;
 	}
	 
	/**
	 * Forward to another page on this site using Output Buffering
	 *
	 */
	public static function forward($uri='')
    {		
		ob_start();
		header("Location: ".rtrim(_app_path,"/") . "/" . ltrim($uri,"/"));
		ob_flush();	
		exit();
	}
	
	/**
	 * Return a token unique to the current session
	 * Can be included as hidden form field and checked upon POST to make sure request is coming from known user
	 *
	 * DO NOT use this value in the browser if using HMAC functionality below
	 */
	public static function get_csrf_token()
	{
		if(isset($_SESSION[session_key]['csrf_token']) && isset($_SESSION[session_key]['csrf_token']) != "")
		{
			return $_SESSION[session_key]['csrf_token'];
		}
		else
		{
			$token = md5(uniqid(rand(), true). time() . session_id() );
			$_SESSION[session_key]['csrf_token'] = $token;
			return $token;
		}
	}

	/**
	 * Generate a random string and hash it to this user's session for machine authentication & CSRF protection
	 * 
	 */
	 public static function get_hmac_pair()
	 {
 		$random_string = md5(uniqid(rand(), true). time() . session_id());
	 	return array(
	 		'message' => $random_string,
	 		'hmac' => base64_encode(hash_hmac('sha256', $random_string, Core::get_csrf_token() ))
 		);
	 }
	 
	/**
	* Check HMAC string and hash
	*/
	public static function check_hmac_pair($string,$hash)
	{
	  	return ($hash == base64_encode(hash_hmac('sha256', $string, Core::get_csrf_token() )) ) ? true : false;
	}

	
}