<?php 

/**
 * Functions used throughout the site
 *
 */
class Core_Model_Tools {

    /**
	 * CRUD tool for standard Create, Replace, Update & Delete functionality
     * load given view populated with data.
     * 
     * $params  Array   Required parameters:
     *      table       STRING REQUIRED name of table
     *      view        STRING REQUIRED path of view template
     *      id          INT optional    id of table row to effect if updating
     *      post        ARRAY optional  form data to be saved
     *      validate    ARRAY optional  array(column, if() statement, error_message)
     *      allow_html  BOOL optional   if set, htmlspecialcahrs() will not be applied to post data
     *      post_ignore ARRAY optional  posted form fields that should not be processed
     *      delete_value STRING if $_POST['delete'] has given value, delete entry.  (USE WITH CAUTION)
     *      crud_all_order_by STRING    optional name of column to order list of all values by
	 *  
     * $thisObj Object REQUIRED $this from calling controller   
     *      
	 */
	public function CRUD($params,$thisObj )
	{
		$id = ( array_key_exists('id',$params) && is_numeric($params['id']) ) ? $params['id']: false;
		
        if($id === false)
        {
            $crud_selected = ORM::for_table($params['table'])->create();
		}
        else
        {
            $crud_selected = ORM::for_table($params['table'])->find_one($id);
        }
        
        if( is_numeric($id) && 
            array_key_exists('delete_value',$params) && 
            isset($params['post']['delete']) && 
            $params['post']['delete'] != "" &&
            $params['delete_value'] == $params['post']['delete'])
        {
                $crud_selected->delete();
                $fwd_uri = (array_key_exists('fwd_after_delete',$params) ) ? $params['fwd_after_delete'] : '/';
                Core::forward($fwd_uri);
        } 
        
		if(is_array($params['post']))
		{
            $errors = array();
            foreach($params['post'] as $key => $val)
			{
				$post[$key] = (!is_array($val)) ? trim($val) : $val; // apply filter to each field
                if(!array_key_exists('allow_html',$params) || !$params['allow_html'])
                {
                    $post[$key] = htmlspecialchars($val);
                }
                
                if(array_key_exists('validate',$params) && is_array($params['validate'][$key]))
                {
                    $check=(eval($params['validate'][$key]['if'])) ? true : false;
                    if(!$check)
                    {
                        $errors[] = $params['validate'][$key]['error_message'];
                    }
                }	
			}
	
			if(count($errors == 0))
			{
				foreach($post as $key => $val)
				{		
					if(array_key_exists('post_ignore',$params) && in_array($key,$params['post_ignore']) )
					{
						continue; 
					}
		
					$crud_selected->$key = $val;
				}
				$crud_selected->save();
                Core::forward(rtrim(Core::getURI(),"/") ."/". $crud_selected->id);			
			}
            else
            {
				$thisObj->errors = $errors; 
				$crud_selected = (object)$post;  // pass the post data back to the view so the user can see their mistakes :)
				$crud_selected->id = $id;		 // re-assign the ID though			
            }		
		}// end check for post
		
		// these are set so the view can show a list of ALL rows in the table
        $crud_all_order_by = ( array_key_exists('crud_all_order_by',$params) ) ? $params['crud_all_order_by'] : 'id';	
		$thisObj->crud_all = ORM::for_table($params['table'])->order_by_desc($crud_all_order_by)->find_many();
		$thisObj->crud_selected = (isset($crud_selected->id)) ? $crud_selected : false;
        
		// spit out the view
        $thisObj->pagebody = Core::view( $params['view'],get_object_vars($thisObj) ); 
        echo Core::view( _app_server_path .'core/views/admin/template.php',get_object_vars($thisObj) );	
	}


	/**
	 * SEND E-MAIL
	 *
	 * $to		string OR array.  Array formated as "to" => "list,of,addresses; "cc" => "you,get,the,idea"
	 * $from	string
	 * $subject	string
	 * $message	string	OPTIONAL	text message to display before form fields
	 * $fields	array	OPTIONAL	friendly/custom labels for each post field "field label" => post_key. 
	 *								(leaving this blank but sending $_POST data will email ALL posted fields
	 * $post	array	original $_POST data
	 *
	 */
	public function sendEmail($to,$from,$subject,$message='',$post=false,$fields=false)
    {
		// suppress_mail constant set in config. If true, don't send any e-mails
		if(!isset($_ENV['config']['send_email']) || !$_ENV['config']['send_email'])
		{
			//uncomment these lines to send all e-mail to another address
			//$old_to = (is_array($to)) ? implode(",",$to) : $to;
			//$subject = $subject ." [intended for:" . $old_to ."]";
			//$to = 'micah.murray+fwisDEV@gmail.com';

			return false;
		}
		
		$mail_newline = "\n";
		$html_linebreak = "<br />\n";
						
		if($post){
			$message .= $html_linebreak;
			$message .= "<br />------------------------------------------------------------ ".$html_linebreak;
			if($fields){
				foreach($fields as $label => $key){
					$message .= "<strong>".stripslashes($label)."</strong> ".stripslashes($post[$key]) .$html_linebreak;
				}
			}else{
				foreach($post as $field_name => $value){
					$message .= "<strong>".stripslashes($field_name).":</strong> ".stripslashes($value) .$html_linebreak;
				}
			}
		}
		
		if($_ENV['config']['MAILGUN_Enabled'])
		{
			$mail_array = array('from' => $from,
			                  	'subject' => $subject,
			                  	'html' => '<html>'.$message.'</html>');
			              	//  'text' => 'plain text message');
			if(is_array($to)){
				if(isset($to['cc'])){
					$mail_array['cc'] = $to['cc'];
				}
					if(isset($to['bcc'])){
					$mail_array['bcc'] = $to['bcc'];
				}
				$mail_array['to'] = $to['to'];
			}
			else
			{
				$mail_array['to'] = $to;
			}
			
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
			curl_setopt($ch, CURLOPT_USERPWD, 'api:'.$_ENV['config']['MAILGUN_API_Key']);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
			curl_setopt($ch, CURLOPT_URL, rtrim($_ENV['config']['MAILGUN_Base_URL'],"/").'/messages');
			curl_setopt($ch, CURLOPT_POSTFIELDS, $mail_array);
			            
			$result = json_decode(curl_exec($ch));
			curl_close($ch);
			
			return (array_key_exists('id',$result)) ? true : false;
		}
		else
		{
			$otherHeaders = "";
			if(is_array($to)){
				$otherHeaders .= (isset($to['cc']) && $to['cc'] != "") ? 'Cc: '.$to['cc']. ' '. $mail_newline : '';
				$otherHeaders .= (isset($to['bcc']) && $to['bcc'] != "") ? 'Bcc: '.$to['bcc']. ' '. $mail_newline : '';
				$to = $to['to'];
			}
			$headers  = 'MIME-Version: 1.0' . $mail_newline;
			$headers .= 'Content-type: text/html; charset=iso-8859-1' . $mail_newline;		
			$headers .= 'From:'.$from.'' . $mail_newline;
			$headers .= $otherHeaders;
			return mail($to, $subject, $message, $headers);
		}
	}	
	
	public function time_ago($pastTime)
	{
		$datetime1 = new DateTime("now");
		$datetime2 = date_create($pastTime);
		$diff = date_diff($datetime1, $datetime2);
		$timemsg = '';
		if($diff->y > 0){
			$timemsg = $diff->y .' year'. ($diff->y > 1?"s":'') . 'ago';
		}
		else if($diff->m > 0){
			$timemsg = $diff->m . ' month'. ($diff->m > 1?"s":'') .' ago';
		}
		else if($diff->d > 0){
			$timemsg = $diff->d .' day'. ($diff->d > 1?"s":'') .' ago';
		}
		else if($diff->h > 0){
			$timemsg = $diff->h .' hour'.($diff->h > 1 ? "s":'') .' ago';
		}
		else if($diff->i > 0){
			$timemsg = $diff->i .' minute'. ($diff->i > 1?"s":'') .' ago';
		}
		else if($diff->s > 20){
			$timemsg = $diff->s .' second'. ($diff->s > 1?"s":'') .' ago';
		}
		else if($diff->s >= 0){
			$timemsg = 'Just now';
		}
	    
		return $timemsg;
	}
	
	/**
	 * Send SMS Text Message with Twilio
	 * 
	 */
	public function sendSMS($to,$message,$from=false)
	{
		if(!$_ENV['config']['TWILIO_Enabled'])
		{ 
            return false;
		}
		
		if(!$from)
		{
            $from = $_ENV['config']['TWILIO_SMS_Number'];
		}
		
		$to = $this->cleanNumber($to);
		$from = $this->cleanNumber($from);
		if(!$to || !$from) { return false; }
		
		require _app_server_path.'core/libs/twilio/sdk/Services/Twilio.php';
		$client = new Services_Twilio($_ENV['config']['TWILIO_AccountSid'],$_ENV['config']['TWILIO_AuthToken']);
		$sms = $client->account->sms_messages->create("+1".$from, "+1".$to, $message, array());

		if ($sms->status == "queued")
		{
			return array("success"=>true, "message_id"=>$sms->sid);
		}
		else
		{
			return array("success"=>false);
		}
	}
	
	public function cleanNumber($number)
	{
		$cleanNumber = substr(preg_replace("/[^0-9]/","",$number),-10);
		if(strlen($cleanNumber) != 10){ 
			return false; 
		}
		else
		{
			return $cleanNumber;
		}
	}
}