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
if( $crud_selected )
{
	switch ($crud_selected->page_type) {
		case "controller" :
		$meta = unserialize($crud_selected->page_meta);		
		$crud_selected->controller = $meta['controller'];
		$crud_selected->controller_action = $meta['action'];
		break;
		
		case "view" :
		$crud_selected->default_view = $crud_selected->page_meta;
		break;
		
		default :	
		$crud_selected->default_view = $crud_selected->page_meta;
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
    
<? /* ******************* */

    <div  id="controller_fields" style="display: <?php echo (value('controller',$crud_selected) != "") ? "block" : "none" ?>">
        
        <label for="controller">Controller:</label>
        <input type="text" name="controller" id="controller" value="<?php echo value('controller',$crud_selected) ?>" placeholder="custom">
    
        <label for="controller_action">Action:</label>
        <input type="text" id="controller_action" name="controller_action" value="<?php echo value('controller_action',$crud_selected) ?>" placeholder="action_index">
        
        <label for="dynamic_uri">Dynamic URI:</label>
        <input type="checkbox" name="dynamic_uri" id="dynamic_uri" value="1"<?php echo (value('dynamic_uri',$crud_selected) == 1) ? " CHECKED" : "" ?> >
        <label class="inline" for="dynamic_uri">URL may include child pages (note: will not return 404 for invalid results)</label>
        </div>
    </div>

    <div id="custom_view_field" style="display: <?php echo (value('default_view',$crud_selected) != "") ? "block" : "none" ?>">
        <label for="default_view">Custom View Path:</label>
        <input type="text" id="default_view" name="default_view" value="<?php echo value('default_view',$crud_selected) ?>" placeholder="tier_pages/my-view">
    </div>


	<label for="available" style="display: block !important">Available:</label>
    <input type="checkbox" name="available" id="available" value="1"<?php echo (value('available',$crud_selected) == 1 || !isset($crud_selected->id) ) ? " CHECKED" : "" ?>>
    <label for="available" class="inline">This template is available for new pages</label><p>
     Unchecking this box allows only developers to use this template.<br >Once a page is assigned this template, non-developers can no longer update that page's template</p>

    <h5>Included Content Blocks:</h5>

   
<?php 
$template_blocks = explode(",", value('blocks',$crud_selected) );
$blocks = ORM::for_table(_table_content_types)->order_by_asc('name')->find_many(); 
foreach($blocks as $block)
{
?>
    <input type="checkbox" id="block_<?php echo $block->id ?>" value="<?php echo $block->id ?>"<?php echo ( in_array($block->id,$template_blocks) ) ? " checked" : "" ?> name="blocks[]">
    <label class="inline" for="block_<?php echo $block->id ?>"><?php echo $block->name ?></label><br>
<?php	
}
?>
	<p>Note: If a block was previously available and content was entered, that content may still appear on the site if the template view allows for it.  Unchecking a block above simply removes it from the list of availably editable blocks for a given template.</p>
  
    <p><input name="submit" type="submit" value="Save" ></p>
   
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