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

<!-- <h2><?php echo $lang["admin_caption"]; ?></h2> -->

<!-- USER MANAGEMENT -->
<div class="panel clappable" id="users">
   <h3 class="clapp"><?php echo $lang["admin_user_management"]; ?></h3>

   <div>
   <table id="editUsers">
     <tr>
       <th></th>
       <th><?php echo $lang["login_un"]; ?></th>
       <th><?php echo $lang["admin_admin"]; ?>?</th>
       <th></th>
     </tr>
     <?php foreach ($ulist as $user): 
             $un = $user['username'];
             $admin = $user['admin'] == 'y';
     ?>
     <tr id="<?php echo $un; ?>" class="adminUserInfoRow">
       <td><a id="<?php echo $un; ?>Del" class="adminUserDelete"><img src="gui/images/proxal/delete.ico" /></a></td>
       <td class="adminUserNameCell"><?php echo $un; ?></td>
       <td class="centered"><img src="gui/images/proxal/check.ico" class="adminUserAdminStatus"
           <?php if (!$admin): ?>style="display: none;"<?php endif; ?>/>
       </td>
       <td>
           <button id="<?php echo $un; ?>Admin" class="adminUserToggleButton" type="button"><?php echo $lang["admin_toggle_admin"]; ?></button>
           <button id="<?php echo $un; ?>Pw" class="adminUserPasswordButton" type="button"><?php echo $lang["admin_change_password"]; ?>...</button>
       </td>
     </tr>
     <?php endforeach; ?>
   </table>

   <button id="createUser" type="button" class="mform">
       <img src="gui/images/proxal/plus.ico" />
       <?php echo $lang["admin_create_user"]; ?>...
   </button>

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
	     $pn = $project['project_name'];
             $pid = $project['project_id'];
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

   <button id="createProject" type="button" class="mform">
       <img src="gui/images/proxal/plus.ico" />
       Neues Projekt hinzufügen...
   </button>

   </div>
</div>


<!-- TAGSET EDITOR -->
<div class="panel clappable" id="tagsets">
   <h3 class="clapp"><?php echo $lang["admin_tagset_caption"]; ?></h3>

   <div>

   <div id="tagsetInfo">
     <form id="chooseTagset">
       <select name="tagset" size="1" onchange="tagsetOnChange();" id="tagset-select">
	 <?php foreach ($tlist as $tagset): 
	       $short = $tagset['shortname'];
	       $long  = $tagset['longname'];  ?>
	 <option value="<?php echo $short; ?>"><?php echo "{$short} ({$long})"; ?></option>
	 <?php endforeach; ?>
       </select>
       <button id="editTagsetButton" type="button"><?php echo $lang["admin_tagset_edit"]; ?>...</button>
       <button id="createTagsetButton" type="button"><?php echo $lang["admin_tagset_create"]; ?>...</button>
     </form>

     <h4 id="tagsetName" style="display: none;">Name</h4>

     <?php foreach ($tlist as $tagset): ?>
     <p id="tagsetLastModified_<?php echo $tagset['shortname']; ?>" style="display: none;">
       <?php echo "{$lang["admin_last_modified_by_user"]} '{$tagset['last_modified_by']}', {$tagset['last_modified']}"; ?>
     </p>
     <?php endforeach; ?>
     <p id="tagsetLastModified"><?php $tagset = $tlist[0]; echo "{$lang["admin_last_modified_by_user"]} '{$tagset['last_modified_by']}', {$tagset['last_modified']}"; ?></p>
   </div>

<div id="editTagset" style="display: none;">
	<div class="panel clappable">
		<h4 class="clapp"><?php echo $lang["admin_tags"]; ?></h4>
		<div>
			<!-- this div is filled by JavaScript code -->
			<table id="editTags">
				<tr>
					<th></th>
					<th><?php echo $lang["admin_tag"]; ?></th>
					<th><?php echo $lang["admin_description"]; ?></th>
					<th><?php echo $lang["admin_possible_attributes"]; ?></th>
				</tr>
				<tr class="newTagRow">
					<td></td>
					<td><a><?php echo $lang["admin_tag_new"]; ?></a></td>
					<td></td>
					<td></td>
				</tr>
			</table>
		</div>
	</div>
	<div class="panel clappable">
		<h4 class="clapp" id="editAttribPanel"><?php echo $lang["admin_attribs"]; ?></h4>
		<div>
			<!-- this div is filled by JavaScript code -->
			<table id="editAttribs">
				<tr>
					<th></th>
					<th><?php echo $lang["admin_attrib"]; ?></th>
					<th><?php echo $lang["admin_description"]; ?></th>
					<th><?php echo $lang["admin_possible_values"]; ?></th>
				</tr>
				<tr class="newAttribRow">
					<td></td>
					<td><a><?php echo $lang["admin_attrib_new"]; ?></a></td>
					<td></td>
					<td></td>
				</tr>
			</table>
		</div>
	</div>
	<div>
		<button id="saveChangesButton" type="button"><img src="gui/images/proxal/file.ico" /><?php echo $lang["dialog_save_changes"]; ?></button>
		<button id="discardChangesButton" type="button" style="min-height:22px;"><?php echo $lang["dialog_discard_changes"]; ?></button>
	</div>

</div>
   </div>

</div>

<div class="templateHolder" style="display: none;">
      <div id="ceraCreateUser" class="ceraCreateUser ceraInput">
	<table>
	  <tr>
	    <th><?php echo "{$lang['login_un']}:"; ?></th>
	    <td><input type="text" name="newuser[un]" value="" /></td>
	  </tr>
	  <tr>
	    <th><?php echo "{$lang['login_pw']}:"; ?></th>
	    <td><input type="password" name="newuser[pw]" value="" /></td>
	  </tr>
	  <tr>
	    <th><?php echo "{$lang['login_pw']} ({$lang['admin_repeat']}):"; ?></th>
	    <td><input type="password" name="newuser[pw2]" value="" /></td>
	  </tr>
	</table>
	  <p class="button">
	    <button name="submitCreateUser" value="save" type="submit">
	      <?php echo $lang['admin_create_user_button']; ?>
	    </button>
	  </p>
      </div>
      <div id="ceraChangePassword" class="ceraChangePassword ceraInput">
	<table>
	  <tr style="display: none;">
	    <th><?php echo "{$lang['login_un']}:"; ?></th>
	    <td><input type="text" name="changepw[un]" value="" /></td>
	  </tr>
	  <tr>
	    <th><?php echo "{$lang['login_pw']}:"; ?></th>
	    <td><input type="password" name="changepw[pw]" value="" /></td>
	  </tr>
	  <tr>
	    <th><?php echo "{$lang['login_pw']} ({$lang['admin_repeat']}):"; ?></th>
	    <td><input type="password" name="changepw[pw2]" value="" /></td>
	  </tr>
	</table>
	  <p class="button">
	    <button name="submitChangePassword" value="save" type="submit">
	      <?php echo $lang['admin_change_password']; ?>
	    </button>
	  </p>
      </div>

     <div id="projectUserChangeForm">
         <form action="request.php"  method="post">
	 <div class="userChangeEditTable">
             <?php foreach ($ulist as $user):
             $un = $user['username'];
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
            <b>Projektname:</b>
	    <input type="text" name="project_name" value="" />
        </p>

	 <p style="text-align:right;">
	    <button name="submitCreateProject" value="save" type="submit">
	      Projekt erstellen
	    </button>
         </p>
     </div>
</div>


</div>

<?php endif; ?>
