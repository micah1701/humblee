/* global $, APP_PATH */

$("#sendSMS").click(function(e){
    e.preventDefault();
    var smsStatus = $("#sms_sent_status");
    smsStatus.html("Sending...").addClass('has-text-info');
    
    $.post(APP_PATH+"core-request/sms_login_code",{email: $("#smsusername").val() },function(data)
    {
        smsStatus.removeClass('has-text-info').removeClass('has-text-danger');
    
        if($.trim(data) == "success")
        {
            $("#sendSMS").fadeOut('fast');
            smsStatus.html("Text Message Sent").addClass('has-text-success');
            $("#smsVerificationCode").fadeIn('fast'); // show the field to enter the code and the submit button
            $("#cellphone_validate").focus();
            
            $("#cellphone_validate").on("keypress",function(e){
                // if they press enter
                if(e.which == 13 || $("#cellphone_validate").val().length == 5)
                {
                    login();
                }
            });
            
            $("#login").on("click",function(){
                login(); 
            });
        }
        else
        {
            smsStatus.html(data).addClass('has-text-danger');
        }
    });    
});



function login()
{
    var sms_token = $("#cellphone_validate").val();
    if(sms_token.length != 5)
    {
        alert("The code should be five (5) characters.");
        $("#cellphone_validate").focus();
        return false;
    }
    
    //disable the login button so it can't be clicked again
    $("#login").html("Checking").attr("disabled", true);
    //ajax the code back to the server
    
    $.post(APP_PATH+"core-request/sms_login",{'sms_token':sms_token,'hmac_token':$("#hmac_token").val(),'hmac_key':$("#hmac_key").val()},function(response){
       if(response.success)
       {
           $("#login").html("Access Granted");
           window.location = APP_PATH + $("#fwd").val();
       }
       else
       {
           var errMsg = (response.error != undefined) ? response.error : response;
           alert(errMsg);
           $("#login").html("Sign In").attr("disabled", false);
           $("#cellphone_validate").focus();
       }
    });
}