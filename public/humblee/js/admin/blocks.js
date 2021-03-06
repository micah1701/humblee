/* global $ */

var param_template_wysiwyg = "<div class=\"content\" id=\"edit_content\">{content}</div>\n<input type=\"hidden\" id=\"content\" name=\"content\">";

var param_template_markdown = "<textarea name=\"content\" class=\"textarea\" id=\"edit_content\">{content}</textarea>";

var param_template_textfield = "<input type=\"text\" class=\"input\" name=\"content\" id=\"edit_content\" value=\"{content}\">";

var param_template_textarea = "<textarea name=\"content\" class=\"textarea\" id=\"edit_content\">{content}</textarea>";

// JSON object
var param_template_multifield = "[\n"
								+" {\"username\":\n   {\"label\":\"User Name\",\n    \"input\": \"<input type=\\\"text\\\" name=\\\"username\\\" value=\\\"{content}\\\" />\"\n   }\n  },\n"
								+" {\"age\":\n   {\"label\":\"Age\",\n    \"input\": \"<input type=\\\"text\\\" name=\\\"age\\\" value=\\\"{content}\\\" />\"\n   }\n  },\n"
								+" {\"confirmed\":\n   {\"label\":\"Do you confirm this?\",\n    \"input\": \"<input type=\\\"checkbox\\\" name=\\\"confirmed\\\" value=\\\"1\\\" selected-data=\\\"{content}\\\" />\"\n   }\n },\n"
								+"]";

var param_template_customform = "admin/contentWidgets/widget/edit.php";

var original_params = "";
			
$(document).ready(function(e) {
	$("#input_type").on("change",function(){
		if(original_params == "")
        {
            original_params = $("#input_parameters").val();
        }
        
        if( $("#reset_params").hasClass('is-invisible'))
        {
            $("#reset_params").removeClass('is-invisible');
        }

		var template = eval("param_template_"+$(this).val());
		$("#input_parameters").val( template  ); 
	});
    
    $("#reset_params").click(function(element){
        element.preventDefault()
        $("#input_parameters").val( original_params );
        $(this).addClass('is-invisible');
    });
	   
});	