<?php
/** @file login.php
 * The login page.
 */
?>

<div id="loginDiv" class="content">

<div class="panel">
   <h3><?php echo $lang["login_caption"]; ?></h3>

   <?php if($_SESSION["failedLogin"]): ?>
   <p style="color: red;"><?php echo $lang["login_failed"]; ?></p>
   <?php endif; ?>

   <form action="<?php echo $_SERVER["PHP_SELF"]; ?>" method="post">
     <input type="hidden" name="action" value="login" />
      <h4><label for="loginform_un"><?php echo $lang["login_un"]; ?></label></h4>
      <p class="text">
        <input type="text" name="loginform[un]" id="loginform_un" value="" style="width:100%" />
      </p>
      <h4><label for="loginform_pw"><?php echo $lang["login_pw"]; ?></label></h4>
      <p class="text">
        <input type="password" name="loginform[pw]" id="loginform_pw" value="" style="width:100%" />
      </p>
      <p class="button">
        <button name="submit_button" value="save" type="submit"><?php echo $lang["login_text"]; ?></button>
      </p>
   </form>

</div>

</div>
