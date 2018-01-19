<section class="columns">
  <div class="column">
    <h1 class="title">User Profile</h1>    
  </div>
  <div class="column">
    <a class="button is-secondary" href="<?php echo _app_path ?>user/logout">Log out</a>   
  </div>
</section>

<section class="section columns">
<form action="<?php echo (isset($_GET['fwd']) && preg_match('/^[\w-\/-]+$/', $_GET['fwd'])) ? "?fwd=".$_GET['fwd'] : '' ?>" method="post">
  
  <?php if(isset($error)){ ?>
  <div class="field is-two-fifths">
    <ul class="control">
  	<?php foreach($error as $err){ ?>
  	<li class="has-text-danger"><?php echo $err ?></li>
  	<?php } ?>    
    </ul>
  </div>
  <?php } ?>

  <div class="field is-two-fifths">
    <label class="label" for="name">Full Name</label> 
    <div class="control">
      <input class="input" type="text" id="name" name="name" value="<?php echo $user->name ?>">
    </div>
  </div>
  
  <div class="field is-two-fifths">
    <label class="label" for="email">E-Mail Address</label> 
    <div class="control">
      <input class="input" type="text" id="email" name="email" value="<?php echo $user->email ?>">
    </div>
  </div>
  
  <div class="field is-two-fifths">
    <label class="label" for="username">Username</label> 
    <div class="control">
      <input class="input" type="text" id="username" name="username" value="<?php echo $user->username ?>">
    </div>
  </div>

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

  <div class="field is-two-fifths">
    <label class="label" for="name">New Password</label><span class="help">Leave blank to keep exisiting password</span>
    <div class="control">
      <input class="input" type="password" id="password" name="password">
    </div>
  </div>
  <div id="confirm_password_block" class="field is-two-fifths" style="display: none">
    <label class="label" for="password_check">Confirm Password</label> 
    <div class="control">
      <input class="input" type="password" id="password_check" name="password_check">
    </div>
  </div>


  <?php 
    $crypto = new Core_Model_Crypto;
    $hmac_pair = $crypto->get_hmac_pair(); 
  ?>
  <input type="hidden" name="hmac_token" value="<?php echo $hmac_pair['message'] ?>">
  <input type="hidden" name="hmac_key" value="<?php echo $hmac_pair['hmac'] ?>">

  <div class="field is-two-fifths">
    <div class="control">
      <input class="button is-primary" name="" type="submit" value="Update Profile" value="">
    </div>
  </div>
  
</form>
</section>


<?php
  if($_ENV['config']['TWILIO_Enabled'])
  {
?>

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
  
</style>

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

<script type="text/javascript">
  $(document).ready(function(){
    $("#password").on("keyup",function(){
      if($(this).val() == "")
      {
        $("#confirm_password_block").fadeOut('fast');
      }
      else
      {
        $("#confirm_password_block").fadeIn('fast');
      }
    });
  });
</script>

<a class="button is-secondary" href="<?php echo  _app_path ?>user/access">View Access Log</a>
<br>Total logins: <strong><?php echo $user->logins; ?></strong> 
