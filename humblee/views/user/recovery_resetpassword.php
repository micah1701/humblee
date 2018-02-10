<h1 class="title">Nice to see you again!</h1>
<p class="subtitle">Now that you've verified your account, create a new password</p>

<div class="columns">
    <div class="column is-one-third">
        <form action="<?php echo (isset($_GET['fwd']) && preg_match('/^[\w-\/-]+$/', $_GET['fwd'])) ? "?fwd=".$_GET['fwd'] : '' ?>" autocomplete="off" method="post">
    
            <div class="field">
              <label class="label" for="name">New Password</label>
              <div class="control">
                <input class="input" type="password" id="password" name="password" autocomplete="new-password" value="" required>
              </div>
            </div>
            <div class="field">
              <label class="label" for="password_check">Confirm Password</label> 
              <div class="control">
                <input class="input" type="password" id="password_check" autocomplete="new-password" name="password_check" required>
              </div>
            </div>
            
            <p>
                <button class="button is-primary" id="login">Reset Password</button>  
            </p>
        
        <?php 
            $crypto = new Core_Model_Crypto;
            $hmac_pair = $crypto->get_hmac_pair(); 
        ?>
            <input type="hidden" name="hmac_token" value="<?php echo $hmac_pair['message'] ?>">
            <input type="hidden" name="hmac_key" value="<?php echo $hmac_pair['hmac'] ?>">
        </form>        
        
    </div>
</div>

<script type="text/javascript">
$(document).ready(function(){
    $("#login").on("click",function(e){
        e.preventDefault();
        if($("#password").val() == "" || $("#password_check").val() == "")
        {
            alert("Both fields must be completed");
            return false;
        }
        
        if($("#password").val() != $("#password_check").val())
        {
           alert("The confirmation password value does not match!");
           $("#password_check").focus();
           return false;
        }
        
        $("form").get(0).submit();
    });
    
    //override browser trying to be nice and prefilling the password field.
    setTimeout(function(){
      $("#password").val('');
      $("#password_check").val('');
    },100);
});
</script>