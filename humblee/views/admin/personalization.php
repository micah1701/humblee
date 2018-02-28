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
<h2 class="title">Personalization and Internationalization</h2>
<div class="field">
    <label class="label" for="block">Select Persona</label>
    <div class="control">
        <div class="select">
            <select  id="block" name="block" onchange="window.location = '<?php echo _app_path ?>admin/personalization/'+$(this).val()">
                 <option value="">Select a content persona to edit...</option>
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
<h2 class="is-size-4"><?php echo (is_object($crud_selected)) ? "Edit" : "Add New" ?> Persona</h2>

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
            <input class="input" type="text" id="name" name="name" value="<?php echo value('name',$crud_selected,true) ?>">
        </div>
    </div>

    <div class="field">
        <label class="label" for="description">Description</label>
        <div class="control">
            <input class="input" type="text" id="description" name="description" value="<?php echo value('description',$crud_selected,true) ?>">
        </div>
    </div>

    <div class="field">
        <label class="label" for="criteria">
            Criteria
        <label>
        <div class="control">
            <textarea class="textarea" id="criteria" name="criteria"><?php echo value('criteria',$crud_selected,true) ?></textarea>
        </div>
    </div>

    <div class="field">
        <input class="button is-primary" name="submit" type="submit" value="Save Persona">
    </div>

</form>