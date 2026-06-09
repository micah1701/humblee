<?php
declare(strict_types=1);

use Humblee\Foundation\Core;

/** @var array<int, string> $roles */
$roles ??= [];
/** @var bool $isDeveloper */
$isDeveloper ??= false;
?>
<script>
window.__USERS_CONFIG__ = {
    xhrPath:     "<?php echo _app_path ?>core-request/",
    isDeveloper: <?php echo json_encode($isDeveloper) ?>,
    roles: <?php
        $rolesForJs = array_map(
            fn(int $id, string $name) => ['id' => $id, 'name' => $name],
            array_keys($roles),
            array_values($roles)
        );
        echo json_encode(array_values($rolesForJs));
    ?>
};
</script>
<div id="app"></div>
