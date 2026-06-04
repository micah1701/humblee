<?php

declare(strict_types=1);
/** @var string $userTheme */
$userTheme ??= 'light';

$i18nSegments = (is_array($_ENV['config']['i18n_segments']) && !empty($_ENV['config']['i18n_segments'])) ? array_values($_ENV['config']['i18n_segments']) : [];
$roleRows = \ORM::for_table(_table_roles)->where('role_type', 'access')->find_many();
$roles = [];
foreach ($roleRows as $role) {
    $roles[] = ['id' => (int)$role->id, 'name' => $role->name];
}
?>
<script>
    window.__P13N_CONFIG__ = {
        XHR_PATH: "<?php echo _app_path ?>core-request/",
        theme: "<?php echo htmlspecialchars($userTheme) ?>",
        i18nSegments: <?php echo json_encode($i18nSegments) ?>,
        roles: <?php echo json_encode($roles) ?>
    };
</script>
<div id="app"></div>