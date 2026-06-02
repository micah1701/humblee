$(document).ready(function(){
 
    loadContentMenu();   

});

// load the menu of editable content
function loadContentMenu(){
    $.get(XHR_PATH +'pages/load-content-menu',function(data){
        $("#contentMenu").html(data);
        $("#contentMenu ul").addClass("menu-list");
    });
}