<?php 
/*
 * Copyright (C) 2015 Marcel Bollmann <bollmann@linguistics.rub.de>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy of
 * this software and associated documentation files (the "Software"), to deal in
 * the Software without restriction, including without limitation the rights to
 * use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of
 * the Software, and to permit persons to whom the Software is furnished to do so,
 * subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS
 * FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR
 * COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER
 * IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN
 * CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */ ?>
<?php
/** @file login.php
 * The login page.
 */
?>

<div id="loginDiv" class="content">

<div class="panel">
   <?php if($_SESSION["failedLogin"]): ?>
   <p style="color: red;">Anmeldung fehlgeschlagen. Bitte überprüfen Sie Ihre Eingaben.</p>
   <?php endif; ?>

   <form action="<?php echo $_SERVER["PHP_SELF"]; ?>" method="post" class="loginForm">
     <input type="hidden" name="action" value="login"/>
      <h4><label for="loginform_un">Benutzername:</label></h4>
      <p class="text">
        <input type="text" name="loginform[un]" id="loginform_un" value="" />
      </p>
   <h4><label for="loginform_pw">Passwort:</label></h4>
      <p class="text">
        <input type="password" name="loginform[pw]" id="loginform_pw" value="" />
      </p>
      <p class="button">
        <button name="submit_button" value="save" type="submit">Anmelden</button>
      </p>
   </form>

</div>

</div>
