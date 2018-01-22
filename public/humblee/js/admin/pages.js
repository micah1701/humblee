function addPage(parent_id){
	  $.post(XHR_PATH +'add_page/',{ parent_id:parent_id }, function(data){
		  	data = $.trim(data);
			if( isNaN( parseInt(data)) ){
				alert("Error! "+data);
			}else{
				loadPagesTable();
				loadContentMenu(); // refresh the content nav too
			}
	  });
	}

var openItems = new Array;  
function loadPagesTable(){
	$("#content").append( $("#page_toolbar").fadeOut(0) ); // move the toolbar out of the hovered div before reloading the list
	$(".pages_menu_item").unbind('hover'); // stop the hover effect while the div is reloading
	
	// before reloading table, get list of open items
	$(".contentViewing").each(function(){
	   openItems.push($(this).attr('id')); 
	});

	$.get(XHR_PATH +'loadPagesTable/',function(data){
		$("#pages").html( $.trim(data) );
		
		//make menu collapsable/expandable
		$("#pages li:has(ul)").addClass('contentContainer');
		$("#pages li ul").css({display:'none'});		
		
		
		$("#pages li").toggle(			
				  	function() { 
					  $(this).children('ul').slideDown();
					  
						if ($(this).hasClass('contentContainer')) {
                            $(this).removeClass('contentContainer').addClass('contentViewing');
					  	}
				  	}, 
				  	function() { 
			            $(this).children('ul').slideUp();
					  
                        if ($(this).hasClass('contentViewing')) {
                            $(this).removeClass('contentViewing').addClass('contentContainer');
                        }
					} 
				); 
		
		//add tool bar on hover
		$(".pages_menu_item").hover( function(){		
			var item_id = $(this).attr('data');
			var page_name = $("span",this).html();
			
			$(this).append( $("#page_toolbar") );
				//bind "Edit Project" pop-up box
				$(".ui-icon-wrench").click(function(e){ e.stopPropagation(); editPage(item_id) });
							
				//bind "Add Supbage" function
				$(".ui-icon-arrowreturnthick-1-e").one('click', function(e){ e.stopPropagation(); addPage(item_id) });
				
				//bind "Remove Project" delete action
				$(".ui-icon-trash").click(function(e){ e.stopPropagation(); 
					var x = confirm("Are you sure you want to PERMANENTLY DELETE the page \""+page_name+"\"?\n\nAll content, past and present, associated with this page will be lost!\n\nThis action can not be undone");
					if(x){
						$.get(XHR_PATH +'delete_page/',{page_id:item_id},function(data){
							if($.trim(data) != "done"){
								alert(data);
							}else{
								loadPagesTable();
								loadContentMenu(); // refresh the content nav too
								return false;
							}
						});
					}else{
						return false;
					}
				
				 });
					
			$("#page_toolbar").fadeIn(0);
		}, function(){
			$(".ui-icon-wrench, .ui-icon-trash, .ui-icon-arrowreturnthick-1-e").unbind('click');
			$("#page_toolbar").fadeOut(0);
		});
		
		$(openItems).each(function(i,value){
		   $("#"+value).children('ul').slideDown(0);
		   $(this).addClass('contentViewing');  
		});

		
		//make sortable
		$("#pages ul").nestedSortable({
			listType: 'ul',
			handle: '.ui-icon-arrowthick-2-n-s',
			placeholder: 'ui-state-highlight',
			forcePlaceholderSize: true,
			maxLevels: 4,
			helper:	'clone',
			items: 'li',
			opacity: .6,
			tabSize: 25,
			update : function () {	
				var x = confirm("Are you sure you want to reorder this page in the sitemap?");
				if(x){
					var order = $('#pages ul').nestedSortable('serialize'); 
					$.post(XHR_PATH +'order_pages/',{ list_order:order }, function(data){
						data = $.trim(data);
						if(!data == "true"){
							alert(data)
						}
					//	loadPagesTable();
					loadContentMenu(); // refresh the content nav 
					});
				}else{
					return false;
				}
			}
		})
		.disableSelection();
	});	
	
} // end "loadPagesTable()" function

function editPage(item_id){ // load the data to be put in the pop-up
 $.getJSON(XHR_PATH +'get_page_properties/',{page_id:item_id},function(data){
		$("#label").val(data.label);
		$("#slug, #original_slug").val(data.slug);
		$("#template_id").val(data.template_id);
		$("#active").attr('checked', data.active);
		$("#searchable").attr('checked', data.searchable);
		$("#display_in_sitemap").attr('checked', data.display_in_sitemap);
		$("#page_id").val(item_id);
		$("#required_role").val(data.required_role);
        
        var disabled_status = (data.template_disabled == 1 ) ? true : false;
        $("#template_id").attr('disabled', disabled_status );

		$("#editPageDialog").dialog('open');
 });	
}

function pageData(){ // return the entered/modified data from the active pop-up
	return { page_id: $("#page_id").val(),
			 label: $("#label").val(),
			 slug: $("#slug").val(),
			 template_id: $("#template_id").val(),
			 active: ( $("#active").is(':checked')) ? 1 : 0,
			 searchable: ( $("#searchable").is(':checked')) ? 1 : 0,
			 display_in_sitemap: ( $("#display_in_sitemap").is(':checked')) ? 1 : 0,
			 required_role: $("#required_role").val()
	}
}

function scrubURL(val){
	val = " "+val+" "; // add padding to find/remove certain common words
	return val.replace(/[^a-zA-Z0-9 -]/g, '') // remove invalid chars
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
	
	if(scrubed != $('#original_slug').val() && $('#original_slug').val != ""){
		$("#reset_slug_link").fadeIn('fast');
	}else{
		$("#reset_slug_link").fadeOut('fast');	
	}	
}

$(document).ready( function() {
	
	loadPagesTable();

	$("#editPageDialog").dialog({
			//width: '90%',
            //maxWidth: 650,
			width: 'auto',
            height: 'auto',
			modal: true,
			autoOpen: false,
			resizable: false,
			buttons: { "Save Changes >" : function (){ 						
							var sendData = pageData();
							$.post(XHR_PATH +'save_page_properties/',sendData, function(data){
								data = $.trim(data);
								if(data != "done"){ alert(data);
								}								
								loadPagesTable(); //refresh list
								loadContentMenu(); // refresh the content nav too
								$("#editPageDialog").dialog('close');
							});
					  },
						"Cancel" : function(){ $(this).dialog('close'); }
			}
	});
				
});