/* global $, XHR_PATH, friendlyFilesize, dateFormat */
$(document).ready(function(){
   
   loadFolders();
   
    
});

//recursive helper function to draw UL list from JSON data
function generateMenu(data,parent)
{
    var ul = $('<ul>'),
        children = 0;
        
        $(data[parent]).each(function (i, folderData) {
            var li = $('<li><a data-id="'+ folderData.id +'">' + folderData.name + '</a></li>');
            ul.append(li);
            
            var thisParent = folderData.id;
            children++;
            li.append(generateMenu(data,thisParent)); // call this function again to list any children folders of this folder
        });
        
        return (children > 0) ? ul : false;
}

function loadFolders(){
    
    $.getJSON(XHR_PATH +'listMediaFolders',function(response)
    {
        $("#folders").html(generateMenu(response,0));
        
        $("#folders ul").addClass('menu-list').not(':first').addClass('is-closed');
        $(".is-closed").siblings('a').addClass('menu-has-children has-closed');
		
		$("a.menu-has-children").on('click', function(){
			var firstUL = $(this).siblings('ul');
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
		
		$("#folders a").on("click",function(){
            $("#folders a.is-active").removeClass('is-active');
            $(this).addClass('is-active');
            loadFiles($(this).data('id')); 
        });
        
    });
}

var folderCache = [];
function loadFiles(folder,updateCache)
{
    var folderKey = "folder_"+folder;
    var cacheData = eval(folderCache[folderKey]);
    
    //check folder cache
    if(cacheData == undefined || updateCache)
    {
        $("#files table tbody").html('<tr><td colspan="3">loading...</td></tr>');
        $.getJSON(XHR_PATH+'listMediaFilesByFolder',{folder:folder}, function(response){
            if(response.error)
            {
                alert(response.error);
            }
            if(!response.success)
            {
                alert(response);
            }
            var cacheData = response.files;
            folderCache[folderKey] = cacheData;
            drawFilesTable(cacheData);
        });
    }
    else
    {
        drawFilesTable(cacheData);
    }
}

function drawFilesTable(cacheData)
{
    //draw files here:
    var tableData = (cacheData.length == 0 ) ? '<tr><td colspan="3">Folder is empty</td></tr>' : '';
    $.each(cacheData,function(index,row)
    {
        tableData+='<tr data-folder="'+row.folder+'" data-file="'+row.id+'">';
        tableData+='<td>'+row.name+'</td>';
        tableData+='<td>'+row.type+'</td>';
        tableData+='<td>'+dateFormat("m/d/Y",row.upload_date)+'</td>';
        tableData+='</tr>';        
    });

    $("#files table tbody").html(tableData);
    $("#files p").addClass('is-invisible');
    $("#files table").removeClass('is-invisible');
    
    $("#files td").on("click",function(){

        var tr = $(this).parent();
        $("#files tr.is-selected").removeClass('is-selected');
        tr.addClass("is-selected");
        loadFileData(tr.data('folder'),tr.data('file'));
    });
}

function loadFileData(folder,id)
{
    var fileData = eval(folderCache['folder_'+folder][id]);
    console.log(fileData);
    $("#file_image img").attr('src',fileData.filepath);
    $("#filename").html(fileData.name);
    $("#filesize").html(friendlyFilesize(fileData.size));
    $("#filetype").html(fileData.type);
    $("#uploadby").html(fileData.uploadname);
    $("#uploaddate").html(dateFormat("F d, Y h:ia",fileData.upload_date));

}