<?php 
if(isset($_SESSION[session_key]['user_id'])){ // user is already logged in
	$fwd = (isset($_GET['fwd']) && preg_match('/^[\w-\/-]+$/', $_GET['fwd'])) ? _app_path.$_GET['fwd'] : _app_path."/user";
	Core::forward($fwd);
}
?>
<h1 class="title">Almost There</h1>
<p class="subtitle">Your account requires two factor authentication to complete the sign in process.</p>

<section class="columns">
    <div class="message">
        <div class="message-body">
            <p><?php echo $_SESSION[session_key]['name'] ?></p>
            <p>(***) *** - <?php echo substr($_SESSION[session_key]['sms_cellphone'], -4) ?></p>
            
            <div class="field">
                <div class="control">
                    <p class="help">A validation code must be sent to your phone number in order to continue.</p>
                    <br>
                    <button id="sendSMS" class="button is-info">
                      <span class="icon is-pulled-left">
                        <i class="fas fa-paper-plane"></i>
                      </span>
                      <span class="is-pulled-right">
                        Send Validation Code Now
                      </span>
                    </button>
                    
                    <div id="sms_sent_status"></div>
                </div>
            </div>
    
            
            <div id="smsVerificationCode" style="display: none" class="field">
              <label class="label" for="cellphone_validate">Enter the Verification Code</label> 
              <div class="control">
                <input type="text" class="input" id="cellphone_validate" name="cellphone_validate">
              </div>
              <br>
                  <button class="button is-primary" id="login">Sign In</button>
            </div>
            
        </div>
    </div>
</section>

<script defer src="https://use.fontawesome.com/releases/v5.0.0/js/all.js"></script>
<script type="text/javascript">var APP_PATH = '<?php echo _app_path ?>';</script>
<script type="text/javascript" src="<?php echo _app_path ?>humblee/js/user/sms_login.js"></script>