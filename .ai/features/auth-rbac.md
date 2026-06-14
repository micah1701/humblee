# Authentication & RBAC

## Overview

Auth is session-based. Roles are cached in session after first lookup. All auth checks flow through `Core::auth()`.

## Session Structure

```php
$_SESSION[session_key] = [
    'user_id'    => 42,
    'username'   => 'alice',
    'has_roles'  => ['admin', 'content', 'developer'],  // cached after first check
    'csrf_token' => '...',  // BLAKE2b hash used for HMAC signing
];
```

`session_key` is a constant defined in `humblee/configuration/env_*.php`.

## Role Check

```php
// Single role
Core::auth('login')     // returns true if user has 'login' role

// Multiple roles (OR — true if user has at least one)
Core::auth(['admin', 'developer'])

// Common role groups
Core::auth('login')                            // any logged-in user
Core::auth(['admin', 'developer'])             // admin panel access
Core::auth(['content', 'publish', 'developer']) // content editing
Core::auth('media')                            // media library
```

`Core::auth()` returns `bool`. It populates `$_SESSION[session_key]['has_roles']` on first call (DB query), then reads from cache on subsequent calls.

## Known Roles

| Role | Access level |
|---|---|
| `login` | Any authenticated user |
| `admin` | CMS admin UI |
| `content` | View/edit page content |
| `publish` | Publish/unpublish content |
| `developer` | Full access (equivalent to admin + content) |
| `media` | Media library upload/manage |

## Controller Auth Pattern

### Admin controller (redirects to login on failure)

```php
public function __construct()
{
    if (!Core::auth(['admin', 'developer'])) {
        header('Location: ' . _app_path . 'user/login');
        exit;
    }
}
```

### XHR controller (returns 403 JSON on failure)

```php
public function myEndpoint(): void
{
    $this->require_hmac();
    $this->require_role(['admin', 'content']);  // exits with 403 on fail
    // ... handle request
}
```

### Model (internal guard)

```php
private static function requireRoles(): void
{
    if (!Core::auth(['content', 'publish', 'developer'])) {
        http_response_code(403);
        exit;
    }
}
```

## Login Flow

`Users::logIn(string $username, string $password): bool`

1. Fetch user by username
2. Try `password_verify($password . '-' . $user_id, $user->password)` (Argon2ID)
3. On failure, try legacy `stringToSaltedHash()` (sodium BLAKE2b)
4. If legacy succeeds: re-hash to Argon2ID and save (auto-upgrade, one-time)
5. On success: populate session, call `Core::cacheUserRoles()`

## Frontend Role Checks

PHP injects role booleans into the window config:

```php
window.__MY_CONFIG__ = {
    hasAdminRole:   <?php echo json_encode((bool)Core::auth(['admin', 'developer'])) ?>,
    hasContentRole: <?php echo json_encode((bool)Core::auth(['content', 'publish', 'developer'])) ?>,
};
```

The Svelte app uses these to show/hide UI elements. The server still enforces roles on every AJAX call — client-side role checks are UI-only.

## "Remember Me"

Set a signed cookie with `Users::setRememberMeCookie()`. On load, `Users::checkRememberMeCookie()` re-populates the session if the session has expired.
