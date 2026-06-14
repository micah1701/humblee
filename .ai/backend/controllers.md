# Backend Controllers

## Class Hierarchy

```
Humblee\Controller\Xhr           ← Base for all AJAX controllers
  ├── Humblee\Controller\Request ← Core CMS AJAX (core-request/)
  └── App\Controller\Request     ← App-level AJAX (request/)

Humblee\Controller\Admin         ← Admin UI pages (admin/)
Humblee\Controller\User          ← Auth pages (user/)
Humblee\Controller\Media         ← Media serving (media/)
Humblee\Controller\Template      ← Public page rendering (catch-all)
```

## Xhr Base Controller

`humblee/src/Controller/Xhr.php` — all AJAX controllers extend this.

**Constructor** sets no-cache headers automatically.

**Inherited methods available in every Xhr subclass:**

```php
$this->require_role('admin');               // 403 + exit if role missing
$this->require_role(['admin', 'content']);  // 403 + exit if none match
$this->require_hmac();                      // 401 + exit if HMAC invalid
```

**JSON responses** use the static method on `Core`, available anywhere (not just Xhr subclasses):

```php
Core::json($array, $status = 200);         // JSON encode + exit
```

## Admin Controller

`humblee/src/Controller/Admin.php`

- Constructor calls `Core::auth(['admin', 'developer'])` — redirects to login if unauthorized
- Action methods set **public properties** that become view variables
- Template renders via `Core::view()` which calls `get_object_vars($this)` to extract them

```php
public function pages(): void
{
    $this->page_list = Pages::getAll();   // public property = view variable
    $this->page_title = 'Pages';
    // Core::view() is called by the dispatcher after method returns
}
```

## Request Controller (AJAX Dispatcher)

`humblee/src/Controller/Request.php` (Humblee core)

Dispatches by the **third URI segment** to a static method on a sub-controller class:

```php
match ($group) {
    'content'         => Requests\Content::dispatch($this, $action),
    'media_files'     => Requests\MediaFiles::dispatch($this, $action),
    'pages'           => Requests\Pages::dispatch($this, $action),
    'templates'       => Requests\Templates::dispatch($this, $action),
    'users'           => Requests\Users::dispatch($this, $action),
    'blocks'          => Requests\Blocks::dispatch($this, $action),
    'personalization' => Requests\Personalization::dispatch($this, $action),
};
```

Sub-controllers live in `humblee/src/Controller/Requests/`. Each has static methods that receive `$controller` (the Xhr instance) for calling `require_hmac()` and `require_role()`. JSON responses call `Core::json()` directly.

## App-Level Request Controller

`application/Controller/Request.php`

Extends `Humblee\Controller\Xhr`. Add custom app AJAX endpoints here. Dispatches on **second URI segment** (`request/{action}`).

```php
namespace App\Controller;

use Humblee\Foundation\Core;
use Humblee\Controller\Xhr;

class Request extends Xhr
{
    public function myEndpoint(): void
    {
        $this->require_hmac();
        $this->require_role('login');

        $result = \ORM::for_table(_table_users)
            ->where('id', $_POST['user_id'])
            ->find_one();

        Core::json(['data' => $result->as_array()]);
    }
}
```

## Template Controller (Public Pages)

`humblee/src/Controller/Template.php`

Handles all URIs not matched by another prefix. Resolves the URL to a page record, loads content blocks via `Content::findContent()`, and renders the matching app view template.

## Adding an Action to Admin

1. Add a `public function myPage(): void` method to `Admin.php`
2. Set public properties for view data
3. Create `humblee/views/admin/my-page.php` to render them
4. If the page needs a Svelte SPA, set `$this->extra_head_code` (see [backend/views.md](views.md))
