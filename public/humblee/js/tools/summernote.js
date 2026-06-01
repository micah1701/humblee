$("head").append('<link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.css" rel="stylesheet">');

$(document).ready(function(){
    $("#edit_content").summernote({
        toolbar: [
            ['style', ['style']],
            ['font', ['bold', 'underline', 'clear']],
            ['para', ['ul', 'ol', 'paragraph']],
            ['table', ['table']],
            ['insert', ['link', 'imageFromMediaManager', 'video']],
            ['view', ['fullscreen', 'codeview', 'help']],
        ],

        styleTags: [
          {title: 'Page Heading', tag: 'h1', value: 'h1'},
          {title: 'Sub Heading', tag: 'h2', value: 'h2'},
          {title: 'Paragraph Text', tag: 'p', value: 'p'},

        ],
        popover: {
          image: [
            ['image', ['resizeFull', 'resizeHalf', 'resizeQuarter', 'resizeNone','scaStyle']],
            ['float', ['floatLeft', 'floatRight', 'floatNone']],
            ['remove', ['removeMedia']]
          ]
        },
        height: 500,
        minHeight: 500,
      
        buttons: {
          scaStyle: makeImageSCAdrop,
          imageFromMediaManager: LaunchMediaManager
          },

        callbacks: {
          onInit: function(){
            console.log("summernote initiated!");
            $(".note-editable").addClass("content");
          }
        }
    });

});

var makeImageSCAdrop = function (context) {
  var ui = $.summernote.ui;
  var button = ui.button({
    contents: 'SCA Drop',
    click: ()=>{
      alert("you want to add a class!");
    }
  });
  return button.render();
}

var LaunchMediaManager = function (context) {
    var ui = $.summernote.ui;
  
    // create button
    var button = ui.button({
      tooltip: "Insert Image from Media Manager",
      contents: '<i class="fa fa-camera"/> Media Manager',
      click: function () {
        // invoke insertText method with 'hello' on editor module.
        // context.invoke('editor.insertText', 'hello');
        mediamanager();
      }
    });
  
    return button.render();   // return button as jquery object
  }

//called when a user selects a file from the media manager
function handleMediaManagerSelect(fileData){
    $('#edit_content').summernote('insertImage', fileData.url, function ($image) {
        let width = $image.width();
        let maxWidth = (width > 800) ? 800 : width;
        $image.css({"width":"100%","max-width":maxWidth+"px"});
        $image.addClass('cms-image');
        $image.attr('data-author',fileData.author);
    });   
}