<?php 
function value($field,$crud_selected,$htmlentities=false)
{
	if($crud_selected && isset($crud_selected->$field))
	{ 
	    return ($htmlentities) ? htmlentities($crud_selected->$field) : $crud_selected->$field;
	}
	else
	{
		return '';
	}
} 
?>
<h2 class="title">Page Templates</h2>
<div class="field">
    <label class="label" for="block">Select Template</label> 
    <div class="control">
        <div class="select">
            <select  id="block" name="block" onchange="window.location = '<?php echo _app_path ?>admin/templates/'+$(this).val()">
                 <option value="">Select a content template to edit...</option>
                 <?php
                  foreach($crud_all as $crud_row){
                	  $selected = (is_object($crud_selected) && $crud_row->id == $crud_selected->id) ? " SELECTED" : "";
                	  echo "  <option value=\"".$crud_row->id."\"".$selected.">".$crud_row->name."</option>\n"; 
                   }
                ?>
            </select>
        </div>
    </div>
</div>
<hr>
<h2 class="is-size-4"><?php echo (is_object($crud_selected)) ? "Edit" : "Add New" ?> Page Template</h2>
    
    <?php if(isset($errors)){ ?>
 
    <ul class="form_errors">
    <?php
      foreach($errors as $field => $error):
    ?>
      <li><?php echo $error; ?></li>    
    <?php  endforeach;  ?>
    </ul>
    <?php 
      } 
    ?>
    
<form action="" method="post">
    <div class="field">
        <label class="label" for="name">Name</label> 
        <div class="control">
            <input class="input" type="text" id="name" name="name" value="<?php echo value('name',$crud_selected) ?>">
        </div>
    </div>
    
    <div class="field">
        <label class="label" for="description">Description</label> 
        <div class="control">
            <input class="input" type="text" id="description" name="description" value="<?php echo value('description',$crud_selected) ?>">
        </div>
    </div>
    
    <div class="field">
        <label class="label">Method</label>
        <div class="control">
            <label class="radio">
                <input type="radio" name="page_type" id="page_type-controller" value="controller"<?php echo (value('page_type',$crud_selected) == "controller") ? " CHECKED" : "" ?>>
                Harded coded in a custom controller
            </label>
            <br>
            <label class="radio">
                <input type="radio" name="page_type" id="page_type-view" value="view"<?php echo (value('page_type',$crud_selected) == "view") ? " CHECKED" : "" ?>>
                Passed to default controller
            </label>
        </div>
    </div>

    <div  id="controller_fields" style="display: <?php echo (is_object($crud_selected) && value('page_type',$crud_selected) == "controller") ? "block" : "none" ?>">
    <?php
    if(is_object($crud_selected) && value('page_type',$crud_selected) == "controller")
    {
        $meta = unserialize($crud_selected->page_meta);		
		$crud_selected->controller = $meta['controller'];
		$crud_selected->controller_action = $meta['action'];   
    }
    ?>
        <div class="field">
            <label class="label" for="controller">Controller</label> 
            <div class="control">
                <input class="input" type="text" id="controller" name="controller" placeholder="custom" value="<?php echo value('controller',$crud_selected) ?>">
            </div>
        </div>
        
        <div class="field">
            <label class="label" for="controller_action">Action</label> 
            <div class="control">
                <input class="input" type="text" id="controller_action" name="controller_action" placeholder="index" value="<?php echo value('controller_action',$crud_selected) ?>">
            </div>
        </div>        
        
        <div class="field">
            <label class="label">Dynamic URI</label>
            <label class="checkbox" for="dynamic_uri">
                <input type="checkbox" name="dynamic_uri" id="dynamic_uri" value="1"<?php echo (value('dynamic_uri',$crud_selected) == 1) ? " CHECKED" : "" ?> >    
                URL may include child pages (note: will not return 404 for invalid results)
            </label>
        </div> 
    </div>

    <div id="custom_view_field" style="display: <?php echo (is_object($crud_selected) && value('page_type',$crud_selected) == "view") ? "block" : "none" ?>">
        <div class="field">
            <label class="label" for="default_view">Custom View Path</label>
            <input class="input" type="text" id="default_view" name="default_view" value="<?php echo value('page_meta',$crud_selected) ?>" placeholder="tier_pages/my-view">
        </div>
    </div>

    <div class="field" style="margin-top: 10px">
        <label class="label">Available</label>
        <label class="checkbox" for="available">
            <input class="checkbox" type="checkbox" name="available" id="available" value="1"<?php echo (value('available',$crud_selected) == 1 || !isset($crud_selected->id) ) ? " CHECKED" : "" ?>>
            This template is available for new pages
        </label>
        <p class="help">Unchecking this box allows only developers to use this template.<br >Once a page is assigned this template, non-developers can no longer change that page's template</p>
    </div> 

    <div class="field">
        <label class="label">Included Content Blocks</label>

    <?php 
    $template_blocks = explode(",", value('blocks',$crud_selected) );
    $blocks = ORM::for_table(_table_content_types)->order_by_asc('name')->find_many(); 
    foreach($blocks as $block)
    {
    ?>
        <label class="checkbox tooltip is-tooltip-right <?php echo (strlen($block->description) > 65) ? "is-tooltip-multiline": "" ?>" for="block_<?php echo $block->id ?>" data-tooltip="<?php echo $block->description ?>">
            <input class="checkbox" type="checkbox" id="block_<?php echo $block->id ?>" value="<?php echo $block->id ?>"<?php echo ( in_array($block->id,$template_blocks) ) ? " checked" : "" ?> name="blocks[]">
            <?php echo $block->name ?>
        </label>
        <br>
    <?php	
    }
    ?>
	<p class="help">Note: If a block was previously available and content was entered, that content will still appear on the site if the template view allows for it.
	<br>
	Unchecking a block above simply removes it from the list of editable blocks available for a given template.</p>
  
    </div>
    
    <div class="field">
        <input class="button is-primary" name="submit" type="submit" value="Save Template">
    </div>
   
</form>



<script type="text/javascript">
$(document).ready(function(e) {
    
	$('input[name="page_type"]').change(function(){
		if( $(this).val() == "controller" )
		{
			$("#custom_view_field").fadeOut(0);
			$("#controller_fields").fadeIn('fast');
		}
		else
		{
			$("#controller_fields").fadeOut(0);
			$("#custom_view_field").fadeIn('fast');
		}
	});
	
});

</script>