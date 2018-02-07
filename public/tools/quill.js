/* global $, mediamanager, Quill */

$("head").append('<link href="https://cdn.quilljs.com/1.3.5/quill.snow.css" rel="stylesheet">');

class Counter {
  constructor(quill, options) {
    this.quill = quill;
    this.options = options;
    this.container = document.querySelector(options.container);
    quill.on('text-change', this.update.bind(this));
    this.update();  // Account for initial contents
  }

  calculate() {
    let text = this.quill.getText();
    if (this.options.unit === 'word') {
      text = text.trim();
      // Splitting empty text returns a non-empty array
      return text.length > 0 ? text.split(/\s+/).length : 0;
    } else {
      return text.length;
    }
  }
  
  update() {
    var length = this.calculate();
    var label = this.options.unit;
    if (length !== 1) {
      label += 's';
    }
    this.container.innerText = length + ' ' + label;
  }
}


var quill = new Quill('#edit_content', {
    theme: 'snow',
    imgHandler: 'selectImage',
    modules: {
        toolbar: [
          ['bold', 'italic'],
          ['link', 'blockquote', 'code-block', 'image'],
          [{ list: 'ordered' }, { list: 'bullet' }],
          [{ size: [ 'small', false, 'large', 'huge' ]}]
        ]
    }
});
quill.getModule("toolbar").addHandler("image", selectImage);

quill.on('editor-change',function(){
    $("#content").val(JSON.stringify(quill.getContents()));  
});

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
