<section class="columns">
  <div class="column">
    <h1 class="title">User Profile</h1>

    <?php if(isset($error)){ ?>
    <div class="notification is-danger">
      <ul>
    	<?php foreach($error as $err){ ?>
    	  <li>
      	  <span class="icon is-small">
          <i class="fas fa-exclamation-triangle fa-xs"></i>
          </span>
          <?php echo $err ?>
        </li>
    	<?php } ?>    
      </ul>
    </div>
    <?php } ?>    

  </div>
  <div class="column">
    
    <a class="button is-secondary is-pulled-right" href="<?php echo _app_path ?>user/logout">
      <span class="icon has-text-danger">
        <i class="fas fa-times"></i>
      </span>
      <span class="is-pulled-right">Log out</span>
    </a>   
  
  </div>
</section>

<section class="columns is-variable is-8">

  <div class="column">  
  <form action="<?php echo (isset($_GET['fwd']) && preg_match('/^[\w-\/-]+$/', $_GET['fwd'])) ? "?fwd=".$_GET['fwd'] : '' ?>" method="post">
    
    <div class="field">
      <label class="label" for="name">Full Name</label> 
      <div class="control">
        <input class="input" type="text" id="name" name="name" value="<?php echo $user->name ?>">
      </div>
    </div>
    
    <div class="field">
      <label class="label" for="email">E-Mail Address</label> 
      <div class="control">
        <input class="input" type="text" id="email" name="email" value="<?php echo $user->email ?>">
      </div>
    </div>
    
    <div class="field">
      <label class="label" for="username">Username</label> 
      <div class="control">
        <input class="input" type="text" id="username" name="username" value="<?php echo $user->username ?>">
      </div>
    </div>
  
  <?php
    if($_ENV['config']['TWILIO_Enabled'])
    {
  ?>
    <div class="field">
      <label class="label" for="username">SMS Cellphone</label> 
      <div class="control smsInputGroup">
        <input class="input" type="text" id="cellphone" name="cellphone" value="<?php echo $user->cellphone ?>">
        
        <p class="help">To validate this number a verification code must be sent to your phone before this form can be submited.</p>
        <button class="button is-info">
          <span class="icon is-pulled-left">
            <i class="fas fa-paper-plane"></i>
          </span>
          <span class="is-pulled-right">
            Send Code Now
          </span>
        </button>
        <div id="sms_sent_status"></div>
      
      </div>
    </div>
    
    <div id="smsVerificationCode" class="field">
      <label class="label" for="cellphone_validate">SMS Verification Code</label> 
      <div class="control">
        <input type="text" class="input" id="cellphone_validate" name="cellphone_validate">
      </div>
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
  
    <div class="field">
      <label class="label" for="name">New Password <span class="has-text-weight-normal has-text-grey">(optional)</span></label>
      <div class="control">
        <input class="input" type="password" id="password" name="password" placeholder="Leave blank to keep exisiting password">
      </div>
    </div>
    <div id="confirm_password_block" class="field" style="display: none">
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
  
    <div class="field">
      <div class="control">
        <input class="button is-primary" name="" type="submit" value="Update Profile" value="">
      </div>
    </div>
    
  </form>

</div>

<div class="column">
    <h2 class="subtitle">Recent Access</h2>
<table class="table">
    <thead>
        <th>Date</th>
        <th>Time</th>
        <th>Location</th>
    </thead>
    <tbody>
<?php
    foreach($userAccessLog as $accessAttempt)
    {
?>
        <tr>
            <td><?php echo date("m/d/Y",strtotime($accessAttempt->timestamp)); ?></td>
            <td><?php echo date("g:ia",strtotime($accessAttempt->timestamp)); ?></td>
            <td><?php echo ($accessAttempt->ip_geolocation != "") ? $accessAttempt->ip_geolocation : 'unkown'; ?> </td>
        </tr>
<?php
    }
?>
    </tbody>
  </table>

    <p>Total logins: <strong><?php echo $user->logins; ?></strong></p>
    <p><a class="button is-info" href="<?php echo  _app_path ?>user/access">
      <span class="icon">
        <i class="fas fa-info-circle is-info"></i>
      </span>
      <span class="is-pulled-right">View Full Access Log</span>
    </a></p>

</div>

</section>


<?php
  if($_ENV['config']['TWILIO_Enabled'])
  {
?>

<style type="text/css">
  .smsInputGroup .notification {
    display: <?php echo ($user->cellphone != "" && $user->cellphone_verified == 0) ? "block" : "none"; ?>;
  }
  
  .smsInputGroup .help,
  .smsInputGroup button {
    display: none;
  }

  #smsVerificationCode {
    display: none;
  }
  
</style>

<script type="text/javascript">var APP_PATH = '<?php echo _app_path ?>';</script>
<script type="text/javascript" src="<?php echo _app_path ?>humblee/js/user/sms_verification.js"></script>
<?php
  }
?>

<script defer src="https://use.fontawesome.com/releases/v5.0.0/js/all.js"></script>
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

