<h1 class="title">Password Recovery</h1>
<h2 class="subtitle">Enter your username or e-mail address</h2>

<?php if(isset($error)){ ?>
    <div class="field is-two-fifths">
      <ul class="control">
    	<?php foreach($error as $err){ ?>
    	<li class="has-text-danger"><?php echo $err ?></li>
    	<?php } ?>    
      </ul>
    </div>
<?php } ?>

<div class="columns is-8">
    <div class="column is-one-third">
        <form action="<?php echo (isset($_GET['fwd']) && preg_match('/^[\w-\/-]+$/', $_GET['fwd'])) ? "?fwd=".$_GET['fwd'] : '' ?>" method="post">
            <div class="field">
                <label class="label" for="username">Username or E-Mail</label> 
                <div class="control">
                    <input class="input" type="text" id="username" name="username">
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
                    <input class="button is-primary" name="" type="submit" value="Next">
                </div>
            </div>  
          
        </form>        
    </div>
    
    <div class="column is-one-fifth"></div>

    <div class="column is-pulled-right is-two-fifths">
        <div class="message is-info">
            <div class="message-body">
                <p>Not sure what your e-mail address or username is?</p>
                <p>Contact your system administrator</p>
            </div>
        </div>
    </div>
</div>


<script type="text/javascript">
$(document).ready(function(){
   $("#username").focus(); 
});
</script>