/* global $, XHR_PATH, confirmation, setEscEvent, unsetEscEvent, quickNotice */
$(document).ready(function(){
    
    $(".removeUser").on("click",function(){
        var userRow = $(this).closest('tr'),
            userID = userRow.data('userid'),
            removeUser = function(){
                $.post(XHR_PATH+'removeUser',{userID:userID},function(response){
                    if(response.success)
                    {
                        userRow.fadeOut('slow');
                        quickNotice("User Removed","is-success");                        
                    }
                    else
                    {
                        quickNotice("Error could not delete user","is-warning");
                    }
            }); 
        };
   
        confirmation('You are about to <span class="has-text-danger">remove this user!</span> This action <u>cannot</u> be undone.<br>'
            +'Note: you can also disable access by simply removing a user\'s "login" role.',
            removeUser,
            function(){ return false; }
        );
    });
    
    $(".setRoles").on("click",function(){
        
        var userRow = $(this).closest('tr'),
            userID = userRow.data('userid'),
            rolesTD = userRow.find('td.rolesColumn'),
            roles = rolesTD.data('userroles').split(','),
            newRoles = '',
            newRolesNames = '';
        
        //pre-check the boxes this user has
        if(roles[0] != "")
        {
            $.each(roles,function(index,roleID){
                $("#manageRolesDialog :checkbox[value="+roleID+"]").prop("checked","true");                
            });            
        }

        //open the roles manager modal dialog
        $("#manageRolesDialog").addClass('is-active');

        //register ESC key and other ways to close the modal
        setEscEvent('pageProperties',function () { closeManageRolesModal() });
        $("#manageRolesDialog .delete, #manageRolesDialog button.cancel").on("click",function(){
            closeManageRolesModal();
            unsetEscEvent('pageProperties');
        });
        
        $("#manageRolesDialog button.saveUserRoles").on("click",function(){
            //make comma seperated lists from the selected new roles
            $.each($("input[name='roles[]']:checked"), function(){
                newRoles+= $(this).val()+",";
                newRolesNames+= $.trim($(this).closest('label').text()) +", ";
            });
            //strip that last comma
            newRoles = newRoles.slice(0,-1);
            newRolesNames = newRolesNames.slice(0,-2); //and remove the whitespace too
            
            $.post(XHR_PATH+'setUserRoles',{userID:userID,roles:newRoles},function(response){
                if(response.success)
                {
                    rolesTD.data('userroles',newRoles);
                    rolesTD.html(newRolesNames);
                    closeManageRolesModal();
                    quickNotice("Roles Updated","is-success");
                }
                else
                {
                    quickNotice("New roles could not be saved","is-danger");
                }
            });
        });
        
    });
});

function closeManageRolesModal()
{
    $("#manageRolesDialog button.saveUserRoles").off("click"); //unbind function
    $("#manageRolesDialog :checkbox").prop("checked",false); //uncheck any checked boxes
    $("#manageRolesDialog").removeClass('is-active'); // close the modal dialog
}