<?php

declare(strict_types=1);

use Humblee\Foundation\Core;
use Humblee\Foundation\Draw;
use Humblee\Model\Pages;

/** @var array<string, mixed> $content */
/** @var string|false $template_view */
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <?php Draw::metaTags($content); ?>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link rel="stylesheet" type="text/css" href="<?php echo _app_path ?>node_modules/bulma/css/bulma.css">
    <link rel="stylesheet" type="text/css" href="<?php echo _app_path ?>node_modules/bulma-tooltip/bulma-tooltip.min.css">
    <link rel="stylesheet" type="text/css" href="<?php echo _app_path ?>application/css/template.css?1">
    <link href="https://use.fontawesome.com/releases/v5.0.6/css/all.css" rel="stylesheet">

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>

    <?php if (Core::auth('admin')) {
    ?>
        <script type="text/javascript">
            $(document).ready(function() {
                // add Humblee CMS toolbar for logged in users
                $.getJSON("core-request/toolbar", function(toolbarData) {
                    if (toolbarData !== false) {
                        window.toolbarData = toolbarData;
                        $.getScript(toolbarData.js_load);
                    }
                });
            });
        </script>
    <?php
    }
    ?>

    <!-- Analytics Tracking Code -->
    <script>
        window.ANALYTICS_CONFIG = {
            trackingId: '+H0xXhj4YCT0IhXr',
            apiUrl: 'https://gmvshvbfvqujlktpqllf.supabase.co/functions/v1/track'
        };
    </script>
    <script src="https://analytics.ad-hoc.app/analytics.js" defer></script>

</head>

<body>

    <header class="section hero"><!-- extends beyond 960 container -->
        <div class="container">
            <h1 class="title"><span class="icon has-text-info"><span class="fas fa-cogs"></span></span>&nbsp;&nbsp;Humblee</h1>
            <p class="subtitle">
                A humble PHP framework &amp;&nbsp;CMS
            </p>
        </div><!-- end container -->
    </header><!-- end "header" -->

    <div id="pageBody" class="section">
        <div class="container">
            <?php echo (isset($template_view)) ? $template_view : '' ?>
        </div><!-- end "pageBody" -->
    </div>

    <footer class="footer">
        <div class="container">
            <p>&copy; 2017&mdash;<?php echo date("Y") ?> Micah J. Murray</p>
            <p>Humblee is an open source project offered by <a href="https://creativeadhocsolutions.com?utm_source=humblee.app" target="_blank">Creative Ad-Hoc Solutions</a> under the MIT License and is provided "AS IS", without warranty of any kind, express or implied. In no event shall
                the authors or copyright holders be liable for any claim, damages or other liability in connection with the use or other dealings of this&nbsp;application.</p>
        </div>

    </footer>

    <?php if (Core::auth('admin')) {
        include_once _app_server_path . '/humblee/views/admin/toolbar.php';
    } ?>
</body>

</html>