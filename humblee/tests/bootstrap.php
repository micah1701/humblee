<?php

declare(strict_types=1);

/**
 * PHPUnit bootstrap — runs once before any test file is loaded.
 *
 * Defines the global constants and environment values that Humblee's core
 * classes expect to find at runtime. In production these are set by
 * humblee/configuration/env_*.php; here we supply safe, isolated stand-ins.
 */

// The Composer autoloader makes Humblee\ and Tests\ classes available.
require_once __DIR__ . '/../vendor/autoload.php';

// -------------------------------------------------------------------------
// Constants that src/ classes reference at runtime
// -------------------------------------------------------------------------

// Used by Crypto::getCsrfToken() to key into $_SESSION.
define('session_key', 'humblee_test');

// Filesystem root that getCryptoKey() prepends to the config path.
// Points at tests/fixtures/ so the test key file is loaded instead of
// the real production key.
define('_app_server_path', __DIR__ . '/fixtures/');

// -------------------------------------------------------------------------
// Environment config (mirrors what env_*.php normally sets)
// -------------------------------------------------------------------------

// Path (relative to _app_server_path) to the encryption key file.
$_ENV['config']['crypto_key'] = 'crypto/key.php';
