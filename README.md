# Humblee - <https://humblee.app>

A humble PHP framework and CMS

Humblee is for roll-your-own developers and DIY programmers who like to build things their own way.

Humblee covers the boring stuff, like page routing, content managment tools, and user role authorization in an easy to use MVC framework. With the basics out of the way, you are free to ~~toil~~ tinker away on your application's custom functionality.

### Prerequisites

* Apache with .htaccess and mod_rewrite
* PHP 8.3+
* MySQL Database
* npm
* composer


### Installing


1. **Clone this repo**
```
git clone https://github.com/micah1701/humblee
```

2. **Configure environment**. Open `~/humblee/configuration/env_dev.php` and add your database credentials.  You can also make copies of this file for different environments, such as development, QA and production. To use a different configuration, link to the file in `~/humblee/configuration/config.php`

3. **Run Composer** in the `~/humblee` directory to install the required and optional vendor libraries
```
$ cd ~/humblee
$ composer install
```
Composer will create a `vendor` folder containing the following libraries:
* Idiorm (required) - a simple object-relational mapper (ORM) class for communicating with the database. Learn more at <https://github.com/j4mie/idiorm>
* Twilio (optional) - for sending two-factor authentication (2FA) messages via SMS. <https://www.twilio.com/docs/api>
* Tinify (optional) - Image compression API <http://tinypng.com/developers>
* Parsedown (optional) - Convert markdown plain text to HTML

Note: PHP 8.3+ includes the sodium extension natively, so no separate encryption library is needed.

4. **Run NPM** from the project root to install all dependencies

```
$ npm run setup
```

This installs:
* In `~/public/node_modules/`: bulma, bulma-tooltip, nestedSortable — CSS/JS libraries served directly by the CMS admin UI
* In `~/frontend/node_modules/`: Vite, Svelte, TypeScript — build tools for the modern frontend tool apps

To build the frontend tool apps (Svelte/React SPAs such as the media manager) after making changes to their source:
```
$ npm run build
```

Built assets are committed to the repo, so `npm run build` is only required when actively developing a frontend tool — not for standard deployments. A plain `npm run setup` is all a production server needs.

5. **Run the installer to create database tables and master user**. In your browser, navigate to //your-site.tld/humblee/install.  On first run, this page will install the necessary tables and default content then prompt you to create a user account.

Note that the install file lives in the subdirectory `humblee` of the `~/public` directory.  While all of the application folders reside in your site's webroot directory, the `.htaccess` file in the root Humblee application directory forwards all traffic to the public folder, effectively making it the root.

### Installation considerations
**Running PHP in CGI/FastCGI mode**

Humblee is configured out of the box to run on Apache's `mod_php` module.  If you see the message `No input file specified` it is more than likely that you are running PHP in CGI or FastCGI mode.  There are two simple steps to configure Humblee to work in this environment.
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

**Folder permissions**

Depending on how you installed the application, you may encounter folder permission issues when the application is attempting to save a file to the server.  There are two areas where you may need to use `chown` or `chmod` to update folders necessary for the proper functionality of the system.
1. During installation, in step 5 above, the site attempts to make a new directory in `~/humbleee/configuration/` and add a file with the site's encryption key.  If the installation file throws an error, you may need to temporarily change that folder's permissions.  You can (and should) change it back to at least `755` after the installation has created the file.
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

## Documentation
Full documentation can be found at <https://humblee.app>

## Author

Humblee was created by **Micah Murray** and is a product offering of [Six Eight Interactive](https://sixeightinteractive.com)

## Questions, comments, bugs or security concerns

Please feel free to [contact the author](https://sixeightinteractive.com/contact).


## License

This project is licensed under the [MIT License](https://opensource.org/licenses/MIT) and is provided "AS IS", without warranty of any kind.

