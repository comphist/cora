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
