<h2 class="title">Edit the pages of this site</h2>

<button class="button is-link" onclick="addPage(0); return false">+ Create New Page</button>

<div id="pages"></div>

<div id="page_toolbar">
	<div class="page_toolbar_button tooltip ui-icon ui-icon-wrench ui-state-default ui-corner-all" title="Edit page properties">Edit Page</div>
	<div class="page_toolbar_button tooltip ui-icon ui-icon-arrowthick-2-n-s ui-state-default ui-corner-all" title="Re-order this page in the sitemap">Reorder</div>
	<div class="page_toolbar_button tooltip ui-icon ui-icon-arrowreturnthick-1-e ui-state-default ui-corner-all" title="Create a new subpage">New Subpage</div>
    <div class="page_toolbar_button tooltip ui-icon ui-icon-trash ui-state-default ui-corner-all" title="Remove this page from the site">Delete</div>
</div>

<div id="editPageDialog" title="Edit Page Properties" class="modal is-active">
    <div class="modal-background"></div>
    <div class="modal-card">
        <header class="modal-card-head">
            <p class="modal-card-title">Edit Page Properties</p>
            <button class="delete" aria-label="close"></button>    
        </header>
        
        <section class="modal-card-body">
        
            <div class="field">
                <label class="label" for="label">Nav Label</label> 
                <div class="control">
                    <input class="input" type="text" id="label" name="label" size="45" value="" onkeyup="updateSlug(this.value)">
                </div>
            </div>    

            <div class="field">
                <label class="label" for="slug">
                    URL Slug
                    <span class="inline" style="display: none" id="reset_slug_link"><a href="" onclick="$('#slug').val( $('#original_slug').val() ); return false">(reset slug)</a></span>
                </label> 
                <div class="control">
                    <input class="input" type="text" id="slug" name="slug">
                    <input type="hidden" id="original_slug" value="">
                </div>
            </div>     
            
            <div class="field">
                <label class="label" for="template_id">Template</label> 
                <div class="control">
                    <div class="select">
                        <select  id="template_id" name="template_id">
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
                    </div>
                </div>
            </div> 
            
            <div class="field">
                <span class="label">Active</span>
                <label class="checkbox " for="active"> 
                    <input type="checkbox" id="active" name="active" value="1">
                    Make page available (uncheck to return 404 Error)
                </label>
            </div>
            
            <div class="field">
                <span class="label">Navigitable</span>
                <label class="checkbox " for="display_in_sitemap"> 
                    <input type="checkbox" id="display_in_sitemap" name="display_in_sitemap" value="1">
                    Display this page in the main menu &amp; sitemap
                </label>
            </div>
            
            <div class="field">
                <label class="label" for="required_role">Log in as...</label> 
                <div class="control">
                    <div class="select">
                        <select  id="required_role" name="required_role">
                            <option value="0" >Public Page (No Login Required)</option>
                            <?php foreach($access_roles as $access_role) : ?>
                    		<option value="<?php echo $access_role->id ?>" ><?php echo $access_role->name ?></option>
                            <?php endforeach ?>
                        </select>
                    </div>
                </div>
            </div>
            
        </section>
        
        <footer class="modal-card-foot">
          <button class="button is-success">Save changes</button>
          <button class="button">Cancel</button>
        </footer>
    </div>
</div><!-- end "editPageDialog" page properties pop-up -->