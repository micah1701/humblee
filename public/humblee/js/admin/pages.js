/* global $, setEscEvent, XHR_PATH, APP_PATH */

$(document).ready(function(){
	
});

function openPagePropertiesModal(page_id)
{
    $("#page_id").val(page_id);
    
    $.post(XHR_PATH + 'getPageProperties', {page_id:page_id}, function(response){
        if(response.success)
        {
            
            $("#label").val(response.label);
            $("#slug").val(response.slug);
            $("#original_slug").val(response.slug);
            $("#template_id").val(response.template_id);
            $("#active").attr('checked',response.active);
            $("#display_in_sitemap").attr('checked',response.display_in_sitemap);
            $("#required_role").val(response.required_role);
            
            $("#saveButton").on('click',function(e){
            	e.preventDefault();
            	savePageProperties();
			});
            
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

function scrubURL(val){
	val = " "+val+" "; // add padding to find/remove certain common words
	return	val.replace(/[^a-zA-Z0-9 -]/g, '') // remove invalid chars
				.replace(/\sthe\b/gi, '-') //strip "the"
				.replace(/\sand\b/gi, '-') //strip "and"
				.replace(/\sof\b/gi,  '-') // strip "of"
				.replace(/\sfor\b/gi, '-') // strip "for"
				.replace(/\son\b/gi,  '-') // strip "on"
				.replace(/\s+/gi, '-') // collapse whitespace and replace by -
				.replace(/-+/gi, '-')  // collapse dashes if more then one in a row
				.substring(1).slice(0,-1) // strip dashes at start and finish from initial padding	
				.toLowerCase();
}

function updateSlug(page_name){
    var scrubed = scrubURL(page_name);
	$('#slug').val(scrubed);	
	
	if(scrubed != $('#original_slug').val() && $('#original_slug').val() != "")
	{
		$("#reset_slug_link")
		.fadeIn('fast')
		.on("click", function(){
			$('#slug').val( $('#original_slug').val() );
			$("#reset_slug_link")
			 .off("click")
			 .fadeOut('fast');
		});
	}
	else
	{
		$("#reset_slug_link")
		 .off("click")
		 .fadeOut('fast');
	}	
}

function savePageProperties()
{
    var postData =  {
                        hmac_token : $("#hmac_token").val(),
                        hmac_key : $("#hmac_key").val(),
                        page_id : $("#page_id").val(),
                        label : $("#label").val(),
                        slug : $("#slug").val(),
                        template_id : $("#template_id").val(),
                        active: ( $("#active").is(':checked')) ? 1 : 0,
                        display_in_sitemap: ( $("#display_in_sitemap").is(':checked')) ? 1 : 0,
                        required_role: $("#required_role").val() 
                    };
    $.post(XHR_PATH + 'setPageProperties', postData, function(response){
       
        if(response.success)
        {
            //do something here to oupdate the list of pages to reflect any changes made to this page
            var doSomething = "something";
            
            //then close the modal
            closePagePropertiesModal();
        }
        else if(response.error != undefined)
        {
            alert(response.error);
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
    $("#saveButton").off("click"); // unbind the "onclick" event
}