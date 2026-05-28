<?php

declare(strict_types=1);

namespace Humblee\Model;

use Humblee\Foundation\Core;

class Tools {

    /**
	 * CRUD tool for standard Create, Replace, Update & Delete functionality
     * Loads given view populated with data.
     *
     * $params  Array   Required parameters:
     *      table           STRING REQUIRED  name of table
     *      view            STRING REQUIRED  path of view template
     *      id              INT optional     id of table row to affect if updating
     *      post            ARRAY optional   form data to be saved
     *      validate        ARRAY optional   array keyed by column; each value has 'if' (callable($val): bool) and 'error_message' (string)
     *      allow_html      BOOL optional    if set, htmlspecialchars() will not be applied to post data
     *      post_ignore     ARRAY optional   posted form fields that should not be processed
     *      delete_value    STRING           if $_POST['delete'] has given value, delete entry (USE WITH CAUTION)
     *      crud_all_order_by STRING         optional name of column to order list of all values by
	 *
     * $thisObj Object REQUIRED  $this from calling controller
	 */
	public function CRUD(array $params, object $thisObj): void
	{
		$id = (array_key_exists('id', $params) && is_numeric($params['id'])) ? $params['id'] : false;

        if($id === false)
        {
            $crud_selected = \ORM::for_table($params['table'])->create();
		}
        else
        {
            $crud_selected = \ORM::for_table($params['table'])->find_one($id);
        }

        if(is_numeric($id) &&
            array_key_exists('delete_value', $params) &&
            isset($params['post']['delete']) &&
            $params['post']['delete'] !== "" &&
            $params['delete_value'] === $params['post']['delete'])
        {
                $crud_selected->delete();
                $fwd_uri = array_key_exists('fwd_after_delete', $params) ? $params['fwd_after_delete'] : '/';
                Core::forward($fwd_uri);
        }

		if(is_array($params['post']))
		{
            $errors = [];
            $post = [];
            foreach($params['post'] as $key => $val)
			{
				$post[$key] = !is_array($val) ? trim($val) : $val;
                if(!array_key_exists('allow_html', $params) || !$params['allow_html'])
                {
                    $post[$key] = htmlspecialchars($val);
                }

                if(array_key_exists('validate', $params) && is_array($params['validate'][$key] ?? null))
                {
                    $validator = $params['validate'][$key]['if'];
                    $check = is_callable($validator) ? (bool)$validator($val) : true;
                    if(!$check)
                    {
                        $errors[] = $params['validate'][$key]['error_message'];
                    }
                }
			}

			if(empty($errors))
			{
				foreach($post as $key => $val)
				{
					if(array_key_exists('post_ignore', $params) && in_array($key, $params['post_ignore']))
					{
						continue;
					}

					$crud_selected->$key = $val;
				}
				$crud_selected->save();

				$fwdID = ($id === false) ? "/". $crud_selected->id : "";

                Core::forward(rtrim(Core::getURI(), "/") . $fwdID);
			}
            else
            {
				$thisObj->errors = $errors;
				$crud_selected = (object)$post;
				$crud_selected->id = $id;
            }
		}

        $crud_all_order_by = array_key_exists('crud_all_order_by', $params) ? $params['crud_all_order_by'] : 'id';
		$thisObj->crud_all = \ORM::for_table($params['table'])->order_by_desc($crud_all_order_by)->find_many();
		$thisObj->crud_selected = isset($crud_selected->id) ? $crud_selected : false;

        $thisObj->template_view = Core::view($params['view'], get_object_vars($thisObj));
        echo Core::view(_app_server_path .'humblee/views/admin/templates/template.php', get_object_vars($thisObj));
	}

	/**
	 * Wrap a string of HTML text with an HTML-formatted email template
	 *
	 * $subject       STRING  Included in title meta tag of email
	 * $message       STRING  HTML-formatted text for the email
	 * $template_path STRING  Server path to the PHP file that houses the template
	 */
	public function emailTemplate(string $subject, string $message, string $template_path): string
	{
		ob_start();
		include $template_path;
		return ob_get_clean();
	}

	/**
	 * Send an email
	 *
	 * $to      string OR array  Array formatted as "to" => "addresses", "cc" => "addresses", "bcc" => "addresses"
	 * $from    string
	 * $subject string
	 * $message string   OPTIONAL  HTML message body
	 * $post    array    OPTIONAL  POST data to append to message
	 * $fields  array    OPTIONAL  friendly labels for each post field "field label" => post_key
	 */
	public function sendEmail(string|array $to, string $from, string $subject, string $message = '', array|false $post = false, array|false $fields = false): bool
    {
		if(!isset($_ENV['config']['send_email']) || !$_ENV['config']['send_email'])
		{
			return false;
		}

		$mail_newline = "\n";
		$html_linebreak = "<br />\n";

		if(is_array($post))
		{
			$message .= $html_linebreak;
			$message .= "<br />------------------------------------------------------------ ".$html_linebreak;
			if(is_array($fields))
			{
				foreach($fields as $label => $key)
				{
					$message .= "<strong>".stripslashes($label)."</strong> ".stripslashes($post[$key]) .$html_linebreak;
				}
			}
			else
			{
				foreach($post as $field_name => $value)
				{
					$message .= "<strong>".stripslashes($field_name).":</strong> ".stripslashes($value) .$html_linebreak;
				}
			}
		}

		if($_ENV['config']['MAILGUN_Enabled'])
		{
			$mail_array = [
				'from'    => $from,
				'subject' => $subject,
				'html'    => '<html>'.$message.'</html>'
			];
			if(is_array($to))
			{
				if(isset($to['cc']))
				{
					$mail_array['cc'] = $to['cc'];
				}
				if(isset($to['bcc']))
				{
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
			curl_setopt($ch, CURLOPT_URL, rtrim($_ENV['config']['MAILGUN_Base_Url'], "/").'/messages');
			curl_setopt($ch, CURLOPT_POSTFIELDS, $mail_array);

			$result = json_decode(curl_exec($ch));

			if(curl_getinfo($ch, CURLINFO_HTTP_CODE) != "200")
			{
				curl_close($ch);
				return false;
			}

			curl_close($ch);
			return array_key_exists('id', (array)$result);
		}
		else
		{
			$otherHeaders = "";
			if(is_array($to))
			{
				$otherHeaders .= (isset($to['cc']) && $to['cc'] !== "") ? 'Cc: '.$to['cc'].' '. $mail_newline : '';
				$otherHeaders .= (isset($to['bcc']) && $to['bcc'] !== "") ? 'Bcc: '.$to['bcc'].' '. $mail_newline : '';
				$to = $to['to'];
			}
			$headers  = 'MIME-Version: 1.0' . $mail_newline;
			$headers .= 'Content-type: text/html; charset=iso-8859-1' . $mail_newline;
			$headers .= 'From:'.$from.'' . $mail_newline;
			$headers .= $otherHeaders;
			return mail($to, $subject, $message, $headers);
		}
	}

	public function time_ago(string $pastTime): string
	{
		$datetime1 = new \DateTime("now");
		$datetime2 = date_create($pastTime);
		$diff = date_diff($datetime1, $datetime2);
		$timemsg = '';
		if($diff->y > 0)
		{
			$timemsg = $diff->y .' year'. ($diff->y > 1 ? "s" : '') . ' ago';
		}
		elseif($diff->m > 0)
		{
			$timemsg = $diff->m . ' month'. ($diff->m > 1 ? "s" : '') .' ago';
		}
		elseif($diff->d > 0)
		{
			$timemsg = $diff->d .' day'. ($diff->d > 1 ? "s" : '') .' ago';
		}
		elseif($diff->h > 0)
		{
			$timemsg = $diff->h .' hour'.($diff->h > 1 ? "s" : '') .' ago';
		}
		elseif($diff->i > 0)
		{
			$timemsg = $diff->i .' minute'. ($diff->i > 1 ? "s" : '') .' ago';
		}
		elseif($diff->s > 20)
		{
			$timemsg = $diff->s .' second'. ($diff->s > 1 ? "s" : '') .' ago';
		}
		elseif($diff->s >= 0)
		{
			$timemsg = 'Just now';
		}

		return $timemsg;
	}

	/**
	 * Send SMS Text Message with Twilio
	 */
	public function sendSMS(string $to, string $message, string|false $from = false): array|false
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

		$client = new \Twilio\Rest\Client($_ENV['config']['TWILIO_AccountSid'], $_ENV['config']['TWILIO_AuthToken']);

		$sms = $client->messages->create("+1".$to, ['from' => "+1".$from, 'body' => $message]);

		if($sms->status === "queued")
		{
			return ['success' => true, 'message_id' => $sms->sid];
		}
		else
		{
			return ['success' => false];
		}
	}

	/**
	 * Parse a 10-digit phone number and return digits only
	 */
	public function cleanNumber(string $number): string|false
	{
		$cleanNumber = substr(preg_replace("/[^0-9]/", "", $number), -10);
		if(strlen($cleanNumber) !== 10)
		{
			return false;
		}
		return $cleanNumber;
	}
}
