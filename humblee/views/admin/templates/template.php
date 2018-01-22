<?php $is_dev = Core::auth('developer'); ?>
<html>
<head>
<meta charset="utf-8">
<title><?php echo  $_ENV['config']['domain'] ?> CMS</title>
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">

<link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/bulma/0.6.2/css/bulma.min.css">
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
<script type="text/javascript">var  APP_PATH = "<?php echo  _app_path ?>", XHR_PATH = "<?php echo  _app_path ?>core-request/";</script>
<script type="text/javascript" src="<?php echo  _app_path ?>humblee/js/admin/admin.js"></script>
<?php echo (isset($extra_head_code) ) ? $extra_head_code : '' ?>

</head>
<body>
    <nav id="navbar" class="navbar is-primary is-fixed-top">
        
        <div class="navbar-brand">
            <h1 class="title">Humblee CMS</h1>
        </div>
        
        <div class="navbar-menu">
            <div class="navbar-end">
                <a class="navbar-item" href="<?php echo  _app_path ?>admin/">Admin Homepage</a>

                <?php
                if(Core::auth('content') || $is_dev)
                {
                ?>
                <a class="navbar-item" href="<?php echo _app_path ?>admin/files" class="tooltip" title="Manage Uploaded Content (files &amp; images)">Files Manager</a>
                <?php
                }
                ?>
                
                <?php
                if(Core::auth('pages') || $is_dev)
                {
                ?>
                <a class="navbar-item" href="<?php echo  _app_path ?>admin/pages" class="tooltip" title="Edit page settings and manage sitemap structure.">Pages</a>
                <?php
                }
                ?>
                
                <?php
                if(Core::auth('users') || $is_dev)
                {
                ?>
                <a class="navbar-item" href="<?php echo  _app_path ?>admin/users" class="tooltip" title="View user information and modify permissions">Users</a>
                <?php
                }
                ?>
                
                <?php
                if(Core::auth('designer') || $is_dev)
                {
                ?>
                <div class="navbar-item has-dropdown is-hoverable">
                    <div class="navbar-link">Design</div>
                    <div class="navbar-dropdown">
                        <a class="navbar-item" href="<?php echo  _app_path ?>admin/blocks">Manage Content Blocks</a>
                        <a class="navbar-item" href="<?php echo  _app_path ?>admin/templates">Manage Templates</a>
                    </div>   
                </div>
                <?php
                }
                ?>
                
                <div class="navbar-item has-dropdown is-hoverable">
                    <div class="navbar-link">Account</div>
                    <div class="navbar-dropdown">
                        <a class="navbar-item" href="<?php echo  _app_path ?>user/profile/?fwd=<?php echo _app_path . Core::getURI(); ?>">Update Profile</a>
                        <a class="navbar-item" href="<?php echo  _app_path ?>user/logout">Log Out</a>
                    </div>   
                </div>
                
            </div>
        </div>
        
    </nav>    

    <div class="section">
        <div class="columns">
             <?php 
            if(Core::auth('content') || $is_dev)
            {
            ?>
            <div id="editnav" class="column is-one-quarter">
                <div id="toggleDrawer" class="tooltip ui-icon ui-icon-transferthick-e-w ui-state-default ui-corner-all" title="Open or Close the Content Nav Menu">Open/Close</div>
                <h3 class="subtitle">Edit Content by Page</h3>
                <aside id="contentMenu" class="menu">&nbsp; loading...</aside>
            </div>
            <?php
            }
            ?>
        
            <section id="content" class="column">   
            <?php echo (isset($pagebody) ) ? $pagebody : '' ?>
            </section><!-- end "content" -->     
        </div>
    
    </div>

</body>
</html>