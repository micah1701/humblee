# Security Reference

## CSRF / HMAC

Every state-changing POST is protected by a signed HMAC pair. This is not optional.

### How it works

1. `Crypto::get_hmac_pair()` returns `['key' => $random_hex, 'token' => $signed_token]`
   - `key` = random hex nonce
   - `token` = `base64(hash_hmac('sha256', $key, $csrf_session_token))`
   - The CSRF session token is a BLAKE2b hash stored in `$_SESSION[session_key]['csrf_token']`
2. Client sends both `hmac_key` and `hmac_token` in every POST
3. Server validates with `Crypto::check_hmac_pair($key, $token)` — uses timing-safe comparison

### In XHR controllers

```php
$this->require_hmac();  // Must be called before reading POST data
```

### In PHP form views

```php
<?php $hmac = \Humblee\Model\Crypto::get_hmac_pair() ?>
<input type="hidden" name="hmac_key"   value="<?php echo $hmac['key'] ?>">
<input type="hidden" name="hmac_token" value="<?php echo $hmac['token'] ?>">
```

### Returning a fresh pair to the client

After every successful AJAX action, return a new pair so the next request can proceed:

```php
Core::json([
    'status' => 'ok',
    'csrf'   => \Humblee\Model\Crypto::get_hmac_pair(),
]);
```

## Input Validation

### URL Slugs

Never use a URL segment in a query without validating it:

```php
// Correct
if (!preg_match('/^[\w\-\.]+$/', $slug)) {
    http_response_code(400);
    exit;
}

// Then safe to use in ORM (ORM also parameterizes, but validate first)
$page = \ORM::for_table(_table_pages)->where('slug', $slug)->find_one();
```

### Integer IDs

Cast to int before use:

```php
$id = (int)$_POST['id'];
if ($id <= 0) { Core::json(['error' => 'Invalid ID'], 400); }
```

### POST Data Whitelist

Only read fields you explicitly expect. Do not pass `$_POST` directly to ORM methods.

## Output Escaping

- PHP views: always `htmlspecialchars($value, ENT_QUOTES, 'UTF-8')` for user-controlled strings
- Trusted HTML (from Parsedown, Draw::content): output directly
- JSON responses: `json_encode()` escapes by default

## SQL Injection

All queries use Idiorm ORM which parameterizes all values. Never concatenate user input into a query string. The only raw SQL is in `Tools::CRUD()` column names — those are validated against a whitelist before use.

## Authentication Checks Order

In any XHR endpoint, always in this order:
1. `$this->require_hmac()` — validate CSRF first
2. `$this->require_role($role)` — then check auth
3. Read and validate POST data
4. Perform the action

Never check roles before HMAC — the HMAC must be validated even if the role check would also fail.

## Encryption

Use `Crypto::encrypt()` / `Crypto::decrypt()` for data at rest (media files). Never roll your own crypto.

- Algorithm: XSalsa20-Poly1305 (authenticated encryption — detects tampering)
- Key: 32-byte symmetric key in `humblee/configuration/crypto/key.php` (never committed)
- Nonce: 24 bytes, randomly generated per encryption, prepended to ciphertext

Always check return values — both methods return `false` on failure.

## Forbidden Patterns

| Pattern | Why |
|---|---|
| `eval()` | Code injection |
| `preg_replace('/e', ...)` | Code execution via regex |
| `include($_GET[...])` | Path traversal / LFI |
| Raw `$_POST` to SQL | SQL injection |
| Hardcoded secrets | Key exposure |
| MD5/SHA1 for passwords | Broken |
| `serialize()` on user input | Object injection |
