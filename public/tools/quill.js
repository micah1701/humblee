/* global $, mediamanager, Quill */

$("head").append('<link href="https://cdn.quilljs.com/1.3.5/quill.snow.css" rel="stylesheet">');

var quill = new Quill('#edit_content', {
    theme: 'snow',
    imgHandler: 'selectImage',
    modules: {
        toolbar: [
          ['bold', 'italic'],
          ['link', 'blockquote', 'code-block', 'image'],
          [{ list: 'ordered' }, { list: 'bullet' }]
        ]
    }
});
quill.getModule("toolbar").addHandler("image", selectImage);

var insertPointIndex = 0;

function selectImage()
{
    var range = quill.getSelection();
    insertPointIndex = (range) ? range.index : 0;
    mediamanager();
}

//called when a user selects a file from the media manager
function handleMediaManagerSelect(fileData){
    quill.insertEmbed(insertPointIndex, 'image', fileData.url);
}

$(document).ready(function(){
//this doesn't work
    $("#edit_content").on("change",function(){
        $("#content").val(JSON.stringify(quill.getContents()));        
    });

})