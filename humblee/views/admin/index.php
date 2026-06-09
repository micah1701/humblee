<?php
declare(strict_types=1);

/** @var object|false $user */
$user ??= false;
?>
<h1 class="title">Welcome, <?php echo htmlspecialchars((string)($user->name ?? '')) ?></h1>
<div id="app"></div>
