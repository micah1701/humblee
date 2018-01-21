function showSMSvalidateion()
{
  $(".smsInputGroup .notification, .smsInputGroup button, .smsInputGroup .help").fadeIn('fast');
}

$(document).ready(function(){
  $("#cellphone").on("keyup",function(){
    
    if($(this).val().length == 10 && $(this).val() != $("#cellphone_oldvalue").val() )
    {
      $("#sms_verified_status").css("display","none");
      $(".smsInputGroup .notification, .smsInputGroup button, .smsInputGroup .help").fadeIn('fast');
    }
    else if($(this).val() == $("#cellphone_oldvalue").val())
    {
      $(".smsInputGroup .notification, .smsInputGroup button, .smsInputGroup .help").fadeOut('fast');
    }
    
  });
  
  $(".smsInputGroup button").click(function(e){
      e.preventDefault();
      var number = $("#cellphone").val().replace(/[^0-9]/g, '');
      if(number.length != 10){ alert("The Cellphone number must be 10 digits."); return false; }
      
      $(this).css("display","none");
      $("#sms_sent_status").html("Sending...").hasClass('has-text-info')

      $.get("<?php echo _app_path ?>core-request/verify_sms_send",{cellphone:$("#cellphone").val()},function(data){
        if($.trim(data) == "success")
        {
          $("#sms_sent_status").html("Text Message Sent").hasClass('has-text-success')
        }
        else
        {
          $("#sms_sent_status").html('Error. Message could not be sent').hasClass('has-text-danger');
        }
        
      });    

  });
  
});