/* global $, XHR_PATH, quickNotice */

$(document).ready(function(){

	//generate the initial criteria builder
	makeCriteriaBuilder();

	//make list of perona's sortable
	$('ul.sortable').sortable({
			listType: 'ul',
			handle: 'a',
			items: 'li',
			forcePlaceholderSize: true,
			helper: 'clone',
			axis: "y",
			opacity: .6,
			placeholder: 'ui-state-highlight',
			revert: 250,
			tabSize: 25,
			tolerance: 'pointer',
			toleranceElement: '> div',

			update: function(){
			    var serialized = $('ul.sortable').sortable('serialize');
				var order = $('ul.sortable').sortable('toArray');
				$.post(XHR_PATH +'p13n_order_priorities',{ list_order:order }, function(response){
					if(response.success)
					{
						quickNotice('Persona priority updated');
						return false;
					}
					else
					{
					    quickNotice('Error updating priorities','is-danger',5000);
					}
				});

			}
		})
		.disableSelection();

		//listen for changes in the criteria builder and update criteria json
		$("#criteria_builder")
		.on("change", "select,input", function(){
				if(!$(this).hasClass('setPersona'))
				{
					updateCriteria();
				}
		})
		.on("change","select.setPersona", function(){
			var setPersona = $(this),
				orID = setPersona.closest('.criteria_OR').data('fieldid'),
				andID = setPersona.data('fieldid'),
				persona_type = setPersona.val();

				//remove the old operator and value columns
				$(".criteria_OR[data-fieldid="+orID+"] .setValue[data-fieldid="+andID+"]").closest('.column').remove();
				$(".criteria_OR[data-fieldid="+orID+"] .setOperator[data-fieldid="+andID+"]").closest('.column').remove();

				//add new operator and value columns
				setPersona.closest('.column').after(getBlock('criteria_'+persona_type, andID));

				updateCriteria();

		})
		.on("click",".criteria_add_and", function(){
			var or_block = $(this).closest('.criteria_OR'),
				newCriteriaID = $('.columns', or_block).length,

				html = getBlock('criteria_seperator',newCriteriaID);

				html+= '<div class="columns" data-fieldID="'+newCriteriaID+'">\n';
				html+= getBlock('criteria_select_persona',newCriteriaID);
				html+= '</div>\n';

			or_block.append(html);
		});

});

//return the HTML of a given div by id.
//passing "setFieldID" looks for the data-fieldID attribuite in the HTML and updates it to that ID
function getBlock(blockID,setFieldID)
{
	var html = $("#"+blockID).html();
	return html.replace(/fieldID=\"\"/gi,"fieldID=\""+setFieldID+"\"");
}

function makeCriteriaBuilder(){
	var criteria = JSON.parse($("#criteria").val());

	$.each(criteria, function(or_index,or_blocks){

		// draw the outer "or" block and add to DOM
		$("#criteria_builder").append(getBlock('criteria_or_block',or_index));

		// heres an object of the block that was just added
		var or_block = $(".criteria_OR[data-fieldID="+or_index+"]");

		$.each(or_blocks,function(and_index, and_blocks){

			var and_blockID = and_index,
				criteria_id = '',
				html = '';

			// draw the "AND" seperator between "and" criteria
			if(and_index > 0)
			{
				html+= getBlock('criteria_seperator',and_blockID); // "and" text between criteria
			}

			//wrap this "row" of criteria fields
			html+= '<div class="columns" data-fieldID="'+and_blockID+'">\n';

			// draw the "select persona type" <select> dropdown
			html+= getBlock('criteria_select_persona', and_blockID);

			// draw the "operator" and "value" fields for this "and" criteria
			html+= getBlock('criteria_'+and_blocks.type, and_blockID);

			// close the wrapper around this "row"
			html+= "\n</div>";

			//add this 'and' criteria row to the outer 'or' block
			or_block.append(html);

			//once the row has been output to the DOM, update fields as needed:
			$(".criteria_OR[data-fieldid="+or_index+"] .setPersona[data-fieldid="+and_blockID+"]").val(and_blocks.type);
			$(".criteria_OR[data-fieldid="+or_index+"] .setValue[data-fieldid="+and_blockID+"]").val(and_blocks.value);
			$(".criteria_OR[data-fieldid="+or_index+"] .setOperator[data-fieldid="+and_blockID+"]").val(and_blocks.operator);

		}); // end loop throuugh "and" criteria

	}); // end loop trhough "or" blocks

}


//find criteria fields in DOM and gererate JSON object
function updateCriteria()
{
	var json = "[";
	var or_criteria_json = "";

	$("#criteria_builder .criteria_OR").each(function(or_index,or_elements)
	{
		or_criteria_json += "[";

		var and_criteria_json = "";
		$(".criteria_OR[data-fieldid="+or_index+"] .columns").each(function(and_blockID,and_elements)
		{
			and_criteria_json += '{';
			and_criteria_json += '"type":"'+ $(".criteria_OR[data-fieldid="+or_index+"] .setPersona[data-fieldid="+and_blockID+"]").val() +'",';
			and_criteria_json += '"operator":"'+ $(".criteria_OR[data-fieldid="+or_index+"] .setOperator[data-fieldid="+and_blockID+"]").val() +'",';
			and_criteria_json += '"value":"'+ $(".criteria_OR[data-fieldid="+or_index+"] .setValue[data-fieldid="+and_blockID+"]").val() +'"';
			and_criteria_json += '},';
		}); //end of individual "and" block

		//add the "and" criteria, without the last "," at the end
		or_criteria_json += and_criteria_json.slice(0,-1);
		or_criteria_json += "],"; // end of "or" block
	});

	//add the "or" block, without the last "," at the end
	json += or_criteria_json.slice(0,-1);
	json += "]";

	$("#criteria").val(json);


	if( $("#reset_params").hasClass('is-invisible'))
    {
        $("#reset_params").removeClass('is-invisible');
    }

	$("#reset_params").click(function(element){
        element.preventDefault()
        $("#criteria").val( $("#criteria_original").val() );
        $("#criteria_builder").html('');
        makeCriteriaBuilder();
        $(this).addClass('is-invisible');
	});
}