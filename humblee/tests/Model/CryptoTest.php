<?php

declare(strict_types=1);

namespace Tests\Model;

use Humblee\Model\Crypto;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Overrides getCsrfToken() with a fixed value so tests run without a real
 * HTTP session. Everything else — encrypt/decrypt, HMAC generation, hashing —
 * exercises the actual production code paths unchanged.
 */
class TestableCrypto extends Crypto
{
    public function getCsrfToken(): string
    {
        return 'static-test-csrf-token-for-unit-tests';
    }
}

#[CoversClass(Crypto::class)]
class CryptoTest extends TestCase
{
    private TestableCrypto $crypto;

    protected function setUp(): void
    {
        $this->crypto = new TestableCrypto();
    }

    // =========================================================================
    // genericHash
    // =========================================================================

    public function test_generic_hash_returns_string_of_correct_length(): void
    {
        $result = $this->crypto->genericHash('hello');

        $this->assertIsString($result);
        $this->assertSame(SODIUM_CRYPTO_GENERICHASH_BYTES, strlen($result));
    }

    public function test_generic_hash_is_deterministic(): void
    {
        $a = $this->crypto->genericHash('same-input');
        $b = $this->crypto->genericHash('same-input');

        $this->assertSame($a, $b);
    }

    public function test_generic_hash_differs_for_different_inputs(): void
    {
        $a = $this->crypto->genericHash('hello');
        $b = $this->crypto->genericHash('world');

        $this->assertNotSame($a, $b);
    }

    // =========================================================================
    // encrypt / decrypt
    // =========================================================================

    public function test_encrypt_returns_string(): void
    {
        $result = $this->crypto->encrypt('test payload');

        $this->assertIsString($result);
    }

    public function test_encrypted_payload_is_longer_than_plaintext(): void
    {
        // Encrypted output = 24-byte nonce + ciphertext, so it must always
        // be longer than the input.
        $plaintext = 'hello world';
        $encrypted = $this->crypto->encrypt($plaintext);

        $this->assertGreaterThan(strlen($plaintext), strlen($encrypted));
    }

    public function test_encrypt_decrypt_roundtrip(): void
    {
        $plaintext = 'This is a secret message.';

        $encrypted = $this->crypto->encrypt($plaintext);
        $this->assertNotFalse($encrypted, 'encrypt() should not return false for valid input');

        $decrypted = $this->crypto->decrypt($encrypted);
        $this->assertSame($plaintext, $decrypted);
    }

    public function test_encrypt_produces_different_ciphertext_on_each_call(): void
    {
        // A fresh random nonce is generated every call, so identical plaintexts
        // must produce different ciphertexts.
        $a = $this->crypto->encrypt('same plaintext');
        $b = $this->crypto->encrypt('same plaintext');

        $this->assertNotSame($a, $b);
    }

    public function test_decrypt_fails_with_tampered_ciphertext(): void
    {
        $encrypted = $this->crypto->encrypt('secret');
        $tampered  = $encrypted . 'x'; // corrupt by appending a byte

        $result = $this->crypto->decrypt($tampered);

        $this->assertFalse($result);
    }

    public function test_encrypt_decrypt_preserves_unicode(): void
    {
        $plaintext = 'Héllo Wörld — こんにちは 🎉';
        $decrypted = $this->crypto->decrypt($this->crypto->encrypt($plaintext));

        $this->assertSame($plaintext, $decrypted);
    }

    public function test_encrypt_decrypt_preserves_empty_string(): void
    {
        $decrypted = $this->crypto->decrypt($this->crypto->encrypt(''));

        $this->assertSame('', $decrypted);
    }

    // =========================================================================
    // HMAC pair (get_hmac_pair / check_hmac_pair)
    // =========================================================================

    public function test_hmac_pair_returns_message_and_hmac_keys(): void
    {
        $pair = $this->crypto->get_hmac_pair();

        $this->assertArrayHasKey('message', $pair);
        $this->assertArrayHasKey('hmac', $pair);
    }

    public function test_hmac_pair_message_is_hex_string(): void
    {
        $pair = $this->crypto->get_hmac_pair();

        $this->assertMatchesRegularExpression('/^[0-9a-f]+$/', $pair['message']);
    }

    public function test_hmac_pair_validates_successfully(): void
    {
        $pair = $this->crypto->get_hmac_pair();

        $this->assertTrue($this->crypto->check_hmac_pair($pair['message'], $pair['hmac']));
    }

    public function test_hmac_pair_fails_with_tampered_message(): void
    {
        $pair = $this->crypto->get_hmac_pair();

        $this->assertFalse($this->crypto->check_hmac_pair('tampered-message', $pair['hmac']));
    }

    public function test_hmac_pair_fails_with_tampered_hash(): void
    {
        $pair = $this->crypto->get_hmac_pair();

        $this->assertFalse($this->crypto->check_hmac_pair($pair['message'], 'not-the-right-hash'));
    }

    public function test_hmac_pair_message_is_unique_per_call(): void
    {
        // Each call generates 16 fresh random bytes, so messages must differ.
        $a = $this->crypto->get_hmac_pair();
        $b = $this->crypto->get_hmac_pair();

        $this->assertNotSame($a['message'], $b['message']);
    }
}
