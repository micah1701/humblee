<?php
declare(strict_types=1);

use Humblee\Foundation\Core;
use Humblee\Foundation\Draw;

$uriParts    = Core::getURIparts();
$currentSlug = $uriParts[1] ?? '';

$navItems = [
    'introduction' => 'Introduction',
    'install'      => 'Installation',
    'architecture' => 'System Architecture',
    'pages'        => 'Create Pages',
];
?>

<div class="columns docs-layout">

    <aside class="column is-3-desktop is-4-tablet docs-sidebar">
        <nav class="menu">
            <p class="menu-label">Documentation</p>
            <ul class="menu-list">
                <?php foreach ($navItems as $slug => $label): ?>
                <li>
                    <a href="<?php echo _app_path ?>docs/<?php echo $slug ?>"
                       <?php if ($currentSlug === $slug): ?>class="is-active"<?php endif; ?>>
                        <?php echo htmlspecialchars($label) ?>
                    </a>
                </li>
                <?php endforeach; ?>
            </ul>
        </nav>
    </aside>

    <div class="column docs-content content">
        <?php Draw::content($content, 'pagebody') ?>
    </div>

</div>
