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
<h2 class="title">Content Blocks</h2>
<div class="field">
    <label class="label" for="block">Select Block</label> 
    <div class="control">
        <div class="select">
            <select  id="block" name="block" onchange="window.location = '<?php echo _app_path ?>admin/blocks/'+$(this).val()">
                 <option value="">Select a content block to edit...</option>
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
<h2 class="is-size-4"><?php echo (is_object($crud_selected)) ? "Edit" : "Add New" ?> Content Block</h2>
    
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
        <label class="label" for="objectkey">Object Key</label> 
        <div class="control">
            <input class="input" type="text" id="objectkey" name="objectkey" value="<?php echo value('objectkey',$crud_selected) ?>">
        </div>
    </div>   

    <div class="field">
        <label class="label" for="description">Description</label> 
        <div class="control">
            <input class="input" type="text" id="description" name="description" value="<?php echo value('description',$crud_selected) ?>">
        </div>
    </div> 

    <div class="field">
        <label class="label" for="output_type">Output Type</label> 
        <div class="control">
            <div class="select">
                <select  id="output_type" name="output_type">
                    <option value="">Select...</option>
                    <option value="content" <?php echo (value('output_type',$crud_selected) == "content") ? "SELECTED" : "" ?>>Visible Content</option>
                    <option value="meta" <?php echo (value('output_type',$crud_selected) == "meta") ? "SELECTED" : "" ?>>Hidden/Meta Data</option>
                </select>
            </div>
        </div>
    </div>

    <div class="field">
        <label class="label" for="input_type">Input Type</label> 
        <div class="control">
            <div class="select">
                <select  id="input_type" name="input_type">
                    <option value="">Select...</option>
                    <option value="wysiwyg" <?php echo (value('input_type',$crud_selected) == "wysiwyg") ? "SELECTED" : "" ?>>WYSIWYG Editor</option>
                    <option value="textfield" <?php echo (value('input_type',$crud_selected) == "textfield") ? "SELECTED" : "" ?>>Single line text field</option>
                    <option value="textarea" <?php echo (value('input_type',$crud_selected) == "textarea") ? "SELECTED" : "" ?>>Block text area</option>
                    <option value="multifield" <?php echo (value('input_type',$crud_selected) == "multifield") ? "SELECTED" : "" ?>>Multiple fields (JSON array)</option>
                    <option value="customform" <?php echo (value('input_type',$crud_selected) == "customform") ? "SELECTED" : "" ?>>Custom PHP Form (include path)</option>
                </select>
            </div>
        </div>
    </div>    

    <div class="field">
        <label class="label" for="input_parameters">
            Input Parameters
            <span id="reset_params" class="inline is-size-7 has-text-info tooltip is-tooltip-right is-invisible" data-tooltip="Reset input parameters to previous state" id="reset_slug_link">
                <span class="icon is-small"><i class="fas fa-undo"></i></span>
            </span>

        <div class="control">
            <textarea class="textarea" id="input_parameters" name="input_parameters"><?php echo value('input_parameters',$crud_selected,true) ?></textarea>
        </div>
    </div> 
    
    <div class="field">
        <input class="button is-primary" name="submit" type="submit" value="Save Block">
    </div>
  
</form>