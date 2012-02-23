<?php
  /** @file edit.php
   * The annotation editor page.
   */

	// $pageMax = $sh->getMaxLinesNo();

?>

<div id="editDiv" class="content" style="display: none;">

<div class="panel clappable">
	<h3 class="clapp">Editor-Benutzereinstellungen</h3>
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

		<p><input type="submit" value="Continue &rarr;" /></p>
	</form>
	</div>
</div>	


<div class="panel">

<div id="pagePanel">
	<span>Seite: </span>
</div>


	<table id="editTable" border="0">
		
		<tr class="editHeadLine">
			<th>P</th>
			<th>E</th>
    		<th class="editTable_token">Token</th>
			<th class="editTable_Norm">Normalisierte Form</th>
			<th class="editTable_POS">POS-Tag</th>
			<th class="editTable_Morph">Morphologie-Tag</th>
			<th class="editTable_Lemma">Lemma</th>
			<th class="editTable_Comment">Kommentar</th>
		</tr>
	</table>
	<!-- <button id="undoEditBtn">Undo</button> -->
</div>

</div>