<p style="float: right; margin-right: 30px"><a href="<?php echo  _app_path ?>user/logout">Log out</a></p>
<h1>User Profile</h1>

<?php if(isset($error)){ ?>
<div class="err_message">
  <ul>
	<?php foreach($error as $err){ ?>
	<li><?php echo $err ?></li>
	<?php } ?>    
  </ul>
</div>
<?php } ?>

<form action="<?php echo ( isset($_GET['fwd']) ) ? "?fwd=".$_GET['fwd'] : '' ?>" method="post">

  <label>Full Name</label>
  <input type="text" id="name" name="name" value="<?php echo $user->name ?>">

  
  <label>E-Mail Address</label>
  <input type="text" id="email" name="email" value="<?php echo $user->email ?>">
  
  <label>Username</label>
  <input type="text" id="username" name="username" value="<?php echo $user->username ?>">

<?php
  if($_ENV['config']['TWILIO_Enabled'])
  {
?>
  <label>SMS Cellphone</label>
  <input type="text" id="cellphone" name="cellphone" maxlength="10" placeholder="8605551234" value="<?php echo $user->cellphone ?>">
  <input type="hidden" id="cellphone_oldvalue" name="cellphone_oldvalue" value="<?php echo $user->cellphone; ?>">
  <button id="sendSMS">Send Verification Code</button><span id="sms_sent_status"></span>
  
  
  <div id="phone_verification_dialog" style="display: none">
    <p>Phone number has not be verified by Text Message (SMS).</p>
    <label>SMS Verification Code</label>
    <input type="text" id="cellphone_validate" name="cellphone_validate">
  </div>
  
  <?php 
    if($user->cellphone_validated != 0)
    {
  ?>    
    <span id="sms_verified_status"><span style="color: green">&#10004;</span> Verified</span>
    
    <label for="use_twofactor_auth" class="tooltip" title="When checked, logging in will require both a password and token code sent to your phone">
      <input type="checkbox" name="use_twofactor_auth" value="1" id="use_twofactor_auth"<?php echo ($user->use_twofactor_auth ==1) ? " checked" : "" ?>>
      Use Two Factor Authentication
    </label>  
      
  <?php
    }
    else
    {
  ?>
  
  <?php
    }
  }
?>
  
<div id="password_box">
  <label>New Password</label>
  <input type="password" id="password" name="password">
  <br>
  <em>Leave these fields blank to keep existing password</em>
  
  <label>Confirm Password</label>
  <input type="password" id="password_check" name="password_check" value="">
</div>

  <?php 
    $crypto = new Core_Model_Crypto;
    $hmac_pair = $crypto->get_hmac_pair(); 
  ?>
  <input type="hidden" name="hmac_token" value="<?php echo $hmac_pair['message'] ?>">
  <input type="hidden" name="hmac_key" value="<?php echo $hmac_pair['hmac'] ?>">

  <p><input name="" type="submit" value="Update Profile" value=""></p>
  
</form>

<style type="text/css">
  #phone_verification_dialog {
    display: <?php echo ($user->cellphone != "" && $user->cellphone_verified == 0) ? "block" : "none"; ?>;
    padding: 20px 20px 20px 10px; 
    width: 525px; 
    border: 1px #036 solid; 
    margin: 0 0 20px -10px; 
    background-color: #F3F3F3
  }
  
  #sendSMS {
    display: none;
  }
  #sms_sent_status {
    margin-left: 10px;
    display: inline-block;
  }
  
  #password_box {
    padding: 20px 20px 20px 10px; 
    width: 525px; 
    border: 1px #036 solid; 
    margin-left: -10px; 
    margin-bottom: 20px; 
    background-color: #F3F3F3"; 
  }
</style>

<?php
  if($_ENV['config']['TWILIO_Enabled'])
  {
?>
<script type="text/javascript">
function showSMSvalidateion()
{
  $("#phone_verification_dialog").fadeIn('fast');
  $("#sendSMS").fadeIn('fast');
}

$(document).ready(function(){
  $("#cellphone").on("keyup",function(){
    
    if($(this).val().length == 10 && $(this).val() != $("#cellphone_oldvalue").val() )
    {
      $("#sms_verified_status").css("display","none");
      $("#phone_verification_dialog").fadeIn('fast');
      $("#sendSMS").fadeIn('fast');
    }
    else if($(this).val() == $("#cellphone_oldvalue").val())
    {
      $("#phone_verification_dialog").fadeOut('fast');
      $("#sendSMS").fadeOut('fast');
    }
    
  });
  
  $("#sendSMS").click(function(e){
      e.preventDefault();
      var number = $("#cellphone").val().replace(/[^0-9]/g, '');
      if(number.length != 10){ alert("The Cellphone number must be 10 digits."); return false; }
      
      $(this).css("display","none");
      $("#sms_sent_status").html("Sending...").css("color","blue");

      $.get("<?php echo _app_path ?>core-request/verify_sms_send",{cellphone:$("#cellphone").val()},function(data){
        if($.trim(data) == "success")
        {
          $("#sms_sent_status").html("Text Message Sent").css("color","green");
        }
        else
        {
          $("#sms_sent_status").html('Error. Message could not be sent').css("color","red");
        }
        
      });    

  });
  
});
</script>
<?php
  }
?>

Total logins: <?php echo $user->logins; ?> &nbsp; <a href="<?php echo  _app_path ?>user/access">View Access Log</a>