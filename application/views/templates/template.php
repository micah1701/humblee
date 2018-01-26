<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php Draw::content($content,array('block_key'=>'meta_tags','field_key'=>'page_title')); ?></title>
<meta name="description" content="<?php Draw::content($content,array('block_key'=>'meta_tags','field_key'=>'meta_description')); ?>">
<?php
    /* here's an example of accessing the content array's content object of serialized content without using the "Draw::content()" method */
    $meta_tags = (isset($content['meta_tags'])) ? json_decode($content['meta_tags']->content) : false;
    if($meta_tags && isset($meta_tags->og_image) && $meta_tags->og_image != "")
    { 
?>
<meta property="og:image" content="http://<?php echo $_SERVER['HTTP_HOST'] . $meta_tags->og_image ?>">
<?php
    } 
?>

<link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/bulma/0.6.2/css/bulma.min.css">
<link rel="stylesheet" type="text/css" href="<?php echo _app_path ?>node_modules/bulma-tooltip/bulma-tooltip.min.css">
<link rel="stylesheet" type="text/css" href="<?php echo _app_path ?>application/css/template.css">


<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
<script type="text/javascript">
$(document).ready(function(){
   // add Humblee CMS toolbar for logged in users
   $.getJSON("core-request/toolbar",function(toolbarData){
       if(toolbarData !== false)
       {
           window.toolbarData = toolbarData;
           $.getScript(toolbarData.js_load);
       }
    });
});
</script>

</head>

<body>

<header class="section hero is-primary"><!-- extends beyond 960 container -->
	<div class="container">
        <p class="title">Your Name Here</p>
        <nav class="nav">
        <?php	
            $pageObj = new Core_Model_Pages;
            $menu = $pageObj->getPages();
			$li_format = '<a href=\"$newSlug\">$item->label</a>'; // raw php code to be eval'd in function
			echo $pageObj->drawMenu_UL($menu,array('li_format'=>$li_format));
        ?>
        </nav>

	</div><!-- end container -->
</header><!-- end "header" -->

<section id="pageBody" class="section">
    <div id="pageBody" class="container">
        <?php echo (isset($template_view)) ? $template_view : '' ?>        
    </div><!-- end "pageBody" -->
</section>

</body>
</html>