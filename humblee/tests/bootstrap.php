<?php

declare(strict_types=1);

/**
 * PHPUnit bootstrap — runs once before any test file is loaded.
 *
 * Defines the global constants and environment values that Humblee's core
 * classes expect to find at runtime. In production these are set by
 * humblee/configuration/env_*.php and humblee/init.php; here we supply
 * safe, isolated stand-ins.
 */

// The Composer autoloader makes Humblee\ and Tests\ classes available.
require_once __DIR__ . '/../vendor/autoload.php';

// -------------------------------------------------------------------------
// Session superglobal (not available by default in CLI — initialise manually)
// -------------------------------------------------------------------------

$_SESSION = [];

// -------------------------------------------------------------------------
// Constants that src/ classes reference at runtime
// -------------------------------------------------------------------------

// Keys into $_SESSION used by Core and Crypto.
define('session_key', 'humblee_test');

// Filesystem root that getCryptoKey() prepends to the config path.
// Points at tests/fixtures/ so the test key file loads instead of the
// real production key.
define('_app_server_path', __DIR__ . '/fixtures/');

// Database table names (defined by humblee/configuration/config.php in prod).
define('_table_pages',          'humblee_pages');
define('_table_content',        'humblee_content');
define('_table_content_p13n',   'humblee_content_p13n');
define('_table_content_types',  'humblee_content_types');
define('_table_templates',       'humblee_templates');
define('_table_template_blocks', 'humblee_template_blocks');
define('_table_users',           'humblee_users');
define('_table_roles',          'humblee_roles');
define('_table_user_roles',     'humblee_user_roles');
define('_table_validation',     'humblee_validation');
define('_table_accesslog',      'humblee_accesslog');
define('_table_media',          'humblee_media');
define('_table_media_folders',  'humblee_media_folders');

// -------------------------------------------------------------------------
// Environment config (mirrors what env_*.php normally sets)
// -------------------------------------------------------------------------

$_ENV['config']['crypto_key']    = 'crypto/key.php';
$_ENV['config']['use_p13n']      = false;
$_ENV['config']['i18n_segments'] = null;
