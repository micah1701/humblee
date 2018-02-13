# Humblee

A humble PHP framework and CMS

Humblee is for roll-your-own developers and DYI programmers who like to build things their own way.

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

2. **Configure environment**. Open `/humblee/configuration/env_dev.php` and add your database credentials.  You can also make copies of this file for different environments, such as development, QA and production. To use a different configuration, link to the file in `humblee/configuration/config.php`

3. **Run Composer** in the `/humblee` director to install the required and optional vendor libraries
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

4. **Create database tables and master user**. In your browser, navigate to https://your-site.tld/**humblee/install**.  On first run, this page will check install the necessary tables and default content and then prompt you to create a user account.

Note that the install file lives in the subdirectory `humblee` in the `/public` directory.  While all of the application folders reside in your site's htdocs directory, the `.htaccess` file in the root folder forwards all traffic to the public folder effectively, making it the root.


## Author

Humblee was created by **Micah Murray** and is a product of [Six Eight Interactive LLC](https://sixeightinteractive.com)


## License

This project is licensed under the [MIT License](https://opensource.org/licenses/MIT) and is provided "As IS", without warranty of any kind.

