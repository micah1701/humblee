<?php

declare(strict_types=1);

namespace Humblee\Model;

class Crypto {

	/**
	 * Generate a generic hash (BLAKE2b via sodium — suitable for CSRF tokens, NOT passwords)
	 */
	public function genericHash(string $string): string
	{
		return sodium_crypto_generichash($string);
	}

    /**
	 * Return a token unique to the current session (for CSRF protection)
	 * DO NOT send this value to the browser if using HMAC functionality
	 */
    public function getCsrfToken(): string
    {
        $token = $_SESSION[session_key]['csrf_token'] ?? '';
        if($token !== '')
		{
			return $token;
		}

		$csrfToken = $this->generateCsrfToken();
		$_SESSION[session_key]['csrf_token'] = $csrfToken;
		return $csrfToken;
    }

    /**
	 * Generate a random string and hash it to this user's session for machine authentication & CSRF protection
	 */
	 public function get_hmac_pair(): array
	 {
 		$random_string = md5(uniqid((string)rand(), true). time() . session_id());
	 	return [
	 		'message' => $random_string,
	 		'hmac' => base64_encode(hash_hmac('sha256', $random_string, $this->getCsrfToken()))
 		];
	 }

	/**
	 * Check HMAC string and hash
	 */
	public function check_hmac_pair(string $string, string $hash): bool
	{
	  	return $hash === base64_encode(hash_hmac('sha256', $string, $this->getCsrfToken()));
	}

    private function generateCsrfToken(): string
    {
		$token = uniqid((string)rand(), true). time() . session_id();
		return $this->genericHash($token);
    }

    private function getCryptoKey(): string
	{
		require _app_server_path.$_ENV['config']['crypto_key'];
		return $_encryption_key;
	}

	/**
	 * Encrypt a string using libsodium
	 * Returns ARRAY with 'crypttext' and 'nonce' — both must be saved for decryption
	 */
	public function encrypt(string $plaintext): array|false
	{
		$nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
		$crypttext = sodium_crypto_secretbox($plaintext, $nonce, $this->getCryptoKey());
		return ['crypttext' => $crypttext, 'nonce' => $nonce];
	}

	/**
	 * Decrypt a previously encrypted string
	 *
	 * $crypttext  ciphered text previously encrypted by this site
	 * $nonce      unique one-time token originally generated at encryption time
	 */
	public function decrypt(string $crypttext, string $nonce): string|false
	{
		return sodium_crypto_secretbox_open($crypttext, $nonce, $this->getCryptoKey());
	}

}
