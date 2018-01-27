/* global $ */

function shrinkToFit(field_id,max_width)
{
    var field = $("#"+field_id),
        text = field.html();
        
        if( field.width() > max_width)
        {
            //shorten by dropping last word and try again
            var lastIndex = text.lastIndexOf(" ");
            var newText = text.substring(0, lastIndex);
            console.log(field.width() +" of "+ max_width +": "+ text +"/"+ newText);
            field.html( newText + " ...");
            // this makes a horrible endless loop
            //return shrinkToFit(field_id,max_width);
        }
        else
        {
            //it short enough, return true
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
   $("#page_title").on("keyup",function(){
       var title_val = $(this).val();
       $("#google_sample_title").html(title_val);
       shrinkToFit('google_sample_title',300);
   });
   
   $("#meta_description").on("keyup",function(){
       var desc_val = $(this).val();
       $("#google_sample_description").html(desc_val);
   });
   
   $(".lengthcount").each(function(){
		charCount( $(this));
		$(this).keyup( function(){ charCount( $(this) ) } )
	});
});


function openKCFinder(field) {
    window.KCFinder = {
        callBack: function(url) {
            field.value = url;
            $("#og_image_preview").attr('src',url).fadeIn('fast');
            window.KCFinder = null;
        }
    };
    window.open('<?php echo _app_path ?>core/libs/kcfinder/browse.php?type=images&dir=images', 'og_image',
        'status=0, toolbar=0, location=0, menubar=0, directories=0, ' +
        'resizable=1, scrollbars=0, width=800, height=600'
    );
}