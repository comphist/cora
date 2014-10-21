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
    <span class="btn-toolbar-entry when-file-open-only" id="tagButton"><span class="oi" data-glyph="excerpt" aria-hidden="true"></span> Automatisch annotieren</span>
    <span class="btn-toolbar-entry when-file-open-only" id="saveButton"><span class="oi" data-glyph="file" aria-hidden="true"></span> Datei speichern</span>
    <span class="btn-toolbar-entry when-file-open-only" id="closeButton">Datei schließen</span>
    <a href="index.php?do=logout" id="logoutLink"><span class="btn-toolbar-entry">Logout</span></a>
  </div>
</div>
<?php endif; ?>
