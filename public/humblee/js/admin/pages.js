/* global $, setEscEvent */
$(document).ready(function(){
    
});

function openPagePropertiesModal(page_id)
{
    
    $.post(APP_PATH_XHR + 'getPageProperties', {page_id:page_id}, function(response){
        if(response.success)
        {
            
            
            $("#editPageDialog").addClass('is-active');
    
            setEscEvent('pageProperties',function () { closePagePropertiesModal() });
            $("#editPageDialog .delete, #editPageDialog button.cancel").on("click",function(){
                closePagePropertiesModal();
            });
            
            
        }
        else if(response.error != undefined)
        {
            alert(response.error)
        }
        else
        {
            alert(response);
        }
    });
    
    
}

function closePagePropertiesModal()
{
    $("#editPageDialog").removeClass('is-active');
}