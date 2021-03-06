<h1 class="title">Knock Knock</h1>
<h2 class="subtitle">Log in using your username and password</h2>

<?php
$fwd = (isset($_GET['fwd']) && preg_match('/^[\w-\/-]+$/', $_GET['fwd'])) ? "?fwd=".$_GET['fwd'] : '';

if(isset($error))
{ 
?>
<div class="has-text-danger"><?php echo $error ?></div>
<?php 
} 
?>

<section class="section columns">
<form action="<?php echo $fwd ?>" method="post">
  
  <div class="field">
    <label class="label" for="username">Username:</label> 
    <div class="control">
      <input class="input" type="text" id="username" name="username">
    </div>
  </div>
  
  <div class="field is-clearfix">
    <label class="label" for="password">Password:</label> 
    <div class="control">
      <input class="input" type="password" id="password" name="password">
      <p class="help is-pulled-right"><a href="<?php echo _app_path ?>user/forgotPassword<?php echo $fwd ?>">Forgot Password?</a></p>
    </div>
  </div>
  
  <?php 
    $crypto = new Core_Model_Crypto;
    $hmac_pair = $crypto->get_hmac_pair(); 
  ?>
  <input type="hidden" name="hmac_token" value="<?php echo $hmac_pair['message'] ?>">
  <input type="hidden" name="hmac_key" value="<?php echo $hmac_pair['hmac'] ?>">

  <div class="field">
    <input class="button is-primary" name="" type="submit" value="Log In">
  </div>

</form>
</section>

<a href="<?php echo _app_path ?>user/register">Register</a>

