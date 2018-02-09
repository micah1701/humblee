/* global $, XHR_PATH, confirmation */
$(document).ready(function(){
    
    $(".removeUser").on("click",function(){
        var userRow = $(this).closest('tr'),
            userID = userRow.data('userid'),
            removeUser = function(){
                $.post(XHR_PATH+'removeUser',{userID:userID},function(response){
                    userRow.fadeOut('slow');        
            }); 
        };
   
        confirmation('You are about to <span class="has-text-danger">remove this user!</span> This action <u>cannot</u> be undone.<br>'
            +'Note: you can also disable access by simply removing a user\'s "login" role.',
            removeUser,
            function(){ return false; }
        );
    });
    
});