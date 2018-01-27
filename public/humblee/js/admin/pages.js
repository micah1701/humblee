/* global $, setEscEvent, XHR_PATH, APP_PATH */

$(document).ready(function(){
	loadPages();
	
});

function loadPages()
{
	saveToolbar();
	$.get(XHR_PATH + 'loadPagesTable', function(response){
		$("#pages").html(response);
		$("#pages ul:first").addClass('sortable');
		$("#pages ul").addClass('menu-list').not(':first').addClass('is-closed');
		$("#pages ul li a").not(':first').addClass('has-closed');
		
		$("a.menu_hasChildren").on('click', function(){
			var firstUL = $(this).parent().next('ul');
			if(firstUL.hasClass('is-closed'))
			{
				$(this).removeClass('has-closed');
				firstUL.removeClass('is-closed');	
			}
			else
			{
				$(this).addClass('has-closed');
				firstUL.addClass('is-closed');
			}
		});
		
		initiateToolBar();
		
		$('ul.sortable').nestedSortable({
			listType: 'ul',
			handle: '.order',
			items: 'li',
			forcePlaceholderSize: true,
			helper: 'clone',
			opacity: .6,
			placeholder: 'ui-state-highlight',
			revert: 250,
			tabSize: 25,
			tolerance: 'pointer',
			toleranceElement: '> div',
			update: function(){
				if(confirm("Are you sure you want to reorder this page in the sitemap?"))
				{
					var order = $('#pages ul.sortable').nestedSortable('serialize'); 
					$.post(XHR_PATH +'order_pages',{ list_order:order }, function(response){
						if(!response.success)
						{
							alert(response);
							return false;
						}
					});
				}
				else
				{
					return false;
				}
			}
		})
		.disableSelection();
		
		var serialized = $('ul.sortable').nestedSortable('serialize');
		console.log(serialized);
		
	});
}

function openPagePropertiesModal(page_id)
{
    $("#page_id").val(page_id);
    
    $.post(XHR_PATH + 'getPageProperties', {page_id:page_id}, function(response){
        if(response.success)
        {
            $("#label").val(response.label);
            $("#slug").val(response.slug);
            $("#original_slug").val(response.slug);
            $("#template_id").val(response.template_id);
            $("#active").attr('checked',response.active);
            $("#display_in_sitemap").attr('checked',response.display_in_sitemap);
            $("#required_role").val(response.required_role);
            
            $("#saveButton").on('click',function(e){
            	e.preventDefault();
            	savePageProperties();
			});
            
            //open the modal
            $("#editPageDialog").addClass('is-active');
    
            //register ESC key and other ways to close the modal
            setEscEvent('pageProperties',function () { closePagePropertiesModal() });
            $("#editPageDialog .delete, #editPageDialog button.cancel").on("click",function(){
                closePagePropertiesModal();
            });
        }
        else if(response.error != undefined)
        {
            alert(response.error)
        }
        else
        {
            alert(response);
        }
    });
}

// move the toolbar off the pages menu so it doesn't accidently get deleted
function saveToolbar()
{
	$("#page_toolbar").fadeOut(0);
	$('body').append($("#page_toolbar"));
}

//add tool bar on hover of pages menu items
function initiateToolBar()
{
	$(".pages_menu_item")
	.off( "mouseenter mouseleave" ) // remove any previous hover states bound to the pages menu
	.hover(function(){		
		var item_id = $(this).attr('data');
		var page_name = $("a",this).html();
		
		$("a", this).append( $("#page_toolbar") );
			//bind "Edit Project" pop-up box
			$(".page_toolbar_button.edit").on('click', function(e){ e.stopPropagation(); openPagePropertiesModal(item_id) });
						
			//bind "Add Supbage" function
			$(".page_toolbar_button.newpage").on('click', function(e){ e.stopPropagation(); addPage(item_id) });
			
			//bind "Remove Project" delete action
			$(".page_toolbar_button.trash").on('click', function(e){ e.stopPropagation(); deletePage(item_id,page_name); });
				
		$("#page_toolbar").fadeIn(0);
	}, function(){
		$(".page_toolbar_button.edit, .page_toolbar_button.order, .page_toolbar_button.newpage, .page_toolbar_button.trash").off('click');
		$("#page_toolbar").fadeOut(0);
	});

}

function scrubURL(val){
	val = " "+val+" "; // add padding to find/remove certain common words
	return	val.replace(/[^a-zA-Z0-9 -]/g, '') // remove invalid chars
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
	
	if(scrubed != $('#original_slug').val() && $('#original_slug').val() != "")
	{
		$("#reset_slug_link")
		.fadeIn('fast')
		.on("click", function(){
			$('#slug').val( $('#original_slug').val() );
			$("#reset_slug_link")
			 .off("click")
			 .fadeOut('fast');
		});
	}
	else
	{
		$("#reset_slug_link")
		 .off("click")
		 .fadeOut('fast');
	}	
}

function savePageProperties()
{
    saveToolbar();
    var postData = {
                    page_id : $("#page_id").val(),
                    label : $("#label").val(),
                    slug : $("#slug").val(),
                    template_id : $("#template_id").val(),
                    active: ( $("#active").is(':checked')) ? 1 : 0,
                    display_in_sitemap: ( $("#display_in_sitemap").is(':checked')) ? 1 : 0,
                    required_role: $("#required_role").val() 
    };
    $.post(XHR_PATH + 'setPageProperties', postData, function(response){
       
        if(response.success)
        {
            var page_id = $("#page_id").val();
            $(".pages_menu_item[data='"+ page_id +"'] a").html($("#label").val()).attr('title',$("#slug").val() );
            
            //then close the modal
            closePagePropertiesModal();
        }
        else if(response.error != undefined)
        {
            alert(response.error);
        }
        else
        {
            alert(response);
        }
       
    });
}

function closePagePropertiesModal()
{
    $("#editPageDialog").removeClass('is-active');
    $("#saveButton").off("click"); // unbind the "onclick" event
    initiateToolBar();
}

function addPage(parent_id)
{
	saveToolbar();
	$.post(XHR_PATH +'add_page',{parent_id:parent_id}, function(response){
		if(response.success)
		{
			var newPageItem = '<li id="pageID_'+response.page_id+'"><div class="pages_menu_item" data="'+ response.page_id +'"><a>New Page</a></div></li>';
			if(parent_id == 0)
			{
				$("#pages ul li").first().after(newPageItem);	
			}
			else
			{
				var parentPageItem = $(".pages_menu_item[data='"+ parent_id +"']").closest('li');
				if(parentPageItem.hasClass('menu_hasChildren'))
				{
					parentPageItem.find('ul').first().append(newPageItem);
				}
				else
				{
					parentPageItem.addClass('menu_hasChildren').after('<ul class="menu-list">'+newPageItem+'</ul>');
				}	
			}
			
			initiateToolBar();
		}
		else if(response.error)
		{
			alert(response.error);
		}
		else
		{
			alert(response);
		}
	});
}

function deletePage(page_id,page_name)
{
	$("#confirm_delete_pagename").html('&ldquo;<em>'+page_name+'</em>&rdquo;');

    saveToolbar();
    
    //open the modal
    $("#deletePageConfirmation").addClass('is-active');

    //register ESC key and other ways to close the modal
    setEscEvent('deletePageConfirmation',function () { closeDeletePageConfirmation() });
    $("#deletePageConfirmation button.cancel").on("click",function(){
        closeDeletePageConfirmation();
    });
    
    $("#deleteButton").on('click',function(){
    	$(this).attr('disabled',true);
    	
    	$.post(XHR_PATH +'delete_page',{page_id:page_id},function(response){
    		$("#deleteButton").attr('disabled',false);
			if(response.success)
			{
				$("#pageID_"+page_id).remove();
				closeDeletePageConfirmation();
			}
			else if(response.error)
			{
				alert(response.error)
			}
			else
			{
				alert(response);
			}
		});
    });
}

function closeDeletePageConfirmation()
{
	$("#deletePageConfirmation").removeClass('is-active');
	$("#deleteButton").off("click"); // unbind the "onclick" event
	initiateToolBar();
}