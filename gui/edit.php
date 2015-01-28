<?php
  /** @file edit.php
   * The annotation editor page.
   */
?>

<div id="editDiv" class="content">
<div id="editPanelDiv" class="panel">
  <div class="btn-toolbar" id="pagePanel">
    <span class="btn-toolbar-entry btn-page-count"><span class="oi" aria-hidden="true"></span>Seite <input type="text" class="btn-page-to" size="2" /><span class="page-active"></span>/<span class="page-max"></span></span>
    <span class="btn-toolbar-entry btn-page-back" title="Seite zurück"><span class="oi" data-glyph="arrow-thick-left" aria-hidden="true"></span></span>
    <span class="btn-toolbar-entry btn-page-forward" title="Seite vor"><span class="oi" data-glyph="arrow-thick-right" aria-hidden="true"></span></span>
    <span class="btn-toolbar-entry btn-jump-to"><span class="oi" data-glyph="book" aria-hidden="true"></span> Springe zu Zeile</span>

    <span class="btn-toolbar-separator"></span>
    <span class="btn-toolbar-entry btn-undo start-disabled" title="Rückgängig"><span class="oi" data-glyph="action-undo" aria-hidden="true"></span></span>
    <span class="btn-toolbar-entry btn-redo start-disabled" title="Wiederherstellen"><span class="oi" data-glyph="action-redo" aria-hidden="true"></span></span>

    <span class="btn-toolbar-separator"></span>
    <span class="btn-toolbar-entry btn-text-search"><span class="oi" data-glyph="magnifying-glass" aria-hidden="true"></span> Suchen</span>
    <span class="btn-toolbar-entry btn-search-back start-disabled" title="Vorheriges Suchergebnis"><span class="oi" data-glyph="arrow-left" aria-hidden="true"></span>&nbsp;<span class="oi" data-glyph="magnifying-glass" aria-hidden="true"></span></span>
    <span class="btn-toolbar-entry btn-search-forward start-disabled" title="Nächstes Suchergebnis"><span class="oi" data-glyph="magnifying-glass" aria-hidden="true"></span>&nbsp;<span class="oi" data-glyph="arrow-right" aria-hidden="true"></span></span>

    <span class="btn-toolbar-separator"></span>
    <span class="btn-toolbar-entry btn-text-annotate"><span class="oi" data-glyph="excerpt" aria-hidden="true"></span> Automatisch annotieren</span>
    <span class="btn-toolbar-entry btn-text-info"><span class="oi" data-glyph="info" aria-hidden="true"></span> Metadaten</span>
  </div>

  <table id="editTable">
  </table>

  <div id="horizontalTextViewContainer">
    <div id="horizontalTextView">
      <span>Text-Vorschau wird geladen...</span>
    </div>
  </div>

  <div class="btn-toolbar btn-toolbar-notop" id="pagePanelBottom">
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

  <div id="searchTokenForm">
    <p>
      Suche Tokens, die
        <select class="editSearchOperator" size="1">
          <option value="all">alle</option>
          <option value="any">mind. eine</option>
        </select>
      <span class="eso-det-agr">diese</span> Bedingungen erfüllen:
      <ul class="flexrow-container"></ul>
    </p>
  </div>

  <div id="deleteTokenWarning">
    <p class="important_text"><strong>Achtung!</strong> Diese Aktion kann nicht rückgängig gemacht werden!</p>
    <p>Soll das Token &quot;<span id="deleteTokenToken"></span>&quot; wirklich gelöscht werden?</p>
  </div>

  <div id="editTokenForm" class="limitedWidth-tiny">
    <p class="important_text"><strong>Achtung!</strong> Diese Aktion kann nicht rückgängig gemacht werden!</p>
    <p>
      <label for="editToken">Transkription: </label>
      <textarea id="editTokenBox" name="editToken" rows="1" cols="30" class="auto-resize mform"></textarea>
    </p>
  </div>

  <div id="addTokenForm" class="limitedWidth-tiny">
    <p class="important_text"><strong>Achtung!</strong> Diese Aktion kann nicht rückgängig gemacht werden!</p>
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

  <ul>
    <li id="editSearchCriterionTemplate" class="editSearchCriterion">
      <select class="editSearchField" size="1"></select>
      <select class="editSearchMatch" size="1"></select>
      <input type="text" name="editSearchText" class="editSearchText" placeholder="(leer)" />
    </li>
  </ul>

  <table id="data-table-template">
    <thead>
      <tr class="editHeadLine">
        <th class="editTable_progress">P</th>
        <th class="editTable_tokenid">#</th>
        <th class="editTable_line">Zeile</th>
        <th class="editTable_error">E</th>
        <th class="editTable_tok_trans">Token (Trans)</th>
        <th class="editTable_token">Token (UTF)</th>
        <th class="editTable_norm et-anno">Normalisierung</th>
        <th class="editTable_norm_broad et-anno">Modernisierung</th>
        <th class="editTable_norm_type et-anno"></th>
        <th class="editTable_pos et-anno">POS-Tag</th>
        <th class="editTable_morph et-anno">Morphologie-Tag</th>
        <th class="editTable_lemma et-anno">Lemma</th>
        <th class="editTable_lemma_sugg et-anno"></th>
        <th class="editTable_lemmapos et-anno">Lemma-Tag</th>
        <th class="editTable_comment et-anno">Kommentar</th>
        <th class="editTable_dropdown"></th>
      </tr>
    </thead>
    <tbody>
      <tr>
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
        <td class="editTable_norm et-anno">
          <input type="text" value="" class="et-input-norm" />
        </td>
        <td class="editTable_norm_broad et-anno">
          <input type="text" value="" class="et-input-norm_broad" />
        </td>
        <td class="editTable_norm_type et-anno">
          <select size="1" disabled="disabled" class="et-select-norm_type">
          </select>
        </td>
        <td class="editTable_pos et-anno">
          <select size="1" class="et-select-pos et-select-pos-main"></select>
        </td>
        <td class="editTable_morph et-anno">
          <select size="1" class="et-select-pos et-select-morph"></select>
        </td>
        <td class="editTable_lemma et-anno">
          <div class="editTableLemma editTableCheckbox"></div>
          <input type="text" value="" class="et-input-lemma" />
        </td>
        <td class="editTable_lemma_sugg et-anno">
          <div class="editTableLemmaLink">
            <span class="oi oi-adjust oi-shadow" data-glyph="external-link" title="Externen Link öffnen" aria-hidden="true"></span>
          </div>
        </td>
        <td class="editTable_lemmapos et-anno">
          <select size="1" class="et-select-lemmapos"></select>
        </td>
        <td class="editTable_comment et-anno">
          <input type="text" value="" maxlength="255" class="et-input-comment" />
        </td>
        <td class="editTable_dropdown">
          <div class="editTableDropdown">
            <span class="oi oi-adjust oi-shadow oi-green editTableDropdownIcon" data-glyph="caret-bottom" title="Dropdown-Menü öffnen" aria-hidden="true"></span>
          </div>
        </td>
      </tr>
    </tbody>
  </table>

</div>
</div>
