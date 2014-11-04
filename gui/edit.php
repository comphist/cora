<?php
  /** @file edit.php
   * The annotation editor page.
   */
?>

<div id="editDiv" class="content" style="display: none;">
<div id="editPanelDiv" class="panel">
  <div class="btn-toolbar" id="pagePanel">
    <span class="btn-toolbar-entry btn-page-count"><span class="oi" aria-hidden="true"></span>Seite <input type="text" class="btn-page-to" size="2" /><span class="page-active"></span>/<span class="page-max"></span></span>
    <span class="btn-toolbar-entry btn-page-back" title="Seite zurück"><span class="oi" data-glyph="arrow-thick-left" aria-hidden="true"></span></span>
    <span class="btn-toolbar-entry btn-page-forward" title="Seite vor"><span class="oi" data-glyph="arrow-thick-right" aria-hidden="true"></span></span>
    <span class="btn-toolbar-entry btn-jump-to"><span class="oi" data-glyph="book" aria-hidden="true"></span> Springe zu Zeile</span>
    <span class="btn-toolbar-entry btn-text-info"><span class="oi" data-glyph="info" aria-hidden="true"></span> Metadaten</span>
  </div>

  <table id="editTable" border="0" class="draggable">
    <thead>
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
    </thead>
    <tbody>
      <tr id="line_template">
        <td class="editTable_progress">
          <div class="editTableProgress"></div>
        </td>
        <td class="editTable_tokenid"></td>
        <td class="editTable_line"></td>
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
             <span class="oi oi-adjust oi-shadow" data-glyph="external-link" title="Externen Link öffnen" aria-hidden="true"></span>
          </div>
        </td>
        <td class="editTable_LemmaPOS"></td>
        <td class="editTable_Comment">
          <input type="text" value="" maxlength="255" />
        </td>
        <td class="editTable_dropdown">
          <div class="editTableDropdown">
             <span class="oi oi-adjust oi-shadow oi-green editTableDropdownIcon" data-glyph="caret-bottom" title="Dropdown-Menü öffnen" aria-hidden="true"></span>
          </div>
          <div class="editTableDropdownMenu">
            <ul>
              <li><a class="editTableDdButtonEdit" href="#">Token bearbeiten...</a></li>
              <li><a class="editTableDdButtonAdd" href="#">Token hinzufügen...</a></li>
              <li><a class="editTableDdButtonDelete" href="#">Token löschen</a></li>
            </ul>
          </div>
        </td>
      </tr>
    </tbody>
  </table>

  <div id="horizontalTextViewContainer">
    <div id="horizontalTextView">
      <span>Text-Vorschau wird geladen...</span>
    </div>
  </div>

  <div class="btn-toolbar" id="pagePanelBottom">
    <span class="btn-toolbar-entry btn-page-count"><span class="oi" aria-hidden="true"></span>Seite <input type="text" class="btn-page-to" size="2" /><span class="page-active"></span>/<span class="page-max"></span></span>
    <span class="btn-toolbar-entry btn-page-back" title="Seite zurück"><span class="oi" data-glyph="arrow-thick-left" aria-hidden="true"></span></span>
    <span class="btn-toolbar-entry btn-page-forward" title="Seite vor"><span class="oi" data-glyph="arrow-thick-right" aria-hidden="true"></span></span>
    <span class="btn-toolbar-entry btn-jump-to"><span class="oi" data-glyph="book" aria-hidden="true"></span> Springe zu Zeile</span>
  </div>
</div>

<!-- templates -->
<div class="templateHolder">
  <div id="jumpToLineForm">
    <label for="jumpTo">Zeilennummer: </label>
    <input id="jumpToBox" type="text" name="jumpTo" placeholder="" size="6" class="mform" />
  </div>

  <div id="deleteTokenWarning">
    <p class="important_text"><strong>Achtung!</strong> Diese Aktion kann nicht rückgängig gemacht werden!</p>
    <p>Soll das Token &quot;<span id="deleteTokenToken"></span>&quot; wirklich gelöscht werden?</p>
  </div>

  <div id="editTokenForm" class="limitedWidth-tiny">
    <p id="editTokenWarning" class="error_text"><strong>Achtung!</strong> Es gibt in diesem Dokument noch ungespeicherte Änderungen, die beim Editieren dieser Transkription verloren gehen werden!</p>
    <p>
      <label for="editToken">Transkription: </label>
      <textarea id="editTokenBox" name="editToken" rows="1" cols="30" class="auto-resize mform"></textarea>
    </p>
  </div>

  <div id="addTokenForm" class="limitedWidth-tiny">
    <p id="addTokenWarning" class="error_text"><strong>Achtung!</strong> Es gibt in diesem Dokument noch ungespeicherte Änderungen, die beim Hinzufügen dieser Transkription verloren gehen werden!</p>
    <form>
      <p>
        <label for="addToken">Transkription: </label>
        <textarea id="addTokenBox" name="addToken" rows="1" cols="30" class="auto-resize mform"></textarea>
      </p>
    </form>
    <p>Die neue Transkription wird <strong>vor</strong> dem Token &quot;<span id="addTokenBefore"></span>&quot; auf Zeile &quot;<span id="addTokenLineinfo"></span>&quot; in das Originaldokument eingefügt.</p>
  </div>

  <div id="automaticAnnotationForm" class="limitedWidth">
    <p>
      <b>Achtung!</b>  &quot;Annotieren&quot; überschreibt vorhandene Annotationen in allen Zeilen, die <b>nicht</b> mittels des Fortschrittsbalkens grün markiert sind.
    </p>

    <p>
      <label for="aa_tagger_select">Tagger: </label>
      <div id="aa_tagger_select"></div>
    </p>
  </div>

  <div id="fileMetadataForm">
    <form>
    <p>
      <label for="fmf-sigle" class="ra">Sigle: </label>
      <input type="text" name="fmf-sigle" size="30" placeholder="" class="mform" />
    </p>
    <p>
      <label for="fmf-name" class="ra">Dateiname: </label>
      <input type="text" name="fmf-name" size="30" placeholder="" class="mform"/>
    </p>
    <p>
      <label for="fmf-header" class="ra vt">Header: </label>
      <textarea cols="60" rows="10" name="fmf-header" class="mform sans"></textarea>
    </p>
    </form>
  </div>

  <div id="editAnnotateSpinner">
    <div id="editAnnotateStatusContainer">
      <div id="eAS_progress"></div>
    </div>
  </div>
</div>
</div>