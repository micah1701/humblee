<div class="columns">
    <div class="column">
        <h1 class="title">
            Edit Content
            <a class="button is-light tooltip is-tooltip-right" id="previewButton" href="<?php echo _app_path . ltrim($page_data->url,"/") .'?preview='.$content->id ?>" data-tooltip="Preview how this revision will appear live on the site" target="_blank">
                <span class="icon is-pulled-left"><i class="fas fa-eye"></i></span>
                <span class="is-pulled-right">Preview</span>
            </a>
        </h1>
    </div>

    <div class="column">
        <?php
        if(!$is_in_iframe)
        {
        ?>
        <span class="tooltip" data-tooltip="Select another block of content associated with this page to edit">
            <div class="select">
                <select id="select_content_type" <?php echo ($is_in_iframe) ? "disabled" : "" ?>>
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
        <?php
        }
        ?>

        <?php
        if($_ENV['config']['use_p13n'])
        {
        ?>
        <span class="tooltip" data-tooltip="Select another personalization version of this content">
            <div class="select">
                <select id="select_p13n_version">
                    <?php
                    foreach ($allP13nVersions as $allP13nVersion)
                    {
                        $selected = ($content->p13n_id == $allP13nVersion->id) ? " SELECTED" : "";
                        echo '<option value="'.$allP13nVersion->id.'"'.$selected.'>'.$allP13nVersion->name.'</option>';
                    }
                    ?>
                </select>
            </div>
        </span>
        <?php
        }
        ?>

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
        For the page:
        <?php
        if($is_in_iframe)
        {
            echo $page_data->label;
        }
        else
        {
        ?>
            <a href="<?php echo $page_data->url ?>" target="_blank"><?php echo $page_data->label ?></a>
        <?php
        }
        if ($page_data->active == 0)
        {
        ?>
            <span class="tooltip is-tooltip-right has-text-danger" data-tooltip="The page this content is located on is currently inactive.">(inactive)</span>
        <?php
        }

        if($_ENV['config']['use_p13n'] && $content->p13n_id != 0)
        {
        ?>
            <br>
            This content is specific to the <strong><?php echo $allP13nVersions[$content->p13n_id]->name ?></strong> persona.
            <span class="icon has-text-warning tooltip" data-tooltip="<?php echo $allP13nVersions[$content->p13n_id]->description ?>"><i class="fas fa-user"></i></span>
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
        	    <select name="revisionList" id="revisionList" onchange="window.location = '<?php echo _app_path ."admin/edit/" ?>'+this.options[this.selectedIndex].value<?php echo ($is_in_iframe) ? "+'?iframe'" : "" ?>;">
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

<hr>

<form action="<?php echo _app_path."admin/edit/".$content->id ?>" id="savecontent" name="savecontent" method="post">
    <input type="hidden" id="edit_time" value="<?php echo date("Y-m-d H:i:s") ?>">
    <input type="hidden" name="content_id" id="content_id" value="<?php echo $content->id ?>">
    <input type="hidden" name="page_id" id="page_id" value="<?php echo $content->page_id ?>">
    <input type="hidden" name="p13n_id" id="p13n_id" value="<?php echo $content->p13n_id ?>">
    <input type="hidden" name="content_type_id" id="content_type_id" value="<?php echo $content_type->id ?>">
    <input type="hidden" name="content_type" id="content_type" value="<?php echo $content_type->input_type ?>">
    <textarea style="display:none" id="original_content"><?php echo $content->content ?></textarea>

<?php
if( $content_type->input_type == "multifield")
{
	$content_array = json_decode( $content->content, true ); //convert to Array
	$input = json_decode( $content_type->input_parameters, true ); // convert to Array
	foreach ($input as $row)
	{
		$key_index = key($row);

		if( count($content_array) > 0 )
		{
			$input = preg_replace('@{content}@',$content_array[$key_index],$row[$key_index]['input']);
		}else{
			$input = $row[$key_index]['input'];
		}
?>
    <div class="one-third column label"><?php echo $row[$key_index]['label'] ?></div>
    <div class="two-thirds column"><?php echo $input ?></div>
<?php
	}
?>
	<input type="hidden" id="content" name="content" value="" />
<?php
}
elseif( $content_type->input_type == "customform")
{
	$content_array = json_decode( $content->content, true ); //convert to Array for use by included file
	include_once _app_server_path.'humblee/views/'. ltrim($content_type->input_parameters,"/");
?>
	<input type="hidden" id="content" name="content" value="" />
<?php
}

// if it's a WYSIWYG editor, load as a textarea and generate neccessary js/css to implement the WYSIWYG editor
elseif ($content_type->input_type == "wysiwyg")
{
	echo  preg_replace('@{content}@',$content->content,$content_type->input_parameters);
?>
    <script src="https://cdn.quilljs.com/1.3.5/quill.js"></script>
    <script src="<?php echo _app_path ?>humblee/js/tools/quill.js"></script>

<?php
}

// for all other types of single input content block, show a basic label and the field
else
{
    echo '<div class="field">';
    echo '<label class="label" for="content">'.$content_type->name .'</label>';
	echo  preg_replace('@{content}@',$content->content,$content_type->input_parameters);
	echo '</div>';
}
?>

    <input type="hidden" name="live" id="live" value="0">
</form>

<button class="button is-primary" id="save"><span class="icon is-pulled-left"><i class="far fa-save"></i></span><span class="is-pulled-right">Save Draft</span></button> &nbsp;
<button class="button is-primary is-outlined" id="publish"><span class="icon is-pulled-left"><i class="fas fa-rocket"></i></span><span class="is-pulled-right">Publish live to site</span></button>

<div id="mediamanager" class="modal">
  <div class="modal-background"></div>
  <div class="modal-card">
    <header class="modal-card-head">
      <p class="modal-card-title">Media Manager</p>
      <button class="delete" aria-label="close"></button>
    </header>
    <section class="modal-card-body"></section>
  </div>
</div>

<!-- used in Quill.js editor to update an image's properties inline -->
<div id="imageProperties" class="modal">
    <div class="modal-background"></div>
    <div class="modal-card">
        <header class="modal-card-head">
            Edit Image Properties
        </header>
        <section class="modal-card-body">
            <label class="label" for="imageWidth">Width</label>
            <input class="input" id="imageWidth">
            <p class="help">Responsive images should have a 100% width</p>
            <label class="label" for="imageMaxWidth">Max Width</label>
            <input class="input" id="imageMaxWidth">
            <p class="help">Largest size the image should display, even if screen is wider</p>
            <label class="label" for="imageClass">Class</label>
            <input class="input" id="imageClass">
        </section>
        <footer class="modal-card-foot">
            <button class="button is-primary" id="imagePropertiesSave">Update</button>
            <button class="button" id="imagePropertiesCancel">Cancel</button>
        </footer>
    </div>
</div>