/* global $, XHR_PATH, quickNotice */

$(document).ready(function(){

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

