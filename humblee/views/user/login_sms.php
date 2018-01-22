<h1 class="title">Almost There</h1>
<p class="subtitle">Your account requires two factor authentication to complete the sign in process.</p>

<section class="columns">
    <div class="message">
        <div class="message-body">
            <p><?php echo $name ?></p>
            <p>(***) *** - <?php echo $cellphone_lastfour ?></p>
            
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
                <input type="text" class="input" id="cellphone_validate" maxlength="5" name="cellphone_validate">
              </div>
              <br>
                  <button class="button is-primary" id="login">Sign In</button>
            </div>
            
        </div>
    </div>
</section>

<?php 
    $crypto = new Core_Model_Crypto;
    $hmac_pair = $crypto->get_hmac_pair(); 
  ?>
  <input type="hidden" id="hmac_token" value="<?php echo $hmac_pair['message'] ?>">
  <input type="hidden" id="hmac_key" value="<?php echo $hmac_pair['hmac'] ?>">
  <input type="hidden" id="fwd" value="<?php echo (isset($_GET['fwd']) && preg_match('/^[\w-\/-]+$/', $_GET['fwd'])) ? $_GET['fwd'] : "user"; ?>">

<script defer src="https://use.fontawesome.com/releases/v5.0.0/js/all.js"></script>
<script type="text/javascript">var APP_PATH = '<?php echo _app_path ?>';</script>
<script type="text/javascript" src="<?php echo _app_path ?>humblee/js/user/sms_login.js"></script>