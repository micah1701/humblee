// load the CSS specific to this toolbar
$('head').append('<link rel="stylesheet" href="'+ toolbarData._app_path +'humblee/css/admin/toolbar.css?'+ Date.now() +'" type="text/css">');

//load bulma CSS framework if its not already included
/*
var bulma = toolbarData._app_path + 'node_modules/bulma/css/bulma.css';
if (!$("link[href='"+bulma+"']").length){
    $('<link href="'+bulma+'" rel="stylesheet">').appendTo("head");
}
*/

//load the humblee admin js file
$.getScript(toolbarData._app_path + 'humblee/js/admin/admin.js');

//add the modal window to the page
$('body').append('<div id="humbleeEditor" class="modal">'
+'  <div class="modal-background"></div>'
+'  <div class="modal-card">'
+'    <header class="modal-card-head">'
+'      <p class="modal-card-title">Content Editor</p>'
+'      <button class="delete" aria-label="close"></button>'
+'    </header>'
+'    <section class="modal-card-body"></section>'
+'  </div>'
+'</div>');

$(".cms_block").hover(function(){
        var editableContent = $(this),
            buttonHTML = '<button class="launch-editor" style="display: none" data-content-id="'+ $(this).attr("data-content-id") +'">Edit "'+$(this).attr("data-block-name")+'"</button>';

        // if any parent of this class has the "content" class, assume bulma.io css framework
        // bulma adds margins to various elements, but not if its the first or last element in the block
        // if we just inject this button at the begining or end of the container, those elements will have excess margin applied
        // so instead, add the button into the first element of the container
        if(editableContent.parents('.content').length)
        {
            editableContent.children().first().append(buttonHTML);
        }
        else // or just add the button at the end of the .cms_block container
        {
            editableContent.append(buttonHTML);
        }

        var button = $('.launch-editor[data-content-id="'+ $(this).attr("data-content-id") +'"]');
        button.fadeIn('fast');

}, function(){
        $(".launch-editor",this).remove();
});

$(document).on("click",".cms_block .launch-editor",function(){
    var iframe = '<iframe src="'+toolbarData._app_path+'admin/edit/'+$(this).data('content-id')+'?iframe=true" border="0">';
    $(".modal-card-body").html(iframe);
    $("#humbleeEditor").addClass('is-active');

    setEscEvent('humbleeEditor',function () { closeHumbleeEditor() });
    $("#humbleeEditor button.delete").on("click",function(){
        closeHumbleeEditor();
        unsetEscEvent('humbleeEditor');
    });
});

function closeHumbleeEditor() { $("#humbleeEditor").removeClass('is-active');  }
