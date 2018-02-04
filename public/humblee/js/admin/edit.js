/* global $, dateFormat, confirmation, setEscEvent, XHR_PATH, APP_PATH */
$(document).ready(function(){

    $("#select_content_type").change(function(){
        window.location = APP_PATH+'admin/edit/?page_id='+$("#page_id").val()+'&content_type='+$(this).find("option:selected").val(); 
    });    
    
    $("#save").on("click",function(){
        validateForm(false); 
    });
    $("#publish").on("click",function(){
        validateForm(true); 
    });
    
});

function mediamanager()
{
    var iframe = '<iframe src="'+APP_PATH+'admin/media?iframe=true" border="0">';
    $("#mediamanager").addClass('is-active');    
    $("#mediamanager .modal-card-body").html(iframe);
    
    setEscEvent('mediaManager',function () { closeMediamanager() });
    $("#mediamanager button.delete").on("click",function(){
        closeMediamanager();
    });
}
function closeMediamanager()
{
    $("#mediamanager").removeClass('is-active');  
}

function validateForm(publish)
{
    //check if a new version of this content has been saved since user began working
    $.post(XHR_PATH+'latestRevisionDate',{page_id: $("#page_id").val(), content_type: $("#content_type_id").val() }, function(response){
		
        if(response.error)
        {
			alert(response.error);
			return false;
		}
		else if(!response.success)
		{
		    alert(response);
		    return false;
		}
		else if(response.content)
		{
            var pageLoadDate = new Date($("#edit_time").val() ),
            latestRevisionDate = new Date( response.content.revision_date);
		    console.log(pageLoadDate+ "  " + latestRevisionDate);
		    
            if( latestRevisionDate > pageLoadDate)
            {
                var msg = (response.content.live == 1) ? "revision was published live to the site " : "draft was saved ";
                var msg2 = (publish) ? "publish" : "save";
                var confirmMessage = "While you were editing this content, a more recent "+msg
                                    +"by <strong>"+ response.content.name +'</strong> ('+ dateFormat('M j, Y g:ia',response.content.revision_date) +")<br><br>"
                                    +"Do you still want to "+msg2+" your changes?";
                confirmation(confirmMessage,function(){ submitForm(publish) }, function() { return false; });                    
            }
            else
            {
                submitForm(publish);
            }
		}
		else
		{
		    alert("Error. Could not validate form at this time");
		}
    });
}

//if publishing, run the validate() function first to check for more recent content
function submitForm(publish)
{
    if(publish)
    {
        $("#live").val(1);
    }
    
    // content multipart fields into a json array for stoarage
    if( $("#content_type").val() == "multifield" || $("#content_type").val() == "customform") 
 	{  
		var encoded_content = '{';
		$("#savecontent input:not(:hidden), #savecontent select").each( function(){
			if( $(this).attr('name') != "undefined" && $(this).attr('name') != "")
				
			{
				var value = $(this).val(), type = $(this).attr('type');
				if(type == "radio" || type == "checkbox")
				{
					value = ( $(this).attr('checked') ) ? 1 : 0;
				}
				encoded_content+='"'+ $(this).attr('name') +'":"' + value +'",';
			}
		});
		encoded_content = encoded_content.substr(0, encoded_content.length - 1) + '}';  // remove trailing "," from string then add ending bracket
		$("#edit_content").val(encoded_content);
	}
	
	$(window).unbind("beforeunload");
	document.forms["savecontent"].submit();
}