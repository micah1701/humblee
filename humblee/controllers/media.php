<?php

/**
 * Read files out of the /storage folder and send to browser
 *
 * URI to this file should look like /media/INT/filename.ext
 * where N = the ID is the row ID in the media table and
 * where filename.ext is the arbitrary file name associated with the file
 */
class Core_Controller_Media {
    
	function __construct()
	{
		global $_uri_parts;
		$this->_uri_parts = $_uri_parts;
		if(isset($_uri_parts[1]) && is_numeric($_uri_parts[1]))
		{
		    $this->file = ORM::for_table(_table_media)->find_one($_uri_parts[1]);
		}
		else
		{
		    $this->file = false;
		}
		
		if(!$this->file)
        {
            header('HTTP/1.1 404 Not Found'); 
            $this->content = array();
			$this->template_view =  Core::view( _app_server_path."application/views/404.php",get_object_vars($this));
			echo Core::view( _app_server_path .'application/views/templates/template.php',get_object_vars($this) );
			exit();
        }
        
        if($this->file->require_role != 0 && !Core::auth($this->file->require_role))
        {
            header('HTTP/1.1 403 Forbidden');
			exit( "<h1>403 Forbidden</h1>You do not have permission to view this file");
        }
        
        $this->filelocation = _app_server_path.'storage/'.$this->file->filepath;
        
        if(!file_exists($this->filelocation))
        {
            header('HTTP/1.1 500 Internal Server Error');
			exit( "<h1>500 Internal Server Error</h1>The file system could not find the requested resource");
        }
	}
	
	private function setHeaders($force_download=false)
	{
	    $cachControl = ($this->file->require_role != 0) ? 'private' : 'public';
	    
	    header('Content-Type: ' . $this->file->type);
	    header('Cache-Control: '. $cachControl);
	    header('Content-Length: ' . filesize($this->filelocation));
	    
	    if($force_download)
	    {
	        header('Content-Disposition: attachment; filename='. $this->file->name);
	    }
	    
	}
    
    public function index()
    {
  
        //if file is encrypted, decrypt now
        if($this->file->encrypted == 1)
        {
            //read the file to a string
            $encrypted_content = file_get_contents($this->filelocation);
            
            // if the raw file isn't found or can't be read
            if($encrypted_content === false)
            {
                header('HTTP/1.1 500 Internal Server Error');
    			exit( "<h1>500 Internal Server Error</h1>The file system could not read the requested resource");
            }
            
            $this->setHeaders();
            $crypto = new Core_Model_Crypto;
            echo $crypto->decrypt($encrypted_content,$this->file->crypto_nonce);
        }
        else
        {
            $this->setHeaders();
            readfile(_app_server_path.'storage/'.$this->filelocation);
        }
    }
    
}