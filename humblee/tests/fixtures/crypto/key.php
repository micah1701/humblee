<?php

/**
 * Test-only encryption key — a fixed 32-byte value used solely during unit testing.
 *
 * WARNING: Never use this key outside of the test suite.
 * The production key lives in humblee/configuration/crypto/key.php
 * and must never be committed to version control.
 */
$_encryption_key = str_repeat("\x42", SODIUM_CRYPTO_SECRETBOX_KEYBYTES);
