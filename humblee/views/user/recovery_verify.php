<h1 class="title">Is this really you?</h1>
<h2 class="subtitle">Verify your account</h2>

<?php
if(isset($error))
{
    echo '<div class="message is-danger"><div class="message-body">'.$error.'</div></div>';
    return; // dont show the rest of this page
}
?>

<?php
// if an e-mail validation e-mail wasn't sent from the controller (and there was no $error message)
// it means this user has 2 factor authentication turned on
// let them choose which method they would like to receive the access code from
if(!$_SESSION[session_key]['recovery']['message_sent'])
{
?>
<div id="selectSendMethod">
    <p>To protect your account, we need to send you a one-time <strong>temporary access code.</strong> Once you receive the code, enter it on the next step.</p>
    <div class="message is-info" style="max-width: 768px; margin-top: 15px">
        <div class="message-header">
            How would you like to receive the temporary access code?
        </div>
        <div class="message-body">
            <div class="columns">
                <div class="column">
                    <p style="margin-bottom: 15px"><strong>My Phone</strong><br>
                    <span id="phonenumber">(***) *** - <?php echo $cellphone_lastfour ?></span>
                    </p>
                    <p>
                        <button class="button is-link sendButton" data-method="sms"><span class="icon is-pulled-left"><i class="fas fa-mobile-alt"></i></span><span class="is-pulled-right">Send Text Message</span></button>
                    </p>
                </div>
                <div class="column">
                    <p style="margin-bottom: 15px"><strong>My Email Address</strong><br>
                    <?php echo $email_masked ?>
                    </p>
                    <p>
                        <button class="button is-link sendButton" data-method="email"><span class="icon is-pulled-left"><i class="fas fa-envelope"></i></span><span class="is-pulled-right">Send E-mail Now</span></button>                        
                    </p>
                </div>
            </div>
        </div>
    </div>
    <button class="button recoveryCancel">Cancel</button>
</div>
<script defer src="https://use.fontawesome.com/releases/v5.0.0/js/all.js"></script>
<?php
}
?>

<div id="messageSent"<?php echo (!$_SESSION[session_key]['recovery']['message_sent']) ? ' style="display:none"' : '' ?>>
    <p>To protect your account, we've sent you a one-time temporary access code.</p>
    <p>The message was sent via <span id="messageMethod">
        <?php echo (isset($_SESSION[session_key]['recovery']['method']) && $_SESSION[session_key]['recovery']['method'] == "sms") ? "text" : "e-mail"; ?>
        </span> 
        to <span id="messageAddress" class="has-text-weight-semibold">
            <?php   
            echo (isset($_SESSION[session_key]['recovery']['method']) && $_SESSION[session_key]['recovery']['method'] == "sms") ? '(***) *** - '.$cellphone_lastfour : $email_masked; ?>
        </span></p>
    <div id="verificationCode" class="field" style="margin-top: 15px; max-width: 400px">
        <label class="label" for="accessCode">Enter the access code</label> 
        <div class="control">
            <input type="text" class="input" id="accessCode" maxlength="5" name="accessCode">
        </div>
        <br>
        <button class="button is-primary submitButton" id="login">Continue</button>
        <button class="button recoveryCancel">Cancel</button>
    </div>
</div>

<script type="text/javascript">var APP_PATH = '<?php echo _app_path ?>';</script>
<script type="text/javascript" src="<?php echo _app_path ?>humblee/js/user/recover_verification.js"></script>
<input type="hidden" id="fwd" value="<?php echo (isset($_GET['fwd']) && preg_match('/^[\w-\/-]+$/', $_GET['fwd'])) ? $_GET['fwd'] : "user"; ?>">