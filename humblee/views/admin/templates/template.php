<?php

declare(strict_types=1);

use Humblee\Foundation\Core;
use Humblee\Model\Crypto;

/** @var string $userTheme */
/** @var string $extra_head_code */
/** @var string|false $template_view */

$is_dev = Core::auth('developer');

// Generate HMAC tokens for AJAX requests
$crypto = new Crypto();
$hmac_pair = $crypto->get_hmac_pair();
?>
<html class="has-navbar-fixed-top">

<head>
    <meta charset="utf-8">
    <title><?php echo  $_ENV['config']['domain'] ?> CMS</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">

    <link rel="stylesheet" type="text/css" href="<?php echo _app_path ?>node_modules/bulma/css/bulma.css">
    <link rel="stylesheet" type="text/css" href="<?php echo _app_path ?>humblee/css/admin/theme-<?php echo $userTheme ?? 'light' ?>.css">
    <link rel="stylesheet" type="text/css" href="<?php echo _app_path ?>node_modules/bulma-tooltip/dist/css/bulma-tooltip.min.css">
    <link href="https://use.fontawesome.com/releases/v5.0.6/css/all.css" rel="stylesheet">

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
    <!--
<script defer src="https://use.fontawesome.com/releases/v5.0.0/js/all.js"></script>
-->
    <script type="text/javascript">
        var APP_PATH = "<?php echo  _app_path ?>",
            XHR_PATH = "<?php echo  _app_path ?>core-request/",
            CURRENT_THEME = "<?php echo $userTheme ?? 'light' ?>",
            hmac_key = "<?php echo $hmac_pair['hmac'] ?>",
            hmac_token = "<?php echo $hmac_pair['message'] ?>";
    </script>
    <script src="<?php echo  _app_path ?>humblee/js/admin/admin.js"></script>
    <script src="<?php echo  _app_path ?>humblee/js/admin/theme-toggle.js"></script>
    <?php echo (isset($extra_head_code)) ? $extra_head_code : '' ?>

    <style>
        /* keep footer at bottom when content is short */
        body {
            min-height: calc(100vh - 3.25rem);
            /* 3.25 accounts for bulma adding the sticky header */

            display: flex;
            flex-direction: column;
        }

        body>section.section {
            flex: 1;
        }
    </style>

</head>

<body>
    <nav id="navbar" class="navbar is-primary is-fixed-top has-shadow">
        <div class="container">
            <div class="navbar-brand">
                <h1 class="is-size-3">Humblee CMS</h1>
                <button class="button navbar-burger">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
            </div>

            <div class="navbar-menu">
                <div class="navbar-end">
                    <a class="navbar-item" href="<?php echo  _app_path ?>admin/">Admin Homepage</a>

                    <?php
                    if (Core::auth('content') || $is_dev) {
                    ?>
                        <a class="navbar-item" href="<?php echo _app_path ?>admin/media" class="tooltip" title="Manage Uploaded Content (files &amp; images)">Media Manager</a>
                    <?php
                    }
                    ?>

                    <?php
                    if (Core::auth('pages') || $is_dev) {
                    ?>
                        <a class="navbar-item" href="<?php echo  _app_path ?>admin/pages" class="tooltip" title="Edit page settings and manage sitemap structure.">Pages</a>
                    <?php
                    }
                    ?>

                    <?php
                    if (Core::auth('users') || $is_dev) {
                    ?>
                        <a class="navbar-item" href="<?php echo  _app_path ?>admin/users" class="tooltip" title="View user information and modify permissions">Users</a>
                    <?php
                    }
                    ?>

                    <?php
                    if (Core::auth('designer') || $is_dev) {
                    ?>
                        <div class="navbar-item has-dropdown is-hoverable">
                            <div class="navbar-link">Design</div>
                            <div class="navbar-dropdown">
                                <a class="navbar-item" href="<?php echo  _app_path ?>admin/blocks">Manage Content Blocks</a>
                                <a class="navbar-item" href="<?php echo  _app_path ?>admin/templates">Manage Templates</a>
                                <?php
                                if ($_ENV['config']['use_p13n']) {
                                ?>
                                    <a class="navbar-item" href="<?php echo  _app_path ?>admin/personalization">Personalization Personas</a>
                                <?php
                                }
                                ?>
                            </div>
                        </div>
                    <?php
                    }
                    ?>

                    <div class="navbar-item has-dropdown is-hoverable">
                        <div class="navbar-link">Account</div>
                        <div class="navbar-dropdown">
                            <a class="navbar-item" href="<?php echo  _app_path ?>user/profile/?fwd=<?php echo _app_path . Core::getURI(); ?>">
                                <span class="icon has-text-info"><i class="fas fa-user" aria-hidden="true"></i></span>
                                Update Profile
                            </a>
                            <hr class="navbar-divider">
                            <p class="navbar-item is-size-7" style="opacity:0.55;letter-spacing:0.08em;text-transform:uppercase;pointer-events:none;">Theme</p>
                            <a class="navbar-item theme-option<?php echo ($userTheme ?? 'light') === 'light' ? ' is-active' : '' ?>" data-theme="light">
                                <span class="icon"><i class="fas fa-sun" aria-hidden="true"></i></span>
                                <span>Light</span>
                            </a>
                            <a class="navbar-item theme-option<?php echo ($userTheme ?? 'light') === 'dark' ? ' is-active' : '' ?>" data-theme="dark">
                                <span class="icon"><i class="fas fa-moon" aria-hidden="true"></i></span>
                                <span>Dark</span>
                            </a>
                            <a class="navbar-item theme-option<?php echo ($userTheme ?? 'light') === 'tech-bro-2026' ? ' is-active' : '' ?>" data-theme="tech-bro-2026">
                                <span class="icon"><i class="fas fa-rocket" aria-hidden="true"></i></span>
                                <span>2026 Tech Bro</span>
                            </a>
                            <hr class="navbar-divider">
                            <a class="navbar-item" href="<?php echo  _app_path ?>user/logout">
                                <span class="icon has-text-danger"><i class="fas fa-sign-out-alt" aria-hidden="true"></i></span>
                                Log Out
                            </a>
                        </div>
                    </div>

                </div>
            </div>
        </div>

    </nav>

    <section class="section">
        <div class="container">
            <?php echo (isset($template_view)) ? $template_view : '' ?>
        </div>
    </section><!-- end "content" -->

    <footer class="footer">
        <div class="container">
            <div class="content has-text-centered">
                <p>Powered by <a href="https://humblee.app" target="_blank">Humblee</a> &copy; <?php echo gmdate("Y"); ?></p>
            </div>
        </div>
    </footer>

    <script>
        window.__SESSION_MONITOR_CONFIG__ = {
            XHR_PATH: "<?php echo _app_path ?>core-request/",
            checkIntervalMs: 60000
        };
    </script>
    <div id="session-monitor-app"></div>
    <script type="module" src="<?php echo _app_path ?>humblee/js/admin/session-monitor/index.js"></script>

    <div id="confirmationBox" class="modal">
        <div class="modal-background"></div>
        <div class="modal-card">
            <header class="modal-card-head">
                <p class="modal-card-title">Are you sure?</p>
            </header>
            <section class="modal-card-body">
                <article class="media">
                    <div class="media-content"></div>
                    <figure class="media-right">
                        <span class="icon is-large">
                            <i class="fas fa-3x fa-exclamation-circle has-text-info"></i>
                        </span>
                    </figure>
                </article>
            </section>
            <footer class="modal-card-foot">
                <button id="confirmButton" class="button is-info">Confirm</button>
                <button class="button cancel">Cancel</button>
            </footer>
        </div>
    </div><!-- end of deletePageConfirmation -->

</body>

</html>