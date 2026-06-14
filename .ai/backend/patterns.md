# Backend Patterns — Core Idioms

These are the recurring patterns used throughout the PHP codebase. Match them exactly when adding new code.

## ORM Queries

All DB access uses `\ORM::for_table()`. Table names come from `_table_*` constants — never string literals.

```php
// Fetch one record
$user = \ORM::for_table(_table_users)
    ->where('id', $user_id)
    ->find_one();

// Fetch many records
$pages = \ORM::for_table(_table_pages)
    ->where('parent_id', $parent_id)
    ->order_by_asc('sort_order')
    ->find_many();

// Join
$roles = \ORM::for_table(_table_user_roles)
    ->distinct()
    ->select(_table_user_roles . '.role_id')
    ->join(_table_roles, [_table_roles . '.id', '=', _table_user_roles . '.role_id'])
    ->where(_table_user_roles . '.user_id', $user_id)
    ->find_many();

// Create
$record = \ORM::for_table(_table_content)->create();
$record->page_id = $page_id;
$record->content = $value;
$record->save();

// Upsert: find-or-create
$record = \ORM::for_table(_table_content)
    ->where('page_id', $page_id)
    ->where('content_type_id', $type_id)
    ->find_one();

if (!$record) {
    $record = \ORM::for_table(_table_content)->create();
}
$record->content = $value;
$record->save();

// Delete
$record = \ORM::for_table(_table_content)->find_one($id);
if ($record) {
    $record->delete();
}
```

## Auth Check (Role-Based)

```php
// Check one role (returns bool)
if (!Core::auth('admin')) {
    header('Location: ' . _app_path . 'user/login');
    exit;
}

// Check multiple roles (OR — user must have at least one)
if (!Core::auth(['admin', 'content', 'developer'])) {
    exit("Unauthorized");
}
```

Roles are cached in `$_SESSION[session_key]['has_roles']` after first check. No extra DB queries.

## CSRF / HMAC

### In PHP views (forms)
```php
<?php
$hmac = Crypto::get_hmac_pair();
?>
<input type="hidden" name="hmac_key" value="<?php echo $hmac['key'] ?>">
<input type="hidden" name="hmac_token" value="<?php echo $hmac['token'] ?>">
```

### In XHR controllers
```php
// Validate before processing POST
$this->require_hmac();     // Issues 401 and exits on failure
$this->require_role('admin');  // Issues 403 and exits on failure
```

### In model or standalone code
```php
if (!Crypto::check_hmac_pair($_POST['hmac_key'], $_POST['hmac_token'])) {
    http_response_code(401);
    exit;
}
```

### Refreshing HMAC for client
```php
// Return a fresh HMAC pair so the client can make the next request
$new_hmac = Crypto::get_hmac_pair();
Core::json(['status' => 'ok', 'hmac' => $new_hmac]);
```

## JSON Response

```php
// Success
Core::json(['status' => 'ok', 'data' => $result]);

// Success with HTTP status
Core::json(['status' => 'created'], 201);

// Error
Core::json(['error' => 'Not found'], 404);
```

`Core::json()` is a static method on `Humblee\Foundation\Core`. It handles UTF-8 encoding, sets `Content-Type: application/json`, and exits. It is available everywhere — not just in Xhr subclasses.

## Encryption

```php
// Encrypt binary/text (returns string: [24-byte nonce][ciphertext])
$encrypted = Crypto::encrypt($plaintext);

// Decrypt (nonce is extracted from the payload automatically)
$plaintext = Crypto::decrypt($encrypted);

// On failure, both return false — always check
if ($plaintext === false) {
    $this->json(['error' => 'Decryption failed'], 500);
}
```

The nonce is embedded — never store it separately.

## Markdown

```php
$parsedown = new \Parsedown();
$html = $parsedown->text($markdown_string);
```

## Constants in Use

| Constant | Example Value |
|---|---|
| `_app_path` | `/` |
| `session_key` | `humblee` |
| `_table_users` | `humblee_users` |
| `_table_content` | `humblee_content` |
