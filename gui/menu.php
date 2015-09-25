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
  /** @file menu.php
   * The menu bar.
   *
   * Generates the menu buttons for each menu entry.
   */
?>

<div id="menu">
  <ul>

<?php
/* Generate a button for each menu entry */
foreach( $menu->getItems() as $item ) {
   $tooltip = $menu->getItemTooltip($item);
   $text = $menu->getItemCaption($item);
echo <<<MENUITEM
      <li class="tabButton" id="{$item}TabButton" title="{$tooltip}" active="false">
         <a onclick="gui.changeTab('$item');">$text</a>
      </li>
MENUITEM;
}
?>
  </ul>
</div>

<?php if($_SESSION["loggedIn"]): ?>
<div id="menuRight">
  <div class="btn-toolbar-dark">
    <span class="btn-toolbar-entry when-file-open-only" id="closeButton"><span class="oi" aria-hidden="true"></span>Datei schließen</span>
    <span class="btn-toolbar-entry" id="logoutButton"><span class="oi" data-glyph="account-logout" aria-hidden="true"></span> Logout</span>
  </div>
</div>

<div id="connectionInfo">
  <p>angemeldet als '<span class="username"><?php echo $_SESSION['user']; ?></span>'<span class="oi connected" data-glyph="pulse" aria-hidden="true"></p>
</div>
<?php endif; ?>
