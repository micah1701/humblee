<?php
declare(strict_types=1);
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
    isInIframe:   <?php echo json_encode((bool)$is_in_iframe) ?>,
    accessRoles:  <?php echo json_encode(array_values(array_map(
        fn($r) => ['id' => (int)$r->id, 'name' => $r->name],
        iterator_to_array($access_roles)
    ))) ?>,
    XHR_PATH: "<?php echo _app_path ?>core-request/",
    WEB_ROOT: "<?php echo rtrim(_app_path, '/') ?>"
};
</script>
<div id="app"></div>
