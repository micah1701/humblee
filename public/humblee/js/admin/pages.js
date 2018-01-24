/* global $, setEscEvent */
$(document).ready(function(){
    
});

function openPagePropertiesModal(page_id)
{
    
    $("#editPageDialog").addClass('is-active');
    
    setEscEvent('pageProperties',function () { closePagePropertiesModal() });
    $("#editPageDialog .delete, #editPageDialog button.cancel").on("click",function(){
        closePagePropertiesModal();
    });
}

function closePagePropertiesModal()
{
    $("#editPageDialog").removeClass('is-active');
}