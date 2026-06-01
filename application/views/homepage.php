<section class="columns content">
    <div class="column">
        <h2 class="heading has-text-centered">
            What is Humblee?
        </h2>
        <p>Humblee is a MVC <strong>framework</strong>. For developers it covers all the boring stuff like page routing, user authentication and role-based authorization.
            It includes tools for easily encrypting data and files, sending transactional emails and SMS messages, and generating UI forms for custom data management.
            With the basics out of the way, you are free to <strike>toil</strike> tinker away on your application's custom functionality.
        </p>
        <p>Humblee is also a <strong>content management system</strong>. For site owners, Humblee allows the creation and editing of pages, content personalization, i18n multi-language support, uploading files, managing users and setting access privileges.</p>

        <h2 class="heading has-text-centered">Why Humblee?</h2>
        <p>Humblee is for roll-your-own developers and DIY programmers who like to build things their own way. By design, there is no “plugin” system because custom functionality requires custom programming.
            The framework has just enough features baked in to make it useful but remains humbly in the background as you create your own unique tools using vanilla&nbsp;PHP.
        </p>

        <aside class="has-text-centered">
            <a href="docs" class="button is-link is-size-5"><span class="icon is-pulled-left"><span class="fas fa-book"></span></span><span class="is-pulled-right">Documentation</span></a>
            &nbsp;
            <a href="https://github.com/micah1701/humblee" target="_blank" class="button is-link is-size-5"><span class="icon is-pulled-left"><span class="fab fa-github"></span></span><span class="is-pulled-right">Get Humblee</span></a>
        </aside>
    </div>

    <div class="column">
        <figure class="image homepage card">
            <img src="<?php echo _app_path ?>/media/7/15191111271.jpg">
            The Humblee CMS is fully responsive and works accross all devices.
        </figure>
    </div>
</section>
<hr>

<section class="tile is-ancestor content">

    <div class="tile is-parent is-vertical is-6">
        <article class="tile is-child is-vertical notification is-danger">
            <p class="title has-text-centered"><span class="icon"><span class="fas fa-flask"></span></span>&nbsp;
                Developer Tools</p>
            <ul>
                <li><u>Object-relational mapping</u> (ORM). Humblee uses the one-class <a href="https://github.com/j4mie/idiorm" target="_blank">Idiorm</a> library for easy, secure database querying.</li>
                <li><u>Autoloading</u> controllers and models.</li>
                <li><u>Custom routing.</u> For scripts not served by the CMS, routing can be hard coded into the init.php file OR managed through the CMS page template manager.</li>
                <li><u>XHR Class</u>. Humblee's built in XHR controller can be extended for custom AJAX requests and includes methods for checking required user roles and HMAC tokens as well as
                    returning cache busting headers and JSON formatted data.</li>
                <li><u>Crypto Class</u>. Using PHP's native encryption tools (or lib sodium in versions &lt;&nbsp;PHP&nbsp;7.2,) Humblee includes methods for generating secure hashed values, machine authentication codes,
                    and encrypting and decrypting text and files.</li>
                <li><u>Helpful Core methods</u> for quickly checking a given user's role, getting the requested URL, loading a custom view or forwarding to a new URI.</li>
                <li><u>Additional tools</u> for sending transactional emails or SMS text messages and a CRUD method to Create, Read, Update and Delete a given database table row with $_POST data.</li>
                <li><u>Draw UI HTML elements</u> such as a &lt;ul&gt; navigation tree generated from the site's page data or output page-specific content entered through the CMS in a custom view.</li>
                <li><u>Configure Personalization (p13n) and Internationalization (i18n)</u> to customize page views and CMS-entered content targeted to defined user demographics or URL segments.</li>
            </ul>
            <div class="has-text-centered">
                <span class="icon is-large"><span class="fab fa-2x fa-php"></span></span>
                <br>
                &hellip;or do it your own way. It's your app.
            </div>

        </article>
    </div>

    <div class="tile is-vertical is-6">
        <div class="tile">
            <div class="tile is-parent is-vertical">
                <article class="tile is-child notification is-info">
                    <p class="title has-text-centered">
                        <span class="icon"><span class="fas fa-lock"></span></span>&nbsp;
                        Login System Features
                    </p>
                    <ul>
                        <li><u>Self service</u> user registration, profile management, and password recovery.</li>
                        <li><u>Role-based user authorization</u> defining who can see certain pages or content, view and download specific files, use the CMS, edit and/or publish content, create and modify
                            pages or manage other users.</li>
                        <li><i>Optional</i> <u>Two-Factor Authentication</u> (2FA) via SMS text messages (requires a paid <a href="https://www.twilio.com/docs/api" target="_blank">Twilio</a> account.)</li>
                        <li><u>Security features</u> include hashed passwords, rate limiting of login attempts, CSRF mitigation via session based HMAC tokens, and logging of all access attempts in the database.</li>
                    </ul>
                </article>

                <article class="tile is-child notification is-primary">
                    <p class="title has-text-centered"><span class="icon"><span class="fas fa-edit"></span></span>&nbsp;
                        CMS Features</p>
                    <ul>
                        <li><u>Page Manager</u> for adding, editing, reordering or deleting pages and setting required user roles for access.</li>
                        <li><u>User Manager</u> for assigning user roles or removing accounts from they system.</li>
                        <li><u>Media Manager</u> to upload, rename or delete images and files. Media files can also be secured with required access roles and be encrypted at rest.</li>
                        <li><u>Content Manager</u> allows modifying page content via WYSIWYG editor or through custom developed UI forms, as the specific content requires. Content can be saved as a draft
                            or published live to the site and an unlimited number of revisions are stored and can be rolled back.</li>
                    </ul>
                </article>
            </div>
        </div>
    </div>
</section>