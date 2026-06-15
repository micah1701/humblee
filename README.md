# Humblee - <https://humblee.app>

A humble PHP framework and CMS

Humblee is for roll-your-own developers and DIY programmers who like to build things their own way.

Humblee covers the boring stuff, like page routing, content managment tools, and user role authorization in an easy to use MVC framework. With the basics out of the way, you are free to ~~toil~~ tinker away on your application's custom functionality.

### Prerequisites

- Apache with mod_rewrite with .htaccess and/or configurable v-host
- NGINX and IIS are also supported but required additional configuration for pathing
- MySQL or PostgreSQL Database
- Node.js runtime for `npm` package manager and `build` commands
- composer (php package manager)

### Installing

1. **Clone this repo**

```
git clone https://github.com/micah1701/humblee
```

2. **Configure environment**. Open `~/humblee/configuration/env_dev.php` and add your database credentials. You can also make copies of this file for different environments, such as development, QA and production. To use a different configuration, link to the file in `~/humblee/configuration/config.php`

3. **Run Composer** in the `~/humblee` directory to install the required and optional vendor libraries

```
$ cd ~/humblee
$ composer install
```

Composer will create a `vendor` folder containing the following libraries:

- Idiorm (required) - a simple object-relational mapper (ORM) class for communicating with the database. Learn more at <https://github.com/j4mie/idiorm>
- Twilio (optional) - for sending two-factor authentication (2FA) messages via SMS. <https://www.twilio.com/docs/api>
- Tinify (optional) - Image compression API <http://tinypng.com/developers>
- Parsedown (optional) - Convert markdown plain text to HTML

Note: PHP 8.3+ includes the sodium extension natively, so no separate encryption library is needed.

4. **Run NPM** from the project root to install all dependencies

```
$ npm run setup
```

This installs:

- In `~/public/node_modules/`: bulma, bulma-tooltip, nestedSortable — CSS/JS libraries served directly by the CMS admin UI
- In `~/frontend/node_modules/`: Vite, Svelte, TypeScript — build tools for the modern frontend tool apps

To build the frontend tool apps (Svelte/React SPAs such as the media manager) after making changes to their source:

```
$ npm run build
```

Built assets are committed to the repo, so `npm run build` is only required when actively developing a frontend tool — not for standard deployments. A plain `npm run setup` is all a production server needs.

5. **Run the installer to create database tables and master user**. In your browser, navigate to //your-site.tld/humblee/install. On first run, this page will install the necessary tables and default content then prompt you to create a user account.

Note that the install file lives in the subdirectory `humblee` of the `~/public` directory. While all of the application folders reside in your site's webroot directory, the `.htaccess` file in the root Humblee application directory forwards all traffic to the public folder, effectively making it the root.

### Installation considerations

**Running PHP in CGI/FastCGI mode**

Humblee is configured out of the box to run on Apache's `mod_php` module. If you see the message `No input file specified` it is more than likely that you are running PHP in CGI or FastCGI mode. There are two simple steps to configure Humblee to work in this environment.

1. Update `~/public/.htaccess` (note, this is different then the `.htaccess` file in the root of the application.)

```
#comment out or remove this line:
#RewriteRule ^(.*)$ index.php/$1 [L]

#replace with these two lines:
RewriteRule . /index.php [L]
RewriteRule ^index.php/(.*)$ [L]
```

2. Update `~/humblee/src/Foundation/Core.php` in the `getURI()` method — replace the final `$uri` assignment with:

```php
$_path_info = preg_split("/\?|\&/", $_path_info); // strip query string
$uri = (!isset($_path_info[0]) || $_path_info[0] === "" || $_path_info[0] === "public") ? "" : ltrim($_path_info[0], "/");
return $uri;
```

**PHP runtime settings (upload size, memory, etc.)**

The preferred place to configure PHP runtime settings (such as `upload_max_filesize` and `post_max_size`) is your server's main `php.ini` file, since those values apply globally and survive `.htaccess` changes.

If you need per-project overrides, the right approach depends on your PHP mode:

- **CGI / FastCGI (php-fpm):** Use a `.user.ini` file placed in your web root (`public/.user.ini`). PHP-FPM reads this file automatically and respects directives like:

  ```ini
  upload_max_filesize = 50M
  post_max_size = 52M
  ```

  Note: PHP-FPM caches `.user.ini` for 5 minutes by default, so changes may take a moment to take effect.

  > **Warning:** Do _not_ use `php_value` directives in `.htaccess` when running CGI/FastCGI. Apache cannot pass these to the FPM process and will fail to parse the `.htaccess` file entirely, which prevents your rewrite rules from running and typically causes a 403 Forbidden error on the site root.

- **mod_php (non-CGI):** `php_value` directives in `.htaccess` work as expected:
  ```apache
  php_value upload_max_filesize 50M
  php_value post_max_size 52M
  ```

**Folder permissions**

Depending on how you installed the application, you may encounter folder permission issues when the application is attempting to save a file to the server. There are two areas where you may need to use `chown` or `chmod` to update folders necessary for the proper functionality of the system.

1. During installation, in step 5 above, the site attempts to make a new directory in `~/humbleee/configuration/` and add a file with the site's encryption key. If the installation file throws an error, you may need to temporarily change that folder's permissions. You can (and should) change it back to at least `755` after the installation has created the file.
2. The site's media manager tool in the CMS saves all files to the `~/storage` folder in the root of the application. This folder must be writable by the website. On Ubuntu/Debian with Apache, the web server runs as `www-data`. Give that user ownership of the folder:

```bash
sudo chown -R www-data:www-data /path/to/storage
sudo chmod -R 755 /path/to/storage
```

If you also need your own user account to manage files in that directory (e.g. via CLI), use shared group ownership with the setgid bit so new files inherit the `www-data` group automatically:

```bash
sudo chown -R youruser:www-data /path/to/storage
sudo chmod -R 775 /path/to/storage
sudo chmod g+s /path/to/storage
```

To confirm Apache is running as `www-data`: `ps aux | grep apache2 | grep -v root`

## Media file encryption

Humblee supports optional at-rest encryption for media files stored in the `/storage` directory. Encryption is performed per-file from the admin media manager and can be toggled on or off at any time.

**Algorithm:** libsodium `secretbox` (XSalsa20-Poly1305 authenticated encryption). This provides confidentiality and integrity in a single primitive using PHP's built-in sodium extension (no external library required).

**Key:** A 32-byte symmetric key is generated automatically by the installer and written to `~/humblee/configuration/crypto/key.php`. This file is excluded from version control. **Do not lose this file** — it is the only way to decrypt your files. Back it up securely alongside your database.

**Nonce handling:** Each file gets a fresh random 24-byte nonce at encryption time. The nonce is prepended to the ciphertext and stored together in the file itself — no database column is required to hold it. The encrypted file on disk is `[24-byte nonce][ciphertext]`; decryption extracts the nonce from the first 24 bytes automatically.

**Database:** The `humblee_media` table tracks encryption state via the `encrypted` column (`0` = plaintext on disk, `1` = encrypted on disk). When a file is served through `/media/{id}/`, the CMS detects the flag, decrypts the payload in memory, and streams the plaintext to the browser — the file on disk is never written back as plaintext during a read.

## Content block slots

Templates can include **multiple instances of the same content block type** on a single page using named slots managed through the `humblee_template_blocks` table.

Each slot has a `label` (shown to editors in the page editor) and an auto-generated `slot_key` (used to retrieve content in view templates). The first slot of a given type inherits the block's `objectkey` directly (e.g. `rich_text`); additional slots append a counter (`rich_text_2`, `rich_text_3`, …).

**In view templates**, all live content for a page is returned by `Content::findContent()` as an associative array keyed by `slot_key`:

```php
// e.g. two rich_text blocks and a hero block on the same page:
$content['rich_text']->content   // first rich text slot
$content['rich_text_2']->content // second rich text slot
$content['hero']->content        // hero block
```

Slots are managed in the **Templates** section of the admin UI. Adding a block type to a template creates a slot row; removing it deletes the slot (any content saved to that slot is orphaned rather than deleted). The `slot_key` is immutable after a slot is first saved — renaming uses the `label` field, not the key.

## Admin UI — Bulma CSS framework

The CMS admin interface uses [Bulma 1.0.4](https://bulma.io) as its CSS framework. Bulma is installed as an npm dependency in `public/` and served directly from `node_modules/` — no build step is needed for Bulma itself.

### File locations

| File                                                               | Purpose                                        |
| ------------------------------------------------------------------ | ---------------------------------------------- |
| `public/node_modules/bulma/css/bulma.css`                          | Bulma core CSS (served directly)               |
| `public/node_modules/bulma-tooltip/dist/css/bulma-tooltip.min.css` | Tooltip extension (served directly)            |
| `public/humblee/css/admin/theme-light.css`                         | Light mode CSS variable overrides              |
| `public/humblee/css/admin/theme-dark.css`                          | Dark mode CSS variable overrides               |
| `public/humblee/css/admin/*.scss`                                  | Admin UI component styles (compiled to `.css`) |

### Customizing Bulma's appearance

Bulma 1.x uses CSS custom properties (variables) for all colors and spacing. To change the look of the admin UI, edit the theme files rather than modifying Bulma's source:

- **Colors, contrast, backgrounds:** override `--bulma-*` variables in `theme-light.css` or `theme-dark.css`
- **Component-specific styles:** add rules to the relevant SCSS file in `public/humblee/css/admin/` and recompile

### Compiling admin SCSS

After editing any `.scss` file in `public/humblee/css/admin/`:

```bash
npm run build:css
```

This delegates to `npm --prefix public run build:css`, which runs `sass` on each admin SCSS file and outputs the compiled `.css` alongside it.

## Running tests

Humblee uses [PHPUnit](https://phpunit.de) for unit testing. Tests live in `humblee/tests/` and cover core PHP functionality that has no database dependency (encryption, CSRF/HMAC tokens, and similar pure-logic classes).

### Requirements

PHPUnit is a **dev-only** dependency — it is installed automatically when you run `composer install` (or `composer update`) without the `--no-dev` flag. Production deployments that use `composer install --no-dev` will not include it.

Your PHP CLI version determines which PHPUnit major version is used:

| PHP CLI version | PHPUnit version installed        |
| --------------- | -------------------------------- |
| 8.3.x           | `^12.0` (current config)         |
| 8.4.1+          | bump to `^13.0` in `require-dev` |

Tests for database-backed models (e.g. `ContentTest`) use an **in-memory SQLite database** so they run without a MySQL server. This requires the `pdo_sqlite` and `sqlite3` PHP extensions to be enabled in your CLI `php.ini`. They ship with PHP 8.3+ on all platforms but are often disabled by default.

To check whether they are active:

```bash
php -r "print_r(PDO::getAvailableDrivers());"
```

`sqlite` should appear in the output. If it does not, open your CLI `php.ini` (find its path with `php --ini`) and uncomment these two lines:

```ini
extension=pdo_sqlite
extension=sqlite3
```

On Windows, the corresponding DLLs (`php_pdo_sqlite.dll` and `php_sqlite3.dll`) are included in the standard PHP distribution and just need to be un-commented — no separate install is required.

### Running the test suite

From the `humblee/` directory:

```bash
cd humblee
vendor/bin/phpunit
```

On Windows (PowerShell):

```powershell
cd humblee
vendor\bin\phpunit
```

A passing run looks like:

```
PHPUnit 12.x.x by Sebastian Bergmann and contributors.

Runtime:       PHP 8.3.x
Configuration: C:\...\humblee\phpunit.xml

........................................                          40 / 40 (100%)

Time: 00:00.195, Memory: 16.00 MB

OK (40 tests, 59 assertions)
```

### Code coverage report

If you have Xdebug installed (the project already includes it in this environment), you can generate a coverage report in the terminal:

```bash
vendor/bin/phpunit --coverage-text
```

Or as an HTML report you can browse:

```bash
vendor/bin/phpunit --coverage-html coverage/
```

### Test structure

```
humblee/
├── phpunit.xml               # PHPUnit configuration
└── tests/
    ├── bootstrap.php         # Defines constants/env before any test runs
    ├── fixtures/
    │   └── crypto/
    │       └── key.php       # Test-only encryption key (safe to commit)
    └── Model/
        ├── ContentTest.php   # Tests for Humblee\Model\Content (uses in-memory SQLite)
        └── CryptoTest.php    # Tests for Humblee\Model\Crypto
```

Tests mirror the `src/` directory layout. To add tests for a new class:

1. Create `tests/<Namespace>/<ClassName>Test.php`
2. Extend `PHPUnit\Framework\TestCase`
3. Name test methods starting with `test_` (snake_case is fine)

Database-backed model tests (like `ContentTest`) use an in-memory SQLite database so they run without a MySQL server. Pure-logic tests (like `CryptoTest`) have no database dependency at all.

### Upgrading to PHPUnit 13

Once your PHP CLI is updated to 8.4.1 or later, change one line in `humblee/composer.json`:

```json
"require-dev": {
    "phpunit/phpunit": "^13.0"
}
```

Then run `composer update phpunit/phpunit` — no test changes required.

## Documentation

Full documentation can be found at <https://humblee.app>

## Author

Humblee was created by **Micah Murray** and is a product offering of [Creative Ad-Hoc Solutions](https://creativeadhocsolutions.com)

## Questions, comments, bugs or security concerns

Please feel free to [contact the author](https://creativeadhocsolutions.com).

## License

This project is licensed under the [MIT License](https://opensource.org/licenses/MIT) and is provided "AS IS", without warranty of any kind.
