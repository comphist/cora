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
      <span class="btn-toolbar-entry" id="adminUsersRefresh" title="<?=$lh("AdminTab.topButton.refresh"); ?>" data-trans-title-id="AdminTab.topButton.refresh">
        <span class="oi" data-glyph="reload" aria-hidden="true"></span>
      </span>
      <span class="btn-toolbar-entry" id="adminViewCollapseAll" title="<?=$lh("AdminTab.topButton.collapseCategories"); ?>" data-trans-title-id="AdminTab.topButton.collapseCategories">
        <span class="oi" data-glyph="collapse-up" aria-hidden="true"></span>
      </span>
      <span class="btn-toolbar-entry" id="adminViewExpandAll" title="<?=$lh("AdminTab.topButton.expandCategories"); ?>" data-trans-title-id="AdminTab.topButton.expandCategories">
        <span class="oi" data-glyph="collapse-down" aria-hidden="true"></span>
      </span>
      <span class="btn-toolbar-entry" id="adminCreateUser"><span class="oi oi-green" data-glyph="plus" aria-hidden="true"></span> <span data-trans-id="AdminTab.topButton.newUser">
      <?=$lh("AdminTab.topButton.newUser"); ?></span>
    </span>
      <span class="btn-toolbar-entry" id="adminCreateProject">
        <span class="oi oi-green" data-glyph="plus" aria-hidden="true"></span> 
        <span data-trans-id="AdminTab.topButton.newProject"><?=$lh("AdminTab.topButton.newProject"); ?>
        </span>
      </span>
      <span class="btn-toolbar-entry" id="adminCreateAnnotator">
        <span class="oi oi-green" data-glyph="plus" aria-hidden="true"></span> <span data-trans-id="AdminTab.topButton.newTagger"><?=$lh("AdminTab.topButton.newTagger"); ?></span></span>
      <span class="btn-toolbar-entry" id="adminCreateNotice">
        <span class="oi oi-green" data-glyph="plus" aria-hidden="true"></span> <span data-trans-id="AdminTab.topButton.newMsg"><?=$lh("AdminTab.topButton.newMsg"); ?></span></span>
      <span class="btn-toolbar-entry" id="adminViewTagset">
        <span class="oi" aria-hidden="true"></span><span data-trans-id="AdminTab.topButton.tagsetBrowser"><?=$lh("AdminTab.topButton.tagsetBrowser"); ?></span></span>
      <span class="btn-toolbar-entry" id="adminImportTagset">
        <span class="oi" data-glyph="data-transfer-upload" aria-hidden="true"></span> <span data-trans-id="AdminTab.topButton.importTagset"><?=$lh("AdminTab.topButton.importTagset"); ?></span></span>
    </div>

    <!-- USER MANAGEMENT -->
    <div class="clappable clapp-modern" id="users">
      <h4 class="clapp"><span data-trans-id="AdminTab.userAdministration.userAdministrationTitle"><?=$lh("AdminTab.userAdministration.userAdministrationTitle"); ?></span></h4>
      <div>
        <table id="editUsers" class="table-modern">
          <thead>
            <tr>
              <th><span data-trans-id="AdminTab.userAdministration.userName"><?=$lh("AdminTab.userAdministration.userName"); ?></span></th>
              <th><span data-trans-id="AdminTab.userAdministration.isAdmin"><?=$lh("AdminTab.userAdministration.isAdmin"); ?></span></th>
              <th><span data-trans-id="AdminTab.userAdministration.lastActive"><?=$lh("AdminTab.userAdministration.lastActive"); ?></span></th>
              <th><span data-trans-id="AdminTab.userAdministration.openFile"><?=$lh("AdminTab.userAdministration.openFile"); ?></span></th>
              <th><span data-trans-id="AdminTab.userAdministration.email"><?=$lh("AdminTab.userAdministration.email"); ?></span></th>
              <th><span data-trans-id="AdminTab.userAdministration.note"><?=$lh("AdminTab.userAdministration.note"); ?></span></th>
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
      <h4 class="clapp"><span data-trans-id="AdminTab.projectAdministration.projectAdministrationTitle"><?=$lh("AdminTab.projectAdministration.projectAdministrationTitle"); ?></span></h4>
      <div>
        <table id="editProjects" class="table-modern">
          <thead>
            <tr>
              <th><span data-trans-id="AdminTab.projectAdministration.projectName"><?=$lh("AdminTab.projectAdministration.projectName"); ?></span></th>
              <th><span data-trans-id="AdminTab.projectAdministration.assignedUsers"><?=$lh("AdminTab.projectAdministration.assignedUsers"); ?></span></th>
              <th><span data-trans-id="AdminTab.autoAnnotation.associatedTagsetTypes"><?=$lh("AdminTab.autoAnnotation.associatedTagsetTypes"); ?></span></th>
              <th><span data-trans-id="AdminTab.projectAdministration.isEdit"><?=$lh("AdminTab.projectAdministration.isEdit"); ?></span></th>
              <th><span data-trans-id="AdminTab.projectAdministration.isImport"><?=$lh("AdminTab.projectAdministration.isImport"); ?></span></th>
              <th class="table-th-nosort"></th>
              <th class="table-th-nosort"></th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>

        <p data-trans-id="AdminTab.projectAdministration.visibleForAdmins">
          <?=$lh("AdminTab.projectAdministration.visibleForAdmins"); ?>
        </p>
      </div>
    </div>

    <!-- AUTOMATIC ANNOTATORS -->
    <div class="clappable clapp-modern starthidden" id="automaticAnnotators">
      <h4 class="clapp"><span data-trans-id="AdminTab.autoAnnotation.autoAnnotationTitle"><?=$lh("AdminTab.autoAnnotation.autoAnnotationTitle"); ?></span></h4>
      <div>
        <table id="editAutomaticAnnotators" class="table-modern">
          <thead>
            <tr>
              <th><span data-trans-id="AdminTab.autoAnnotation.annoId"><?=$lh("AdminTab.autoAnnotation.annoId"); ?></span></th>
              <th><span data-trans-id="AdminTab.autoAnnotation.annoName"><?=$lh("AdminTab.autoAnnotation.annoName"); ?></span></th>
              <th><span data-trans-id="AdminTab.autoAnnotation.annoClass"><?=$lh("AdminTab.autoAnnotation.annoClass"); ?></span></th>
              <th><span data-trans-id="AdminTab.autoAnnotation.isTrained"><?=$lh("AdminTab.autoAnnotation.isTrained"); ?></span></th>
              <th><span data-trans-id="AdminTab.autoAnnotation.associatedTagsetTypes"><?=$lh("AdminTab.autoAnnotation.associatedTagsetTypes"); ?></span></th>
              <th class="table-th-nosort"></th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>
    </div>

    <!-- SERVER NOTICES -->
    <div class="clappable clapp-modern starthidden" id="serverNotices">
      <h4 class="clapp"><span data-trans-id="AdminTab.serverMessages.serverNotifications"><?=$lh("AdminTab.serverMessages.serverNotifications"); ?></span></h4>
      <div>
        <table id="editNotices" class="table-modern">
          <thead>
            <tr>
              <th><span data-trans-id="AdminTab.serverMessages.serverId"><?=$lh("AdminTab.serverMessages.serverId"); ?></span></th>
              <th><span data-trans-id="AdminTab.serverMessages.serverMsg"><?=$lh("AdminTab.serverMessages.serverMsg"); ?></span></th>
              <th><span data-trans-id="AdminTab.serverMessages.serverType"><?=$lh("AdminTab.serverMessages.serverType"); ?></span></th>
              <th><span data-trans-id="AdminTab.serverMessages.expire"><?=$lh("AdminTab.serverMessages.expire"); ?></span></th>
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
      <p>
        <label for="noticetype" class="ra">
          <span data-trans-id="AdminTab.Forms.addServerMsg.type"><?=$lh("AdminTab.Forms.addServerMsg.type"); ?></span> </label>
        <select name="noticetype" size="1">
          <option value="alert">Alert</option>
          <!-- <option value="info">Info</option> -->
        </select>
      </p>
      <p>
        <label for="noticeexpires" class="ra">
          <span data-trans-id="AdminTab.Forms.addServerMsg.expireDate"><?=$lh("AdminTab.Forms.addServerMsg.expireDate"); ?></span> </label>
        <input type="text" name="noticeexpires" value="" data-required="" class="mform" />
      </p>
      <p>
        <label for="noticetext" class="ra vt">
          <span data-trans-id="AdminTab.Forms.addServerMsg.msgText"><?=$lh("AdminTab.Forms.addServerMsg.msgText"); ?></span> </label>
        <textarea cols="30" rows="3" name="noticetext" value="" data-required="" class="mform"></textarea>
      </p>
    </div>

    <div id="templateCreateUser">
      <p>
        <label for="newuser" class="ra">
          <span data-trans-id="AdminTab.Forms.addUser.newUserName"><?=$lh("AdminTab.Forms.addUser.newUserName"); ?></span> </label>
        <input type="text" name="newuser" value="" data-required="" class="mform" />
      </p>
      <p>
        <label for="newpw" class="ra">
          <span data-trans-id="AdminTab.Forms.addUser.password"><?=$lh("AdminTab.Forms.addUser.password"); ?></span> </label>
        <input type="password" name="newpw" value="" data-required="" class="mform" />
      </p>
      <p>
        <label for="newpw2" class="ra">
          <span data-trans-id="AdminTab.Forms.addUser.repeatPassword"><?=$lh("AdminTab.Forms.addUser.repeatPassword"); ?></span> </label>
        <input type="password" name="newpw2" value="" data-required="" class="mform" />
      </p>
    </div>

    <div id="templateChangePassword">
      <p>
        <label for="newchpw" class="ra">
          <span data-trans-id="AdminTab.Forms.changePasswordForm.newPassword"><?=$lh("AdminTab.Forms.changePasswordForm.newPassword"); ?></span> </label>
        <input type="password" name="newchpw" value="" data-required="" class="mform" />
      </p>
      <p>
        <label for="newchpw2" class="ra">
          <span data-trans-id="AdminTab.Forms.changePasswordForm.repeatNewPassword"><?=$lh("AdminTab.Forms.changePasswordForm.repeatNewPassword"); ?></span> </label>
        <input type="password" name="newchpw2" value="" data-required="" class="mform" />
      </p>
    </div>

    <div id="annotatorEditForm" class="annotatorEditForm">
      <p>
        <label for="annotatorDisplayName" class="ra">
          <span data-trans-id="AdminTab.Forms.taggerOptionsForm.taggerName"><?=$lh("AdminTab.Forms.taggerOptionsForm.taggerName"); ?></span> </label>
        <input type="text" name="annotatorDisplayName" class="mform" />
      </p>
      <p>
        <label for="annotatorClassName" class="ra">
          <span data-trans-id="AdminTab.Forms.taggerOptionsForm.taggerClass"><?=$lh("AdminTab.Forms.taggerOptionsForm.taggerClass"); ?></span> </label>
        <input type="text" name="annotatorClassName" class="mform" />
      </p>
      <p>
        <label for="annotatorOptions"><span data-trans-id="AdminTab.Forms.taggerOptionsForm.taggerOptions"><?=$lh("AdminTab.Forms.taggerOptionsForm.taggerOptions"); ?></span> </label>
        <ul class="flexrow-container"></ul>
      </p>
      <p>
        <input type="checkbox" id="annotatorIsTrainable" name="annotatorIsTrainable" value="trainable" class="mform" />
        <label for="annotatorIsTrainable" data-trans-id="AdminTab.Forms.taggerOptionsForm.trainablePerProject"><?=$lh("AdminTab.Forms.taggerOptionsForm.trainablePerProject"); ?></label>
      </p>
      <p><label data-trans-id="AdminTab.Forms.taggerOptionsForm.associatedTagsets"><?=$lh("AdminTab.Forms.taggerOptionsForm.associatedTagsets"); ?></label></p>
      <div class="tagsetSelectPlaceholder"></div>
      <p data-trans-id="AdminTab.Forms.taggerOptionsForm.taggerOptionsInfo">
        <?=$lh("AdminTab.Forms.taggerOptionsForm.taggerOptionsInfo"); ?>
      </p>
    </div>

    <div id="userEditForm" class="userEditForm">
      <p>
        <label for="adminUserEmail"><span data-trans-id="AdminTab.Forms.userOptionsForm.emailAddress"><?=$lh("AdminTab.Forms.userOptionsForm.emailAddress"); ?></span> </label><br />
        <input type="text" name="adminUserEmail" placeholder="<?=$lh("AdminTab.Forms.userOptionsForm.placeholderMail"); ?>" data-trans-placeholder-id="AdminTab.Forms.userOptionsForm.placeholderMail" size="60" class="mform" />
      </p>
      <p>
        <label for="adminUserComment"><span data-trans-id="AdminTab.Forms.userOptionsForm.userNote"><?=$lh("AdminTab.Forms.userOptionsForm.userNote"); ?></span> </label><br />
        <input type="text" name="adminUserComment" placeholder="<?=$lh("AdminTab.Forms.userOptionsForm.placeholderNote"); ?>" data-trans-placeholder-id="AdminTab.Forms.userOptionsForm.placeholderNote" size="60" class="mform" />
      </p>
    </div>

    <div id="projectEditForm" class="projectEditForm">
      <p>
        <label for="projectCmdEditToken"><span data-trans-id="AdminTab.Forms.projectOptionsForm.editTokenCommand"><?=$lh("AdminTab.Forms.projectOptionsForm.editTokenCommand"); ?></span> </label><br />
        <input type="text" name="projectCmdEditToken" placeholder="<?=$lh("AdminTab.Forms.projectOptionsForm.placeholderCommand"); ?>" data-trans-placeholder-id="AdminTab.Forms.projectOptionsForm.placeholderCommand" size="60" class="mform" />
      </p>
      <p>
        <label for="projectCmdImport"><span data-trans-id="AdminTab.Forms.projectOptionsForm.importCommand"><?=$lh("AdminTab.Forms.projectOptionsForm.importCommand"); ?></span> </label><br />
        <input type="text" name="projectCmdImport" placeholder="<?=$lh("AdminTab.Forms.projectOptionsForm.placeholderCommand"); ?>" data-trans-placeholder-id="AdminTab.Forms.projectOptionsForm.placeholderCommand" size="60" class="mform" />
      </p>
      <p><label data-trans-id="AdminTab.Forms.projectOptionsForm.assignedUsers"><?=$lh("AdminTab.Forms.projectOptionsForm.assignedUsers"); ?></label></p>
      <div class="userSelectPlaceholder"></div>
      <p data-trans-id="AdminTab.Forms.projectOptionsForm.assignUserInfo">
        <?=$lh("AdminTab.Forms.projectOptionsForm.assignUserInfo"); ?>
      </p>
      <p><label data-trans-id="AdminTab.Forms.projectOptionsForm.associatedTagsets"><?=$lh("AdminTab.Forms.projectOptionsForm.associatedTagsets"); ?></label></p>
      <div class="tagsetSelectPlaceholder"></div>
      <p data-trans-id="AdminTab.Forms.projectOptionsForm.associatedTagsetsInfo">
        <?=$lh("AdminTab.Forms.projectOptionsForm.associatedTagsetsInfo"); ?>
      </p>
    </div>

    <div id="projectCreateForm">
      <p>
        <label for="project_name" class="ra" data-trans-id="AdminTab.Forms.addProjectForm.projectName">
          <?=$lh("AdminTab.Forms.addProjectForm.projectName"); ?>
        </label>
        <input type="text" name="project_name" value="" class="mform" />
      </p>
    </div>

    <div id="annotatorCreateForm">
      <p>
        <label for="annotator_name" class="ra" data-trans-id="AdminTab.Forms.addTaggerForm.newTaggerName">
          <?=$lh("AdminTab.Forms.addTaggerForm.newTaggerName"); ?>
        </label>
        <input type="text" name="annotator_name" value="" class="mform" />
      </p>
    </div>

    <span id="tagsetImportForm_title" data-trans-id="AdminTab.Forms.importTagsetForm.importTagset"><?=$lh("AdminTab.Forms.importTagsetForm.importTagset"); ?></span>
    <div id="tagsetImportForm">
      <form action="request.php" id="newTagsetImportForm" method="post" accept-charset="utf-8" enctype="multipart/form-data">
      <p>
        <label for="tagset_name" data-trans-id="AdminTab.Forms.importTagsetForm.tagsetName" class="ra">
          <?=$lh("AdminTab.Forms.importTagsetForm.tagsetName"); ?>
        </label>
        <input type="text" name="tagset_name" value="" size="40" maxlength="255" data-required />
      </p>
      <p>
        <label for="tagset_class" data-trans-id="AdminTab.Forms.importTagsetForm.tagsetClass" class="ra">
          <?=$lh("AdminTab.Forms.importTagsetForm.tagsetClass"); ?>
        </label>
        <select size="1" name="tagset_class"></select>
      </p>
      <p>
        <label for="txtFile" data-trans-id="AdminTab.Forms.importTagsetForm.tagsetFile" class="ra">
          <?=$lh("AdminTab.Forms.importTagsetForm.tagsetFile"); ?>
        </label>
        <input type="file" name="txtFile" data-required />
      </p>
      <p style="max-width:32em;" data-trans-id="AdminTab.Forms.importTagsetForm.tagsetInfo">
        <?=$lh("AdminTab.Forms.importTagsetForm.tagsetInfo"); ?>
      </p>
      <p>
        <input type="hidden" name="action" value="importTagsetTxt" />
        <input type="hidden" name="via" value="iframe" />
        <input type="hidden" name="tagset_settype" value="closed" />
      </p>
      <p style="text-align:right;">
        <input type="submit" value="<?=$lh("AdminTab.Forms.importTagsetForm.importBtn"); ?>" data-trans-value-id="AdminTab.Forms.importTagsetForm.importBtn"/>
      </p>
      </form>
    </div>

    <span id="adminTagsetBrowser_title" data-trans-id="AdminTab.Forms.tagsetBrowserForm.tagsetBrowser"><?=$lh("AdminTab.Forms.tagsetBrowserForm.tagsetBrowser"); ?></span>
    <div id="adminTagsetBrowser">
      <p>
        <select id="aTBtagset" class="mform">
	<?php foreach($tlist as $set):?>
          <option value="<?php echo $set['shortname'];?>"><?php echo $set['longname'];?></option>
        <?php endforeach;?>
        </select>
        <button id="aTBview" type="button" class="mform" data-trans-id="AdminTab.Forms.tagsetBrowserForm.preview"><?=$lh("AdminTab.Forms.tagsetBrowserForm.preview"); ?>
        </button>
      </p>
      <p><textarea id="aTBtextarea" cols="80" rows="10" readonly="readonly"></textarea></p>
    </div>

    <ul>
      <li id="annotatorOptEntryTemplate"><input type="text" name="annotatorOptKey[]" class="mform annotatorOptKey" /><input type="text" name="annotatorOptValue[]" class="mform annotatorOptValue" /></li>
    </ul>
 
    <table>
      <tr id="templateUserInfoRow" class="adminUserInfoRow">
        <td class="adminUserNameCell"></td>
        <td class="centered adminUserAdminStatusTD" title="<?=$lh("AdminTab.userAdministration.isNotAdminTitle"); ?>" data-trans-title-id="AdminTab.userAdministration.isNotAdminTitle"><span class="oi oi-shadow oi-green adminUserAdminStatus" data-glyph="check" aria-hidden="true"></span>
        </td>
        <td class="adminUserLastactiveCell"></td>
        <td class="adminUserActivityCell"></td>
        <td class="adminUserEmailCell"></td>
        <td class="adminUserCommentCell"></td>
        <td>
          <a class="adminUserEditButton"><span class="oi oi-shadow" data-glyph="cog" aria-hidden="true"></span> <span data-trans-id="AdminTab.userAdministration.options"><?=$lh("AdminTab.userAdministration.options"); ?></span></a>
        </td>
        <td class="adminUserDelete"><a class="deletion-link"><span class="oi oi-shadow" data-glyph="delete" title="<?=$lh("AdminTab.userAdministration.deleteUser"); ?>" data-trans-title-id="AdminTab.userAdministration.deleteUser" aria-hidden="true"></span></a></td>
      </tr>
    </table>

    <table>
      <tr id="templateProjectInfoRow" class="adminProjectInfoRow">
        <td class="adminProjectNameCell"></td>
        <td class="adminProjectUsersCell"></td>
        <td class="adminProjectTagsetsCell"></td>
        <td class="centered adminProjectCmdEdittoken" title="<?=$lh("AdminTab.projectAdministration.editScript"); ?>" data-trans-title-id="AdminTab.projectAdministration.editScript">
          <span class="oi oi-shadow oi-green" data-glyph="check" aria-hidden="true"></span></td>
        <td class="centered adminProjectCmdImport" title="<?=$lh("AdminTab.projectAdministration.importScript"); ?>" data-trans-title-id="AdminTab.projectAdministration.importScript">
          <span class="oi oi-shadow oi-green" data-glyph="check" aria-hidden="true"></span></td>
        <td>
          <a class="adminProjectEditButton"><span class="oi oi-shadow" data-glyph="cog" aria-hidden="true"></span> <span data-trans-id="AdminTab.projectAdministration.projectOptions"><?=$lh("AdminTab.projectAdministration.projectOptions"); ?></a>
        </td>
        <td><a class="adminProjectDelete deletion-link"><span class="oi oi-shadow" data-glyph="delete" title="<?=$lh("AdminTab.projectAdministration.deleteProject"); ?>" data-trans-title-id="AdminTab.projectAdministration.deleteProject" aria-hidden="true"></span></a></td>
      </tr>
    </table>

    <table>
      <tr id="templateNoticeInfoRow" class="adminNoticeInfoRow">
        <td class="adminNoticeIDCell"></td>
        <td class="adminNoticeTextCell"></td>
        <td class="adminNoticeTypeCell"></td>
        <td class="adminNoticeExpiresCell"></td>
        <td class="adminNoticeDelete"><a class="deletion-link"><span class="oi oi-shadow" data-glyph="delete" title="<?=$lh("AdminTab.serverMessages.deleteMsg"); ?>" data-trans-title-id="AdminTab.serverMessages.deleteMsg" aria-hidden="true"></span></a></td>
      </tr>
      <tr id="templateAnnotatorInfoRow" class="adminAnnotatorInfoRow">
        <td class="adminAnnotatorIDCell"></td>
        <td class="adminAnnotatorNameCell"></td>
        <td class="adminAnnotatorClassCell"></td>
        <td class="centered adminAnnotatorTrainableCell"><span class="oi oi-shadow oi-green adminAnnotatorTrainableStatus" data-glyph="check" aria-hidden="true"></span>
        </td>
        <td class="adminAnnotatorTagsetCell"></td>
        <td class="adminAnnotatorConfig">
          <a class="adminAnnotatorEditButton">
            <span class="oi oi-shadow" data-glyph="cog" aria-hidden="true"></span> 
            <span data-trans-id="AdminTab.autoAnnotation.options"><?=$lh("AdminTab.autoAnnotation.options"); ?></span>
          </a>
          <a class="deletion-link"><span class="oi oi-shadow" data-glyph="delete" title="<?=$lh("AdminTab.autoAnnotation.deleteTagger"); ?>" data-trans-title-id="AdminTab.autoAnnotation.deleteTagger" aria-hidden="true"></span></a></td>
      </tr>
    </table>
  </div>
</div>

<?php endif; ?>
