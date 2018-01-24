<h2 class="title">Edit the pages of this site</h2>

<button class="button is-link" onclick="addPage(0); return false">+ Create New Page</button>

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

    <label for="required_role">Log in as:</label>
    <?php $access_roles = ORM::for_table( _table_roles)->where('role_type','access')->find_many(); ?>
    <select id="required_role" name="required_role">
        <option value="0" >Public Page (No Login Required)</option>
        <?php foreach($access_roles as $access_role) : ?>
		<option value="<?php echo $access_role->id ?>" ><?php echo $access_role->name ?></option>
        <?php endforeach ?>
	</select>
 	
</div><!-- end "editPageDialog" page properties pop-up -->