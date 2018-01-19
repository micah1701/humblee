<?php

class Core_Controller_User {
	
	function __construct()
	{
		global $_uri_parts;
		$this->_uri_parts = $_uri_parts;
		$this->tools = new Core_Model_Tools;
		$this->users = new Core_Model_Users;
		
		//the template these views are loaded into may be looking for the $content object but these views doen't have $content by default
		$this->content = false;

		//any form posted to this controller must pass an HMAC token and key
		if($_POST)
		{
			$crypto = new Core_Model_Crypto;
			if(!$crypto->check_hmac_pair($_POST['hmac_token'], $_POST['hmac_key']))
			{
				exit("Invalid Machine Authentication Key");
			}
		}
		
	}
	
	public function index()
	{
		if(!Core::auth(1)){ Core::forward("/user/login"); }
		
	    //	$this->template_view = Core::view( _app_server_path .'humblee/views/user/index.php',get_object_vars($this) ); 	
	    //	echo Core::view( _app_server_path .'application/views/templates/template.php',get_object_vars($this) );
		Core::forward("/user/profile"); // (if site has no index page for logged in users, just forward to profile page)	
	}
	
	public function logout()
	{
		if($this->users->logOut() )
        {
            Core::forward();	    
        }
	}
	
	public function login()
	{
        // check if user is already logged in
        if(isset($_SESSION[session_key]['user_id']) && Core::auth('login'))
        { 
            $this->pagebody = "<h1 class=\"text-has-danger\">You are already logged in</h1><p>If you were forwarded to this page unexpectedly, you most likely do not have permission to access the page you were trying to go to.</p><p>If you feel this is in error, please contact your system administrator.  For now, use your back button to return to wherever you came from.</p>";
            echo Core::view( _app_server_path .'humblee/views/admin/templates/template.php',get_object_vars($this) );
            exit();
	    }
	    
        // process log-in POST data
		if(isset($_POST['username']) || isset($_POST['smsusername'])){
		
			if(isset($_POST['cellphone_validate']) && trim($_POST['cellphone_validate']) != "")
			{
				if(trim($_POST['smsusername']) == "" || trim($_POST['cellphone_validate']) == "" )
				{
					$this->error = "Missing Credentials";
				}
				else
				{
					$username = $_POST['smsusername'];
					$password = trim($_POST['cellphone_validate']);
					$isSMS = true;
				}
			}
			else
			{
				if(trim($_POST['username']) == "" || trim($_POST['password']) == "" )
				{
					$this->error = "Missing Credentials";
				}
				else
				{
					$username = $_POST['username'];
					$password = trim($_POST['password']);
					$isSMS = false;	
				}
			}
			
			if(!isset($this->error))
			{
				$login = $this->users->logIn($username,$password,$isSMS);
				
				if($login['access_granted'] === true )
				{
					$fwd = (isset($_GET['fwd']) && preg_match('/^[\w-\/-]+$/', $_GET['fwd'])) ? $_GET['fwd'] : "user";
		    		Core::forward($fwd);	
				}
				else
				{
					$this->error = $login['error'];
				}
			}
		}
		
		$this->template_view = Core::view( _app_server_path .'humblee/views/user/login.php',get_object_vars($this) ); 	
		echo Core::view( _app_server_path .'application/views/templates/template.php',get_object_vars($this) );
	}
	
	public function register()
	{
		//check if user is already logged in
		if(isset($_SESSION[session_key]['user_id']))
		{ 
		    $fwd = (isset($_GET['fwd']) && preg_match('/^[\w-\/-]+$/', $_GET['fwd'])) ? $_GET['fwd'] : "user";
		    Core::forward($fwd);
		}
		
		if(isset($_POST['email']) && isset($_POST['password']) && isset($_POST['password_check']) ){
			
			if($_POST['password'] != $_POST['password_check']){
				$this->error[] = "Passwords do not match";
			}
			if( strlen($_POST['password']) < 2 ){
				$this->error[] = "Password must be longer";
			}
			if( !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL) || !preg_match('/@.+\./', $_POST['email'] )){
				$this->error[] = "Invalid or malformed e-mail address";
			}
			if( trim($_POST['name']) == "") {
				$this->error[] = "Please enter your full name";
			}
			if (isset($_POST['username']) && strpos($_POST['username'], '@') !== FALSE)
			{
				$this->error[] = "Username can not contain an '@' symbol";
			}
			
			if(isset($_POST['username']) && trim($_POST['username'] != ""))
			{
				$usernamecheck = ORM::for_table( _table_users)->where('username',$_POST['username'])->find_one();
				if($usernamecheck)
				{
					$this->error[] = "A user with this Username already exists";
				}
			}
			
			$emailcheck = ORM::for_table( _table_users)->where('email',$_POST['email'])->find_one();
			if( $emailcheck )
			{
				$this->error[] = "A user with this e-mail address already exists";
			}
			
			if(!isset($this->error)){
				//ok, valid entry
				$user = ORM::for_table( _table_users)->create();
				$user->name = $_POST['name'];
				$user->email = $_POST['email'];
				$user->username = (isset($_POST['username']) && trim($_POST['username']) != "") ? trim($_POST['username']) : '';
				$user->password = $this->users->stringToSaltedHash( $_POST['password']);
				$user->active = 1;
				$user->save();	
				
				/* optional - create basic user role 
				 * (this can be commented out for security. Roles can be assigned through the "Users" tool by Administrator)
				 */
				$role = ORM::for_table( _table_user_roles)->create();
				$role->role_id = 1;
				$role->user_id = $user->id;
				$role->save();
				
				/* if a role has been assigned, optionally auto-login the user */
				$this->users->logIn($_POST['email'],$_POST['password']);
				
				Core::forward("/user/");
				exit();	
				
			} //end validation check
		}// end check for $_POST data
		
		$this->template_view = Core::view( _app_server_path .'humblee/views/user/register.php',get_object_vars($this) ); 	
		echo Core::view( _app_server_path .'application/views/templates/template.php',get_object_vars($this) );
	}
	
	public function profile()
	{
		$this->user = $this->users->profile(); //get user profile data to bind to view
	
		if(isset($_POST['email']) && isset($_POST['password']) && isset($_POST['password_check']) )
		{
			if( $_POST['password'] != "" && $_POST['password'] != $_POST['password_check']){
				$this->error[] = "Passwords do not match";
			}
			if( $_POST['password'] != "" && strlen($_POST['password']) < 2 ){
				$this->error[] = "Password must be longer";
			}
			if( !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL) || !preg_match('/@.+\./', $_POST['email'] )){
				$this->error[] = "Invalid or malformed e-mail address";
			}
			if( trim($_POST['name']) == "") {
				$this->error[] = "Please enter your full name";
			}
			if (isset($_POST['username']))
			{
				if(strpos($_POST['username'], '@') !== FALSE)
				{
					$this->error[] = "Username can not contain an '@' symbol";
				}
				
				$check = ORM::for_table( _table_users)->where('username',$_POST['username'])->find_one();
				if( $check && $check->id != $this->user->id ){
					$this->error[] = "This username already exists";
				}
			}
			
			$check = ORM::for_table( _table_users)->where('email',$_POST['email'])->find_one();
			if( $check && $check->id != $this->user->id ){
				$this->error[] = "A user with this e-mail address already exists";
			}
			
			
			if(	$_ENV['config']['TWILIO_Enabled'] &&
				$_POST['cellphone'] != "" && 
				$_POST['cellphone'] != $this->user->cellphone && 
				$_POST['cellphone_validate'] != "")
			{
				$validation = ORM::for_table(_table_validation)
								->where('new_value',$_POST['cellphone'])
								->where('user_id',$this->user->id)
								->where('type','sms')
								->find_one();
				if(!$validation)
				{
					$this->error[] = "There is no SMS Verification Code associated with this phone number";
				}
				if($_POST['cellphone_validate'] != $validation->token)
				{
					$this->error[] = "SMS Verification Code does not match value sent to phone.";
				}
				elseif($_POST['cellphone_validate'] == $validation->token)
				{
					$validation->token_accepted = date("Y-m-d H:i:s");
					$validation->old_value = $this->user->cellphone;
					$validation->save();
					
					$this->user->cellphone = $validation->new_value;
					$this->user->cellphone_validated = 1;
				}
			}
			
			if(!isset($_POST['cellphone']) || $_POST['cellphone'] == "" || strlen($_POST['cellphone']) != 10)
			{
				$this->user->cellphone = "";
				$this->user->cellphone_validated = 0;
			}
			
			if(!isset($this->error)){
				
				$this->user->name = $_POST['name'];
				$this->user->username = (isset($_POST['username']) && trim($_POST['username']) != "") ? trim($_POST['username']) : $this->user->username;
				$this->user->email = $_POST['email'];
				if( trim($_POST['password']) != ""){
					$this->user->password = $this->users->stringToSaltedHash( $_POST['password']);
				}
				$this->user->use_twofactor_auth = (isset($_POST['use_twofactor_auth']) && $_POST['use_twofactor_auth'] == 1) ? 1 : 0;
				$this->user->save();
				
				if(isset($_GET['fwd']) && preg_match('/^[\w-\/-]+$/', $_GET['fwd']))
				{
					 Core::forward($_GET['fwd']); 
				}
				else
				{
					$this->error[] = "Changes Saved!";	
				}
			}
		} // end check for $_POST data
		
		$this->template_view = Core::view( _app_server_path .'humblee/views/user/profile.php',get_object_vars($this) ); 	
		echo Core::view( _app_server_path .'application/views/templates/template.php',get_object_vars($this) );
	}
	
	public function access()
	{
		$this->user = $this->users->profile(); //get user profile data to bind to view
		$this->userAccessLog = $this->users->access_log(); // get users access log
		$this->template_view = Core::view( _app_server_path .'humblee/views/user/access.php',get_object_vars($this) ); 	
		echo Core::view( _app_server_path .'application/views/templates/template.php',get_object_vars($this) );
	}
	
}