<?php

/**
 * this class is not meant to extend the default controller. It is only used to return "AJAX" requests
 *
 * it extends the core controller "xhr" which sends no-cache headers and provides some methods for authorization and returning JSON
 */
 
class Controller_Request extends Core_Controller_Xhr {
	
	/**
	 * an example function that returns JSON Object of the logged in user's profile
	 */
	public function getUserProfile()
    {
        $this->require_role('login'); // make sure this function is only called by a logged in user
        $user = ORM::for_table( _table_users)->where('id',$_SESSION[session_key]['user_id'])->find_array();
        $this->json($user[0]);
	}

}