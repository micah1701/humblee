$("head").append('<link href="https://cdn.quilljs.com/1.3.5/quill.snow.css" rel="stylesheet">');
var quill = new Quill('#edit_content', {
    theme: 'snow',
    imgHandler: 'myImg',
    modules: {
        toolbar: [
          ['bold', 'italic'],
          ['link', 'blockquote', 'code-block', 'image'],
          [{ list: 'ordered' }, { list: 'bullet' }]
        ]
    }
});
quill.getModule("toolbar").addHandler("image", myImg);


function myImg(a) {
    console.log(a);
    var img = '<img src="https://placebear.com/300/200" width="300" height="200">';

    quill.insertEmbed(10, 'image', 'https://placebear.com/300/200');
}