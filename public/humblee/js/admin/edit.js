/* global $, dateFormat, confirmation, XHR_PATH, APP_PATH */
$(document).ready(function(){

    $("#select_content_type").change(function(){
        window.location = APP_PATH+'admin/edit/?page_id='+$("#page_id").val()+'&content_type='+$(this).find("option:selected").val(); 
    });    
    
    $("#save").on("click",function(){
        submitForm();
    });
    $("#publish").on("click",function(){
        validateForm(true); 
    });
    
});

function validateForm(publish)
{
    //check if a new version of this content has been saved since user began working
    $.post(XHR_PATH+'latestRevision',{page_id: $("#page_id").val(), content_type: $("#content_type_id").val() }, function(response){
		
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
		    
            if( latestRevisionDate < pageLoadDate)
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

function submitForm(publish)
{
    if(publish)
    {
        $("#live").val(1);
    }
    
    var action = (publish) ? "publish" : "save";
    alert('gonna '+action+' the form now');
}