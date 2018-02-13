/* global $, mediamanager, Quill */

$("head").append('<link href="https://cdn.quilljs.com/1.3.5/quill.snow.css" rel="stylesheet">');

var quill = new Quill('#edit_content', {
    theme: 'snow',
    imgHandler: 'selectImage',
    modules: {
        toolbar: [
          ['bold', 'italic', 'underline', 'strike'],
          ['link', 'blockquote', 'image'],
          [{ 'header': [1, 2, 3, false] }],
          [{ list: 'ordered' }, { list: 'bullet' }],
          [{ 'script': 'sub'}, { 'script': 'super' }],
          ['clean'], 
        ]
    }
});

quill.getModule("toolbar").addHandler("image", selectFromMediaManager);

quill.on('editor-change',function(){
    $("#content").val(quill.container.firstChild.innerHTML);
    //console.log($("#content").val());
});

var insertPointIndex = 0;

function selectFromMediaManager()
{
    var range = quill.getSelection();
    console.log(range);
    insertPointIndex = (range) ? range.index : 0;
    mediamanager();
}

//called when a user selects a file from the media manager
function handleMediaManagerSelect(fileData){
    quill.insertEmbed(insertPointIndex, 'image', fileData.url);
}

$(document).on("dblclick", "#edit_content img", function(event){
  var image = $(this),
  declaredWidth = image[0].style.width, // just vanilla JS to get declared width. jQuery will always return calculated width
  setWidth = (declaredWidth == "") ? "100%" : declaredWidth; // default to 100%
  
  $("#imageClass").val(image.attr('class'));
  $("#imageWidth").val(setWidth);
  $("#imageMaxWidth").val(image.css('max-width'));  
  $("#imageProperties").addClass('is-active'); 
  $("#imagePropertiesSave").on("click",function(e)
  {
    image.attr('class',$("#imageClass").val());
    image.css('width',$("#imageWidth").val());
    image.css('max-width',$("#imageMaxWidth").val());
    closeImagePropertiesDialog();
  });
  $("#imagePropertiesCancel").on("click",function(){
    closeImagePropertiesDialog();
  });
  
});

function closeImagePropertiesDialog()
{
  $("#imageProperties").removeClass('is-active');
  $("#imagePropertiesSave").off("click");
  $("#imagePropertiesCancel").off("click");
  $("#imageClass").val('');
  $("#imageWidth").val('');
  $("#imageMaxWidth").val('');
}