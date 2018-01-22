/* global $, APP_PATH */

function showSMSvalidateion()
{
    $(".smsInputGroup .notification, .smsInputGroup button, .smsInputGroup .help").fadeIn('fast');
}

$(document).ready(function(){
    $("#cellphone").on("keyup",function(){
    
        if($(this).val().length == 10 && $(this).val() != $("#cellphone_original").val() )
        {
            $("#sms_verified_status").css("display","none");
            $(".smsInputGroup .notification, .smsInputGroup button, .smsInputGroup .help").fadeIn('fast');
        }
        else if($(this).val() == $("#cellphone_original").val())
        {
            $(".smsInputGroup .notification, .smsInputGroup button, .smsInputGroup .help").fadeOut('fast');
        }
    });
  
    $(".smsInputGroup button").click(function(e){
        e.preventDefault();
        var number = $("#cellphone").val().replace(/[^0-9]/g, '');
        if(number.length != 10){ alert("The Cellphone number must be 10 digits."); return false; }
        
        $(this).css("display","none");
        var smsStatus = $("#sms_sent_status");
        smsStatus.html("Sending...").addClass('has-text-info');
        
        $.post(APP_PATH+"core-request/verify_sms_send",{cellphone:$("#cellphone").val()},function(data){
            smsStatus.removeClass('has-text-info').removeClass('has-text-danger');
            if($.trim(data) == "success")
            {
                smsStatus.html("Text Message Sent").addClass('has-text-success');
                $("#smsVerificationCode").fadeIn('fast'); // show the field to enter the code into
            }
            else
            {
                smsStatus.html('Error. Message could not be sent').addClass('has-text-danger');
            }
        });    
    });
  
});