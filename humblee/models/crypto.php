<?php defined('include_only') or die('No direct script access.');

class Core_Model_Crypto {
    
    function __construct()
    {
        if(version_compare(phpversion(), "7.2", ">=") && extension_loaded('sodium'))
        {
            $this->sodium_library = 'native';
        }
        elseif(extension_loaded('libsodium'))
        {
            $this->sodium_library = 'pecl';
        }
        elseif(class_exists('ParagonIE_Sodium_Compat'))
        {
            $this->sodium_library = 'compat';
        }
        else
        {
            $this->sodium_library = false;
        }
    }
    
    /**
	 * Return a token unique to the current session
	 * Can be included as hidden form field and checked upon POST to make sure request is coming from known user
	 *
	 * DO NOT send this value to the browser if using HMAC functionality below
	 */
    public function getCsrfToken()
    {
        if(isset($_SESSION[session_key]['csrf_token']) && isset($_SESSION[session_key]['csrf_token']) != "")
		{
			return $_SESSION[session_key]['csrf_token'];
		}
		else
		{
			$csrfToken = $this->generateCsrfToken();
			$_SESSION[session_key]['csrf_token'] = $csrfToken;
			return $csrfToken;
		}
    }
    
    /**
	 * Generate a random string and hash it to this user's session for machine authentication & CSRF protection
	 * 
	 */
	 public function get_hmac_pair()
	 {
 		$random_string = md5(uniqid(rand(), true). time() . session_id());
	 	return array(
	 		'message' => $random_string,
	 		'hmac' => base64_encode(hash_hmac('sha256', $random_string, $this->getCsrfToken() ))
 		);
	 }
	 
	/**
	* Check HMAC string and hash
	*/
	public function check_hmac_pair($string,$hash)
	{
	  	return ($hash == base64_encode(hash_hmac('sha256', $string, $this->getCsrfToken() )) ) ? true : false;
	}
    
    private function generateCsrfToken()
    {
		$token = uniqid(rand(), true). time() . session_id();
		if($this->sodium_library == "native")
		{
		    return sodium_crypto_generichash($token);
		}
		elseif($this->sodium_library != false)
		{
	        return \Sodium\crypto_generichash($token);
		}
		else
		{
		    return md5($token);
		}
    }
    
    private function getCryptoKey()
	{
		require _app_server_path."humblee/configuration/crypto.php";
		return $_encryption_key;
	}

	/**
	 * Encrypt a string
	 * Requires the libsodium extension
	 * 
	 * $plaintext	STRING	value to be encrypted
	 * 
	 * Returns	ARRAY	contains encrypted text AND a unique one-time "nonce" value that is required to decrypt the file (in addition to the stored key)
	 * 
	 * Be sure to save the encrypted value AND the unique $none value
	 *
	 */
	public function encrypt($plaintext)
	{
		if(!$this->sodium_library)
		{
			return false;
		}
		
		$nonce = random_bytes(24);
		
		if($this->sodium_library == "native")
		{
    		$crypttext = sodium_crypto_secretbox($plaintext, $nonce, $this->getCryptoKey());  
		}
		else
		{
		    $crypttext = \Sodium\crypto_secretbox($plaintext, $nonce, $this->getCryptoKey());
		}

		return array('crypttext'=>$crypttext,'nonce'=>$nonce);
	}
	
	/** 
	 * Decrypt a previously encrypted string
	 * 
	 * $crypttext	ciphered text previously encrypted by this site
	 * $nonce		unique one-time token originally generated at the time of encryption
	 * 
	 * Returns plain text
	 *
	 */
	public function decrypt($crypttext,$nonce)
	{
		if(!$this->sodium_library)
		{
			return false;
		}
		if($this->sodium_library == "native")
		{
    		return sodium_crypto_secretbox_open($crypttext, $nonce,  $this->getCryptoKey());		    
		}
		else
		{
		    return \Sodium\crypto_secretbox_open($crypttext, $nonce,  $this->getCryptoKey());
		}

	}
    
}