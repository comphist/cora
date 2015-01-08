<?php
  /** @file search.php
   * The search results page.
   */
?>

<div id="searchDiv" class="content" style="display: none;">
  <div id="searchPanelDiv" class="panel">
    <div class="srl-criteria" id="searchResultsCriteria">
      <p>
        Es gibt <span class="srl-count">256</span> <span class="srl-agr-count">Ergebnisse</span> für Tokens, die <span class="srl-operator">mindestens eine</span> <span class="srl-agr-operator">dieser</span> Bedingungen erfüllen:
      </p>
      <ul class="srl-condition-list">
        <li>Token enthält "esse"</li>
      </ul>
    </div>

    <div class="btn-toolbar btn-toolbar-notop" id="searchPagePanel">
      <span class="btn-toolbar-entry btn-page-count"><span class="oi" aria-hidden="true"></span>Ergebnis-Seite <input type="text" class="btn-page-to" size="2" /><span class="page-active"></span>/<span class="page-max"></span></span>
      <span class="btn-toolbar-entry btn-page-back" title="Seite zurück"><span class="oi" data-glyph="arrow-thick-left" aria-hidden="true"></span></span>
      <span class="btn-toolbar-entry btn-page-forward" title="Seite vor"><span class="oi" data-glyph="arrow-thick-right" aria-hidden="true"></span></span>
      <span class="btn-toolbar-entry btn-text-search"><span class="oi" data-glyph="magnifying-glass" aria-hidden="true"></span> Suche ändern</span>
    </div>

    <table id="searchTable">
    </table>

    <div class="btn-toolbar btn-toolbar-notop" id="searchPagePanelBottom">
      <span class="btn-toolbar-entry btn-page-count"><span class="oi" aria-hidden="true"></span>Ergebnis-Seite <input type="text" class="btn-page-to" size="2" /><span class="page-active"></span>/<span class="page-max"></span></span>
      <span class="btn-toolbar-entry btn-page-back" title="Seite zurück"><span class="oi" data-glyph="arrow-thick-left" aria-hidden="true"></span></span>
      <span class="btn-toolbar-entry btn-page-forward" title="Seite vor"><span class="oi" data-glyph="arrow-thick-right" aria-hidden="true"></span></span>
    </div>
  </div>
</div>
