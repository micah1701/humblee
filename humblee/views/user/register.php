<?php

if(isset($_SESSION[session_key]['user_id'])) // user is already logged in
{ 
    $fwd = (isset($_GET['fwd'])) ? _app_path.$_GET['fwd'] : _app_path."user";
    header("Location: ".$fwd);
    exit();
}
?>

<h1>Register</h1>

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

  <label for="name">Full Name</label>
  <input type="text" id="name" name="name">
  
  <label for="username">Username</label>
  <input type="text" id="username" name="username">
  
  <label for="email">E-Mail Address</label>
  <input type="text" id="email" name="email">

  <label for="password">Create Password</label>
  <input type="password" id="password" name="password">

  <label for="password_check">Confirm Password</label>
  <input type="password" id="password_check" name="password_check">

  <?php 
    $crypto = new Core_Model_Crypto;
    $hmac_pair = $crypto->get_hmac_pair(); 
  ?>
  <input type="hidden" name="hmac_token" value="<?php echo $hmac_pair['message'] ?>">
  <input type="hidden" name="hmac_key" value="<?php echo $hmac_pair['hmac'] ?>">

  <p><input name="" type="submit" value="Register"></p>

</form>