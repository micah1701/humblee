# Skill: Add a New Admin Page

Admin pages are server-rendered PHP pages under `/admin/{method}`. They may optionally mount a Svelte SPA.

## Step 1 — Add Method to Admin Controller

`humblee/src/Controller/Admin.php`

```php
public function myPage(): void
{
    // Auth already enforced in __construct() — no need to re-check here
    // unless this page needs stricter roles than ['admin', 'developer']

    // Set public properties — these become view variables
    $this->page_title  = 'My Page';
    $this->items       = \Humblee\Model\MyModel::getAll();

    // If this page uses a Svelte SPA, load the assets:
    $this->extra_head_code  = '<link rel="stylesheet" href="' . _app_path . 'humblee/js/admin/my-app/index.css">';
    $this->extra_head_code .= '<script type="module" src="' . _app_path . 'humblee/js/admin/my-app/index.js"></script>';
}
```

URL: `/admin/myPage`

## Step 2 — Create the View

`humblee/views/admin/my-page.php`

For a pure PHP view:

```php
<div class="content">
    <h1 class="title"><?php echo htmlspecialchars($page_title) ?></h1>
    <ul>
        <?php foreach ($items as $item): ?>
            <li><?php echo htmlspecialchars($item->name) ?></li>
        <?php endforeach ?>
    </ul>
</div>
```

For a Svelte SPA view (inject config + mount point):

```php
<script>
window.__MY_APP_CONFIG__ = {
    hasAdminRole: <?php echo json_encode((bool)\Humblee\Foundation\Core::auth(['admin', 'developer'])) ?>,
    XHR_PATH:     "<?php echo _app_path ?>core-request/",
    WEB_ROOT:     "<?php echo _app_path ?>",
    csrf:         <?php echo json_encode(\Humblee\Model\Crypto::get_hmac_pair()) ?>,
    items:        <?php echo json_encode($items) ?>
};
</script>
<div id="app"></div>
```

## Step 3 — Wire the View to the Controller

The Admin dispatcher automatically looks for a view at `humblee/views/admin/{method}.php`. The naming must match: `public function myPage()` → `humblee/views/admin/my-page.php` (camelCase method → kebab-case file).

Verify the dispatcher's view-path derivation logic in `humblee/src/Controller/Admin.php` to confirm the exact slug transformation used — it may vary.

## Step 4 — Add Nav Entry (if needed)

Admin navigation is in `humblee/views/admin/templates/template.php`. Add a `<li>` to the appropriate nav section:

```html
<li>
    <a href="<?php echo _app_path ?>admin/myPage">My Page</a>
</li>
```

## Checklist

- [ ] Method is `public` and returns `void`
- [ ] Public properties only carry serializable data (strings, arrays, ORM objects)
- [ ] View file path matches method name (check dispatcher convention)
- [ ] Svelte SPA: window config injected before `<div id="app">`
- [ ] Svelte SPA: `extra_head_code` uses `type="module"` for script tag
- [ ] HTML output uses `htmlspecialchars()` for user-controlled values
- [ ] If new data access needed: implement in a Model method, not inline in controller
