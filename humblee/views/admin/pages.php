<h2 class="title">Edit the pages of this site</h2>

<button class="button tooltip is-tooltip-right is-info is-outlined" data-tooltip="Create a top level page" onclick="addPage(0); return false">
    <span class="icon is-pulled-left"><i class="fas fa-plus"></i></span><span class="is-pulled-right">New Page</span></button>

<div id="pages" class="menu">Loading...</div>


<div id="page_toolbar" class="is-pulled-right">
	<span class="icon page_toolbar_button tooltip edit" data-tooltip="Edit page properties"><i class="fas fa-edit"></i></span></span>
	<span class="icon page_toolbar_button tooltip order" data-tooltip="Re-order this page in the sitemap"><i class="fas fa-arrows-alt-v"></i></span>
	<span class="icon page_toolbar_button tooltip newpage" data-tooltip="Create a new subpage"><i class="fas fa-plus"></i></span>
    <span class="icon page_toolbar_button tooltip trash" data-tooltip="Remove this page from the site"><i class="fas fa-trash"></i></div>
</div>

<div id="editPageDialog" class="modal">
    <div class="modal-background"></div>
    <div class="modal-card">
        <header class="modal-card-head">
            <p class="modal-card-title">Edit Page Properties</p>
            <button class="delete" aria-label="close" title="close dialog"></button>    
        </header>
        
        <section class="modal-card-body">
        
            <div class="field">
                <label class="label" for="label">Nav Label</label> 
                <div class="control">
                    <input class="input" type="text" id="label" name="label" size="45" value="" onkeyup="updateSlug(this.value)">
                    <input type="hidden" id="page_id">
                </div>
            </div>    

            <div class="field">
                <label class="label" for="slug">
                    URL Slug
                    <span class="inline is-size-7 has-text-info tooltip is-tooltip-right" data-tooltip="Reset URL Slug to previous state" style="display: none" id="reset_slug_link">
                        <span class="icon is-small"><i class="fas fa-undo"></i></span>
                    </span>
                </label> 
                <div class="control">
                    <input class="input" type="text" id="slug" name="slug">
                    <input type="hidden" id="original_slug">
                </div>
            </div>     
            
            <div class="field">
                <label class="label" for="template_id">Layout</label> 
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
          <button id="saveButton" class="button is-success">Save changes</button>
          <button class="button cancel">Cancel</button>
        </footer>
    </div>

</div><!-- end "editPageDialog" page properties pop-up -->

<div id="deletePageConfirmation" class="modal">
    <div class="modal-background"></div>
        <div class="modal-card">
        <header class="modal-card-head">
            <p class="modal-card-title">Delete <span id="confirm_delete_pagename"><em>"Your Page Name that is really long and just keeps going on forever"</em></span></p>
        </header>
        
        <section class="modal-card-body">
            <article class="media">
                <div class="media-content">
                    <p><strong>Are you sure you want to <span class="has-text-danger">PERMANENTLY DELETE</span> this page?</strong></p>
                    <p>All content, past and present, associated with this page will be lost!</p>
                </div>
                <figure class="media-right">
                    <span class="icon is-large">
                        <i class="fas fa-3x fa-exclamation-circle has-text-danger"></i>
                    </span>
                </figure>
            </article>
        </section>
    
        <footer class="modal-card-foot">
          <button id="deleteButton" class="button is-danger">Delete Page</button>
          <button class="button cancel">Cancel</button>
          <span class="has-text-info">This action can not be undone</span>    
        </footer>
    </div>
</div><!-- end of deletePageConfirmation -->