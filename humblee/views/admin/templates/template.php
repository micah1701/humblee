<?php $is_dev = Core::auth('developer'); ?>
<html class="has-navbar-fixed-top">
<head>
<meta charset="utf-8">
<title><?php echo  $_ENV['config']['domain'] ?> CMS</title>
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">

<link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/bulma/0.6.2/css/bulma.min.css">

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
<script defer src="https://use.fontawesome.com/releases/v5.0.0/js/all.js"></script>

<script type="text/javascript">var  APP_PATH = "<?php echo  _app_path ?>", XHR_PATH = "<?php echo  _app_path ?>core-request/";</script>
<script src="<?php echo  _app_path ?>humblee/js/admin/admin.js"></script>
<?php echo (isset($extra_head_code) ) ? $extra_head_code : '' ?>

</head>
<body>
    <nav id="navbar" class="navbar is-primary is-fixed-top has-shadow">
        <div class="container">
            <div class="navbar-brand">
                <h1 class="is-size-3">Humblee CMS</h1>
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
        </div>    
     
    </nav>    

    <section class="section">
        <div id="content" class="container">
        <?php echo (isset($pagebody) ) ? $pagebody : '' ?>
        </div>
    </section><!-- end "content" -->

    <footer class="footer">
        <div class="container">
            <div class="content has-text-centered">
               <p>Powered by <a href="https://humblee.io" target="_blank">Humblee</a> &copy; <?php echo date("Y"); ?></p> 
            </div>
        </div>
    </footer>

</body>
</html>