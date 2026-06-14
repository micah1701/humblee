# Skill: Write PHPUnit Tests

## Setup

Tests live in `humblee/tests/`. Run from the `humblee/` directory:

```bash
cd humblee
./vendor/bin/phpunit
```

**Do not run from the project root** — the bootstrap path won't resolve.

## Bootstrap

`humblee/tests/bootstrap.php` sets up:
- Composer autoloader
- SQLite in-memory database (for model tests that need DB)
- Test encryption key from `humblee/tests/fixtures/crypto/key.php`
- Any constants that config files would normally define (`session_key`, `_table_*`, etc.)

Check bootstrap.php before adding tests to understand what's pre-configured.

## Test Locations

| Test type | Directory |
|---|---|
| Model tests | `humblee/tests/Model/` |
| Foundation tests | `humblee/tests/Foundation/` (create if needed) |
| PHPUnit config | `humblee/phpunit.xml` |

## Model Test Pattern

```php
<?php
declare(strict_types=1);

namespace Humblee\Tests\Model;

use PHPUnit\Framework\TestCase;
use Humblee\Model\Crypto;

class CryptoTest extends TestCase
{
    public function testEncryptDecryptRoundtrip(): void
    {
        $plaintext = 'hello world';
        $encrypted = Crypto::encrypt($plaintext);

        $this->assertNotFalse($encrypted);
        $this->assertNotEquals($plaintext, $encrypted);

        $decrypted = Crypto::decrypt($encrypted);
        $this->assertSame($plaintext, $decrypted);
    }

    public function testDecryptReturnsFalseOnTamperedPayload(): void
    {
        $encrypted = Crypto::encrypt('data');
        $tampered  = $encrypted . 'x';  // Modify ciphertext

        $this->assertFalse(Crypto::decrypt($tampered));
    }
}
```

## ORM / Database Test Pattern

Tests that need DB use SQLite in-memory (set up in bootstrap):

```php
<?php
declare(strict_types=1);

namespace Humblee\Tests\Model;

use PHPUnit\Framework\TestCase;

class ContentTest extends TestCase
{
    protected function setUp(): void
    {
        // Bootstrap creates the SQLite tables — verify bootstrap.php includes yours
        // Insert fixture data using ORM directly
        $record = \ORM::for_table(_table_content)->create();
        $record->page_id         = 1;
        $record->content_type_id = 1;
        $record->template_block_id = 0;
        $record->p13n_id         = 0;
        $record->content         = 'Test content';
        $record->save();
    }

    protected function tearDown(): void
    {
        // Clean up inserted rows to isolate tests
        \ORM::for_table(_table_content)->delete_many();
    }

    public function testFindContentReturnsExpectedSlotKey(): void
    {
        $content = \Humblee\Model\Content::findContent(1, 0);

        $this->assertArrayHasKey('rich_text', $content);
        $this->assertSame('Test content', $content['rich_text']->content);
    }
}
```

## HMAC Test Pattern

```php
public function testHmacPairValidates(): void
{
    $pair = \Humblee\Model\Crypto::get_hmac_pair();

    $this->assertTrue(
        \Humblee\Model\Crypto::check_hmac_pair($pair['key'], $pair['token'])
    );
}

public function testTamperedHmacFails(): void
{
    $pair = \Humblee\Model\Crypto::get_hmac_pair();

    $this->assertFalse(
        \Humblee\Model\Crypto::check_hmac_pair($pair['key'], 'invalid_token')
    );
}
```

## Checklist

- [ ] Test file in `humblee/tests/` (matching subdirectory)
- [ ] Class extends `PHPUnit\Framework\TestCase`
- [ ] `declare(strict_types=1)` at top
- [ ] Namespace matches directory: `Humblee\Tests\Model\`
- [ ] `setUp()` and `tearDown()` clean up any DB state
- [ ] No HTTP requests in tests — mock or test pure logic only
- [ ] Run from `humblee/` directory: `./vendor/bin/phpunit`
