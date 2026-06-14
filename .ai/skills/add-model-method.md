# Skill: Add a Model Method

## Anatomy of a Model Method

```php
<?php
declare(strict_types=1);

namespace Humblee\Model;

use Humblee\Foundation\Core;

class MyModel
{
    // Step 1: Define a role guard (only if the method requires auth)
    private static function requireRoles(): void
    {
        if (!Core::auth(['admin', 'content', 'developer'])) {
            http_response_code(403);
            exit;
        }
    }

    // Step 2: Write the method with typed signature
    public static function getByPageId(int $page_id): array
    {
        self::requireRoles();

        // Step 3: ORM query (no raw SQL)
        $rows = \ORM::for_table(_table_my_table)
            ->where('page_id', $page_id)
            ->where('active', 1)
            ->order_by_asc('sort_order')
            ->find_many();

        return $rows ?: [];
    }
}
```

## Common Patterns

### Return single record or false

```php
public static function getById(int $id): object|false
{
    self::requireRoles();
    return \ORM::for_table(_table_my_table)->find_one($id);
}
```

### Create or update (upsert)

```php
public static function save(array $post): bool
{
    self::requireRoles();

    $id = (int)($post['id'] ?? 0);

    $record = $id > 0
        ? \ORM::for_table(_table_my_table)->find_one($id)
        : \ORM::for_table(_table_my_table)->create();

    if (!$record) return false;

    $record->name       = $post['name'] ?? '';
    $record->sort_order = (int)($post['sort_order'] ?? 0);
    $record->save();

    return true;
}
```

### Delete

```php
public static function delete(int $id): bool
{
    self::requireRoles();

    $record = \ORM::for_table(_table_my_table)->find_one($id);
    if (!$record) return false;

    $record->delete();
    return true;
}
```

### Join query

```php
public static function getWithUser(int $page_id): array
{
    return \ORM::for_table(_table_my_table)
        ->select(_table_my_table . '.*')
        ->select(_table_users . '.username')
        ->join(_table_users, [
            _table_users . '.id', '=', _table_my_table . '.created_by'
        ])
        ->where(_table_my_table . '.page_id', $page_id)
        ->find_many()
        ->as_array();
}
```

## Checklist

- [ ] `declare(strict_types=1)` at top of file
- [ ] Correct namespace (`Humblee\Model\` or `App\Model\`)
- [ ] `use Humblee\Foundation\Core;` imported if using `Core::auth()`
- [ ] Role check called at the top of any method that touches privileged data
- [ ] All inputs cast to correct types before ORM use (`(int)`, `(bool)`, `trim()`)
- [ ] No raw SQL — only `\ORM::for_table()`
- [ ] Table names use `_table_*` constants, never string literals
- [ ] Return type declared on method signature
- [ ] `find_one()` result checked for `false` before property access
