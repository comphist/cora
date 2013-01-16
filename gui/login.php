<?php
/** @file login.php
 * The login page.
 */
?>

<div id="loginDiv" class="content">

<div class="panel">
   <h3>Anmeldung</h3>

   <?php if($_SESSION["failedLogin"]): ?>
   <p style="color: red;">Anmeldung fehlgeschlagen. Bitte überprüfen Sie Ihre Eingaben.</p>
   <?php endif; ?>

   <form action="<?php echo $_SERVER["PHP_SELF"]; ?>" method="post">
     <input type="hidden" name="action" value="login" />
      <h4><label for="loginform_un">Benutzername:</label></h4>
      <p class="text">
        <input type="text" name="loginform[un]" id="loginform_un" value="" style="width:100%" />
      </p>
   <h4><label for="loginform_pw">Passwort:</label></h4>
      <p class="text">
        <input type="password" name="loginform[pw]" id="loginform_pw" value="" style="width:100%" />
      </p>
      <p class="button">
        <button name="submit_button" value="save" type="submit">Anmelden</button>
      </p>
   </form>

</div>

</div>
