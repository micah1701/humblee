/* global $, setEscEvent */
$(document).ready(function(){
    
});

function openPagePropertiesModal(page_id)
{
    
    $.post(XHR_PATH + 'getPageProperties', {page_id:page_id}, function(response){
        if(response.success)
        {
            $("#label").val(response.label);
            $("#slug, #original_slug").val(response.slug);
            $("#template_id").val(response.template_id);
            $("#active").attr('checked',response.active);
            $("#display_in_sitemap").attr('checked',response.display_in_sitemap);
            $("#required_role").val(response.required_role);
            
            //open the modal
            $("#editPageDialog").addClass('is-active');
    
            //register ESC key and other ways to close the modal
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