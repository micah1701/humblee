<h1 class="title">Manage Users</h1>

<div class="columns">
    <div class="column">
        <form name="search" action="<?php echo _app_path . 'admin/users'; ?>" method="POST">
        	<div class="field">
            	<label class="label" for="user_search">
            	    Search
            	</label>
            	<p class="control has-icons-right">
                	<input class="input" type="text" name="user_search" id="user_search" size="50" value="<?php echo (isset($_POST['user_search'])) ? htmlspecialchars(trim($_POST['user_search'])) : ''; ?>">        	    
            	    <span class="icon is-small is-right">
            	        <i class="fas fa-search"></i>
            	    </span>
            	    <p class="help">Search by name, username, or e-mail address</p>
            	</p>
        	</div>
        	<p>
            	<input class="button is-primary" type="submit" id="btn_search" value="Search">
            	<input class="button"type="button" id="btn_reset" value="Reset" onclick="location.href = '<?php echo _app_path . 'admin/users'; ?>'">        	    
        	</p>
        </form>    
    </div>
    
    <div class="column">
        <label class="label">Filter by role</label>
        <div class="select">
            <select id="filter_by_role" onchange="window.location.href = '<?php echo (isset($_GET['user_search'])) ?"?user_search=".$_GET['user_search']."&filter=" : "?filter=" ?>'+this.value">
                <option value="">Show All</option>
            <?php
            foreach ($roles as $roleID => $roleName)
            {
            ?>
                <option value="<?php echo $roleID ?>"<?php echo (isset($_GET['filter']) && $_GET['filter'] == $roleID) ? " SELECTED" : "" ?>><?php echo ucwords($roleName) ?></option>
            <?php
            }
            ?>
            </select>	
        </div>	     
    </div>
</div>

<?php
if(count($users) == 0)
{
    echo '<p class="is-size-5">No Results Found</p>';
}
else
{
?>
<table class="table is-striped is-hoverable is-fullwidth">
    <thead>
        <tr>
            <th>Name</th>
            <th>E-Mail Address</th>
            <th>Username</th>
            <th>Roles</th>
            <th>Last Login</th>
            <th>Logins</th>
            <th>&nbsp;</th>
        </tr>
    </thead>
    <tbody>
    <?php
    foreach($users as $user)
    {
		if(in_array($user->email,$hidden_users)){ continue; } // don't show hidden users
		if($user->active != 1){ continue; } // don't show deleted users
		
		$is_current_user = ($_SESSION[session_key]['user_id'] === $user->id) ? true : false;
		
		$user_roles = array();
 		$get_roles = ORM::for_table( _table_user_roles )->where('user_id',$user->id)->find_many(); // create an array of role_id's assigned to this user
 		foreach($get_roles as $user_role){
			$user_roles[$user_role->role_id] = array('role_id' => $user_role->role_id, 'role_name' => $roles[$user_role->role_id]);
		}

        //skip this user if they don't have the filtered role
		if(isset($_GET['filter']) && is_numeric($_GET['filter']) && !array_key_exists($_GET['filter'], $user_roles))
		{
			continue; 
		}
	?>
	    <tr data-userid="<?php echo $user->id ?>">
            <td><?php echo $user->name ?></td>
            <td><?php echo $user->email ?></td>
            <td><?php echo $user->username ?></td>
            <td class="rolesColumn" data-userroles="<?php echo implode(',', array_column($user_roles, 'role_id')); ?>">
    <?php 
            echo ucwords(implode(', ', array_column($user_roles, 'role_name')));
    ?>
            </td>
            <td>
        <?php
        if($user->last_login == "0000-00-00 00:00:00")
        {
            echo "Never";
        }
        else
        {
        ?>
            <span class="tooltip" data-tooltip="<?php echo date("M j, Y g:ia",strtotime($user->last_login)) ?>"><?php echo $tools->time_ago($user->last_login) ?></span>
        <?php
        }
        ?>
            </td>
            <td><?php echo $user->logins ?></td>
            <td>
            <?php //don't let current user delete themeselves or change their own roles 
            if(!$is_current_user)
            { 
            ?>
                <span class="icon setRoles tooltip has-text-primary" data-tooltip="Manage Roles"><i class="fas fa-key"></i></span>
                <span class="icon removeUser tooltip has-text-danger" data-tooltip="Remove User"><i class="fas fa-ban"></i></span>
        	<?php
        	}
        	?>
            </td>
        </tr>
    <?php
    } //end loop through $users
    ?>
    </tbody>
    
</table>
<?php
} // end count($users) > 0
?>

<div id="manageRolesDialog" class="modal">
    <div class="modal-background"></div>
    <div class="modal-card">
        <header class="modal-card-head">
            <p class="modal-card-title">Manage User Roles</p>
            <button class="delete" aria-label="close" title="close dialog"></button>    
        </header>
        <section class="modal-card-body">
            <div class="columns is-multiline">
                
            <?php
            foreach($roles as $roleID => $roleName)
            {
                if($roleName == "developer" && !Core::auth('developer'))
                {
                    continue; // only developers can add other developers
                }
            ?>
                <div class="column is-one-quarter">
                    <label class="checkbox">
                        <input type="checkbox" name="roles[]" value="<?php echo $roleID ?>">
                        <?php echo ucwords($roleName) ?>
                    </label>                    
                </div>
            <?php
            }
            ?>
            </div>
        </section>
        <footer class="modal-card-foot">
            <button class="button is-info saveUserRoles">Save Roles</button>
            <button class="button cancel">Cancel</button>
        </footer>
    </div>
</div>