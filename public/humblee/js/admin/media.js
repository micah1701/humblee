/* global $, XHR_PATH, friendlyFilesize, quickNotice, dateFormat, setEscEvent */
$(document).ready(function(){
   
   loadFolders();
   $("#files button.uploadButton").on("click",function(){
        $("#uploaderModal").addClass('is-active');
        
        //register ESC key and other ways to close the modal
        setEscEvent('fileUploader',function () { closeUploaderModal() });
        $("#uploaderModal .delete").on("click",function(){
            closeUploaderModal();
        });
   });
    
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
            
            $("#folder_name")
                .addClass('editable-text')
                .data('fieldID',$(this).data('id'))
                .html( $(this).html());
                
            $("#folder_id").val($(this).data('id'));
            $("#files .level .level-right.is-invisible").removeClass('is-invisible'); 
                
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
    $("#file_name").html(fileData.name).data('fieldID',fileData.id);
    $("#filesize").html(friendlyFilesize(fileData.size));
    $("#filetype").html(fileData.type);
    $("#uploadby").html(fileData.uploadname);
    $("#uploaddate").html(dateFormat("F d, Y h:ia",fileData.upload_date));

    $("#fileProperties.is-invisible").removeClass('is-invisible');
}


$(document).on("click", ".editable-text", function() {
    var original_text = $(this).text();
    var field_id = $(this).data('fieldID');
    var dom_id = $(this).attr('id');
    
    var new_input = $("<input class=\"input\"/>");
    new_input.val(original_text);
    
    $(this).replaceWith(new_input);
    new_input.focus();
    
    new_input.on("blur", function() {
      var newValue = new_input.val();
      var updated_text = $('<p class="is-size-5 editable-text">');
          updated_text.data('fieldID',field_id);
          updated_text.attr('id',dom_id);
          
      if(newValue == original_text)
      {
        //no change was made, just put the text back
        updated_text.text(original_text);
      }
      else
      {
        
        $.post(XHR_PATH +'updateMediaName',{type:dom_id,record:field_id,value:new_input.val()},function(response){
          if(response.success)
          {
            updated_text.text(new_input.val());
            var objectLable;
            if(dom_id == "folder_name")
            {
                objectLable = "Folder";
                $("#folders a[data-id='"+ field_id +"']").html(new_input.val());
            }
            if(dom_id == "file_name")
            {
                objectLable = "File";
                //find the table tr row with this file id and update the name
                //or refresh the table maybe, I dunno whatever is easier
            }
            
            quickNotice(objectLable+" name updated!");
            
          }
          else
          {
            alert("Error: changes could not be saved at this time"); 
            updated_text.text(original_text);
          }
        });  
      }
      
      $(this).replaceWith(updated_text);
      new_input.remove();
      
    }); // end onBlur check of text_editor class input
    
}); // end onClick of editable-text string

/** file uploader **/
// https://css-tricks.com/drag-and-drop-file-uploading/
var drapAndDropMessage = "Drag &amp; Drop";

$(document).ready(function(){
    
    var isAdvancedUpload = function() {
      var div = document.createElement('div');
      return (('draggable' in div) || ('ondragstart' in div && 'ondrop' in div)) && 'FormData' in window && 'FileReader' in window;
    }();
    
    var dropZone = $(".dropZone");

    if(!isAdvancedUpload)
    {
        dropZone.css({display:'none'});
    }
    else
    {
        dropZone.on('drag dragstart dragend dragover dragenter dragleave drop', function(e) {
            e.preventDefault();
            e.stopPropagation();
        })
        .on('dragover dragenter', function() {
            dropZone.addClass('is-dragover');
        })
        .on('dragleave dragend drop', function() {
            dropZone.removeClass('is-dragover');
        })
            .on('drop', function(e) {
            var droppedFiles = e.originalEvent.dataTransfer.files;
            uploaderSubmit(droppedFiles);
        });
    }
    
    $("#uploaderModal input").on('change', function(e) { //when manually selecting files
        uploaderSubmit(false);
    });

});

function uploaderSubmit(droppedFiles) {
    var dropZone = $(".dropZone");
    
    if (dropZone.hasClass('is-uploading')){
        return false;
    }

    dropZone.addClass('is-uploading')
        .removeClass('is-error')
        .html('<span class="icon"><i class="fas fa-spinner fa-pulse"></i></span>&nbsp;<span id="processingMessage">Uploading…</span>');

    var ajaxData = new FormData($("#uploader form").get(0));

    if(droppedFiles)
    {
        $.each( droppedFiles, function(i, file) {
            ajaxData.append( $("#uploader form input[name='uploaderFiles']").attr('name'), file );
        });
    }

    $.ajax({
        url: XHR_PATH+'uploadFiles',
        type: 'post',
        data: ajaxData,
        dataType: 'json',
        cache: false,
        contentType: false,
        processData: false,
        complete: function() {
          $('.dropZone').removeClass('is-uploading');
          $('.dropZone span#processingMessage').html('Processing…')
          
        },
        success: function(data) {
            $('.dropZone').html(drapAndDropMessage);
            
            if(data.success == true)
            {
                quickNotice('Upload Complete','is-success');
            }
            else
            {
                quickNotice('Upload Failed','is-danger');
            }
        },
        error: function(data) {
          // Log the error, show an alert, whatever works for you
          $('.dropZone').html(drapAndDropMessage);
          quickNotice(data,'is-warning');
        }
    });
}
function closeUploaderModal()
{
    $("#uploaderModal").removeClass('is-active');
}