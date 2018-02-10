/* global $, APP_PATH */
$(document).ready(function(){
    
    $(".sendButton").on("click",function(e){
        e.preventDefault();
        var method = $(this).data('method');
        $(".sendButton").prop('disabled',true);
        $.post(APP_PATH+"core-request/recoveryRequestVerification",{method:method},function(response){
        
            if(response.success)
            {
                    $("#selectSendMethod").addClass('is-invisible');
                    if(methodText == "sms")
                    {
                        $("#messageMethod").val(phone);
                        $("#messageAddress").val($("#phonenumber").val());
                    }
                    $("#messageSent").removeClass('is-invisible');
            }
            else
            {
                alert("There was a system error and your message could not be sent.");
                $(".sendButton").prop('disabled',false);
            }
        });
    });
    
});