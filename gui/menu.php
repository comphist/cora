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
   $tooltip = $lang["tooltip_".$item];
   $text = $lang["menu_".$item];
echo <<<MENUITEM
      <li class="tabButton" id="{$item}TabButton" title="{$tooltip}" active="false">
         <a onclick="changeTab('$item');">$text</a>
      </li>
MENUITEM;
} 
?>

    </ul>

</div>

<div id="menuRight" style="display:none;">
    <ul>
      <li id="saveButton" title="Speichert die aktuelle Datei" active="false">
         <a><img src="gui/images/proxal/file.ico"> Datei speichern</a>
      </li>
      <li id="closeButton" title="Schließt die aktuelle Datei" active="false">
         <a>Datei schließen</a>
      </li>
    </ul>
</div>