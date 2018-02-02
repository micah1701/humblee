<?php

/**
 * this class is not meant to extend the default controller. It is only used to return "AJAX" requests
 *
 * it extends the core controller "xhr" which sends no-cache headers and provides some methods for authorization and returning JSON
 */
 
class Core_Controller_Request extends Core_Controller_Xhr {
	
    /**
	 * this function is called when a user is already logged in.
	 * it is used to generate and save a token that is sent to their cellphone
	 * then entered to verify their phone number
	 */
	public function verify_sms_send()
	{
		if(!$_ENV['config']['TWILIO_Enabled'])
		{
		    $this->json(array("error"=>"Site not configured to use SMS")); 
	    }
	    
		$this->require_role('login'); //user must be loged in to verify their cellphone number
		
		if(!isset($_POST['cellphone']) || !is_numeric($_POST['cellphone']) || strlen($_POST['cellphone']) != 10) 
		{
			$this->json(array("error"=>"Invalid or missing 10 digit cellphone number"));
		}
		$userID = $_SESSION[session_key]['user_id'];
		
		$token = rand(10000,99999);		
		
		//look for exisiting entry in validation table for this user & number
		$previousValidation = ORM::for_table(_table_validation)
								->where('new_value',$_POST['cellphone'])
								->where('user_id',$userID)
								->where('type','sms')
								->find_one();
		if(!$previousValidation)
		{
			$validation = ORM::for_table(_table_validation)->create();
			$validation->user_id = $_SESSION[session_key]['user_id'];
			$validation->type = "sms";
		}
		else
		{
			//use the previous data row from the last time user used this feature

			if(strtotime($previousValidation->token_created) >= strtotime("-1 minutes"))
			{
				exit("Error: can not send more than 1 SMS per minute");
			}
			elseif(strtotime($previousValidation->token_created) >= strtotime("-10 minutes"))
			{
				$token = $previousValidation->token; // use the old token if it was just created within the last 10 minutes
			}
			
			$validation = $previousValidation;
			$validation->token_accepted = "0000-00-00 00:00:00"; // clear any old "accepted" date
		}

		//send SMS 
		$tools = new Core_Model_Tools;
		$txtmsg = $_ENV['config']['domain'] ." log in code: ". $token; 
		$sms_status = $tools->sendSMS($_POST['cellphone'],$txtmsg);
		
		if($sms_status['success'])
		{
			$validation->new_value = $_POST['cellphone'];
			$validation->token = $token;
			$validation->token_created = date("Y-m-d H:i:s");
			$validation->message_id = $sms_status['message_id'];
			$validation->save();	
			echo "success";
		}
		else
		{
			echo "error";
		}
	}

	/**
	 * send an SMS token for the purposes of logging in
	 */
	public function sms_login_code()
	{
		if(!$_ENV['config']['TWILIO_Enabled'])
		{ 
		    $this->json(array("error"=>"Site not configured to use SMS")); 
		}
		//the "sms_login_email" must have been set in the intial login (w/ username & password) before requesting an SMS code
		if(!isset($_SESSION[session_key]['sms_login_email']) || $_SESSION[session_key]['sms_login_email'] == "")
		{ 
	        $this->json(array("error"=>"Invalid Request")); 
		}
		
		//check if given "username" is an e-mail address or a username
		$username_column = (filter_var($_SESSION[session_key]['sms_login_email'], FILTER_VALIDATE_EMAIL)) ? 'email' : 'username';
		
		//look up user
		$user = ORM::for_table( _table_users)
					->where($username_column,$_SESSION[session_key]['sms_login_email'])
					->where('active',1)
					->find_one();
		if(!$user){ exit("Invalid User Account"); }
		if($user->cellphone == "" || !is_numeric($user->cellphone)) { exit("No phone number associated with this account."); }
		if($user->cellphone_validated != 1) { exit("Phone number has not yet been validated."); }
		
		if(	isset($_SESSION[session_key]['login_token_expires']) && 
			time() < strtotime("-570 seconds",$_SESSION[session_key]['login_token_expires']))
		{
			// login_token_expires was set to 10 minutes in the future from when the message was sent.
			//if the current time is less than 9-1/2 minutes before that time, make the user wait
			exit("Please wait at least 30 seconds before re-sending code");
		}
		
		//generate token
		if(	isset($_SESSION[session_key]['login_token']) &&
			isset($_SESSION[session_key]['login_token_expires']) && 
			time() < $_SESSION[session_key]['login_token_expires'])
		{
			$token = $_SESSION[session_key]['login_token']; // use an exisiting token if there is a recent one.
		}
		else
		{
			$start_point = rand(0,10);
			$token = strtoupper(substr(md5(rand(10000,999999)),$start_point,5));
			
			//define the session if it doesnt' already exist.
			if(!isset($_SESSION[session_key]))
			{
				$_SESSION[session_key] = array();
			}
			
			$_SESSION[session_key]['login_token'] = $token;
		}
		
		$_SESSION[session_key]['login_token_expires'] = strtotime("+10 minutes");
		
		//send SMS 
		$tools = new Core_Model_Tools;
		$txtmsg = $_ENV['config']['domain'] ." log in code: ". $token; 
		$sms_status = $tools->sendSMS($user->cellphone,$txtmsg);
		echo ($sms_status['success']) ? "success" : "Message could not be sent.";
	}
	
	/**
	 * Check the user submited SMS verification code and log them in if it matches what was sent in sms_login_code()
	 */
    public function sms_login()
    {
        if(!$_ENV['config']['TWILIO_Enabled'])
		{ 
		    $this->json(array("error"=>"Site not configured to use SMS")); 
		}
		//the "sms_login_email" must have been set in the intial login (w/ username & password) before loggin w/ this second factor side channel
		if(!isset($_SESSION[session_key]['sms_login_email']) || $_SESSION[session_key]['sms_login_email'] == "")
		{ 
	        $this->json(array("error"=>"You are not authorized to make this request.")); 
		}
		
		$this->require_hmac();
		
		if(!isset($_POST['sms_token']) || strlen($_POST['sms_token']) != 5)
		{
		    $this->json(array("error"=>"Missing or malformed verification token"));
		}
		
		$users = new Core_Model_Users;
		$login = $users->logIn($_SESSION[session_key]['sms_login_email'],$_POST['sms_token'],true);
		if($login['access_granted'] === true )
		{
		    $this->json(array("success"=>true));	
		}
		else
		{
		    $this->json(array("error"=>$login['error']));
		}
    }
    
    /**
     * Get list of pages with editable content
     */
    public function loadContentMenu()
	{
		$this->require_role('content');
		$pageObj = new Core_Model_Pages;
	    $menu = $pageObj->getPages(array('active_only'=>false,'display_in_sitemap_only'=>false));
		$li_format = '<a href=\"'._app_path.'admin/edit/?page_id=$item->thisID\">$item->label</a>'; // raw php code to be eval'd in function
		echo $pageObj->drawMenu_UL($menu,array('li_format'=>$li_format,'id_label'=>'contentNav_'));
	}
	
	/**
	 * manage pages
	 * 
	 */
	// return table of pages 
	public function loadPagesTable()
    {
   		$this->require_role('pages');
        $li_contents = '<div class=\"pages_menu_item\" data=\"$item->thisID\"><a $drawClass title=\"$newSlug\">$item->label</a></div>'; // raw php code to be eval'd in function
    	
    	$pages = new Core_Model_Pages;
		
		$all_pages = $pages->getPages(array('active_only'=>false,'display_in_sitemap_only'=>false));
		echo $pages->drawMenu_UL($all_pages,array('li_format'=>$li_contents,'id_label'=>'pageID_'));
	}
	
	// return properties for a given page by ID
	public function getPageProperties()
    {
		$this->require_role('pages');
		if(!isset($_POST['page_id']) || !is_numeric($_POST['page_id']))
		{
			$this->json(array("error"=>"Invalid or missing page ID"));
		}
		
        $page = ORM::for_table(_table_pages)->find_one($_POST['page_id']);
        if(!$page)
        {
        	$this->json(array("error"=>"Page data not found"));
        }
		
		$active = ($page->active == 0) ? false : true;
		$searchable = ($page->searchable == 0) ? false : true;
		$display_in_sitemap = ($page->display_in_sitemap == 0) ? false : true;
		
        $checkTemplate = ORM::for_table( _table_templates)->select('available')->find_one($page->template_id);
        
		$array = array(	"success" => true,
						"label" => $page->label, 
						"slug" => $page->slug,
						"template_id"=>$page->template_id,
						"required_role"=>$page->required_role,
						"template_disabled" => ($checkTemplate->available == 0 && !Core::auth(array('designer','developer')) ) ? 1 : 0,
						"active"=>$active,
						"display_in_sitemap"=>$display_in_sitemap,
						"searchable"=>$searchable
					);
					
		$this->json( $array );		
	}
	
	// save properties for a given page
	public function setPageProperties()
	{
		$this->require_role('pages');
		$pages = new Core_Model_Pages;
		$page = $pages->add_or_update("update",$_POST);
		if(is_numeric($page))
		{
			$this->json(array('success'=>true,'page_id'=>$page));			
		}

		$this->json(array('error'=>$page));
	}
	
	public function add_page()
    {
		$this->require_role('pages');
        $pages = new Core_Model_Pages;
		$newPage = $pages->add_or_update("add",$_POST);
		if(is_numeric($newPage))
		{
			$this->json(array('success'=>true,'page_id'=>$newPage));			
		}
		
		$this->json(array('error'=>$newPage));
	}

	//delete a page and all of its contents
	public function delete_page()
    {
		$this->require_role('pages');
        $pages = new Core_Model_Pages;
        $deletePage = $pages->add_or_update("delete",$_POST);
        if($deletePage == "success")
        {
        	$contents = ORM::for_table( _table_content)->where('page_id',$_POST['page_id'])->find_many();
			foreach($contents as $content)
			{
				$content->delete();
			}
			$this->json(array('success'=>true));
        }
        
        $this->json(array('error'=>$deletePage));
    }
    
    public function order_pages()
    {
    	$this->require_role('pages');
        if(!isset($_POST['list_order']) || $_POST['list_order'] == "")
        {
        	exit("Missing list order post data");
        }
        
        //convert string to an array
		$list_string = urldecode($_POST['list_order']);
		$list_string = preg_replace('/&/',';',$list_string).";";
		$list_string = preg_replace('/null/','0',$list_string).";";
		$list_string = preg_replace('/pageID/','$page_id',$list_string);
		eval($list_string);
			
		$current_parent = 0;
		$last_level = 0;
		foreach($page_id as $id => $level)
		{
			if($level > $last_level) 
			{ 
				// we've started a new sub section
				$parent_level[$last_level] = $current_parent; // save previous level's parent id for use later
				$current_parent = $last_id;
			}
			if($level < $last_level)
			{ 
				// we've gone back a section to the previous parent
				$current_parent = $parent_level[$level];
			}
			if($level == 0)
			{
				$current_parent = 0;
			}
		
			$order_pointer[$level] = (isset($order_pointer[$level])) ? $order_pointer[$level] + 1 : 0; // this page's display order within it's level				
		
			$orderpage = ORM::for_table(_table_pages)->find_one($id);
	 	 	$orderpage->parent_id = $current_parent;
			$orderpage->display_order = $order_pointer[$current_parent];
	 	 	$orderpage->save();
			
			$last_id = $id;
			$last_level = $level;	
		}
		
		$this->json(array("success"=>true));
    }
    
    /**
     * Content Management
     */
     
	//return ARRAY of most recent content for a given block on a given page
	//includes the username of the user who updated the content
    public function latestRevision()
    {
    	$this->require_role('content');
    	if(!isset($_POST['content_type']) || !is_numeric($_POST['content_type']) || !isset($_POST['page_id']) || !is_numeric($_POST['page_id']))
    	{
    		$this->json(array("error"=>"Missing required parameters"));
    	}
    	$content = ORM::for_table( _table_content)
    					->select(_table_content.'.*')
    					->select(_table_users.'.name')
						->join( _table_users, array( _table_content.'.updated_by', '=', _table_users.'.id'))
                        ->where('page_id',$_POST['page_id'])
                        ->where('type_id',$_POST['content_type'])
                        ->order_by_asc('revision_date')
                        ->limit(1)
                        ->find_array();
		if(!$content)
		{
			$this->json(array("error"=>"could not confirm previously saved content"));
		}
		
		$this->json(array("success"=>true,"content"=>$content[0]));
    }

	/**
	 * media file manager
	 */
	public function listMediaFolders()
	{
		$this->require_role('content');
		$media = new Core_Model_Media;
		$this->json($media->listFolders());
	}
	
	public function listMediaFilesByFolder()
	{
		$this->require_role('content');
		if(!isset($_GET['folder']) || !is_numeric($_GET['folder']))
		{
			$result['error'] = "missing folder ID";
		}
		$media = new Core_Model_Media;
		$response = array("success"=>true,"files"=>$media->listFilesByFolder($_GET['folder']));
		$this->json($response);
	}
	
	//update the name of a file or folder
	public function updateMediaName()
	{
		$this->require_role('content');
		if(!isset($_POST['type']) || !isset($_POST['record']) || !is_numeric($_POST['record']))
		{
			$this->json(array("error"=>"invalid request"));
		}
		if($_POST['type'] == "folder_name")
		{
			$record = ORM::for_table(_table_media_folders)->where('id',$_POST['record'])->find_one();
		}
		if($_POST['type'] == "file_name")
		{
			$record = ORM::for_table(_table_media)->where('id',$_POST['record'])->find_one();
		}
		
		if(!$record)
		{
			$this->json(array("error"=>"record not found"));
		}
		
		$record->name = $_POST['value'];
		$record->save();
		$this->json(array("success"=>true));
		
	}
	
	//change the required_role for a given media file
	public function updateMediaRole()
	{
		$this->require_role('content');
		if(!isset($_POST['file_id']) || !is_numeric($_POST['file_id']) || !isset($_POST['required_role']) || !is_numeric($_POST['required_role']))
		{
			exit("Invalid or missing file ID or role type");
		}
		$file = ORM::for_table(_table_media)->find_one($_POST['file_id']);
		if(!$file)
		{
			exit("File record not found");
		}
		$file->required_role = $_POST['required_role'];
		$file->save();
		$this->json(array("success"=>true));
	}
	
	public function deleteMediaFile()
	{
		$this->require_role('content');
		if(!isset($_POST['file_id']) || !is_numeric($_POST['file_id']))
		{
			exit("Invalid or missing file ID");
		}
		$file = ORM::for_table(_table_media)->find_one($_POST['file_id']);
		if(!$file)
		{
			exit("File record not found");
		}
		if(!unlink(_app_server_path."storage/".$file->filepath))
		{
			exit("Could not unlink file");
		}
		$file->delete();
		
		$this->json(array("success"=>true));
	}

	public function createMediaFolder()
	{
		//set name name and parent
	}
	
	public function updateMediaFolder()
	{
		//rename or move folder (maybe combine with create function )
	}
	
	public function deleteMediaFolder()
	{
		//remove all files in a folder
		//delete the folder record in database
	}
	
	//helper function to re-image array of uploaded files
	//see http://php.net/manual/en/features.file-upload.multiple.php#53240
	private function reArrayFiles(&$file_post) {
	    $file_ary = array();
	    $file_count = count($file_post['name']);
	    $file_keys = array_keys($file_post);
	
	    for ($i=0; $i<$file_count; $i++) {
	        foreach ($file_keys as $key) {
	            $file_ary[$i][$key] = $file_post[$key][$i];
	        }
	    }
	
	    return $file_ary;
	}
	
	//process and save uploaded files
	public function uploadMediaFiles()
	{
		$this->require_role('content');
		$errors = array();
		
		if(!$_FILES['uploaderFiles'])
		{
			$errors[] = "No Files Uploaded";
		}
		else
		{
			$files = $this->reArrayFiles($_FILES['uploaderFiles']);
		}
		
		$totalFiles = count($files);
		$savedFiles = 0;
		
		foreach($files as $file)
		{
			$cleanFilename = filter_var($file['name'],FILTER_SANITIZE_URL);
			$cleanFilename = str_replace(" ","-",$cleanFilename);
			
			$fileRecord = ORM::for_table(_table_media)->create();
			$fileRecord->folder = (isset($_POST['folder_id']) && is_numeric($_POST['folder_id'])) ? $_POST['folder_id'] : 0;
			$fileRecord->name = $cleanFilename;
			$fileRecord->size = $file['size'];
			$fileRecord->type = $file['type'];
			$fileRecord->upload_by = $_SESSION[session_key]['user_id'];
			$fileRecord->upload_date = date("Y-m-d H:i:s");
			$fileRecord->save();
			
			$id = $fileRecord->id();
			$nameParts = explode(".",$file['name']);
			$storageName = time().$id.".".strtolower(array_pop($nameParts));
			
			if($file['error'] == 0)
			{
				if(move_uploaded_file($file['tmp_name'], _app_server_path."storage/".$storageName))
				{
					$fileRecord->filepath = $storageName;
					$fileRecord->save();
					$savedFiles++;
				}
				else
				{
					$errors[] = 'could not store file '.$file['name'];
					$fileRecord->delete();
				}			
			}
			else
			{
				$errors[] = 'there was a problem with a file '.$file['name'];
				$fileRecord->delete();	
			}
		}	
		
		$success = ($savedFiles > 0) ? true : false;
		$this->json(array("success"=>$success,"errors"=>$errors,"filesReceived"=>$totalFiles,"filesSaved"=>$savedFiles));
	}
}