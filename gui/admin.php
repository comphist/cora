<?php
/** @file admin.php
 * The administrator page.
 */


 if ($_SESSION["admin"]):

$tlist = $sh->getTagsetList();
$ulist = $sh->getUserList();
// $projects = $sh->getProjectList();   defined in gui.php
// $project_users = $sh->getProjectUsers();   defined in gui.php

 ?>

<div id="adminDiv" class="content" style="display: none;">

<!-- <h2>Admin</h2> -->

<!-- USER MANAGEMENT -->
<div class="panel clappable" id="users">
   <h3 class="clapp">Benutzerverwaltung</h3>

   <div>
   <table id="editUsers">
     <tr>
       <th></th>
       <th>Benutzername</th>
       <th>Admin?</th>
       <th>Letzte Aktivität</th>
<!--       <th>Norm.-Spalte?</th> -->
       <th></th>
     </tr>
     <?php foreach ($ulist as $user): 
             $un = $user['name'];
             $la = $user['lastactive'];
             $admin = $user['admin'] == '1';
             $norm = TRUE; //$user['normvisible'] == 'y';
     ?>
     <tr id="User_<?php echo $un; ?>" class="adminUserInfoRow">
       <td class="adminUserDelete"><img src="gui/images/proxal/delete.ico" /></td>
       <td class="adminUserNameCell"><?php echo $un; ?></td>
       <td class="centered adminUserAdminStatus"><img src="gui/images/proxal/check.ico" class="adminUserAdminStatus"
           <?php if (!$admin): ?>style="display: none;"<?php endif; ?>/>
       </td>
       <td class="adminUserLastactiveCell"><?php echo $la; ?></td>
<!--       <td class="centered adminUserNormStatus"><img src="gui/images/proxal/check.ico" class="adminUserNormStatus"
           <?php if (!$norm): ?>style="display: none;"<?php endif; ?>/>
       </td>
-->
       <td>
           <button class="adminUserPasswordButton" type="button">Passwort ändern...</button>
       </td>
     </tr>
     <?php endforeach; ?>
   </table>

<!--   <p><strong>Hinweis:</strong> Administratoren können <i>immer</i> die Spalte &quot;Normalisierung&quot; sehen.</p> -->
   <p><strong>Hinweis:</strong> Sichtbarkeit der Spalte &quot;Normalisierung&quot; wird derzeit über einen Hack in <i>session.php</i> gesteuert...</p>

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
     <tr>
       <th></th>
       <th>Projektname</th>
       <th>Zugeordnete Benutzer</th>
       <th></th>
     </tr>
     <?php foreach ($projects as $project): 
	     $pn = $project['name'];
             $pid = $project['id'];
     ?>
     <tr id="project_<?php echo $pid; ?>" class="adminProjectInfoRow">
       <td><a id="projectdelete_<?php echo $pid; ?>" class="adminProjectDelete"><img src="gui/images/proxal/delete.ico" /></a></td>
       <td class="adminProjectNameCell"><?php echo $pn; ?></td>
       <td class="adminProjectUsersCell">
       <?php 
	       echo implode(',', $project_users[$pid]);
       ?>
       </td>
       <td>
           <button id="projectbutton_<?php echo $pid; ?>" class="adminProjectUsersButton" type="button">Benutzergruppe bearbeiten...</button>
       </td>
     </tr>
     <?php endforeach; ?>
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
      <div id="ceraCreateUser" class="ceraCreateUser ceraInput">
	<table>
	  <tr>
            <th>Benutzername:</th>
	    <td><input type="text" name="newuser[un]" value="" /></td>
	  </tr>
	  <tr>
            <th>Passwort:</th>
	    <td><input type="password" name="newuser[pw]" value="" /></td>
	  </tr>
	  <tr>
	    <th>Passwort wiederholen:</th>
	    <td><input type="password" name="newuser[pw2]" value="" /></td>
	  </tr>
	</table>
	  <p class="button">
	    <button name="submitCreateUser" value="save" type="submit">
	      Benutzer hinzufügen
	    </button>
	  </p>
      </div>
      <div id="ceraChangePassword" class="ceraChangePassword ceraInput">
	<table>
	  <tr style="display: none;">
	    <th>Benutzername:</th>
	    <td><input type="text" name="changepw[un]" value="" /></td>
	  </tr>
	  <tr>
	    <th>Neues Passwort:</th>
	    <td><input type="password" name="changepw[pw]" value="" /></td>
	  </tr>
	  <tr>
	    <th>Neues Passwort wiederholen:</th>
	    <td><input type="password" name="changepw[pw2]" value="" /></td>
	  </tr>
	</table>
	  <p class="button">
	    <button name="submitChangePassword" value="save" type="submit">
	      Passwort ändern
	    </button>
	  </p>
      </div>

     <div id="projectUserChangeForm">
         <form action="request.php"  method="post">
	 <div class="userChangeEditTable">
             <?php foreach ($ulist as $user):
             $un = $user['name'];
?>
             <span><input type="checkbox" name="allowedUsers[]" value="<?php echo $un; ?>" /> <label for="allowedUsers[]"><?php echo $un; ?></label></span>
	     <?php endforeach; ?>
	 </div>							    
	 <p style="text-align:right;">
	   <input type="hidden" name="project_id" value="" />
	   <input type="hidden" name="action" value="changeProjectUsers" />
           <input type="submit" value="Änderungen bestätigen" />
         </p>
         </form>
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
    <p><select id="aTBtagset" class="mform">
			<?php foreach($tlist as $set):?>
			<option value="<?php echo $set['shortname'];?>"><?php echo $set['longname'];?></option>
			<?php endforeach;?>
       </select>
       <button id="aTBview" type="button" class="mform">Anzeigen</button>
    </p>
    <p><textarea id="aTBtextarea" cols="80" rows="10" readonly="readonly"></textarea></p>
  </div>


</div>


</div>

<?php endif; ?>
