<!-- list of input fields below to be saved as content -->
<input type="hidden" name="serialize_fields" value="page_title,meta_description,og_title,og_description,og_image">
<textarea style="display:none" id="edit_content"></textarea>

<div class="columns">
    <div class="column">
        
        <div class="field">
            <label class="label" for="page_title">SEO Page Title:</label> 
                <div class="control">
                    <input type="text" class="input" id="page_title" name="page_title" value="<?php echo (isset($content_array['page_title'])) ? $content_array['page_title'] : '' ?>">
                    <p>The page title should describe the given page and include the most important words you want to rank for in search results. Note that search engines will only display the first 60-70 characters of this title.</p>
                </div>
        </div>
        
        <div class="field">
            <label class="label" for="meta_description">SEO Page Description:</label> 
                <div class="control">
                    <textarea class="textarea lengthcount" maxlength="300" id="meta_description" name="meta_description"><?php echo $content_array['meta_description'] ?></textarea>
                    <p class="help" id="meta_description_count_label">test</p>
                </div>
        </div>
        
    </div>

    <div class="column">
        <div id="google_sample">
            <span id="google_sample_title"><?php echo (isset($content_array['page_title'])) ? $content_array['page_title'] : 'Page Title Goes Here' ?></span>
            <span id="google_sample_url"><?php echo $_SERVER['HTTP_HOST'] . _app_path . ltrim($page_data->url,"/") ?></span>
            <span id="google_sample_description"><?php echo (isset($content_array['meta_description'])) ? $content_array['meta_description'] : 'Description goes here' ?></span></span>
        </div>
        <i>This is a sample of how this page may appear in Google's search results.</i>
    </div>

</div>
<div class="box">
    <p class="is-size-5">Open Graph Tags</p>
    <p>Used by Facebook and other social media sharing sites</p>
    
    <div class="field">
        <label class="label" for="og_image">Open Graph Image Path:</label>
        <input class="file-input" type="file" id="og_image" name="og_image">
        <span class="file-cta">
            <span class="file-icon"><i class="fas fa-upload"></i></span>
            <span class="file-label">
                Choose a file…
            </span>
        </span>
    
    <img id="og_image_preview" width="300" src="<?php echo (isset($content_array['og_image'])) ? $content_array['og_image'] : '' ?>" <?php if(!isset($content_array['og_image']) || $content_array['og_image']
     == ""){ echo 'style="display: none"'; } ?>>
         
    </div>
</div>

 
<style type="text/css">
    #google_sample {
        width: 560px; /* 600 less padding */
        background-color: #fff;
        height: auto;
        padding: 10px 20px;
        font-family: arial,sans-serif;
    }
        #google_sample_title {
            font-size: 18px;
            color: #1a0dab;
            height: 24px;
        }
        #google_sample_url {
            display: block;
            font-size: 14px;
            color: #006621;
        }
        #google_sample_description {
            font-size: small;
            color: #545454;
        }
</style> 

<script type="text/javascript"> /* Global $ */


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
		content = element.val();
	
	if( current_len > max_len )
	{		
		element.val( content.substring(0,max_len));	
		current_len = max_len;
	}	

	
	$("#meta_description_count_label").html( current_len +" of "+max_len +" characters.");	
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
</script>