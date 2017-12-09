<!DOCTYPE html>
<!--[if lt IE 7 ]><html class="ie ie6" lang="en"> <![endif]-->
<!--[if IE 7 ]><html class="ie ie7" lang="en"> <![endif]-->
<!--[if IE 8 ]><html class="ie ie8" lang="en"> <![endif]-->
<!--[if (gte IE 9)|!(IE)]><!--><html lang="en"> <!--<![endif]-->
<html>
<head>
<meta charset="utf-8">
<title><?php Draw::content($content,array('block_key'=>'meta_tags','field_key'=>'page_title')); ?></title>
<meta name="description" content="<?php Draw::content($content,array('block_key'=>'meta_tags','field_key'=>'meta_description')); ?>">
<?php
/* here's an example of accessing the content array's content object of serialized content without using the "Draw::content()" method */
$meta_tags = (isset($content['meta_tags'])) ? json_decode($content['meta_tags']->content) : false;
if($meta_tags && $meta_tags->og_image != "") { 
?>
<meta property="og:image" content="http://<?php echo $_SERVER['HTTP_HOST'] . $meta_tags->og_image ?>">
<?php
} 
?>
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">

<link rel="stylesheet" type="text/css" href="<?php echo _app_path ?>humblee/css/normalize.css">
<link rel="stylesheet" type="text/css" href="<?php echo _app_path ?>humblee/css/skeleton.css">
<link rel="stylesheet" type="text/css" href="<?php echo _app_path ?>application/css/layout.css">

<!--[if lt IE 9]>
    <script src="https://html5shim.googlecode.com/svn/trunk/html5.js"></script>
<![endif]-->

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
    
<div id="header"><!-- extends beyond 960 container -->
	<div class="container">
	    
        <div class="row clearfix">
            <!--
            <a id="header_logo" class="one-third columns" href="/"><img src="logo"></a>
            -->
            <h1>Your Name Here</h1>
            
            <div id="navigation" class="two-thirds columns">
            <?php	
	            $pageObj = new Core_Model_Pages;
	            $menu = $pageObj->getPages();
				$li_format = '<a href=\"$newSlug\">$item->label</a>'; // raw php code to be eval'd in function
				echo $pageObj->drawMenu_UL($menu,array('li_format'=>$li_format));
            ?>
            </div>
		</div>

	<br class="clear">
	</div><!-- end container -->
    
</div><!-- end "header" -->

<div id="pageBody" class="container">
    <?php echo (isset($template_view)) ? $template_view : '' ?>
</div><!-- end "pageBody" -->

</body>
</html>