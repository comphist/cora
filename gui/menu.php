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

<div id="menuRight" style="display:none;">
  <ul>
    <?php if($_SESSION['admin']): ?>
    <li id="tagButton" title="Zeigt Optionen für die automatische Annotation" active="false">
      <a><span class="oi oi-adjust" data-glyph="excerpt" aria-hidden="true"></span> Automatisch annotieren</a>
    </li>
    <?php endif; ?>
    <li id="saveButton" title="Speichert die aktuelle Datei" active="false">
      <a><span class="oi oi-adjust" data-glyph="file" aria-hidden="true"></span> Datei speichern</a>
    </li>
    <li id="closeButton" title="Schließt die aktuelle Datei" active="false">
      <a>Datei schließen</a>
    </li>
  </ul>
</div>