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
                      $active = ($crud_row->active == 0) ? " (inactive)" : "";
                	  $selected = (is_object($crud_selected) && $crud_row->id == $crud_selected->id) ? " SELECTED" : "";
                	  echo "  <option value=\"".$crud_row->id."\"".$selected.">". $crud_row->name . $active."</option>\n";
                   }
                ?>
            </select>
        </div>
    </div>
</div>
<hr>
<div class="columns">
    <div class="column">
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
                <label class="label">Active</label>
                <label class="checkbox" for="active">
                    <input type="checkbox" name="active" id="active" value="1"<?php echo (value('active',$crud_selected) == 1) ? " CHECKED" : "" ?> >
                    Activate Persona (uncheck to ignore this Persona)
                </label>
            </div>

            <div class="field">
                <label class="label" for="criteria">
                    Criteria
                    <span id="reset_params" class="inline is-size-7 has-text-info tooltip is-tooltip-right is-invisible" data-tooltip="Reset criteria to previous state" id="reset_criteria_link">
                        <span class="icon is-small"><i class="fas fa-undo"></i></span>
                    </span>
                <label>
            </div>

            <div id="criteria_builder"></div>

            <hr>

            <div class="field">
                <label class="label" for="criteria">Generated Criteria</label>
                <div class="control">
                    <textarea class="textarea" id="criteria" name="criteria"><?php echo value('criteria',$crud_selected,true) ?></textarea>
                    <input type="hidden" id="criteria_original" value="<?php echo value('criteria',$crud_selected,true) ?>">
                </div>
            </div>

            <hr>

            <div class="field">
                <input class="button is-primary" name="submit" type="submit" value="Save Persona">
            </div>
        </form>

    </div>

    <div class="column">

        <div class="panel">
            <h2 class="panel-heading is-size-4">Set Persona Priorities</h2>

            <div class="panel-block">
                <p>
                    The first persona to match its given criteria will be used. More specific criteria should be given a higher priority over more generalized personas.
                    Drag and drop personas to change their priority.
                </p>
            </div>

            <ul id="priorities" class="sortable ui-sortable">
            <?php
            foreach($allP13nVersions as $p13n)
            {
            ?>
                <li id="personaID_<?php echo $p13n->id ?>">
                    <div>
                        <a class="panel-block">
                            <span class="panel-icon"><i class="fas fa-arrows-alt-v"></i></span>
                            <?php echo $p13n->name ?>
                            <?php echo ($p13n->active == 0) ? '<span class="has-text-danger"> &nbsp;(inactive)</span>' : ''; ?>
                        </a>
                    </div>
                </li>
            <?php
            }
            ?>
            </ul>

            <p class="panel-block"><span style="margin-left: 25px">Default Content (No Persona)</span></p>
        </div>

    </div>
</div>


<!-- HTML elements for criteria builder -->
<div style="display: none">

<!-- criteria "OR" block -->
<div id="criteria_or_block">
    <div class="field criteria_OR" data-fieldID="">
        <span class="criteria_add_and icon" data-fieldID=""><span class="fas fa-plus"></span></span>Add Criteria
    </div>
</div>


<!-- select a persona -->
<div id="criteria_select_persona">
    <div class="column" data-fieldID="">
        <div class="select">
            <select class="setPersona select" data-fieldID="">
                <option value="i18n" SELECTED>URL i18n Segment</option>
                <option value="session_key">Session Variable</option>
                <option value="required_role">User Role</option>
            </select>
        </div>
    </div>
</div>

<!-- remove a persona -->
<div id="criteria_remove_and">
    <div class="column column-slim has-text-right" data-fieldID="">
        <span class="criteria_remove_and icon" data-fieldID=""><span class="fas fa-trash"></span></span>
    </div>
</div>


<!-- criteria sperator -->
<div id="criteria_seperator">
    <div class="criteria_seperator has-text-centered" data-fieldID="">AND</div>
</div>


<!-- criteria to select url i18n segment -->
<div id="criteria_i18n">
    <div class="column" data-fieldID="">
        <div class="select">
            <select class="select setOperator" data-fieldID="">
                <option value="=">IS</option>
                <option value="!=">IS NOT</option>
            </select>
        </div>
    </div>
    <div class="column" data-fieldID="">
        <div class="select">
            <select class="select setValue" data-fieldID="">
                <?php
                foreach ($_ENV['config']['i18n_segments'] as $segment)
                {
                ?>
                <option value="<?php echo strtolower($segment) ?>">~/<?php echo strtoupper($segment) ?></option>
                <?php
                }
                ?>
                <option value="">Not Set</option>
            </select>
        </div>
    </div>
</div>

<!-- criteria to check session -->
<div id="criteria_session_key">
    <div class="column" data-fieldID="">
        <input class="input setValue" data-fieldID="" type="text" placeholder="Session_Key">
    </div>
    <div class="column" data-fieldID="">
        <div class="select">
            <select class="select setOperator" data-fieldID="">
                <option value="true">IS TRUE</option>
                <option value="false">IS FALSE</option>
                <option value="isset">IS SET</option>
            </select>
        </div>
    </div>
</div>

<!-- criteria to check user role -->
<div id="criteria_required_role">
    <div class="column" data-fieldID="">
        <div class="select">
            <select class="select setOperator" data-fieldID="">
                <option value="=">HAS ROLE</option>
                <option value="!=">IS NOT</option>
            </select>
        </div>
    </div>
    <div class="column" data-fieldID="">
        <div class="select">
            <select class="select setValue" data-fieldID="">
                <?php
                $roles = ORM::for_table( _table_roles)->where('role_type','access')->find_many();
                foreach ($roles as $role)
                {
                ?>
                <option value="<?php echo strtolower($role->name) ?>"><?php echo ucfirst($role->name) ?></option>
                <?php
                }
                ?>
            </select>
        </div>
    </div>
</div>


</div><!-- end wrapper around hidden criteria builder html elements -->