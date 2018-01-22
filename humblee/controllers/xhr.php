<?php

/**
 * this class is not meant to extend the default controller. It is only used to return "AJAX" requests
 *
 */
 
class Core_Controller_Xhr {
	
	public function __construct()
    {
        header("Expires: Sat, 07 Apr 1979 05:00:00 GMT");
        header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
        header("Cache-Control: no-store, no-cache, must-revalidate");
        header("Cache-Control: post-check=0, pre-check=0", false);
        header("Pragma: no-cache");
	}
	
	/**
     * call this from any function to ensure the user has the necessary role required to access the given page
     * $role can be a string, eg "login" or an array of possilbe roles, like array("admin","customer")
     */
    public function require_role($role)
    {
        if(!Core::auth($role) && !Core::auth('developer') )
        {
         	header('HTTP/1.1 403 Forbidden');  
            exit("<h1>403 Forbidden</h1><h3>You do not have access to view this page</h3><p>If you believe this is an error, please see your site administrator.</p>");
        }
    }
    
    /**
     * if the request uses HMAC, call this function to check the valeus
     * 
     */
    public function require_hmac()
    {
        if(!isset($_POST['hmac_token']) || !isset($_POST['hmac_key']))
        {
            header('HTTP/1.1 401 Unauthorized');  
            exit("<h1>401 Unauthorized</h1><h3>Missing Machine Key</h3><p>If you believe this is an error, please see your site administrator.</p>");
        }

        $crypto = new Core_Model_Crypto;
		if(!$crypto->check_hmac_pair($_POST['hmac_token'], $_POST['hmac_key']))
		{
			header('HTTP/1.1 401 Unauthorized');  
            exit("<h1>401 Unauthorized</h1><h3>Invalid Machine Authentication Key</h3>");
		}
        
        return true;
    }
	
   /**
    * if outputting json_encode() data, call $this->json() to set this header
    * optionally pass a string or array to return
    */
    public function json($package=false)
    {
        header('Content-Type: application/json');
        if(is_array($package))
        {
            echo json_encode($package);
            exit();
        }
    }

	public function index()
    {	
		exit("Error!<br />\n No Request action sent");
	}
	
}