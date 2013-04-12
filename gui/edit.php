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
			<th class="editTable_tokenid">#</th>
			<th class="editTable_line">Zeile</th>
			<th class="editTable_error">E</th>
                        <th class="editTable_tok_trans">Token (Trans)</th>
                        <th class="editTable_token">Token (UTF)</th>
   <?php if($_SESSION["normvisible"]): ?>
			<th class="editTable_Norm">Normalisierung</th>
			<th class="editTable_Mod">Modernisierung</th>
   <?php endif; ?>
			<th class="editTable_POS">POS-Tag</th>
			<th class="editTable_Morph">Morphologie-Tag</th>
			<th class="editTable_Lemma">Lemma</th>
			<th class="editTable_Comment">Kommentar</th>
			<th class="editTable_dropdown"></th>
		</tr>

		<tr id="line_template">
			<td class="editTable_progress">
			    <div class="editTableProgress"></div>
			</td>
			<td class="editTable_tokenid">
			</td>
			<td class="editTable_line">
			</td>
			<td class="editTable_error">
			    <div class="editTableError editTableCheckbox"></div>
			</td>
			<td class="editTable_tok_trans"></td>
			<td class="editTable_token"></td>
   <?php if($_SESSION["normvisible"]): ?>
			<td class="editTable_Norm">
			    <input type="text" value="" />
			</td>
			<td class="editTable_Mod">
			    <input type="text" value="" />
                            <select size="1" disabled="disabled">
                              <option value=""></option>
                              <option value="1">1</option>
                              <option value="2">2</option>
                              <option value="3">3</option>
                            </select>
			</td>
   <?php endif; ?>
			<td class="editTable_POS"></td>
			<td class="editTable_Morph"></td>
			<td class="editTable_Lemma">
			    <div class="editTableLemma editTableCheckbox"></div>
			    <input type="text" value="" />
			</td>
			<td class="editTable_Comment">
			    <input type="text" value="" />
			</td>
			<td class="editTable_dropdown">
   <div class="editTableDropdown"><img class="editTableDropdownIcon" src="gui/images/proxal/arrow-down.ico"/></div>
   <div class="editTableDropdownMenu">
   <ul>
   <li><a class="editTableDdButtonEdit" href="#">Token bearbeiten...</a></li>
   <li><a class="editTableDdButtonAdd" href="#">Token hinzufügen...</a></li>
   <li><a class="editTableDdButtonDelete" href="#">Token löschen</a></li>
   </ul>
   </div>
			</td>
		</tr>

	</table>
	<!-- <button id="undoEditBtn">Undo</button> -->
</div>

<div class="templateHolder">
  <div id="saveErrorPopup">
    <p></p>
    <p><textarea cols="80" rows="3" readonly="readonly"></textarea></p>
  </div>

   <div id="jumpToLineForm">
     <label for="jumpTo">Zeilennummer: </label>
     <input id="jumpToBox" type="text" name="jumpTo" placeholder="" size="6" class="mform" />
   </div>

   <div id="deleteTokenWarning">
   <p class="important_text"><strong>Achtung!</strong> Diese Aktion kann nicht rückgängig gemacht werden!</p>
   <p>Soll das Token &quot;<span id="deleteTokenToken"></span>&quot; wirklich gelöscht werden?</p>
   </div>

   <div id="editTokenConfirm" class="limitedWidth">
   <p>Die neue Transkription enthält mindestens ein Trennzeichen (<code>=</code> oder <code>(=)</code>), auf das kein Leerzeichen folgt.  Dies bedeutet, dass das Trennzeichen in der Transkription <strong>nicht am Zeilenende</strong>, sondern mitten im Wort stehen wird!</p>
     <p>Falls dies nicht beabsichtigt ist, muss hinter das Trennzeichen ein Leerzeichen eingefügt werden, um den Zeilenumbruch zu signalisieren.</p>
   <p>Sind Sie sicher, dass Sie diese Änderungen so durchführen wollen?</p>
   </div>

   <div id="editTokenForm" class="limitedWidth">
   <p id="editTokenWarning" class="error_text"><strong>Achtung!</strong> Es gibt in diesem Dokument noch ungespeicherte Änderungen, die beim Editieren dieser Transkription verloren gehen werden!</p>
   <p>
     <label for="editToken">Transkription: </label>
     <input id="editTokenBox" type="text" name="editToken" size="42" placeholder="" class="mform" />
   </p>
   <p><strong>Achtung!</strong> Leerzeichen in der Transkription werden als Zeilenumbrüche interpretiert und dürfen <strong>nur</strong> nach einem Trennzeichen (<code>=</code> oder <code>(=)</code>) benutzt werden.</p>

   <p>Wenn Sie aus einem Token zwei machen wollen, benutzen Sie bitte die Funktion &quot;Token hinzufügen&quot; und bearbeiten Sie dann die Transkription entsprechend.</p>
   </div>

   <div id="addTokenForm" class="limitedWidth">
   <p id="addTokenWarning" class="error_text"><strong>Achtung!</strong> Es gibt in diesem Dokument noch ungespeicherte Änderungen, die beim Hinzufügen dieser Transkription verloren gehen werden!</p>
   <form>														  
   <p>
     <label for="addToken">Transkription: </label>
     <input id="addTokenBox" type="text" name="addToken" size="42" placeholder="" />
   </p>
   </form>
   <p>Die neue Transkription wird <strong>vor</strong> dem Token &quot;<span id="addTokenBefore"></span>&quot; auf Zeile &quot;<span id="addTokenLineinfo"></span>&quot; in das Originaldokument eingefügt.</p>
   </div>

</div>	

</div>