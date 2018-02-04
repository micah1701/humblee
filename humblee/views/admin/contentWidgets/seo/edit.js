/* global mediamanager, $ */

$(document).ready(function(){
   $("button.og_image_picker").on("click",function(e){
       e.preventDefault(); 
       mediamanager();
   });
});

//data returned from media manager iframe for a selected file
function handleMediaManagerSelect(fileData)
{
    var imagePath = encodeURI(window.location.protocol +"//"+ window.location.hostname + fileData.url);
    $("#og_image").val(imagePath);
}

function shrinkToFit(field_id,max_width)
{
    var field = $("#"+field_id),
        text = field.html()+" ";
        
        if( field.width() > max_width)
        {
            //shorten by dropping last word and try again
            var lastIndex = text.lastIndexOf(" ");
            var newText = text.substring(0, lastIndex);
            console.log(field.width() +" of "+ max_width +": "+ text +"/"+ newText);
            field.html( newText + "...");
        }
        else
        {
            //it's short enough, return true
            return true;
        }
}

function charCount(element)
{
  var current_len = element.val().length,
		max_len = element.attr("maxlength"), 
		content = element.val(),
		label = element.attr("id")+"_count_label";
	
	if( current_len > max_len )
	{		
		element.val( content.substring(0,max_len));	
		current_len = max_len;
	}
	
	if( $("#"+label).length == 0)
	{
		element.parent().append('<p class="help" id="'+label+'"></p>');	
	}

	$("#"+label).html( current_len +" of "+max_len +" characters.");		
}

$(document).ready(function(){
   $("#page_title, #og_title").on("keyup",function(){
       
       var title_val = $("#page_title").val();
       $("#google_sample_title").html(title_val);
       shrinkToFit('google_sample_title',300);
       
       var fb_title = ($("#og_title").val() == "") ?  title_val : $("#og_title").val();
       $("#facebook_sample_title").html(fb_title);
       shrinkToFit('facebook_sample_title',500);

   });
   
   $("#meta_description, #og_description").on("keyup",function(){
       var desc_val = $("#meta_description").val();
       $("#google_sample_description").html(desc_val);
       
       var fb_desc = ($("#og_description").val() == "") ? desc_val : $("#og_description").val();
       $("#facebook_sample_description").html(fb_desc)
   });
   
   $(".lengthcount").each(function(){
		charCount( $(this));
		$(this).keyup( function(){ charCount( $(this) ) } )
	});
});