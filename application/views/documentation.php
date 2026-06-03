<?php

declare(strict_types=1);

use Humblee\Foundation\Draw;
use Humblee\Model\Pages;

$pagesObj = new Pages;
$docPages = $pagesObj->getPages(['parent_id' => 3, 'active_only' => true, 'display_in_sitemap_only' => false]);

$navHtml = $pagesObj->drawMenu_UL($docPages, [
    'thisID'           => (int)$page->id,
    'currentPageClass' => 'is-active',
    'li_format'        => fn($item, $slug, $class) => '<a href="' . _app_path . ltrim($slug, '/') . '" ' . $class . '>' . htmlspecialchars($item->label) . '</a>',
]);

$navHtml = preg_replace('/<ul>/', '<ul class="menu-list">', $navHtml, 1);
?>

<div class="columns docs-layout">

    <aside class="column is-3-desktop is-4-tablet docs-sidebar">
        <a href="<?php echo _app_path ?>" class="docs-back-link">&larr; Back to homepage</a>
        <nav class="menu">
            <p class="menu-label">Documentation</p>
            <?php echo $navHtml ?>
        </nav>
    </aside>

    <div class="column docs-content content">
        <?php Draw::content($content, 'pagebody') ?>
        <?php Draw::content($content, 'markdown') ?>
    </div>

</div>
