<?php
/** @file admin.php
 * The administrator page.
 */


 if ($_SESSION["admin"]):

$tlist = $sh->getTagsetList();
$ulist = array();

 ?>

<div id="adminDiv" class="content" style="display: none;">

<!-- <h2>Admin</h2> -->

<!-- USER MANAGEMENT -->
<div class="panel clappable" id="users">
   <h3 class="clapp">Benutzerverwaltung</h3>

   <div>
    <img id="adminUsersRefresh" src="gui/images/View-refresh.svg" width="20px" height="20px"/>
   <table id="editUsers">
     <tr>
       <th></th>
       <th>Benutzername</th>
       <th>Admin?</th>
       <th>Letzte Aktivität</th>
       <th>Geöffnete Datei</th>
       <th></th>
     </tr>
   </table>

   <p>
   <button id="createUser" type="button" class="mform">
       <img src="gui/images/proxal/plus.ico" />
       Neuen Benutzer hinzufügen...
   </button>
   </p>
   
   </div>
</div>


<!-- PROJECT MANAGEMENT -->
<div class="panel clappable" id="projectMngr">
   <h3 class="clapp">Projektverwaltung</h3>

   <div>
   <table id="editProjects">
     <thead>
     <tr>
       <th></th>
       <th>Projektname</th>
       <th>Zugeordnete Benutzer</th>
       <th>Zugeordnete Tagset-Typen</th>
       <th>Edit-Skript?</th>
       <th>Import-Skript?</th>
       <th></th>
     </tr>
     </thead>
     <tbody>
     </tbody>
   </table>

   <p><strong>Hinweis:</strong> Administratoren können <i>immer</i> alle Projektgruppen sehen.</p>

   <p>
   <button id="createProject" type="button" class="mform">
       <img src="gui/images/proxal/plus.ico" />
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
       <img src="gui/images/proxal/plus.ico" />
       Neues (POS-)Tagset importieren...
   </button>
   </p>

   </div>
</div>

  <div class="templateHolder" style="display: none;">
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
        <td class="adminUserDelete"><img src="gui/images/proxal/delete.ico" /></td>
        <td class="adminUserNameCell"></td>
        <td class="centered adminUserAdminStatus"><img src="gui/images/proxal/check.ico" class="adminUserAdminStatus"/>
        </td>
        <td class="adminUserLastactiveCell"></td>
        <td class="adminUserActivityCell"></td>
        <td>
          <button class="adminUserPasswordButton" type="button">Passwort ändern...</button>
        </td>
      </tr>
    </table>

    <table>
      <tr id="templateProjectInfoRow" class="adminProjectInfoRow">
        <td><a class="adminProjectDelete"><img src="gui/images/proxal/delete.ico" /></a></td>
        <td class="adminProjectNameCell"></td>
        <td class="adminProjectUsersCell"></td>
        <td class="adminProjectTagsetsCell"></td>
        <td class="centered adminProjectCmdEdittoken"><img src="gui/images/proxal/check.ico"/></td>
        <td class="centered adminProjectCmdImport"><img src="gui/images/proxal/check.ico" /></td>
        <td>
          <button class="adminProjectEditButton" type="button">Projekt verwalten...</button>
        </td>
      </tr>
    </table>
  </div>
</div>

<?php endif; ?>
