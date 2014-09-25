<?php
/** @file file.php
 * The document selection page.
 */
?>

<div id="fileDiv" class="content" style="display: none;">
  <div class="panel" id="fileImport">
    <h3>Datei importieren</h3>
    <p><button class="mform" id="importNewTransLink" disabled="disabled">Neues Dokument aus Transkriptionsdatei importieren...</button>
       <button class="mform" id="importNewXMLLink" disabled="disabled">Neues Dokument aus XML-Datei importieren...</button>
    </p>
  </div>

  <div class="panel">
    <div class="fileViewRefresh">	
      <h3>Datei öffnen</h3>
      <img src="gui/images/View-refresh.svg" width="20px" height="20px"/>
      <p><a class="collapseAll" href="#">[Alle Projektgruppen zuklappen]</a></p>
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
   
    <div class="panel clappable" id="fileGroup">
      <h4 class="projectname clapp">Projektname</h4>
        <div>
          <table class="fileList">
          <thead>
            <tr class="fileTableHeadLine">
              <th></th>
              <th>Dateiname</th>
              <th colspan="2">zuletzt bearbeitet am/von</th>
              <th colspan="2">erstellt am/von</th>
              <th></th>
              <th></th>
              <th></th>
            </tr>
          </thead>
          <tbody>
          </tbody>
        </table>
      </div>
    </div>

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
        <td></td>
        <td>Dieses Projekt enthält keine Dateien.</td>
        <td></td><td></td><td></td><td></td><td></td><td></td><td></td>
      </tr>
    </table>
  </div>
</div>