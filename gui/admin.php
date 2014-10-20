<?php
/** @file admin.php
 * The administrator page.
 */


 if ($_SESSION["admin"]):

$tlist = $sh->getTagsetList();
$ulist = array();

 ?>

<div id="adminDiv" class="content" style="display: none;">

<!-- SERVER NOTICES -->
<div class="panel clappable" id="serverNotices">
   <h3 class="clapp">Server-Benachrichtigungen</h3>

   <div>
     <div class="btn-toolbar">
       <span class="btn-toolbar-entry" id="createNotice"><span class="oi oi-green" data-glyph="plus" aria-hidden="true"></span> Neue Benachrichtigung...</span>
     </div>

     <table id="editNotices">
       <thead>
         <tr>
           <th>ID</th>
           <th>Nachricht</th>
           <th>Typ</th>
           <th>Expires</th>
           <th></th>
         </tr>
       </thead>
       <tbody></tbody>
     </table>
   </div>
</div>

<!-- USER MANAGEMENT -->
<div class="panel clappable" id="users">
   <h3 class="clapp">Benutzerverwaltung</h3>

   <div>
     <div class="btn-toolbar">
       <span class="btn-toolbar-entry" id="adminUsersRefresh" title="Aktualisieren"><span class="oi" data-glyph="reload" aria-hidden="true"></span></span>
       <span class="btn-toolbar-entry" id="createUser"><span class="oi oi-green" data-glyph="plus" aria-hidden="true"></span> Benutzer hinzufügen...</span>
     </div>

     <table id="editUsers">
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
<div class="panel clappable" id="projectMngr">
   <h3 class="clapp">Projektverwaltung</h3>

   <div>
   <table id="editProjects">
     <thead>
     <tr>
       <th>Projektname</th>
       <th>Zugeordnete Benutzer</th>
       <th>Zugeordnete Tagset-Typen</th>
       <th>Edit-Skript?</th>
       <th>Import-Skript?</th>
       <th class="table-th-nosort"></th>
       <th class="table-th-nosort"></th>
     </tr>
     </thead>
     <tbody>
     </tbody>
   </table>

   <p><strong>Hinweis:</strong> Administratoren können <i>immer</i> alle Projektgruppen sehen.</p>

   <p>
   <button id="createProject" type="button" class="mform">
       <span class="oi oi-adjust oi-green" data-glyph="plus" aria-hidden="true"></span>
       Neues Projekt hinzufügen...
   </button>
   </p>

   </div>
</div>


<!-- TAGSET EDITOR -->
<div class="panel clappable" id="tagsets">
   <h3 class="clapp">Tagset-Editor</h3>

   <div>
     <p>Tagsets können zur Zeit nicht über die Web-Oberfläche editiert werden.</p>

   <p>
   <button id="viewTagset" type="button" class="mform">
       (POS-)Tagsets anzeigen
   </button>
   <button id="importTagset" type="button" class="mform">
       <span class="oi oi-adjust oi-green" data-glyph="plus" aria-hidden="true"></span>
       Neues (POS-)Tagset importieren...
   </button>
   </p>

   </div>
</div>

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
      <p>
        <label for="project_name"><b>Projektname:</b></label>
        <input type="text" name="project_name" value="" />
      </p>
      <p style="text-align:right;">
        <button name="submitCreateProject" value="save" type="submit">
          Projekt erstellen
        </button>
      </p>
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
 
    <table>
      <tr id="templateUserInfoRow" class="adminUserInfoRow">
        <td class="adminUserNameCell"></td>
        <td class="centered adminUserAdminStatusTD" title="Admin"><span class="oi oi-shadow oi-green adminUserAdminStatus" data-glyph="check" aria-hidden="true"></span>
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
    </table>
  </div>
</div>

<?php endif; ?>
