<?php

declare(strict_types=1);

/**
 * The following variables are expected to be defined before including this view:
 * - $hasMediaRole: bool indicating if the current user has media role
 * - $is_in_iframe: bool indicating if the view is rendered inside an iframe
 * - $access_roles: iterable of role objects with 'id' and 'name' properties
 */
/** @var string $userTheme */
/** @var bool $hasMediaRole */
$hasMediaRole ??= false;
/** @var bool $is_in_iframe */
$is_in_iframe ??= false;
/** @var iterable $access_roles */
$access_roles ??= [];
?>
<script>
    window.__MEDIA_CONFIG__ = {
        hasMediaRole: <?php echo json_encode((bool)$hasMediaRole) ?>,
        isInIframe: <?php echo json_encode((bool)$is_in_iframe) ?>,
        accessRoles: <?php echo json_encode(array_values(array_map(
                            fn($r) => ['id' => (int)$r->id, 'name' => $r->name],
                            iterator_to_array($access_roles)
                        ))) ?>,
        XHR_PATH: "<?php echo _app_path ?>core-request/",
        WEB_ROOT: "<?php echo rtrim(_app_path, '/') ?>",
        theme: "<?php echo $userTheme ?? 'light' ?>",
        tinyPngEnabled: <?php echo json_encode(
            !empty($_ENV['config']['TINYPNG_Enabled']) && !empty($_ENV['config']['TINYPNG_API_Key'])
        ) ?>
    };
</script>
<div id="app"></div>