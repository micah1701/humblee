<?php $is_dev = Core::auth('developer'); ?>
<html>
<head>
<meta charset="utf-8">
<title><?php echo  _DOMAIN ?> CMS</title>
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">

<link rel="stylesheet" type="text/css" href="<?php echo  _app_path ?>humblee/css/normalize.css">
<link rel="stylesheet" type="text/css" href="<?php echo  _app_path ?>humblee/css/skeleton.css">
<link rel="stylesheet" type="text/css" href="<?php echo  _app_path ?>humblee/css/admin-layout.css">
<link rel="stylesheet" type="text/css" href="<?php echo  _app_path ?>humblee/css/admin-layout-768.css">
<link rel="stylesheet" href="https://ajax.googleapis.com/ajax/libs/jqueryui/1.11.4/themes/smoothness/jquery-ui.css">

<!--[if lt IE 9]>
    <script src="https://html5shim.googlecode.com/svn/trunk/html5.js"></script>
<![endif]-->

<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js"></script>
<script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.11.4/jquery-ui.min.js"></script>
<script type="text/javascript">var  APP_PATH = "<?php echo  _app_path ?>", XHR_PATH = "<?php echo  _app_path ?>core-request/";</script>
<script type="text/javascript" src="<?php echo  _app_path ?>humblee/js/admin.js"></script>
<?php echo (isset($extra_head_code) ) ? $extra_head_code : '' ?>

</head>
<body>
<header>
    <h1>My Website</h1>
    
    <nav>
        <ul>
            <li><a href="<?php echo  _app_path ?>admin/">Admin Homepage</a></li>
            <?php
                if(Core::auth('content') || $is_dev) : ?>
            <li><a href="<?php echo  _app_path ?>admin/files" class="tooltip" title="Manage Uploaded Content (files &amp; images)">Files Manager</a></li>     
            <?php endif;
            
                if(Core::auth('pages') || $is_dev) : ?>
            <li><a href="<?php echo  _app_path ?>admin/pages" class="tooltip" title="Edit page settings and manage sitemap structure.">Pages</a></li>
            <?php endif;
            
                if(Core::auth('users') || $is_dev) : ?>
            <li><a href="<?php echo  _app_path ?>admin/users" class="tooltip" title="View user information and modify permissions">Users</a></li>
            <?php endif; 
            
                if( $is_dev) : ?>
            <li><a href="javascript:">Design</a>
                 <ul>
                    <li><a href="<?php echo  _app_path ?>admin/blocks">Manage Content Blocks</a></li>
                     <li><a href="<?php echo  _app_path ?>admin/templates">Manage Templates</a></li>                    
                 </ul>
            </li>
            <?php endif; ?>
            
            <li><a href="javascript:">Account</a>
                <ul>
                    <li><a href="<?php echo  _app_path ?>user/profile/?fwd=<?php echo _app_path . Core::getURI(); ?>">Update Profile</a></li>       
                    <li><a href="<?php echo  _app_path ?>user/logout">Log Out</a></li>        
                </ul>
            </li>
            
        </ul>
    </nav>

</header>

<?php if(Core::auth('content') || $is_dev) : ?>
<div id="editnav">
    <div id="toggleDrawer" class="tooltip ui-icon ui-icon-transferthick-e-w ui-state-default ui-corner-all" title="Open or Close the Content Nav Menu">Open/Close</div>
    <h3>Edit Content by Page</h3>
    <div id="contentMenu">&nbsp; loading...</div>
</div>
<?php endif ?>

<section id="content">   
<?php echo (isset($pagebody) ) ? $pagebody : '' ?>
</section><!-- end "content" -->

<style type="text/css">
#editnav select {display: none;}

@media (max-width: 767px) {
      #editnav ul     { display: none; }
      #editnav select { display: inline-block; }
}
</style>

<!-- switch menu to a drop down select input at mobile -->
<script src="<?php echo  _app_path ?>humblee/js/libs/ul2select.js"></script>
<script>
$(document).ready(function(){
    $("#editnav").ul2select({topLevelOnly:false});
});
</script>

</body>
</html>