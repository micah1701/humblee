<?php

declare(strict_types=1);

use Humblee\Foundation\Core;
use Humblee\Foundation\Draw;

$uriParts    = Core::getURIparts();
$currentSlug = $uriParts[1] ?? '';

$navItems = [
    '' => 'Introduction',
    'installation'      => 'Installation',
    'architecture' => 'System Architecture',
    'pages'        => 'Creating Pages',
];
?>

<div class="columns docs-layout">

    <aside class="column is-3-desktop is-4-tablet docs-sidebar">
        <a href="<?php echo _app_path ?>" class="docs-back-link">&larr; Back to homepage</a>
        <nav class="menu">
            <p class="menu-label">Documentation</p>
            <ul class="menu-list">
                <?php foreach ($navItems as $slug => $label):
                    $slug = ($slug === '') ? "docs" : "docs/{$slug}";
                ?>
                    <li>
                        <a href="<?php echo _app_path . $slug ?>"
                            <?php if (rtrim($currentSlug, '/') === ltrim($slug, 'docs/') || ($currentSlug === '' && $slug === 'docs')): ?>class="is-active" <?php endif; ?>>
                            <?php echo htmlspecialchars($label) ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </nav>
    </aside>

    <div class="column docs-content content">
        <?php Draw::content($content, 'pagebody') ?>
        <?php Draw::content($content, 'markdown') ?>
    </div>

</div>