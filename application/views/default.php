<?php

declare(strict_types=1);

use Humblee\Foundation\Draw;

/** @var array<string, mixed> $content */
echo '<div class="content">';
Draw::content($content, 'pagebody');
echo "\n</div>\n";
