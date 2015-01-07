<?php
  /** @file search.php
   * The search results page.
   */
?>

<div id="searchDiv" class="content" style="display: none;">
  <div id="searchPanelDiv" class="panel">
    <div class="btn-toolbar" id="searchPagePanel">
      <span class="btn-toolbar-entry btn-page-count"><span class="oi" aria-hidden="true"></span>Ergebnisseite <input type="text" class="btn-page-to" size="2" /><span class="page-active"></span>/<span class="page-max"></span></span>
      <span class="btn-toolbar-entry btn-page-back" title="Seite zurück"><span class="oi" data-glyph="arrow-thick-left" aria-hidden="true"></span></span>
      <span class="btn-toolbar-entry btn-page-forward" title="Seite vor"><span class="oi" data-glyph="arrow-thick-right" aria-hidden="true"></span></span>
    </div>

    <table id="searchTable">
    </table>

    <div class="btn-toolbar btn-toolbar-bottom" id="searchPagePanelBottom">
      <span class="btn-toolbar-entry btn-page-count"><span class="oi" aria-hidden="true"></span>Ergebnisseite <input type="text" class="btn-page-to" size="2" /><span class="page-active"></span>/<span class="page-max"></span></span>
      <span class="btn-toolbar-entry btn-page-back" title="Seite zurück"><span class="oi" data-glyph="arrow-thick-left" aria-hidden="true"></span></span>
      <span class="btn-toolbar-entry btn-page-forward" title="Seite vor"><span class="oi" data-glyph="arrow-thick-right" aria-hidden="true"></span></span>
    </div>
  </div>
</div>
