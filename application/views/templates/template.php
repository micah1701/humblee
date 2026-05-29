<?php
declare(strict_types=1);

use Humblee\Foundation\Core;
use Humblee\Foundation\Draw;
use Humblee\Model\Pages;
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <?php Draw::metaTags($content); ?>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link rel="stylesheet" type="text/css" href="<?php echo _app_path ?>node_modules/bulma/css/bulma.css">
    <link rel="stylesheet" type="text/css" href="<?php echo _app_path ?>node_modules/bulma-tooltip/bulma-tooltip.min.css">
    <link rel="stylesheet" type="text/css" href="<?php echo _app_path ?>application/css/template.css">

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>

</head>

<body>

    <header class="section hero is-primary"><!-- extends beyond 960 container -->
        <div class="container">
            <p class="title">Your Name Here</p>
            <nav class="navbar">
                <?php
                $pageObj = new Pages;
                $menu = $pageObj->getPages(['active_only' => true, 'display_in_sitemap_only' => true, 'generations' => 1]);
                $li_format = fn($item, $slug, $class) => '<a href="' . $slug . '">' . $item->label . '</a>';
                echo $pageObj->drawMenu_UL($menu, ['li_format' => $li_format]);
                ?>
            </nav>

        </div><!-- end container -->
    </header><!-- end "header" -->

    <section id="pageBody" class="section">
        <div class="content container">
            <?php echo (isset($template_view)) ? $template_view : '' ?>
        </div><!-- end "pageBody" -->
    </section>

    <?php if (Core::auth('admin')) {
        include_once _app_server_path . '/humblee/views/admin/toolbar.php';
    } ?>
</body>

</html>