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
		console.log(or_index);
		// draw the outer "or" block and add to DOM
		$("#cirteria_builder").append(getBlock('criteria_or_block',or_index));

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

			// determine which which criteria operator and value fields to show
			switch(and_blocks.type) {

				case 'i18n' :
					criteria_id = 'criteria_url_i18n_segment';
				break;

				case 'session_key' :
					criteria_id = 'criteria_session_key';
				break;

				case 'required_role' :
					criteria_id = 'criteria_user_role';
			}

			// draw the "operator" and "value" fields for this "and" criteria
			html+= getBlock(criteria_id,and_blockID);

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