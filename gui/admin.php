<?php
/** @file admin.php
 * The administrator page.
 */


 if ($_SESSION["admin"]):

$tlist = $sh->getTagsetList();
$ulist = array();

 ?>

<div id="adminDiv" class="content" style="display: none;">
  <div class="panel">
    <div class="btn-toolbar">
      <span class="btn-toolbar-entry" id="adminUsersRefresh" title="Aktualisieren"><span class="oi" data-glyph="reload" aria-hidden="true"></span></span>
      <span class="btn-toolbar-entry" id="adminViewCollapseAll" title="Alle Kategorien zuklappen"><span class="oi" data-glyph="collapse-up" aria-hidden="true"></span></span>
      <span class="btn-toolbar-entry" id="adminViewExpandAll" title="Alle Kategorien aufklappen"><span class="oi" data-glyph="collapse-down" aria-hidden="true"></span></span>
      <span class="btn-toolbar-entry" id="adminCreateUser"><span class="oi oi-green" data-glyph="plus" aria-hidden="true"></span> Neuer Benutzer</span>
      <span class="btn-toolbar-entry" id="adminCreateProject"><span class="oi oi-green" data-glyph="plus" aria-hidden="true"></span> Neues Projekt</span>
      <span class="btn-toolbar-entry" id="adminCreateAnnotator"><span class="oi oi-green" data-glyph="plus" aria-hidden="true"></span> Neuer Tagger</span>
      <span class="btn-toolbar-entry" id="adminCreateNotice"><span class="oi oi-green" data-glyph="plus" aria-hidden="true"></span> Neue Benachrichtigung</span>
      <span class="btn-toolbar-entry" id="adminViewTagset"><span class="oi" aria-hidden="true"></span>Tagset-Browser</span>
      <span class="btn-toolbar-entry" id="adminImportTagset"><span class="oi" data-glyph="data-transfer-upload" aria-hidden="true"></span> Tagset importieren</span>
    </div>

    <!-- USER MANAGEMENT -->
    <div class="clappable clapp-modern" id="users">
      <h4 class="clapp">Benutzerverwaltung</h4>
      <div>
        <table id="editUsers" class="table-modern">
          <thead>
            <tr>
              <th>Benutzername</th>
              <th>Admin?</th>
              <th>Letzte Aktivität</th>
              <th>Geöffnete Datei</th>
              <th class="table-th-nosort"></th>
              <th class="table-th-nosort"></th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>
    </div>

    <!-- PROJECT MANAGEMENT -->
    <div class="clappable clapp-modern starthidden" id="projectMngr">
      <h4 class="clapp">Projektverwaltung</h4>
      <div>
        <table id="editProjects" class="table-modern">
          <thead>
            <tr>
              <th>Projektname</th>
              <th>Zugeordnete Benutzer</th>
              <th>Zugeordnete Tagset-Typen</th>
              <th>Edit?</th>
              <th>Import?</th>
              <th class="table-th-nosort"></th>
              <th class="table-th-nosort"></th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>

        <p><strong>Hinweis:</strong> Administratoren können <i>immer</i> alle Projektgruppen sehen.</p>
      </div>
    </div>

    <!-- AUTOMATIC ANNOTATORS -->
    <div class="clappable clapp-modern starthidden" id="automaticAnnotators">
      <h4 class="clapp">Automatische Annotation</h4>
      <div>
        <table id="editAutomaticAnnotators" class="table-modern">
          <thead>
            <tr>
              <th>ID</th>
              <th>Name</th>
              <th>Klasse</th>
              <th>Train?</th>
              <th>Zugeordnete Tagset-Typen</th>
              <th class="table-th-nosort"></th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>
    </div>

    <!-- SERVER NOTICES -->
    <div class="clappable clapp-modern starthidden" id="serverNotices">
      <h4 class="clapp">Server-Benachrichtigungen</h4>
      <div>
        <table id="editNotices" class="table-modern">
          <thead>
            <tr>
              <th>ID</th>
              <th>Nachricht</th>
              <th>Typ</th>
              <th>Expires</th>
              <th class="table-th-nosort"></th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>
    </div>

    <!-- TAGSET EDITOR
    <div class="clappable clapp-modern" id="tagsets">
      <h4 class="clapp">Tagset-Editor</h4>
      <div>
        <p>Tagsets können zur Zeit nicht über die Web-Oberfläche editiert werden.</p>
      </div>
    </div>
    -->
  </div>

  <!-- TEMPLATES -->
  <div class="templateHolder" style="display: none;">
    <div id="templateCreateNotice">
      <form>
      <p>
        <label for="noticetype" class="ra">Typ: </label>
        <select name="noticetype" size="1">
          <option value="alert">Alert</option>
          <option value="info">Info</option>
        </select>
      </p>
      <p>
        <label for="noticeexpires" class="ra">Expires: </label>
        <input type="text" name="noticeexpires" value="" data-required="" />
      </p>
      <p>
        <label for="noticetext" class="ra vt">Nachricht: </label>
        <textarea cols="30" rows="3" name="noticetext" value="" data-required=""></textarea>
      </p>
      </form>
    </div>

    <div id="templateCreateUser">
      <form>
      <p>
        <label for="newuser" class="ra">Benutzername: </label>
        <input type="text" name="newuser" value="" data-required="" />
      </p>
      <p>
        <label for="newpw" class="ra">Passwort: </label>
        <input type="password" name="newpw" value="" data-required="" />
      </p>
      <p>
        <label for="newpw2" class="ra">Passwort wiederholen: </label>
        <input type="password" name="newpw2" value="" data-required="" />
      </p>
      </form>
    </div>

    <div id="templateChangePassword">
      <form>
      <p>
        <label for="newchpw" class="ra">Neues Passwort: </label>
        <input type="password" name="newchpw" value="" data-required="" />
      </p>
      <p>
        <label for="newchpw2" class="ra">Neues Passwort wiederholen: </label>
        <input type="password" name="newchpw2" value="" data-required="" />
      </p>
      </form>
    </div>

    <div id="annotatorEditForm" class="annotatorEditForm">
      <p>
        <label for="annotatorDisplayName" class="ra">Name: </label>
        <input type="text" name="annotatorDisplayName" class="mform" />
      </p>
      <p>
        <label for="annotatorClassName" class="ra">Klasse: </label>
        <input type="text" name="annotatorClassName" class="mform" />
      </p>
      <p>
        <label for="annotatorOptions">Optionen: </label>
        <ul class="flexrow-container"></ul>
      </p>
      <p>
        <input type="checkbox" id="annotatorIsTrainable" name="annotatorIsTrainable" value="trainable" class="mform" /><label for="annotatorIsTrainable">Projekt-spezifisch trainierbar</label>
      </p>
      <p><label>Zugeordnete Tagsets:</label></p>
      <div class="tagsetSelectPlaceholder"></div>
      <p>
        Nur Texte, denen auch mindestens die hier ausgewählten Tagsets zugeordnet sind, können mit diesem Tagger annotiert werden.
      </p>
    </div>

    <div id="projectEditForm" class="projectEditForm">
      <p>
        <label for="projectCmdEditToken">Befehl zum Editieren von Tokens: </label><br />
        <input type="text" name="projectCmdEditToken" placeholder="(nicht definiert)" size="60" class="mform" />
      </p>
      <p>
        <label for="projectCmdImport">Befehl zum Importieren von Texten: </label><br />
        <input type="text" name="projectCmdImport" placeholder="(nicht definiert)" size="60" class="mform" />
      </p>
      <p><label>Zugeordnete Benutzer:</label></p>
      <div class="userSelectPlaceholder"></div>
      <p>
        Nur Administratoren oder dem Projekt zugeordnete Benutzer können dieses Projekt und die darin enthaltenen Dateien sehen und bearbeiten.
      </p>
      <p><label>Zugeordnete Tagsets:</label></p>
      <div class="tagsetSelectPlaceholder"></div>
      <p>
        Neue Texte, die in dieses Projekt importiert werden, werden standardmäßig mit den hier zugeordneten Tagsets verknüpft.  Änderungen hier haben <strong>keine</strong> Auswirkungen auf bereits in der Datenbank vorhandene Texte!
      </p>
    </div>

    <div id="projectCreateForm">
      <form>
      <p>
        <label for="project_name" class="ra">Projektname:</label>
        <input type="text" name="project_name" value="" />
      </p>
      </form>
    </div>

    <div id="annotatorCreateForm">
      <form>
      <p>
        <label for="annotator_name" class="ra">Name:</label>
        <input type="text" name="annotator_name" value="" />
      </p>
      </form>
    </div>

    <div id="tagsetImportForm">
      <form action="request.php" id="newTagsetImportForm" method="post" accept-charset="utf-8" enctype="multipart/form-data">
      <p>
        <label for="tagset_name">Name des Tagsets:</label>
        <input type="text" name="tagset_name" value="" size="40" maxlength="255" data-required />
      </p>
      <p class="error_text">Bitte wählen Sie eine Datei zum Importieren aus!</p>
      <p>
        <label for="txtFile">Tagset-Datei: </label>
        <input type="file" name="txtFile" data-required />
      </p>
      <p style="max-width:32em;">
        Hinweis: Als Tagset-Datei wird eine Textdatei erwartet, die aus einem Tag pro Zeile besteht.  Punkte werden als Trennsymbole zwischen POS- und Morph-Attributen interpretiert.  Tags, die als "korrekturbedürftig" markiert werden sollen, muss jeweils ein Caret (^) vorangestellt werden.
      </p>
      <p><input type="hidden" name="action" value="importTagsetTxt" /></p>
      <p style="text-align:right;">
        <input type="submit" value="Tagset importieren &rarr;" />
      </p>
      </form>
    </div>

    <div id="adminImportPopup">
      <p></p>
      <p><textarea cols="80" rows="10" readonly="readonly"></textarea></p>
    </div>

    <div id="adminTagsetBrowser">
      <p>
        <select id="aTBtagset" class="mform">
	<?php foreach($tlist as $set):?>
          <option value="<?php echo $set['shortname'];?>"><?php echo $set['longname'];?></option>
        <?php endforeach;?>
        </select>
        <button id="aTBview" type="button" class="mform">Anzeigen</button>
      </p>
      <p><textarea id="aTBtextarea" cols="80" rows="10" readonly="readonly"></textarea></p>
    </div>

    <ul>
      <li id="annotatorOptEntryTemplate"><input type="text" name="annotatorOptKey[]" class="mform annotatorOptKey" /><input type="text" name="annotatorOptValue[]" class="mform annotatorOptValue" /></li>
    </ul>
 
    <table>
      <tr id="templateUserInfoRow" class="adminUserInfoRow">
        <td class="adminUserNameCell"></td>
        <td class="centered adminUserAdminStatusTD" title="Kein Admin"><span class="oi oi-shadow oi-green adminUserAdminStatus" data-glyph="check" aria-hidden="true"></span>
        </td>
        <td class="adminUserLastactiveCell"></td>
        <td class="adminUserActivityCell"></td>
        <td>
          <a class="adminUserPasswordButton">Passwort ändern...</a>
        </td>
        <td class="adminUserDelete"><a class="deletion-link"><span class="oi oi-shadow" data-glyph="delete" title="Benutzer löschen" aria-hidden="true"></span></a></td>
      </tr>
    </table>

    <table>
      <tr id="templateProjectInfoRow" class="adminProjectInfoRow">
        <td class="adminProjectNameCell"></td>
        <td class="adminProjectUsersCell"></td>
        <td class="adminProjectTagsetsCell"></td>
        <td class="centered adminProjectCmdEdittoken" title="Edit-Skript zugeordnet"><span class="oi oi-shadow oi-green" data-glyph="check" aria-hidden="true"></span></td>
        <td class="centered adminProjectCmdImport" title="Import-Skript zugeordnet"><span class="oi oi-shadow oi-green" data-glyph="check" aria-hidden="true"></span></td>
        <td>
          <a class="adminProjectEditButton"><span class="oi oi-shadow" data-glyph="cog" aria-hidden="true"></span> Projekt verwalten...</a>
        </td>
        <td><a class="adminProjectDelete deletion-link"><span class="oi oi-shadow" data-glyph="delete" title="Projekt löschen" aria-hidden="true"></span></a></td>
      </tr>
    </table>

    <table>
      <tr id="templateNoticeInfoRow" class="adminNoticeInfoRow">
        <td class="adminNoticeIDCell"></td>
        <td class="adminNoticeTextCell"></td>
        <td class="adminNoticeTypeCell"></td>
        <td class="adminNoticeExpiresCell"></td>
        <td class="adminNoticeDelete"><a class="deletion-link"><span class="oi oi-shadow" data-glyph="delete" title="Benachrichtigung löschen" aria-hidden="true"></span></a></td>
      </tr>
      <tr id="templateAnnotatorInfoRow" class="adminAnnotatorInfoRow">
        <td class="adminAnnotatorIDCell"></td>
        <td class="adminAnnotatorNameCell"></td>
        <td class="adminAnnotatorClassCell"></td>
        <td class="centered adminAnnotatorTrainableCell"><span class="oi oi-shadow oi-green adminAnnotatorTrainableStatus" data-glyph="check" aria-hidden="true"></span>
        </td>
        <td class="adminAnnotatorTagsetCell"></td>
        <td class="adminAnnotatorConfig">
          <a class="adminAnnotatorEditButton"><span class="oi oi-shadow" data-glyph="cog" aria-hidden="true"></span> Optionen...</a>
          <a class="deletion-link"><span class="oi oi-shadow" data-glyph="delete" title="Tagger löschen" aria-hidden="true"></span></a></td>
      </tr>
    </table>
  </div>
</div>

<?php endif; ?>
