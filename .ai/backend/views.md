# Backend Views

## Rendering

Views are PHP files — not autoloaded. `Core::view()` renders them:

```php
// Inside a controller action:
// 1. Set public properties (these become view variables)
$this->user = $user_object;
$this->page_list = $pages;

// 2. The dispatcher calls Core::view() after the method returns
//    passing get_object_vars($this) as the variable context
```

Inside the view file, every public property is available as a local variable:

```php
// humblee/views/admin/my-page.php
<?php foreach ($page_list as $page): ?>
    <li><?php echo htmlspecialchars($page->name) ?></li>
<?php endforeach ?>
```

Always escape output with `htmlspecialchars()` unless the value is already trusted HTML from `Parsedown` or `Draw::content()`.

## Admin Layout

Admin views are wrapped in `humblee/views/admin/templates/template.php` which provides the nav, toolbar, and `extra_head_code` injection point.

```php
// In a controller action, to add CSS/JS to <head>:
$this->extra_head_code  = '<link rel="stylesheet" href="' . _app_path . 'humblee/js/admin/my-app/index.css">';
$this->extra_head_code .= '<script type="module" src="' . _app_path . 'humblee/js/admin/my-app/index.js"></script>';
```

Use `type="module"` — Vite outputs ES modules.

## Draw Helper

`Humblee\Foundation\Draw` provides helpers for rendering content blocks on **public-facing pages**.

```php
// Render a content block (outputs HTML)
Draw::content($content_array, 'rich_text');

// Render a specific slot
Draw::content($content_array, 'rich_text_2');

// Render multiple slots at once
Draw::content($content_array, ['hero', 'intro']);
```

`Draw::content()` wraps output in `<div class="cms_block">` if the session user has content role, enabling inline editing. Otherwise it outputs the raw content.

## PHP → Svelte Config Injection

When a view mounts a Svelte SPA, set the config global **before** the module script:

```php
<script>
window.__MY_APP_CONFIG__ = {
    hasAdminRole: <?php echo json_encode((bool)$hasAdminRole) ?>,
    XHR_PATH:     "<?php echo _app_path ?>core-request/",
    WEB_ROOT:     "<?php echo _app_path ?>",
    csrf:         <?php echo json_encode(Crypto::get_hmac_pair()) ?>
};
</script>
<div id="app"></div>
```

Then load the built module:

```php
$this->extra_head_code  = '<link rel="stylesheet" href="' . _app_path . 'humblee/js/admin/my-app/index.css">';
$this->extra_head_code .= '<script type="module" src="' . _app_path . 'humblee/js/admin/my-app/index.js"></script>';
```

The view file provides the `<div id="app">` mount point and the `<script>` config block. The controller sets `extra_head_code` to load the assets.

## View File Locations

| View type | Directory |
|---|---|
| CMS admin pages | `humblee/views/admin/` |
| CMS user (login, profile) | `humblee/views/user/` |
| Email templates | `humblee/views/email/` |
| Admin layout wrapper | `humblee/views/admin/templates/template.php` |
| App-level views | `application/views/` |

Do not modify `humblee/views/` or `application/views/` as a side effect of backend changes.
