# Humblee

A humble PHP framework and CMS

Humblee is for roll-your-own developers and DIY programmers who like to build things their own way.

Humblee covers the boring stuff, like page routing, content managment tools, and user role authorization in an easy to use MVC framework. With the basics out of the way, you are free to ~~toil~~ tinker away on your application's custom functionality.

### Prerequisites

* Apache with .htaccess and mod_rewrite
* PHP 5.3+ w/ cURL and file_get_contents()
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
Composer will create a `vendor` folder containing the following libraries by default:
* Idirom (required) - a simple object-relational mapper (ORM) class for communicating with the database. Learn more at <https://github.com/j4mie/idiorm>
* Paragonie Sodium (recommended if PHP version < 7.2 ) - for encryption and more secure password hashing
* Mailgun (optional) - for sending transactional e-mails, like password recovery. <https://documentation.mailgun.com>
* Twilio (optional) - for sending two-factor authentication (2FA) messages via SMS. <https://www.twilio.com/docs/api>
* Tinify (optional) - Image compression API <http://tinypng.com/developers>
* Parsedown (optional) - Convert markdown plain text to HTML
End with an example of getting some data out of the system or using it for a little demo

4. **Run NPM** to install Javascript and CSS libraries
```
$ cd ~/public
$ npm install
```
NPM will create a `node_modules` folder containing the following required libraries:
* bulma - CSS framework used extensively for the CMS UI. Learn more at <https://bulma.io>
* bulma-tooltip - an extension for bulma to display tooltip information
* nestedSortable - a jQueryUI plugin used for drag/drop functionality in the CMS page manager.

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
2. Update `~/humblee/controllers/core.php`
```
/* comment out or remove these two lines: */
// $uri = (!isset($_path_info) || $_path_info == "" || $_path_info == "public") ? "" : ltrim($_path_info,"/");
// return $uri;

// uncomment these two lines:
$_path_info = preg_split("/\?|\&/",$_path_info); // check for ? or & in url
return (!isset($_path_info[0]) || $_path_info[0] == "") ? "" : ltrim($_path_info[0],"/");
```

**Folder permissions**

Depending on how you installed the application, you may encounter folder permission issues when the application is attempting to save a file to the server.  There are two areas where you may need to use `chown` or `chmod` to update folders necessary for the proper functionality of the system.
1. During installation, in step 5 above, the site attempts to make a new directory in `~/humbleee/configuration/` and add a file with the site's encryption key.  If the installation file throws an error, you may need to temporarily change that folder's permissions.  You can (and should) change it back to at least `755` after the installation has created the file.
2. The site's media manager tool in the CMS saves all files to the `~/storage` folder in the root of the application. This folder must be writable by the website.

## Author

Humblee was created by **Micah Murray** and is a product offering of [Six Eight Interactive LLC](https://sixeightinteractive.com)

## Questions, comments, bugs or security concerns

Please feel free to [contact the author](https://sixeightinteractive.com/contact).


## License

This project is licensed under the [MIT License](https://opensource.org/licenses/MIT) and is provided "AS IS", without warranty of any kind.

