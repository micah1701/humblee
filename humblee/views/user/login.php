<?php 
if(isset($_SESSION[session_key]['user_id'])){ // user is already logged in
	$fwd = (isset($_GET['fwd'])) ? _app_path.$_GET['fwd'] : _app_path."/user";
	header("Location: ".$fwd);
	exit();
}
?>

<h1 class="title">Knock Knock</h1>
<h2 class="subtitle"<?php echo (!$_ENV['config']['TWILIO_Enabled']) ? '' : ' style="display:none"'; ?>>Log in using your username and password</h2>


<?php 
  if($_ENV['config']['TWILIO_Enabled'] && isset($error) && $error == "use_twofactor_auth")
  {
?>

<div id="sms-login">
<form action="<?php echo ( isset($_GET['fwd']) ) ? "?fwd=".$_GET['fwd'] : '' ?>" method="post">  
  <p>Your account required two factor authentication.</p>
  <label for="smsusername">Username:</label>
  <input type="text" id="smsusername" name="smsusername" value="<?php echo (isset($_POST['username'])) ? $_POST['username'] : '' ?>"> 
  <button id="sendSMS">Send Verification Code</button>
  <br><span id="sms_sent_status"></span>

  <div id="sms_block" style="display:<?php echo (isset($_SESSION[session_key]['login_token']) && isset($_SESSION[session_key]['login_token_expires']) && time() < $_SESSION[session_key]['login_token_expires']) ? "block" : "none"; ?>">
    <label>SMS Log In Code:</label>
    <input type="text" id="cellphone_validate" name="cellphone_validate">
  
    <p><input name="" type="submit" value="Log In"></p>
  </div>
  
</div>

<script type="text/javascript">
$("#sendSMS").click(function(e){
      e.preventDefault();
      
      $("#sms_sent_status").html("Sending...").css("color","blue");
      $.get("<?php echo _app_path ?>core-request/sms_login_code",{email: $("#smsusername").val() },function(data){
        if($.trim(data) == "success")
        {
          $("#sendSMS").fadeOut('fast');
          $("#sms_sent_status").html("Text Message Sent").css("color","green");
          $("#sms_block").fadeIn('fast');
        }
        else
        {
          $("#sms_sent_status").html('Error: '+ data).css("color","red");
        }
        
      });    

  });
</script>  
<?php 
  }else{

   if(isset($error))
   { 
   ?>
<div class="has-text-danger"><?php echo $error ?></div>
<?php } ?>

<section class="section columns">
<form action="<?php echo (isset($_GET['fwd']) && preg_match('/^[\w-\/-]+$/', $_GET['fwd'])) ? "?fwd=".$_GET['fwd'] : '' ?>" method="post">
  
  <div class="field  is-two-fifths">
    <label class="label" for="username">Username:</label> 
    <div class="control">
      <input class="input" type="text" id="username" name="username">
    </div>
  </div>
  
  <div class="field is-two-fifths">
    <label class="label" for="password">Password:</label> 
    <div class="control">
      <input class="input" type="password" id="password" name="password">
    </div>
  </div>
  
  <?php 
    $crypto = new Core_Model_Crypto;
    $hmac_pair = $crypto->get_hmac_pair(); 
  ?>
  <input type="hidden" name="hmac_token" value="<?php echo $hmac_pair['message'] ?>">
  <input type="hidden" name="hmac_key" value="<?php echo $hmac_pair['hmac'] ?>">

  <div class="field is-two-fifths">
    <input class="button is-primary is-outlined" name="" type="submit" value="Log In">
  </div>

</form>
</section>

<?php
}