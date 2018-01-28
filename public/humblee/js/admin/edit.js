/* global $, setEscEvent, XHR_PATH, APP_PATH */
$(document).ready(function(){

    $("#select_content_type").change(function(){
        window.location = APP_PATH+'admin/edit/?page_id='+$("#page_id").val()+'&content_type='+$(this).find("option:selected").val(); 
    });    
    
    $("#save").on("click",function(){
       
       confirmation('you clicked save, thats cool', function(){ alert('and you hit ok!'); confirmationClose()});
        
    });
    
    
});