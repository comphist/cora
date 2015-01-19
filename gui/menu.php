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
