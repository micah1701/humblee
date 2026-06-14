# Backend Models

## Location & Naming

Core models: `humblee/src/Model/` — namespace `Humblee\Model\`
App models: `application/Model/` — namespace `App\Model\`

One class per file, filename matches class name.

## Standard Model Structure

```php
<?php
declare(strict_types=1);

namespace Humblee\Model;

use Humblee\Foundation\Core;

class MyModel
{
    // Role check helper — call at the top of any privileged method
    private static function requireRoles(): void
    {
        if (!Core::auth(['admin', 'content', 'developer'])) {
            http_response_code(403);
            exit;
        }
    }

    public static function getAll(): array
    {
        self::requireRoles();

        return \ORM::for_table(_table_my_table)
            ->order_by_asc('sort_order')
            ->find_many()
            ->as_array();
    }
}
```

## Model Reference

| Class | Purpose |
|---|---|
| `Content` | `listRevisions()`, `saveContent()`, `findContent()`, p13n logic |
| `Crypto` | `get_hmac_pair()`, `check_hmac_pair()`, `encrypt()`, `decrypt()`, `genericHash()` |
| `Media` | Media metadata, encrypt/decrypt file on disk, folder management |
| `Pages` | Page CRUD, URL slug validation, hierarchy tree |
| `Personalization` | Audience segments CRUD |
| `Tools` | CRUD scaffolding, SMS/email helpers, image compression |
| `Users` | `logIn()`, `logOut()`, `password_hash`, role management, access log |

## ORM Return Types

| ORM method | Returns |
|---|---|
| `find_one()` | ORM object or `false` |
| `find_many()` | Array of ORM objects (empty array if none) |
| `find_array()` | Array of plain PHP arrays |
| `->as_array()` | Converts ORM object to plain PHP array |
| `->save()` | void — mutates the ORM object in place |
| `->delete()` | void |

Always check `find_one()` result before accessing properties:
```php
$record = \ORM::for_table(_table_pages)->find_one($id);
if (!$record) {
    return false;
}
```

## Content Model Key Concepts

**`Content::findContent(int $page_id, int $p13n_id): array`**

Returns an associative array keyed by `slot_key`:
- Legacy content (no slot): keyed by content type's `objectkey` (e.g., `rich_text`)
- Slotted content (`template_block_id > 0`): keyed by `slot_key` (e.g., `rich_text_2`)

```php
$content = Content::findContent($page_id, $p13n_id);
echo $content['rich_text']->content;   // first slot
echo $content['rich_text_2']->content; // second slot
```

**`Content::saveContent(array $post): bool`**

Expects `$post` to include:
- `page_id`, `content_type_id`, `template_block_id`, `p13n_id`
- `content` (or individual field values if `serialize_fields` is set)
- `hmac_token` + `hmac_key` (validated inside)

## Users Model Key Concepts

```php
// Authentication
Users::logIn(string $username, string $password): bool

// Password hash (for new users)
password_hash($password . '-' . $user_id, PASSWORD_ARGON2ID)

// Role management
Users::addRole(int $user_id, int $role_id): void
Users::removeRole(int $user_id, int $role_id): void
```

The legacy `Users::stringToSaltedHash()` (sodium BLAKE2b) is only used in the login fallback path. Do not use it anywhere else.
