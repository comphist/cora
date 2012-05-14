<?php
  /** @file edit.php
   * The annotation editor page.
   */

	// $pageMax = $sh->getMaxLinesNo();

?>

<div id="editDiv" class="content" style="display: none;">

<div class="panel clappable">
	<h3 class="clapp">Editor-Benutzereinstellungen</h3>
	<div style="display:none;">
	<div>
	<form action="request.php" id="editUserSettings" method="get" accept-charset="utf-8">
		<p>
		<label for="noPageLines">Zeilen pro Seite</label>
		<input type="text" name="noPageLines" value="<?php echo $_SESSION['noPageLines'];?>" size="3" />
		</p>
        <p>
		<label for="contextLines">Anzahl überlappender Zeilen</label>
		<input type="text" name="contextLines" value="<?php echo $_SESSION['contextLines'];?>" size="2" />
		</p>		

		<p><input type="submit" value="Zeilen-Einstellungen übernehmen" /></p>
	</form>
	</div>
	<div id="editorSettingsHiddenColumns">
	<h4>Angezeigte Spalten:</h4>
	<p>
	    <input type="checkbox" name="displayedColumns" value="token" checked="yes" /> Token
	    <input type="checkbox" name="displayedColumns" value="Norm" checked="yes" /> Normalisierte Form
	    <input type="checkbox" name="displayedColumns" value="POS" checked="yes" /> POS-Tag
	    <input type="checkbox" name="displayedColumns" value="Morph" checked="yes" /> Morphologie-Tag
	    <input type="checkbox" name="displayedColumns" value="Lemma" checked="yes" /> Lemma
	    <input type="checkbox" name="displayedColumns" value="Comment" checked="yes" /> Kommentar
	</p>
	</div>
	</div>
</div>	


<div id="editPanelDiv" class="panel" style="display: none;">

<div id="pagePanel">
	<span>Seite: </span>
</div>


	<table id="editTable" border="0" class="draggable">
		
		<tr class="editHeadLine">
			<th class="editTable_progress">P</th>
			<th class="editTable_error">E</th>
			<th class="editTable_token">Token</th>
			<th class="editTable_Norm">Normalisierte Form</th>
			<th class="editTable_POS">POS-Tag</th>
			<th class="editTable_Morph">Morphologie-Tag</th>
			<th class="editTable_Lemma">Lemma</th>
			<th class="editTable_Comment">Kommentar</th>
		</tr>

		<tr id="line_template">
			<td class="editTable_progress">
			    <div class="editTableProgress"></div>
			</td>
			<td class="editTable_error">
			    <div class="editTableError"></div>
			</td>
			<td class="editTable_token"></td>
			<td class="editTable_Norm">
			    <input type="text" size="10" value="" />
			</td>
			<td class="editTable_POS"></td>
			<td class="editTable_Morph"></td>
			<td class="editTable_Lemma">
			    <input type="text" size="10" value="" />
			</td>
			<td class="editTable_Comment">
			    <input type="text" size="30" value="" />
			</td>
		</tr>

	</table>
	<!-- <button id="undoEditBtn">Undo</button> -->
</div>

<div class="templateHolder" style="display: none;">
  <div id="saveErrorPopup">
    <p></p>
    <p><textarea cols="80" rows="3" readonly="readonly"></textarea></p>
  </div>
</div>	

</div>