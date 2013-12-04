<?php
  /** @file edit.php
   * The annotation editor page.
   */

	// $pageMax = $sh->getMaxLinesNo();

?>

<div id="editDiv" class="content" style="display: none;">

<div id="editPanelDiv" class="panel">

<div id="pagePanel" class="pagePanel">
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
			<th class="editTable_Norm">Normalisierung</th>
			<th class="editTable_Mod">Modernisierung</th>
			<th class="editTable_POS">POS-Tag</th>
			<th class="editTable_Morph">Morphologie-Tag</th>
			<th class="editTable_Lemma">Lemma</th>
			<th class="editTable_LemmaPOS">Lemma-Tag</th>
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
			<td class="editTable_Norm">
			    <input type="text" value="" />
			</td>
			<td class="editTable_Mod">
			    <input type="text" value="" />
                            <select size="1" disabled="disabled">
                              <option value=""></option>
                              <option value="s">s</option>
                              <option value="f">f</option>
                              <option value="x">x</option>
                            </select>
			</td>
			<td class="editTable_POS"></td>
			<td class="editTable_Morph"></td>
			<td class="editTable_Lemma">
			    <div class="editTableLemma editTableCheckbox"></div>
			    <input type="text" value="" />
                            <div class="editTableLemmaLink">
                                <img src="gui/images/proxal/chat-event.ico" />
                            </div>
			</td>
			<td class="editTable_LemmaPOS"></td>
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

   <div id="horizontalTextView">
     <span>Fehler bei der Text-Vorschau.</span>
   </div>

   <div id="pagePanel_bottom" class="pagePanel">
	<span>Seite: </span>
   </div>

</div>

<div class="templateHolder">
   <div id="jumpToLineForm">
     <label for="jumpTo">Zeilennummer: </label>
     <input id="jumpToBox" type="text" name="jumpTo" placeholder="" size="6" class="mform" />
   </div>

   <div id="deleteTokenWarning">
   <p class="important_text"><strong>Achtung!</strong> Diese Aktion kann nicht rückgängig gemacht werden!</p>
   <p>Soll das Token &quot;<span id="deleteTokenToken"></span>&quot; wirklich gelöscht werden?</p>
   </div>

   <div id="editTokenConfirm" class="limitedWidth">
   <p>Die neue Transkription enthält mindestens ein Trennzeichen, auf das kein Leerzeichen folgt.  Dies bedeutet, dass das Trennzeichen in der Transkription <strong>nicht am Zeilenende</strong>, sondern mitten im Wort stehen wird!</p>
     <p>Falls dies nicht beabsichtigt ist, muss hinter das Trennzeichen ein Leerzeichen eingefügt werden, um den Zeilenumbruch zu signalisieren.</p>
   <p>Sind Sie sicher, dass Sie diese Änderungen so durchführen wollen?</p>
   </div>

   <div id="editTokenForm" class="limitedWidth">
   <p id="editTokenWarning" class="error_text"><strong>Achtung!</strong> Es gibt in diesem Dokument noch ungespeicherte Änderungen, die beim Editieren dieser Transkription verloren gehen werden!</p>
   <p>
     <label for="editToken">Transkription: </label>
     <input id="editTokenBox" type="text" name="editToken" size="42" placeholder="" class="mform" />
   </p>
   <p><strong>Achtung!</strong> Leerzeichen in der Transkription werden als Zeilenumbrüche interpretiert und dürfen <strong>nur</strong> nach einem Trennzeichen benutzt werden.</p>

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

   <div id="automaticAnnotationForm" class="limitedWidth">
   <p>
     <b>Achtung!</b>  Dieser Vorgang überschreibt vorhandene Annotationen in allen Zeilen, die <b>nicht</b> mittels des Fortschrittsbalkens grün markiert sind.
   </p>

   <p>
   <label for="tagger">Tagger: </label>
   <select name="tagger" size="1">
   </select>
   </p>

   <p>
   <label><input type="checkbox" name="retrain" checked="checked" />  Auf vorhandenen Daten neu trainieren</label>
   </p>

   </div>

  <div id="editAnnotateSpinner">
   <div id="editAnnotateStatusContainer">
   <!--
   <table>
   <tr id="tIS_upload"><td class="proc proc-running"></td><td>Datei übermitteln</td></tr>
   <tr id="tIS_check"><td class="proc" /></td><td>Transkription prüfen</td></tr>
   <tr id="tIS_convert"><td class="proc" /></td><td>Transkription analysieren</td></tr>
   <tr id="tIS_tag"><td class="proc" /></td><td>Automatisch vorannotieren</td></tr>
   <tr id="tIS_import"><td class="proc" /></td><td>Importieren abschließen</td></tr>
   </table>
   -->
   <div id="eAS_progress"></div>
   </div>
  </div>


</div>	

</div>