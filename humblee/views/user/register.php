<h1 class="title">Hello? Who is it?</h1>
<h2 class="subtitle">Register for access</h2>
<p>Already have an account? <a href="<?php echo _app_path ?>user/login">Sign In</a></p>
<?php
/* uncomment this message if the registration process does not automatically grant "login" access.
<div class="message is-info">
  <div class="message-body">
    <p>Registration will create your account but it must first be approved by the system administrator before access is granted</p>
  </div>
</div>
*/
?>

<section class="section columns">

  <div class="column is-one-third">
    
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
        <input class="input" type="text" id="name" name="name">
      </div>
    </div>
    
    <div class="field is-two-fifths">
      <label class="label" for="username">Username</label> 
      <div class="control">
        <input class="input" type="text" id="username" name="username">
      </div>
    </div>
    
    <div class="field is-two-fifths">
      <label class="label" for="email">E-mail Address:</label> 
      <div class="control">
        <input class="input" type="text" id="email" name="email">
      </div>
    </div>
    
    <div class="field is-two-fifths">
      <label class="label" for="password">Create Password</label> 
      <div class="control">
        <input class="input" type="password" id="password" name="password">
      </div>
    </div>
    
    <div class="field is-two-fifths">
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
        <input class="button is-primary" name="" type="submit" value="Register">
      </div>
    </div>
  
  </form>    
  </div>

</section>