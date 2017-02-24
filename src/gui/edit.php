<?php
/*
 * Copyright (C) 2015 Marcel Bollmann <bollmann@linguistics.rub.de>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy of
 * this software and associated documentation files (the "Software"), to deal in
 * the Software without restriction, including without limitation the rights to
 * use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of
 * the Software, and to permit persons to whom the Software is furnished to do so,
 * subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS
 * FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR
 * COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER
 * IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN
 * CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */ ?>
<?php
  /** @file edit.php
   * The annotation editor page.
   */
?>

<div id="editDiv" class="content">
<div id="editPanelDiv" class="panel">
  <div class="btn-toolbar" id="pagePanel">
    <span class="btn-toolbar-entry btn-page-count">
      <span class="oi" aria-hidden="true"></span>
      <span data-trans-id="EditorTab.topButton.page"><?=$lh("EditorTab.topButton.page"); ?></span>
      <input type="text" class="btn-page-to" size="2" />
      <span class="page-active"></span>/<span class="page-max"></span>
    </span>

    <span class="btn-toolbar-entry btn-page-back" title="<?=$lh("EditorTab.topButton.previousPage"); ?>" data-trans-title-id="EditorTab.topButton.previousPage">
      <span class="oi" data-glyph="arrow-thick-left" aria-hidden="true"></span>
    </span>
    <span class="btn-toolbar-entry btn-page-forward" title="<?=$lh("EditorTab.topButton.nextPage"); ?>" data-trans-title-id="EditorTab.topButton.nextPage">
      <span class="oi" data-glyph="arrow-thick-right" aria-hidden="true"></span>
    </span>
    <span class="btn-toolbar-entry btn-jump-to">
      <span class="oi" data-glyph="book" aria-hidden="true"></span><span data-trans-id="EditorTab.topButton.goToLine"><?=$lh("EditorTab.topButton.goToLine"); ?></span>
    </span>

    <span class="btn-toolbar-separator"></span>
    <span class="btn-toolbar-entry btn-undo start-disabled" title="<?=$lh("EditorTab.topButton.undo"); ?>" data-trans-title-id="EditorTab.topButton.undo">
      <span class="oi" data-glyph="action-undo" aria-hidden="true"></span>
    </span>
    <span class="btn-toolbar-entry btn-redo start-disabled" title="<?=$lh("EditorTab.topButton.redo"); ?>" data-trans-title-id="EditorTab.topButton.redo">
      <span class="oi" data-glyph="action-redo" aria-hidden="true"></span>
    </span>

    <span class="btn-toolbar-separator"></span>
    <span class="btn-toolbar-entry btn-text-search">
      <span class="oi" data-glyph="magnifying-glass" aria-hidden="true"></span> <span data-trans-id="EditorTab.topButton.search"><?=$lh("EditorTab.topButton.search"); ?></span></span>
    <span class="btn-toolbar-entry btn-search-back start-disabled" title="<?=$lh("EditorTab.topButton.previousMatch"); ?>" data-trans-title-id="EditorTab.topButton.previousMatch">
      <span class="oi" data-glyph="arrow-left" aria-hidden="true"></span>&nbsp;<span class="oi" data-glyph="magnifying-glass" aria-hidden="true"></span>
    </span>
    <span class="btn-toolbar-entry btn-search-forward start-disabled" title="<?=$lh("EditorTab.topButton.nextMatch"); ?>" data-trans-title-id="EditorTab.topButton.nextMatch">
      <span class="oi" data-glyph="magnifying-glass" aria-hidden="true"></span>&nbsp;<span class="oi" data-glyph="arrow-right" aria-hidden="true"></span>
    </span>

    <span class="btn-toolbar-separator"></span>
    <span class="btn-toolbar-entry btn-text-annotate">
      <span class="oi" data-glyph="excerpt" aria-hidden="true"></span> <span data-trans-id="EditorTab.topButton.autoAnnotation">
      <?=$lh("EditorTab.topButton.autoAnnotation"); ?>
    </span></span>
    <span class="btn-toolbar-entry btn-text-info">
      <span class="oi" data-glyph="info" aria-hidden="true"></span> <span data-trans-id="EditorTab.topButton.metaData">
      <?=$lh("EditorTab.topButton.metaData"); ?>
    </span></span>
  </div>

  <table id="editTable">
  </table>

  <div id="horizontalTextViewContainer">
    <div id="horizontalTextView">
      <span>Text-Vorschau wird geladen...</span>
    </div>
  </div>

  <div class="btn-toolbar btn-toolbar-notop" id="pagePanelBottom">
    <span class="btn-toolbar-entry btn-page-count"><span class="oi" aria-hidden="true"></span>
    <span data-trans-id="EditorTab.topButton.page"><?=$lh("EditorTab.topButton.page"); ?></span> <input type="text" class="btn-page-to" size="2" /><span class="page-active"></span>/<span class="page-max"></span></span>
    <span class="btn-toolbar-entry btn-page-back" title="<?=$lh("EditorTab.topButton.previousPage"); ?>" data-trans-title-id="EditorTab.topButton.previousPage">
      <span class="oi" data-glyph="arrow-thick-left" aria-hidden="true"></span>
    </span>
    <span class="btn-toolbar-entry btn-page-forward" title="<?=$lh("EditorTab.topButton.nextPage"); ?>" data-trans-title-id="EditorTab.topButton.nextPage">
      <span class="oi" data-glyph="arrow-thick-right" aria-hidden="true"></span>
    </span>
    <span class="btn-toolbar-entry btn-jump-to"><span class="oi" data-glyph="book" aria-hidden="true"></span>
    <span data-trans-id="EditorTab.topButton.goToLine"><?=$lh("EditorTab.topButton.goToLine"); ?></span> </span>
  </div>
</div>

<!-- templates -->
<div class="templateHolder">
  <div id="jumpToLineForm">
    <label for="jumpTo" data-trans-id="EditorTab.topButton.lineNumber"><?=$lh("EditorTab.topButton.lineNumber"); ?></label>
    <input id="jumpToBox" type="text" name="jumpTo" placeholder="" size="6" class="mform" />
  </div>

  <div id="searchTokenForm">
    
    <p>
    <span data-trans-id="EditorTab.Forms.searchForm.searchForm1"><?=$lh("EditorTab.Forms.searchForm.searchForm1"); ?></span>

    <select class="editSearchOperator" size="1">
      <option value="all" data-trans-id="EditorTab.Forms.searchForm.allFulfilled">
        <?=$lh("EditorTab.Forms.searchForm.allFulfilled"); ?>
      </option>
      <option value="any" data-trans-id="EditorTab.Forms.searchForm.oneFulfilled">
        <?=$lh("EditorTab.Forms.searchForm.oneFulfilled"); ?>
      </option>
    </select>

    <span data-trans-id="EditorTab.Forms.searchForm.searchForm2"><?=$lh("EditorTab.Forms.searchForm.searchForm2"); ?></span>


    </p>
      <ul class="flexrow-container"></ul>
    </p>


  </div>

  <div id="deleteTokenWarning">
    <p class="important_text">
      <strong data-trans-id="EditorTab.general.warning"><?=$lh("EditorTab.general.warning"); ?></strong>
      <span data-trans-id="EditorTab.Forms.cannotBeUndone"><?=$lh("EditorTab.Forms.cannotBeUndone"); ?></span>
    </p>
    <p id='deleteTokenToken'></p>

  </div>

  <div id="editTokenForm" class="limitedWidth-tiny">
    <p class="important_text">
      <strong data-trans-id="EditorTab.general.warning"><?=$lh("EditorTab.general.warning"); ?></strong>
      <span data-trans-id="EditorTab.Forms.cannotBeUndone"><?=$lh("EditorTab.Forms.cannotBeUndone"); ?></span>
    </p>

    <p>
      <label for="editToken" data-trans-id="EditorTab.Forms.editTranscriptionForm.transcription"><?=$lh("EditorTab.Forms.editTranscriptionForm.transcription"); ?></label>
      <textarea id="editTokenBox" name="editToken" rows="1" cols="30" class="auto-resize mform"></textarea>
    </p>
  </div>

  <div id="addTokenForm" class="limitedWidth-tiny">
    <p class="important_text">
      <strong data-trans-id="EditorTab.general.warning"><?=$lh("EditorTab.general.warning"); ?></strong>
      <span data-trans-id="EditorTab.Forms.cannotBeUndone"><?=$lh("EditorTab.Forms.cannotBeUndone"); ?></span>
    </p>

    <form>
      <p>
        <label for="addToken" data-trans-id="EditorTab.Forms.addTranscriptionForm.newTranscription"><?=$lh("EditorTab.Forms.addTranscriptionForm.newTranscription"); ?></label>
        <textarea id="addTokenBox" name="addToken" rows="1" cols="30" class="auto-resize mform"></textarea>
      </p>
    </form>
    <p>
    <span id='addTokenBefore'></span>
    </p>
  </div>

  <div id="automaticAnnotationForm" class="limitedWidth">
    <p>
      <strong data-trans-id="EditorTab.general.warning"><?=$lh("EditorTab.general.warning"); ?></strong>

      <span data-trans-id="EditorTab.Forms.autoAnnotationForm.autoAnnotationInfo"><?=$lh("EditorTab.Forms.autoAnnotationForm.autoAnnotationInfo"); ?></span>

    </p>
    
    <p>
      <label for="aa_tagger_select" data-trans-id="EditorTab.Forms.autoAnnotationForm.availableTagger"><?=$lh("EditorTab.Forms.autoAnnotationForm.availableTagger"); ?></label>
      <div id="aa_tagger_select"></div>
    </p>
  </div>

  <div id="fileMetadataForm">
    <form>
    <p>
      <label for="fmf-sigle" class="ra" data-trans-id="EditorTab.Forms.metaDataForm.siglum"><?=$lh("EditorTab.Forms.metaDataForm.siglum"); ?> </label>
      <input type="text" name="fmf-sigle" size="30" placeholder="" class="mform" />
    </p>
    <p>
      <label for="fmf-name" class="ra" data-trans-id="EditorTab.Forms.metaDataForm.fileName"><?=$lh("EditorTab.Forms.metaDataForm.fileName"); ?> </label>
      <input type="text" name="fmf-name" size="30" placeholder="" class="mform"/>
    </p>
    <p>
      <label for="fmf-header" class="ra vt" data-trans-id="EditorTab.Forms.metaDataForm.header"><?=$lh("EditorTab.Forms.metaDataForm.header"); ?> </label>
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
      <input type="text" name="editSearchText" class="editSearchText" placeholder="<?=$lh("EditorTab.Forms.searchForm.blank"); ?>" data-trans-placeholder-id="EditorTab.Forms.searchForm.blank" />
    </li>
  </ul>

  <table id="data-table-template">
    <thead>
      <tr class="editHeadLine">
        <th class="editTable_progress">P</th>
        <th class="editTable_tokenid">#</th>
        <th class="editTable_line" data-trans-id="Columns.line"><?=$lh("Columns.line"); ?></th>
        <th class="editTable_error">E</th>
        <th class="editTable_tok_trans" data-trans-id="Columns.transTok"><?=$lh("Columns.transTok"); ?></th>
        <th class="editTable_token" data-trans-id="Columns.utfTok"><?=$lh("Columns.utfTok"); ?></th>
        <th class="editTable_norm et-anno" data-trans-id="Columns.norm"><?=$lh("Columns.norm"); ?></th>
        <th class="editTable_norm_broad et-anno" data-trans-id="Columns.mod"><?=$lh("Columns.mod"); ?></th>
        <th class="editTable_norm_type et-anno"></th>
        <th class="editTable_boundary et-anno" data-trans-id="Columns.sentBoundary"><?=$lh("Columns.sentBoundary"); ?></th>
        <th class="editTable_pos et-anno" data-trans-id="Columns.pos"><?=$lh("Columns.pos"); ?></th>
        <th class="editTable_morph et-anno" data-trans-id="Columns.morph"><?=$lh("Columns.morph"); ?></th>
        <th class="editTable_lemma et-anno" data-trans-id="Columns.lemma"><?=$lh("Columns.lemma"); ?></th>
        <th class="editTable_lemma_sugg et-anno"></th>
        <th class="editTable_lemmapos et-anno" data-trans-id="Columns.lemmaTag"><?=$lh("Columns.lemmaTag"); ?></th>
        <th class="editTable_comment et-anno" data-trans-id="Columns.comment"><?=$lh("Columns.comment"); ?></th>
        <th class="editTable_sec_comment et-anno" data-trans-id="Columns.secondaryComment"><?=$lh("Columns.secondaryComment"); ?></th>
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
        <td class="editTable_boundary et-anno">
          <div class="editTableBoundary editTableCheckbox"></div>
          <select size="1" class="et-select-boundary"></select>
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
            <span class="oi oi-adjust oi-shadow" data-glyph="external-link" title="<?=$lh("EditorTab.general.openExternalLink"); ?>" data-trans-title-id="EditorTab.general.openExternalLink" aria-hidden="true"></span>
          </div>
        </td>
        <td class="editTable_lemmapos et-anno">
          <select size="1" class="et-select-lemmapos"></select>
        </td>
        <td class="editTable_comment et-anno">
          <input type="text" value="" maxlength="255" class="et-input-comment" />
        </td>
        <td class="editTable_sec_comment et-anno">
          <input type="text" value="" maxlength="255" class="et-input-sec_comment" />
        </td>
        <td class="editTable_dropdown">
          <div class="editTableDropdown">
            <span class="oi oi-adjust oi-shadow oi-green editTableDropdownIcon" data-glyph="caret-bottom" title="<?=$lh("EditorTab.dropDown.openDropdownMenu"); ?>" data-trans-title-id="EditorTab.dropDown.openDropdownMenu" aria-hidden="true"></span>
          </div>
        </td>
      </tr>
    </tbody>
  </table>

</div>
</div>
