$(document).ready(function(){
    
   loadContentMenu();
    
});

function loadContentMenu(){
    $.get(XHR_PATH +'loadContentMenu',function(data){
        $("#contentMenu").html(data);
        $("#contentMenu ul").addClass("menu-list");
    });
}