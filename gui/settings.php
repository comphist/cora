<?php
  /** @file settings.php
   * The user settings page.
   */

?>

<div id="settingsDiv" class="content" style="display: none;">

<div class="panel clappable">
	<h3 class="clapp">Editor-Einstellungen</h3>
	<div>

	<div id="editorSettingsNumberOfLines">
   <h4>Zeilenanzahl</h4>
	<form action="request.php" id="editUserSettings" method="get" accept-charset="utf-8">
		<p>
		<label for="noPageLines">Zeilen pro Seite:</label>
		<input type="text" name="noPageLines" value="<?php echo $_SESSION['noPageLines'];?>" size="2" />
		</p>
        <p>
		<label for="contextLines">Überlappende Zeilen:</label>
		<input type="text" name="contextLines" value="<?php echo $_SESSION['contextLines'];?>" size="2" />
		</p>		

		<p><input type="submit" value="Zeilen-Einstellungen übernehmen" /></p>
	</form>
	</div>

	<div id="editorSettingsHiddenColumns">
	<h4>Sichtbare Spalten</h4>
	<p>
	    <input type="checkbox" name="displayedColumns" value="token" checked="yes" /> Token
	    <input type="checkbox" name="displayedColumns" value="Norm" checked="yes" /> Normalisierte Form
	    <input type="checkbox" name="displayedColumns" value="POS" checked="yes" /> POS-Tag
	    <input type="checkbox" name="displayedColumns" value="Morph" checked="yes" /> Morphologie-Tag
	    <input type="checkbox" name="displayedColumns" value="Lemma" checked="yes" /> Lemma
	    <input type="checkbox" name="displayedColumns" value="Comment" checked="yes" /> Kommentar
	</p>
	</div>

	<div id="editorSettingsInputAids">
	<h4>Editierhilfen</h4>
	<p>
   <input type="checkbox" name="showInputErrors" value="showInputErrors" checked="yes" /> Fehlerhafte Tags hervorheben
	</p>
	</div>
	</div>
</div>	

<div class="panel clappable">
	<h3 class="clapp">Allgemeine Einstellungen</h3>
	<div>

	<div id="generalSettings">
	<p>
	    <input type="checkbox" name="showTooltips" value="showTooltips" checked="yes" /> Tooltips anzeigen
	</p>
	</div>
	</div>
</div>	


</div>