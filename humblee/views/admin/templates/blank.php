<html>
<head>
<meta charset="utf-8">
<title><?php echo  $_ENV['config']['domain'] ?> CMS</title>
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">

<link rel="stylesheet" type="text/css" href="<?php echo _app_path ?>node_modules/bulma/css/bulma.css">
<link rel="stylesheet" type="text/css" href="<?php echo _app_path ?>node_modules/bulma-tooltip/dist/bulma-tooltip.min.css">
<link href="https://use.fontawesome.com/releases/v5.0.6/css/all.css" rel="stylesheet">

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>

<script type="text/javascript">var  APP_PATH = "<?php echo  _app_path ?>", XHR_PATH = "<?php echo  _app_path ?>core-request/";</script>
<script src="<?php echo  _app_path ?>humblee/js/admin/admin.js"></script>
<?php echo (isset($extra_head_code) ) ? $extra_head_code : '' ?>

</head>
<body>
    <section class="section">
        <div id="content" class="container">
        <?php echo (isset($template_view) ) ? $template_view : '' ?>
        </div>
    </section><!-- end "content" -->

    <div id="confirmationBox" class="modal">
        <div class="modal-background"></div>
            <div class="modal-card">
            <header class="modal-card-head">
                <p class="modal-card-title">Are you sure?</p>
            </header>
            <section class="modal-card-body">
                <article class="media">
                    <div class="media-content"></div>
                    <figure class="media-right">
                        <span class="icon is-large">
                            <i class="fas fa-3x fa-exclamation-circle has-text-info"></i>
                        </span>
                    </figure>
                </article>
            </section>
            <footer class="modal-card-foot">
              <button id="confirmButton" class="button is-info">Confirm</button>
              <button class="button cancel">Cancel</button>
            </footer>
        </div>
    </div><!-- end of deletePageConfirmation -->
</body>
</html>