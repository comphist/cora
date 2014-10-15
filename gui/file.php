<?php
/** @file file.php
 * The document selection page.
 */
?>

<div id="fileDiv" class="content" style="display: none;">
  <div class="panel">
    <!--
    <div>
      <p>
         <button class="mform" id="importNewTransLink" disabled="disabled">Neues Dokument aus Transkriptionsdatei importieren...</button>
         <button class="mform" id="importNewXMLLink" disabled="disabled">Neues Dokument aus XML-Datei importieren...</button>
      </p>
    </div>
    -->
    <div class="btn-toolbar">
      <span class="btn-toolbar-entry" id="fileViewRefresh" title="Aktualisieren"><span class="oi" data-glyph="reload" aria-hidden="true"></span></span>
      <span class="btn-toolbar-entry" id="fileViewCollapseAll" title="Alle Projektgruppen zuklappen"><span class="oi" data-glyph="collapse-up" aria-hidden="true"></span></span>
      <span class="btn-toolbar-entry" id="fileViewExpandAll" title="Alle Projektgruppen aufklappen"><span class="oi" data-glyph="collapse-down" aria-hidden="true"></span></span>
      <span class="btn-toolbar-entry start-disabled" id="importNewTransLink"><span class="oi" data-glyph="data-transfer-upload" aria-hidden="true"></span> Import aus Textdatei</span>
      <span class="btn-toolbar-entry start-disabled" id="importNewXMLLink"><span class="oi" data-glyph="data-transfer-upload" aria-hidden="true"></span> Import aus XML-Datei</span>
    </div>

    <div id="files"></div>
  </div>

  <!-- templates -->
  <div class="templateHolder">
    <div id="transImportSpinner">
      <div id="transImportStatusContainer">
        <table>
          <tr id="tIS_upload"><td class="proc proc-running"></td><td>Datei übermitteln</td></tr>
          <tr id="tIS_check"><td class="proc" /></td><td>Transkription prüfen</td></tr>
          <tr id="tIS_convert"><td class="proc" /></td><td>Transkription analysieren</td></tr>
          <tr id="tIS_tag"><td class="proc" /></td><td>Automatisch vorannotieren</td></tr>
          <tr id="tIS_import"><td class="proc" /></td><td>Importieren abschließen</td></tr>
        </table>
        <div id="tIS_progress"></div>
      </div>
    </div>
   
    <div class="filegroup clappable" id="fileGroup">
      <h4 class="clapp">
        <span class="oi clapp-status-open" data-glyph="caret-bottom" title="Zuklappen" aria-hidden="true"></span><span class="oi clapp-status-hidden" data-glyph="caret-right" title="Aufklappen" aria-hidden="true"></span> <span class="projectname">Projektname</span></h4>
        <div>
          <table class="fileList">
          <thead>
            <tr class="fileTableHeadLine">
              <th class="ftr-filename">Dateiname</th>
              <th colspan="2" class="ftr-changed">Letzte Änderung am/von</th>
              <th colspan="2" class="ftr-created">Erstellt am/von</th>
              <th class="ftr-id start-hidden admin-only">ID</th>
              <th class="ftr-options"></th>
            </tr>
          </thead>
          <tbody>
          </tbody>
        </table>
      </div>
    </div>

    <table>
      <tr id="fileTableRow">
        <td class="ftr-filename filename"><a class="filenameOpenLink"></a></td>
        <td class="ftr-changed-at"></td>
        <td class="ftr-changed-by"></td>
        <td class="ftr-created-at"></td>
        <td class="ftr-created-by"></td>
        <td class="ftr-id start-hidden admin-only"></td>
        <td class="ftr-options">
          <a class="deleteFileLink deletion-link start-hidden"><span class="oi oi-shadow oi-adjust" data-glyph="delete" title="Datei löschen" aria-hidden="true"></span></a>
          <a class="exportFileLink"><span class="oi oi-shadow oi-adjust" data-glyph="data-transfer-download" title="Datei exportieren" aria-hidden="true"></span> Exportieren...</a>
          <a class="editTagsetAssocLink start-hidden admin-only"><span class="oi oi-shadow oi-adjust" data-glyph="link-intact" title="Tagset-Verknüpfungen bearbeiten" aria-hidden="true"></span> Tagsets...</a>
          <a class="closeFileLink start-hidden"><span class="oi oi-shadow" data-glyph="x" title="Datei schließen" aria-hidden="true"></span> Schließen</a>
        </td>
      </tr>
    </table>

    <div id="fileExportPopup">
      <p>In welchem Format möchten Sie die Datei exportieren?</p>
      <p>
        <label for="format">Exportformat: </label>
        <select id="fileExportFormat" name="format" size="1">
          <option value="<?php echo ExportType::Tagging ?>" selected="selected">Spaltenformat (POS)</option>
          <?php if($_SESSION["admin"]): ?>
            <option value="<?php echo ExportType::Normalization ?>">Spaltenformat (Normalisierung)</option>
          <?php endif; ?>
          <option value="<?php echo ExportType::CoraXML ?>">CorA-XML</option>
          <?php if($_SESSION["admin"]): ?>
            <option value="<?php echo ExportType::Transcription ?>" disabled="disabled">Transkriptionsformat (ohne Annotationen)</option>
          <?php endif; ?>
        </select>
      </p>
      <p></p>
    </div>

    <div id="fileImportXMLForm" class="limitedWidth">
      <form action="request.php" id="newFileImportForm" method="post" accept-charset="utf-8" enctype="multipart/form-data">
      <p class="error_text error_text_import">Bitte wählen Sie eine Datei zum Importieren aus!</p>
      <p>
        <label for="xmlFile" class="ra">Datei: </label>
        <input type="file" name="xmlFile" data-required="" />
      </p>
      <p>
        <label for="project" class="ra">Projekt: </label>
        <select name="project" size="1"></select>
      </p>
      <p>Die folgenden Felder müssen nicht ausgefüllt werden, falls die entsprechenden Informationen bereits in der XML-Datei enthalten sind.</p>
      <p>
        <label for="xmlName" class="ra">Name: </label>
        <input type="text" name="xmlName" placeholder="(Dokumentname)" size="30" />
      </p>
      <p>
        <label for="sigle" class="ra">Sigle: </label>
        <input type="text" name="sigle" placeholder="(Sigle &ndash; optional)" size="30" />
      </p>
      <div class="fileImportTagsetLinks" <?php if(!$_SESSION['admin']) {echo 'style="display:none;"';} ?>>
        <p style="padding-top: 15px;">Tagset-Verknüpfungen:</p>
        <div class="import_LinkTagsets_MS"></div>
      </div>
      <p><input type="hidden" name="action" value="importXMLFile" /></p>
      <p style="text-align:right;">
        <input type="submit" value="Importieren &rarr;" />
      </p>
      </form>
    </div>

    <div id="fileImportTransForm" class="limitedWidth">
      <form action="request.php" id="newFileImportTransForm" method="post" accept-charset="utf-8" enctype="multipart/form-data">
      <p class="error_text error_text_import">Bitte wählen Sie eine Datei zum Importieren aus!</p>
      <p>
        <label for="transFile" class="ra">Datei: </label>
        <input type="file" name="transFile" data-required="" />
      </p>
      <p>
        <label for="fileEnc" class="ra">Encoding: </label>
        <select name="fileEnc" size="1">
          <option value="utf-8">UTF-8 (Unicode)</option>
          <option value="iso-8859-1">ISO-8859-1 (Latin 1)</option>
          <option value="IBM850">MS-DOS (IBM-850)</option>
        </select>
      </p>
      <p>
        <label for="project" class="ra">Projekt: </label>
        <select name="project" size="1"></select>
      </p>
      <p class="error_text error_text_cmdimport">Für das gewählte Projekt ist kein Importskript festgelegt.  Ohne Importskript können neue Dokumente nur als CorA-XML-Datei importiert werden.</p>
      <p>
        <label for="transName" class="ra">Name: </label>
        <input type="text" name="transName" placeholder="(Dokumentname)" size="30" data-required="" />
      </p>
      <p>
        <label for="sigle" class="ra">Sigle: </label>
        <input type="text" name="sigle" placeholder="(Sigle &ndash; optional)" size="30" />
      </p>
      <div class="fileImportTagsetLinks" <?php if(!$_SESSION['admin']) {echo 'style="display:none;"';} ?>>
        <p style="padding-top: 15px;">Tagset-Verknüpfungen:</p>
        <div class="import_LinkTagsets_MS"></div>
      </div>
      <p><input type="hidden" name="action" value="importTransFile" /></p>
      <p style="text-align:right;">
        <input type="submit" value="Importieren &rarr;" />
      </p>
      </form>
    </div>

    <div id="tagsetAssociationTable" class="limitedWidth">
      <table class="tagset-list">
        <thead>
        <tr><th></th><th class="numeric">ID</th><th>Name</th><th>Class</th><th>Set</th></tr>
        </thead>
        <tbody>
          <?php foreach($tagsets_all as $set): ?>
          <tr>
            <td class="check"><input type="checkbox" name="linktagsets[]" value="<?php echo $set['shortname']; ?>" /></td>
            <td class="numeric"><?php echo $set['shortname']; ?></td>
            <td><?php echo $set['longname']; ?></td>
            <td><?php echo $set['class']; ?></td>
            <td><?php echo $set['set_type']; ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <div id="noProjectGroups">
      <h4>Keine Projektgruppen verfügbar!</h4>
      <p>Sie gehören zur Zeit keiner Projektgruppe an.  Wenden Sie sich an einen Administrator, um zu einer Projektgruppe hinzuzufügt zu werden.</p>
    </div>

    <table>
      <tr id="noProjectFiles">
        <td colspan="7">Dieses Projekt enthält keine Dateien.</td>
      </tr>
    </table>
  </div>
</div>