<?php
declare(strict_types=1);
/** @var string $userTheme */
$userTheme ??= 'light';
?>
<script>
window.__TEMPLATES_CONFIG__ = {
    XHR_PATH: "<?php echo _app_path ?>core-request/",
    theme:    "<?php echo htmlspecialchars($userTheme) ?>"
};
</script>
<div id="app"></div>
