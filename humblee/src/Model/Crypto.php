<?php

declare(strict_types=1);

namespace Humblee\Model;

class Crypto
{

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
		if ($token !== '') {
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
		$random_string = bin2hex(random_bytes(16));
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
		return $this->genericHash(bin2hex(random_bytes(32)));
	}

	private function getCryptoKey(): string
	{
		$_encryption_key = $_ENV['config']['crypto_key'] ?? '';
		if ($_encryption_key === '') {
			throw new \RuntimeException('Encryption key not found');
		}
		return $_encryption_key;
	}

	/**
	 * Encrypt plaintext with libsodium secretbox.
	 * Returns a single binary string: [24-byte nonce][ciphertext].
	 * The nonce is prepended at a fixed length so decrypt() can extract it without a separator.
	 */
	public function encrypt(string $plaintext): string|false
	{
		$nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
		$ciphertext = sodium_crypto_secretbox($plaintext, $nonce, $this->getCryptoKey());
		if ($ciphertext === false) {
			return false;
		}
		return $nonce . $ciphertext;
	}

	/**
	 * Decrypt a payload produced by encrypt().
	 * Extracts the nonce from the first SODIUM_CRYPTO_SECRETBOX_NONCEBYTES bytes.
	 */
	public function decrypt(string $payload): string|false
	{
		$nonce      = substr($payload, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
		$ciphertext = substr($payload, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
		return sodium_crypto_secretbox_open($ciphertext, $nonce, $this->getCryptoKey());
	}
}
