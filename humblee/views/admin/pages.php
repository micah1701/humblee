<style type="text/css">
#pages { width: 100%; max-width: 600px; }

 #pages li {
	 list-style-type: none !important;
	 list-style-position: inside;
	 list-style-image:url('/code/mjm-web/core/assets/images/admin/icon-doc.png');
	 padding-top: 10px;
     margin-bottom: 0px !important;
 }

/*** *** */

#pages li ul li {
	border-left: 1px solid #336699;
	padding:0 0 10px 10px;
}
#pages li.contentContainer  {
	list-style-image:url('/code/mjm-web/core/assets/images/admin/icon-e.png');
	cursor: pointer;
}

.pages_menu_item .menu_hasChildren:after {
    content: ' +';
}

#pages li.contentViewing {
	list-style-image:url('/code/mjm-web/core/assets/images/admin/icon-s.png');
}
.contentContainer ul, .contentViewing ul {
    color: #000;
	margin:10px 0 0 10px;
}

 .move_handle { cursor: move; }
 
 .pages_menu_item {
	border: 1px solid #d4d4d4;
	-webkit-border-radius: 3px;
	-moz-border-radius: 3px;
	border-radius: 3px;
	border-color: #D4D4D4 #D4D4D4 #BCBCBC;
	
	padding: 6px;
	
	background: #f6f6f6;
	background: -moz-linear-gradient(top,  #ffffff 0%, #f6f6f6 47%, #ededed 100%);
	background: -webkit-gradient(linear, left top, left bottom, color-stop(0%,#ffffff), color-stop(47%,#f6f6f6), color-stop(100%,#ededed));
	background: -webkit-linear-gradient(top,  #ffffff 0%,#f6f6f6 47%,#ededed 100%);
	background: -o-linear-gradient(top,  #ffffff 0%,#f6f6f6 47%,#ededed 100%);
	background: -ms-linear-gradient(top,  #ffffff 0%,#f6f6f6 47%,#ededed 100%);
	background: linear-gradient(top,  #ffffff 0%,#f6f6f6 47%,#ededed 100%);
	filter: progid:DXImageTransform.Microsoft.gradient( startColorstr='#ffffff', endColorstr='#ededed',GradientType=0 );
 }
 .placeholder {
			border: 1px dashed #4183C4;
			-webkit-border-radius: 3px;
			-moz-border-radius: 3px;
			border-radius: 3px;
		}
 
 .pages_menu_utilities{
	 float: right;
 }
 
 .dialog {
	 text-align: left;
	 font-size: .8em;
 }
 .dialog select:focus,
 .dialog input[type='checkbox']:focus { 
 	outline: none !important; 
 }
 
 .form_label{ width: 105px !important; }
 .dialog label{ font-weight: normal; font-size: 1em; }
 .form_field{ margin-left: 110px !important; }
 
 .page_toolbar_button { float: left; margin-right: 2px; }
 .ui-icon-arrowthick-2-n-s{ cursor: move !important; }
 
 .pages_menu_item { position: relative; }
 .pages_menu_item:hover { background-color: #FFC; }
 .ui-state-highlight { height: 20px; }
 #page_toolbar {
		display: none;
		position: absolute;
		right: 2px;
		top: 50%;
		margin-top: -8px;
		z-index: 2;
	}
 
</style>


    <h2 class="title">Edit the pages of this site</h2>
    
    <a href="#" class="button is-link" onclick="addPage(0); return false">+ Create New Page</a>
    <div id="pages"></div>

<div id="page_toolbar">
	<div class="page_toolbar_button tooltip ui-icon ui-icon-wrench ui-state-default ui-corner-all" title="Edit page properties">Edit Page</div>
	<div class="page_toolbar_button tooltip ui-icon ui-icon-arrowthick-2-n-s ui-state-default ui-corner-all" title="Re-order this page in the sitemap">Reorder</div>
	<div class="page_toolbar_button tooltip ui-icon ui-icon-arrowreturnthick-1-e ui-state-default ui-corner-all" title="Create a new subpage">New Subpage</div>
    <div class="page_toolbar_button tooltip ui-icon ui-icon-trash ui-state-default ui-corner-all" title="Remove this page from the site">Delete</div>
</div>

<div id="editPageDialog" title="Edit Page Properties" class="container">

    <label for="label">Nav Label: </label>
    <input type="text" id="label" name="label" size="45" value="" onkeyup="updateSlug(this.value)">
    <input type="hidden" id="page_id" value="" >

    <label for="slug">URL Slug:
    <span class="inline" style="display: none" id="reset_slug_link"><a href="" onclick="$('#slug').val( $('#original_slug').val() ); return false">(reset slug)</a></span>
    </label>
    
    <input type="text" id="slug" name="slug" size="45" value="" >
    <input type="hidden" id="original_slug" value="">

	<label for="template_id">Template: </label>
    
    <select id="template_id" name="template_id">
    <?php
        $templates = ORM::for_table( _table_templates )->order_by_asc('name')->find_many();
        foreach($templates as $template)
        {
             if($template->available == 0)
             {
                 $disabled = (!Core::auth('developer') && !Core::auth('designer') ) ? " disabled" : "";
                 $disabled_text = " (locked)";
             }
             else
             {
                $disabled = ""; $disabled_text = "";
             }

             echo "<option value=\"".$template->id."\"".$disabled." >". $template->name . $disabled_text ."</option> \n";      
        }
    ?>
	</select>


    <label for="active">Active: </label>
    <input type="checkbox" id="active" name="active" value="1" >
    <label class="inline" for="active">Make page available (uncheck to return 404 Error)</label>
    
	<label for="display_in_sitemap">Navigitable: </label>
    <input type="checkbox" id="display_in_sitemap" name="display_in_sitemap" value="1" >
    <label class="inline" for="display_in_sitemap">Display this page in the main menu &amp; sitemap</label>

    

<!--
	<br class="clear">
	<div class="three columns">&nbsp;</div>
    <div class="columns">
        <input type="checkbox" id="searchable" name="searchable" value="1" >
        <label for="searchable">Searchable (include this page when using site's custom search feature) <br > 
       		Note that search engines will still index any public page regardless of this setting.</label>
    </div>
-->

    <label for="required_role">Log in as:</label>
    <?php $access_roles = ORM::for_table( _table_roles)->where('role_type','access')->find_many(); ?>
    <select id="required_role" name="required_role">
        <option value="0" >Public Page (No Login Required)</option>
<?php foreach($access_roles as $access_role) : ?>
		<option value="<?php echo $access_role->id ?>" ><?php echo $access_role->name ?></option>
<?php endforeach ?>
	</select>
 	
</div><!-- end "editPageDialog" page properties pop-up -->