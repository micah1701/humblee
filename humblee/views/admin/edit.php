<div class="columns">
    <div class="column">
        <h1 class="title">Edit Content</h1>        
    </div>
    
    <div class="column">
        <span class="tooltip" data-tooltip="Select another block of content associated with this page to edit">
            <div class="select">
                <select id="select_content_type">
                    <option value="">Select Content to Edit</option>
                    <?php 
                    foreach ($allContentTypes as $allContentType)
                    {
                        $selected = ($content_type->id == $allContentType->id) ? " SELECTED" : "";
                        echo '<option value="'.$allContentType->id().'"'.$selected.'>'.$allContentType->name.'</option>';
                    }    
                    ?>
                </select>
            </div>
        </span>
        &nbsp;
        <button class="button is-info tooltip" id="previewButton" data-url="<?php echo ltrim($page_data->url,"/") .'?preview='.$content->id ?>" data-tooltip="Preview how this revision will appear live on the site">Preview</button> 
    </div>
</div>

<?php
$old_version = false;
if(count($revisions) > 1 && $content->revision_date != $revisions[0]->revision_date)
{	
   	 $old_version = true; 
?>
    <div class="notification is-warning">
	    <span class="icon"><i class="fa fa-info-circle"></i></span>A more recently saved revision of this content exists.</p>
    </div>
<?php 
}
?>

<div class="columns">
    <div class="column">
        You are editing the <strong><?php echo $content_type->name ?></strong>
        <span class="icon has-text-info tooltip" data-tooltip="<?php echo $content_type->description ?>"><i class="far fa-question-circle"></i></span>
        <br>
        For the page: <a href="<?php echo $page_data->url ?>" target="_blank"><?php echo $page_data->label ?></a>
        <?php 
        if ($page_data->active == 0)
        {
        ?>
            <span class="tooltip is-tooltip-right has-text-danger" data-tooltip="The page this content is located on is currently inactive.">(inactive)</span>
        <?php
        } 
        ?>           
    </div>
    
    <div class="column">
        <?php
        if($content->updated_by != 0)
        {
		   $updated_by_user = ORM::for_table( _table_users)->find_one($content->updated_by);
	    ?>
        <strong>Saved: </strong><?php echo date("F j, Y h:ia",strtotime($content->revision_date)) ?> &nbsp; <strong>By:</strong> <?php echo $updated_by_user->name; ?>
        <br>
            <?php  
            if($content->publish_date == "0000-00-00 00:00:00")
            { 
            ?>
        		<span class="has-text-info">Unpublished Draft.</span> This content has not yet been published.
            <?php 
            }
            elseif ($content->live == 1)
            { 
            ?>
           		<span title="This version was published on <?php echo date("M d, Y @ h:ia",strtotime($content->publish_date)) ?> "><span class="has-text-success">Live Version.</span> This content is currently being shown on the website.</span>
            <?php 
            }
            else
            { 
            ?>
           		<span title="This version was published on <?php echo date("M d, Y @ h:ia",strtotime($content->publish_date)) ?> "><span class="has-text-danger">Previously published.</span>  This revision was previously live on the website.</span>
            <?php 
            }  
        }
        ?> 
        <?php
        if(!$old_version)
        { 
        ?>
        <p>
            <button class="button tooltip" data-tooltip="Show revision history" onclick="$(this).css({display:'none'}); $('#revision_list').fadeIn('fast'); return false"><span class="is-pulled-left">History</span><span class="icon is-pulled-right"><i class="fas fa-history"></i></span></button>
        </p>
        <?php
        } 
        ?>
        <div id="revision_list"<?php if(!$old_version) { echo ' style="display: none;"'; } ?>>
        	<div class="select">
        	    <select name="revisionList" id="revisionList" onchange="window.location = '<?php echo _app_path ."admin/edit/" ?>'+this.options[this.selectedIndex].value;">
                <?php foreach($revisions as $revision)
                { 
                ?>
            	     <option value="<?php echo $revision->id ?>"<?php if($content->id == $revision->id){ echo " SELECTED"; } ?>>
            	    <?php 
            	        echo date("M d, Y g:ia",strtotime($revision->revision_date)); 	    
                        if($revision->live == 1){ echo " - LIVE";  }
                        elseif($revision->publish_date != "0000-00-00 00:00:00"){ echo " - Previously published"; }
                        else{ echo " - Draft (never published)"; } ?>
                    </option>
                <?php 
                } 
                ?>
            	</select>
            </div>
        </div>    
    </div>
</div>

<div id="edit_form" data-content_id="<?php echo $content->id ?>">Loading...</div>