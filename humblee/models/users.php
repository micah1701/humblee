<?php

class Core_Model_Users {
	
	/**
	* Converted a string of text into a standard salted hash
	*
	* $string	STRING	value to be hased
	* $salt	MIXED	string or INT, for unique salt use the user's ID
	* 
	* attempts to use libsodium via the paragonie/sodium_compact polyfil library
	* if not installed, use a simple md5();
	*/
	public function stringToSaltedHash($string,$salt)
	{
		$salted_string = $string.'-'.$salt;
		if(class_exists('ParagonIE_Sodium_Compat'))
		{
			return \Sodium\crypto_generichash($salted_string);
		}
		else
		{
			return md5($salted_string);	    	
		}
	}
	 
	/**
	* Log in as given user
	*	
	*/
	public function logInSession($user_id){
		$_SESSION[session_key] = array();
		$_SESSION[session_key]['user_id'] = $user_id; 
	}
	 
	/**
	 * Update the access log 
	 * 
	 */
	public function accesslog($status='')
	{
		$log = ORM::for_table(_table_accesslog)->create();
		$log->session_id = session_id();
		$log->user_id = (isset($_SESSION[session_key]['user_id'])) ? $_SESSION[session_key]['user_id'] : '';
		$log->ip_address = $_SERVER['REMOTE_ADDR'];
		$log->user_agent = $_SERVER['HTTP_USER_AGENT'];
		$log->timestamp = date("Y-m-d H:i:s");
		$log->status = $status;
		$log->save();

		$ch = curl_init('https://freegeoip.net/json/' . $_SERVER['REMOTE_ADDR']);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$result = json_decode(curl_exec($ch));
		curl_close($ch);

		if(isset($result->country_name) && $result->country_name != "")
		{
			$log->ip_geolocation = $result->city .", ".$result->region_name ." ".$result->country_name;
			$log->save(); // yes, this is a second DB call on the same row, but this way we know the first one saved and if the API fails, its no biggie
		}
	 }
	 
	/**
	 * Check credentials and log in
	 *
	 */
	public function logIn($username,$password,$sms_login=false)
	{	
		//check if given "username" is an e-mail address or a username
		$username_column = (filter_var($username, FILTER_VALIDATE_EMAIL)) ? 'email' : 'username';
		
		//check for username first
		$user = ORM::for_table( _table_users)
			->where($username_column,$username)
			->where('active',1)
			->find_one();
		
		if(!$user) { 
			$this->accesslog('Failed: invalid credentials');
			return array("access_granted"=> false, "error"=>"Invalid Username");
		}
		
		//check credentials
		if($sms_login)
		{
			if(	!isset($_SESSION[session_key]['login_token']) || 
				(isset($_SESSION[session_key]['login_token']) && strtoupper($password) !== $_SESSION[session_key]['login_token'])
			){
				$this->accesslog('Failed: invalid SMS token');
				return array("access_granted"=> false, "error"=>"Invalid SMS Code");
			}
			if(!isset($_SESSION[session_key]['login_token_expires']) || time() > $_SESSION[session_key]['login_token_expires'])
			{
				$this->accesslog('Failed: SMS token expired');
				return array("access_granted"=> false, "error"=>"SMS Code Expired");
			}
		}
		else
		{
			if($this->stringToSaltedHash($password,$user->id) != ($user->password))
			{
				$this->accesslog('Failed: Invalid Password');
				return array("access_granted"=> false, "error"=>"Invalid Password");	
			}
			
			if($_ENV['config']['TWILIO_Enabled'] && $user->use_twofactor_auth == 1)
			{
				$this->accesslog('Valid password. SMS requested');
				return array("access_granted"=> false, "error"=>"use_twofactor_auth", "cellphone"=>$user->cellphone, "name"=>$user->name, "email"=>$user->email);
			}
		}
		
		//set session values to recognize user as being logged in
		$this->logInSession($user->id);	
		
		//update user's record to record this login
		$user->logins = $user->logins +1;
		$user->last_login = date("Y-m-d H:i:s");		
		$user->save();
		
		$log_msg = ($sms_login) ? "Accepted SMS" : "Accepted Password";
		$this->accesslog($log_msg);
		return array("access_granted"=> true);	
	}
	
	/**
	* Log current user out
	*/
	public function logOut()
	{
		session_destroy();
		return true;
	}
    
	/**
	* Get user's profile
	*
	* returns logged in user unless $user_id is specified
	*/
	public function profile($user_id=NULL)
	{
		$user_id = (is_numeric($user_id)) ? $user_id : $_SESSION[session_key]['user_id'];
		return ORM::for_table( _table_users)->find_one($user_id); 
	}

	/**
	* Get a user's access log
	*
	* returns logged in user unless $user_id is specified
	*/
	public function access_log($limit=100,$user_id=NULL)
	{
		$user_id = (is_numeric($user_id)) ? $user_id : $_SESSION[session_key]['user_id'];
		return ORM::for_table( _table_accesslog)
			->where('user_id',$user_id)
			->order_by_desc('timestamp')
			->limit($limit)
			->find_many(); 
	}
	 
	/**
	 * Generate a random PLAIN TEXT password string
	 *
	 */
	public function generatePassword($length=8){
		$chars = "ABCDEFGHJKLMNPQRSTUVWXYZ23456789";
		$pw = "";
		for($i=0; $i<$length; $i++){
			$random = rand(0, strlen($chars) -1);
			$pw.= $chars[$random];
		}
		return $pw;
	}	 
	
	/**
	 * Create a new user
	 *
	 */
	public function createUser($name,$email,$username,$password=''){
		$user = ORM::for_table( _table_users )->create();
		$user->name = $name;
		$user->username = $username;
		$user->email = $email;
		$user->password = 'random-temp-password-'.time();
		$user->save();
		
		$user->password = $this->stringToSaltedHash($password,$user->id);
		$user->active = 1;
		$user->save();
		
		return $user->id;
	}
	
	/**
	 * remove all roles for a given user
	 */
	public function stripRoles($user_id)
	{
		if(!isset($user_id) || !is_numeric($user_id))
		{
			return false;
		}
		$roles = ORM::for_table( _table_user_roles )->where('user_id',$user_id)->find_many();
		if(!$roles)
		{
			return false;
		}
		foreach ($roles as $role){
			$role->delete();
		}
		return true;
	}
	
	/**
	 * add a role for a given user
	 */
	public function addRole($user_id, $role_id){
		$role = ORM::for_table(_table_user_roles)->create();
		$role->user_id = $user_id;
		$role->role_id = $role_id;
		$role->save();
		return true;
	}
	 
    /**
     * Delete a user
     *
     * $complete_removal	BOOL	(optional) 
     *						True 	physcially deletes the row.
     *						False 	updates record to remove access
     *
     */
    public function deleteUser($user_id,$complete_removal=false){
		if(!is_numeric($user_id)){ return false; } 
		$user = ORM::for_table(_table_users)->find_one($user_id);
		if(!$user){ return false; }
		
		
		//delete this user's associated  roles
		$this->stripRoles($user_id);
	
		//delete user
		if($complete_removal){
			return $user->delete();
		}
		//or remove credentials but keep record (important for integrity of content revision history)
		$user->name = $user->name." [DELETED USER]";
		$user->username = $user->username." [DELETED USER]";
		$user->email = $user->email." [DELETED USER]";
		$user->password = "";
		$user->active = 0;
		$user->save();
		return true;
    }
	
	/**
	* Reset a user's password and notify them by e-mail
	*
	*/
	public function resetPassword($user_id,$new_password,$sendEmail=true)
	{
		if(!is_numeric($user_id)){ return false; }
		$user = ORM::for_table( _table_users)->find_one($user_id);
		if(!$user){ return false; }
		$user->password = $this->stringToSaltedHash($new_password,$user_id);
		$user->save();
		
		if($sendEmail)
		{
			$from = _default_mail_address;
			$subject = "You've successfully reset your ". $_ENV['config']['domain'] ." password";
			$body = "Hi {$user->name},\n\n";
			$body.= "This message is to notify that the password associated with your account has been reset.\n\n";
			$body.= "If you did not initiate this change, you can recover your account at ". $_ENV['config']['domain'] . _app_path ."user/forgotPassword \n\n";
			$body.=" Thanks.";
			
			$tools = new Core_Model_Tools;
			return $tools->sendEmail($user->email,$from,$subject,nl2br($body));			
		}
		else
		{
			return true;
		}
		
	}
	
	/**
	* Send a registration confirmation e-mail to user
	*
	*/
	public function registrationEmail($email,$name,$password){
		$from = _default_mail_address;
		$subject = $_ENV['config']['domain'] ." Username and Password";
		$body = "Hi {$name},\n\n";
		$body.= "Welcome to ". $_ENV['config']['domain'] ."!\n\n";
		$body.= "Please hold on to your sign-in info:\n\n";
		$body.= "Username: {$email} \n";
		$body.= "Password: {$password} \n\n";
		$body.=" To change your password, log in to ". $_ENV['config']['domain'] ." and update your user profile.\n\n";
		$body.=" Thanks!";
		
		$tools = new Core_Model_Tools;
		return $tools->sendEmail($email,$from,$subject,nl2br($body));		
	}
	
	/**
	 * Send an account verification e-mail for reseting forgotten password
	 * 
	 */
	public function forgotPasswordVerifyEmail($email,$name,$token)
	{
		$from = _default_mail_address;
		$subject = $_ENV['config']['domain'] ." verification access code";
		$body = "Hi {$name},\n\n";
		$body.= "Someone has initiated a password reset request for your account at  ". $_ENV['config']['domain'] ."!\n\n";
		$body.= "The one-time temporary access code to complete this request is: {$token}\n\n";
		$body.=" If you did not request this, you can ignore and delete this message.\n\n";
		$body.=" Thanks!";
		
		$tools = new Core_Model_Tools;
		return $tools->sendEmail($email,$from,$subject,nl2br($body));		
	}
}