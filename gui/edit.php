<?php
  /** @file edit.php
   * The annotation editor page.
   */

	// $pageMax = $sh->getMaxLinesNo();

?>

<div id="editDiv" class="content" style="display: none;">

<div id="editPanelDiv" class="panel">

<div id="pagePanel">
	<span>Seite: </span>
</div>


	<table id="editTable" border="0" class="draggable">
		
		<tr class="editHeadLine" id="editHeadline">
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