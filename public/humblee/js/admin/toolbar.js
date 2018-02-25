// load the CSS specific to this toolbar
$('head').append('<link rel="stylesheet" href="'+ toolbarData._app_path +'humblee/css/admin/toolbar.css?'+ Date.now() +'" type="text/css">');

//load bulma CSS framework if its not already included
var bulma = toolbarData._app_path + 'node_modules/bulma/css/bulma.css';
if (!$("link[href='"+bulma+"']").length){
    $('<link href="'+bulma+'" rel="stylesheet">').appendTo("head");
}

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
    $(this).append('<button class="button is-danger is-outlined launch-editor" data-block-id="'+ $(this).attr("data-block-id") +'">Edit "'+$(this).attr("data-block-name")+'"</button>');
}, function(){
    $(".launch-editor",this).remove();
});

$(document).on("click",".cms_block .launch-editor",function(){
    var iframe = '<iframe src="'+toolbarData._app_path+'admin/edit/'+$(this).data('block-id')+'?iframe=true" border="0">';
    $(".modal-card-body").html(iframe);
    $("#humbleeEditor").addClass('is-active');

    setEscEvent('humbleeEditor',function () { closeHumbleeEditor() });
    $("#humbleeEditor button.delete").on("click",function(){
        closeHumbleeEditor();
        unsetEscEvent('humbleeEditor');
    });
});

function closeHumbleeEditor() { $("#humbleeEditor").removeClass('is-active');  }