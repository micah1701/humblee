<?php
$_toolbar_user = \ORM::for_table(_table_users)->select('name')->find_one($_SESSION[session_key]['user_id']);
?>
<link rel="stylesheet" href="<?php echo _app_path ?>humblee/js/admin/toolbar/index.css">
<script>
window.__TOOLBAR_CONFIG__ = {
  appPath: "<?php echo _app_path ?>",
  name: <?php echo json_encode($_toolbar_user ? $_toolbar_user->name : '') ?>
};
</script>
<div id="toolbar-app"></div>
<script type="module" src="<?php echo _app_path ?>humblee/js/admin/toolbar/index.js"></script>
