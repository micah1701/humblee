/* global $, APP_PATH */
$(document).ready(function(){
    
    $(".sendButton").on("click",function(e){
        e.preventDefault();
        var method = $(this).data('method');
        $(".sendButton").prop('disabled',true);
        $.post(APP_PATH+"core-request/recoveryRequestVerification",{method:method},function(response){
            if(response.success)
            {
                    $("#selectSendMethod").css('display','none');
                    if(method == "sms")
                    {
                        $("#messageMethod").val('phone');
                        $("#messageAddress").val($("#phonenumber").val());
                    }
                    $("#messageSent").fadeIn('fast');;
            }
            else if (response.error)
            {
                alert(response.error);
                $(".sendButton").prop('disabled',false);
            }
            else
            {
                alert("There was a system error and your message could not be sent.");
                $(".sendButton").prop('disabled',false);
            }
        });
    });
    
    $(".submitButton").on("click",function(e){
        e.preventDefault();
        $(".submitButton").prop('disabled',true);
        $.post(APP_PATH+"core-request/recoverySubmitVerification",{accessCode :$("#accessCode").val() },function(response){
            if(response.success)
            {
                window.location = APP_PATH + 'user/resetPassword?fwd='+ $("#fwd").val();
            }
            else
            {
                alert(response);
                $(".submitButton").prop('disabled',false);
            }
        });
    });
    
    $("button.recoveryCancel").on("click",function(){
       $.post(APP_PATH+"core-request/recoveryCancel",function(response){
            if(response.success)
            {
                window.location = APP_PATH +"user/login?fwd="+$("#fwd").val();
            }
        });
    });
    
});