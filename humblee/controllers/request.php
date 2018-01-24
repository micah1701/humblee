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
        $li_contents = '<div class=\"pages_menu_item\" data=\"$item->thisID\"><span $drawClass title=\"$newSlug\">$item->label</span></div>'; // raw php code to be eval'd in function
    	
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
			$this->json(array=>"error"=>"Invalid or missing page ID");
		}
		
        $page = ORM::for_table(_table_pages)->find_one($_POST['page_id']);
        if(!$page)
        {
        	$this->json(array=>"error"=>"Page data not found");
        }
		
		$active = ($page->active == 0) ? false : true;
		$searchable = ($page->searchable == 0) ? false : true;
		$display_in_sitemap = ($page->display_in_sitemap == 0) ? false : true;
		
        $checkTemplate = ORM::for_table( _table_templates)->select('available')->find_one($page->template_id);
        
		$array = array(	"success" => true,
						"label" => $page->label, 
						"slug" => $page->slug,
						"template_id"=>$page->template_id,"required_role"=>$page->required_role,
						"template_disabled" => ($checkTemplate->available == 0 && !Core::auth(array('designer','developer')) ? 1 : 0,
						"active"=>$active, "display_in_sitemap"=>$display_in_sitemap,
						"searchable"=>$searchable
					);
					
		$this->json( $array );		
	}

}