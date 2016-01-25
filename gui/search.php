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
  /** @file search.php
   * The search results page.
   */
?>

<div id="searchDiv" class="content">
  <div id="searchPanelDiv" class="panel">
    <div class="srl-criteria" id="searchResultsCriteria">
      <p>
        <span class="srl-head-count">Es gibt</span> <span class="srl-count">256</span> <span class="srl-agr-count">Ergebnisse</span> <span data-trans-id="EditorTab.searchResult.resultInfoMiddle"><?=$lh("EditorTab.searchResult.resultInfoMiddle"); ?></span> 
        <span class="srl-operator">mindestens eine</span> <span class="srl-agr-operator">dieser</span> 
        <span data-trans-id="EditorTab.searchResult.resultInfoTail"><?=$lh("EditorTab.searchResult.resultInfoTail"); ?></span>
      </p>
      <ul class="srl-condition-list">
        <li>Token enthält "esse"</li>
      </ul>
    </div>

    <div class="btn-toolbar btn-toolbar-notop" id="searchPagePanel">
      <span class="btn-toolbar-entry btn-page-count"><span class="oi" aria-hidden="true"></span>
      <span data-trans-id="EditorTab.searchResult.resultPage"><?=$lh("EditorTab.searchResult.resultPage"); ?></span> <input type="text" class="btn-page-to" size="2" />
      <span class="page-active"></span>/<span class="page-max"></span></span>
      <span class="btn-toolbar-entry btn-page-back" title="<?=$lh("EditorTab.topButton.previousPage"); ?>" data-trans-title-id="EditorTab.topButton.previousPage">
        <span class="oi" data-glyph="arrow-thick-left" aria-hidden="true"></span></span>
      <span class="btn-toolbar-entry btn-page-forward" title="<?=$lh("EditorTab.topButton.nextPage"); ?>" data-trans-title-id="EditorTab.topButton.nextPage">
        <span class="oi" data-glyph="arrow-thick-right" aria-hidden="true"></span></span>
      <span class="btn-toolbar-entry btn-text-search">
        <span class="oi" data-glyph="magnifying-glass" aria-hidden="true"></span> <span data-trans-id="EditorTab.searchResult.editQuery"><?=$lh("EditorTab.searchResult.editQuery"); ?></span></span>
    </div>

    <table id="searchTable">
    </table>

    <div class="btn-toolbar btn-toolbar-notop" id="searchPagePanelBottom">
      <span class="btn-toolbar-entry btn-page-count"><span class="oi" aria-hidden="true"></span>
      <span data-trans-id="EditorTab.searchResult.resultPage"><?=$lh("EditorTab.searchResult.resultPage"); ?></span> <input type="text" class="btn-page-to" size="2" />
      <span class="page-active"></span>/<span class="page-max"></span></span>
      <span class="btn-toolbar-entry btn-page-back" title="<?=$lh("EditorTab.topButton.previousPage"); ?>" data-trans-title-id="EditorTab.topButton.previousPage">
        <span class="oi" data-glyph="arrow-thick-left" aria-hidden="true"></span></span>
      <span class="btn-toolbar-entry btn-page-forward" title="<?=$lh("EditorTab.topButton.nextPage"); ?>" data-trans-title-id="EditorTab.topButton.nextPage">
        <span class="oi" data-glyph="arrow-thick-right" aria-hidden="true"></span></span>
    </div>
  </div>
</div>
