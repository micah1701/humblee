# Skill: Add an AJAX Endpoint

## Choose the Right Controller

| Use case | URI prefix | Controller file |
|---|---|---|
| Core CMS feature | `core-request/{group}/{action}` | `humblee/src/Controller/Requests/{Group}.php` |
| App-level custom | `request/{action}` | `application/Controller/Request.php` |

## Option A: App-Level Endpoint (simpler)

Add a method to `application/Controller/Request.php`:

```php
public function myAction(): void
{
    $this->require_hmac();
    $this->require_role('login');  // or ['admin', 'content'], etc.

    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) {
        Core::json(['error' => 'Invalid id'], 400);
    }

    $record = \ORM::for_table(_table_pages)->find_one($id);
    if (!$record) {
        Core::json(['error' => 'Not found'], 404);
    }

    Core::json([
        'status' => 'ok',
        'data'   => $record->as_array(),
        'csrf'   => \Humblee\Model\Crypto::get_hmac_pair(),
    ]);
}
```

Called via: `POST /request/myAction`

## Option B: Core AJAX Endpoint (new action in existing group)

Add a static method to the appropriate handler in `humblee/src/Controller/Requests/`:

```php
// humblee/src/Controller/Requests/Content.php

public static function myAction(\Humblee\Controller\Xhr $controller): void
{
    $controller->require_hmac();
    $controller->require_role(['content', 'developer']);

    $page_id = (int)($_POST['page_id'] ?? 0);
    if ($page_id <= 0) {
        Core::json(['error' => 'Invalid page_id'], 400);
    }

    $result = \Humblee\Model\Content::doSomething($page_id);

    Core::json([
        'status' => 'ok',
        'result' => $result,
        'csrf'   => \Humblee\Model\Crypto::get_hmac_pair(),
    ]);
}
```

Then wire it in the `dispatch()` method's `match()` block in `humblee/src/Controller/Request.php`:

```php
match ($action) {
    'save'     => Content::save($this),
    'revisions'=> Content::listRevisions($this),
    'myAction' => Content::myAction($this),     // ← add this line
    default    => Core::json(['error' => 'Not found'], 404),
};
```

Called via: `POST /core-request/content/myAction`

## Option C: New Group Entirely

1. Create `humblee/src/Controller/Requests/MyGroup.php` with a `dispatch()` and action methods
2. Add to `humblee/src/Controller/Request.php` dispatch match:
   ```php
   'my_group' => Requests\MyGroup::dispatch($this, $action),
   ```
3. Add `use Humblee\Controller\Requests\MyGroup;` to the import block

## Checklist

- [ ] `require_hmac()` is the first call
- [ ] `require_role()` is the second call
- [ ] All POST integers are cast with `(int)` and validated `> 0`
- [ ] Response includes a fresh `csrf` pair
- [ ] Error responses use appropriate HTTP status codes (400, 403, 404, 500)
- [ ] No raw SQL — all queries use `\ORM::for_table()`
- [ ] No user-controlled string used directly in query without validation

## Svelte Service (Frontend Side)

```ts
// src/lib/services/myApi.ts
export async function callMyAction(
  xhrPath: string,
  csrf: { key: string; token: string },
  id: number
) {
  const data = new FormData()
  data.append('id', String(id))
  data.append('hmac_key', csrf.key)
  data.append('hmac_token', csrf.token)

  const res = await fetch(xhrPath + 'my_group/myAction', { method: 'POST', body: data })
  if (!res.ok) throw new Error(`HTTP ${res.status}`)
  return res.json()
}
```
