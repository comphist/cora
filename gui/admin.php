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
      <span class="btn-toolbar-entry" id="adminUsersRefresh" title="<?=$lh("AdminTab.refresh"); ?>" data-trans-title-id="AdminTab.refresh">
        <span class="oi" data-glyph="reload" aria-hidden="true"></span>
      </span>
      <span class="btn-toolbar-entry" id="adminViewCollapseAll" title="<?=$lh("AdminTab.collapseCategories"); ?>" data-trans-title-id="AdminTab.collapseCategories">
        <span class="oi" data-glyph="collapse-up" aria-hidden="true"></span>
      </span>
      <span class="btn-toolbar-entry" id="adminViewExpandAll" title="<?=$lh("AdminTab.expandCategories"); ?>" data-trans-title-id="AdminTab.expandCategories">
        <span class="oi" data-glyph="collapse-down" aria-hidden="true"></span>
      </span>
      <span class="btn-toolbar-entry" id="adminCreateUser"><span class="oi oi-green" data-glyph="plus" aria-hidden="true"></span> <span data-trans-id="AdminTab.newUser">
      <?=$lh("AdminTab.newUser"); ?></span>
    </span>
      <span class="btn-toolbar-entry" id="adminCreateProject">
        <span class="oi oi-green" data-glyph="plus" aria-hidden="true"></span> 
        <span data-trans-id="AdminTab.newProject"><?=$lh("AdminTab.newProject"); ?>
        </span>
      </span>
      <span class="btn-toolbar-entry" id="adminCreateAnnotator">
        <span class="oi oi-green" data-glyph="plus" aria-hidden="true"></span> <span data-trans-id="AdminTab.newTagger"><?=$lh("AdminTab.newTagger"); ?></span></span>
      <span class="btn-toolbar-entry" id="adminCreateNotice">
        <span class="oi oi-green" data-glyph="plus" aria-hidden="true"></span> <span data-trans-id="AdminTab.newMsg"><?=$lh("AdminTab.newMsg"); ?></span></span>
      <span class="btn-toolbar-entry" id="adminViewTagset">
        <span class="oi" aria-hidden="true"></span><span data-trans-id="AdminTab.tagsetBrowser"><?=$lh("AdminTab.tagsetBrowser"); ?></span></span>
      <span class="btn-toolbar-entry" id="adminImportTagset">
        <span class="oi" data-glyph="data-transfer-upload" aria-hidden="true"></span> <span data-trans-id="AdminTab.importTagset"><?=$lh("AdminTab.importTagset"); ?></span></span>
    </div>

    <!-- USER MANAGEMENT -->
    <div class="clappable clapp-modern" id="users">
      <h4 class="clapp"><span data-trans-id="AdminTab.userAdministration"><?=$lh("AdminTab.userAdministration"); ?></span></h4>
      <div>
        <table id="editUsers" class="table-modern">
          <thead>
            <tr>
              <th><span data-trans-id="AdminTab.userName"><?=$lh("AdminTab.userName"); ?></span></th>
              <th><span data-trans-id="AdminTab.isAdmin"><?=$lh("AdminTab.isAdmin"); ?></span></th>
              <th><span data-trans-id="AdminTab.lastActive"><?=$lh("AdminTab.lastActive"); ?></span></th>
              <th><span data-trans-id="AdminTab.openFile"><?=$lh("AdminTab.openFile"); ?></span></th>
              <th><span data-trans-id="AdminTab.email"><?=$lh("AdminTab.email"); ?></span></th>
              <th><span data-trans-id="AdminTab.note"><?=$lh("AdminTab.note"); ?></span></th>
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
      <h4 class="clapp"><span data-trans-id="AdminTab.projectAdministration"><?=$lh("AdminTab.projectAdministration"); ?></span></h4>
      <div>
        <table id="editProjects" class="table-modern">
          <thead>
            <tr>
              <th><span data-trans-id="AdminTab.projectName"><?=$lh("AdminTab.projectName"); ?></span></th>
              <th><span data-trans-id="AdminTab.assignedUsers"><?=$lh("AdminTab.assignedUsers"); ?></span></th>
              <th><span data-trans-id="AdminTab.associatedTagsetTypes"><?=$lh("AdminTab.associatedTagsetTypes"); ?></span></th>
              <th><span data-trans-id="AdminTab.isEdit"><?=$lh("AdminTab.isEdit"); ?></span></th>
              <th><span data-trans-id="AdminTab.isImport"><?=$lh("AdminTab.isImport"); ?></span></th>
              <th class="table-th-nosort"></th>
              <th class="table-th-nosort"></th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>

        <p data-trans-id="AdminTab.visibleForAdmins">
          <?=$lh("AdminTab.visibleForAdmins"); ?>
        </p>
      </div>
    </div>

    <!-- AUTOMATIC ANNOTATORS -->
    <div class="clappable clapp-modern starthidden" id="automaticAnnotators">
      <h4 class="clapp"><span data-trans-id="AdminTab.autoAnnotationOptions"><?=$lh("AdminTab.autoAnnotationOptions"); ?></span></h4>
      <div>
        <table id="editAutomaticAnnotators" class="table-modern">
          <thead>
            <tr>
              <th><span data-trans-id="AdminTab.annoId"><?=$lh("AdminTab.annoId"); ?></span></th>
              <th><span data-trans-id="AdminTab.annoName"><?=$lh("AdminTab.annoName"); ?></span></th>
              <th><span data-trans-id="AdminTab.annoClass"><?=$lh("AdminTab.annoClass"); ?></span></th>
              <th><span data-trans-id="AdminTab.isTrained"><?=$lh("AdminTab.isTrained"); ?></span></th>
              <th><span data-trans-id="AdminTab.associatedTagsetTypes"><?=$lh("AdminTab.associatedTagsetTypes"); ?></span></th>
              <th class="table-th-nosort"></th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>
    </div>

    <!-- SERVER NOTICES -->
    <div class="clappable clapp-modern starthidden" id="serverNotices">
      <h4 class="clapp"><span data-trans-id="AdminTab.serverNotifications"><?=$lh("AdminTab.serverNotifications"); ?></span></h4>
      <div>
        <table id="editNotices" class="table-modern">
          <thead>
            <tr>
              <th><span data-trans-id="AdminTab.serverId"><?=$lh("AdminTab.serverId"); ?></span></th>
              <th><span data-trans-id="AdminTab.serverMsg"><?=$lh("AdminTab.serverMsg"); ?></span></th>
              <th><span data-trans-id="AdminTab.serverType"><?=$lh("AdminTab.serverType"); ?></span></th>
              <th><span data-trans-id="AdminTab.expire"><?=$lh("AdminTab.expire"); ?></span></th>
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
          <span data-trans-id="AdminTab.Forms.type"><?=$lh("AdminTab.Forms.type"); ?></span> </label>
        <select name="noticetype" size="1">
          <option value="alert">Alert</option>
          <!-- <option value="info">Info</option> -->
        </select>
      </p>
      <p>
        <label for="noticeexpires" class="ra">
          <span data-trans-id="AdminTab.Forms.expireDate"><?=$lh("AdminTab.Forms.expireDate"); ?></span> </label>
        <input type="text" name="noticeexpires" value="" data-required="" class="mform" />
      </p>
      <p>
        <label for="noticetext" class="ra vt">
          <span data-trans-id="AdminTab.Forms.msgText"><?=$lh("AdminTab.Forms.msgText"); ?></span> </label>
        <textarea cols="30" rows="3" name="noticetext" value="" data-required="" class="mform"></textarea>
      </p>
    </div>

    <div id="templateCreateUser">
      <p>
        <label for="newuser" class="ra">
          <span data-trans-id="AdminTab.Forms.newUserName"><?=$lh("AdminTab.Forms.newUserName"); ?></span> </label>
        <input type="text" name="newuser" value="" data-required="" class="mform" />
      </p>
      <p>
        <label for="newpw" class="ra">
          <span data-trans-id="AdminTab.Forms.password"><?=$lh("AdminTab.Forms.password"); ?></span> </label>
        <input type="password" name="newpw" value="" data-required="" class="mform" />
      </p>
      <p>
        <label for="newpw2" class="ra">
          <span data-trans-id="AdminTab.Forms.repeatPassword"><?=$lh("AdminTab.Forms.repeatPassword"); ?></span> </label>
        <input type="password" name="newpw2" value="" data-required="" class="mform" />
      </p>
    </div>

    <div id="templateChangePassword">
      <p>
        <label for="newchpw" class="ra">
          <span data-trans-id="AdminTab.Forms.newPassword"><?=$lh("AdminTab.Forms.newPassword"); ?></span> </label>
        <input type="password" name="newchpw" value="" data-required="" class="mform" />
      </p>
      <p>
        <label for="newchpw2" class="ra">
          <span data-trans-id="AdminTab.Forms.repeatNewPassword"><?=$lh("AdminTab.Forms.repeatNewPassword"); ?></span> </label>
        <input type="password" name="newchpw2" value="" data-required="" class="mform" />
      </p>
    </div>

    <div id="annotatorEditForm" class="annotatorEditForm">
      <p>
        <label for="annotatorDisplayName" class="ra">
          <span data-trans-id="AdminTab.Forms.taggerName"><?=$lh("AdminTab.Forms.taggerName"); ?></span> </label>
        <input type="text" name="annotatorDisplayName" class="mform" />
      </p>
      <p>
        <label for="annotatorClassName" class="ra">
          <span data-trans-id="AdminTab.Forms.taggerClass"><?=$lh("AdminTab.Forms.taggerClass"); ?></span> </label>
        <input type="text" name="annotatorClassName" class="mform" />
      </p>
      <p>
        <label for="annotatorOptions"><span data-trans-id="AdminTab.Forms.taggerOptions"><?=$lh("AdminTab.Forms.taggerOptions"); ?></span> </label>
        <ul class="flexrow-container"></ul>
      </p>
      <p>
        <input type="checkbox" id="annotatorIsTrainable" name="annotatorIsTrainable" value="trainable" class="mform" />
        <label for="annotatorIsTrainable" data-trans-id="AdminTab.Forms.trainablePerProject"><?=$lh("AdminTab.Forms.trainablePerProject"); ?></label>
      </p>
      <p><label data-trans-id="AdminTab.Forms.associatedTagsets"><?=$lh("AdminTab.Forms.associatedTagsets"); ?></label></p>
      <div class="tagsetSelectPlaceholder"></div>
      <p data-trans-id="AdminTab.Forms.taggerOptionsInfo">
        <?=$lh("AdminTab.Forms.taggerOptionsInfo"); ?>
      </p>
    </div>

    <div id="userEditForm" class="userEditForm">
      <p>
        <label for="adminUserEmail"><span data-trans-id="AdminTab.Forms.emailAddress"><?=$lh("AdminTab.Forms.emailAddress"); ?></span> </label><br />
        <input type="text" name="adminUserEmail" placeholder="<?=$lh("AdminTab.Forms.dummyMail"); ?>" data-trans-placeholder-id="AdminTab.Forms.dummyMail" size="60" class="mform" />
      </p>
      <p>
        <label for="adminUserComment"><span data-trans-id="AdminTab.Forms.userNote"><?=$lh("AdminTab.Forms.userNote"); ?></span> </label><br />
        <input type="text" name="adminUserComment" placeholder="<?=$lh("AdminTab.Forms.dummyNote"); ?>" data-trans-placeholder-id="AdminTab.Forms.dummyNote" size="60" class="mform" />
      </p>
    </div>

    <div id="projectEditForm" class="projectEditForm">
      <p>
        <label for="projectCmdEditToken"><span data-trans-id="AdminTab.Forms.editTokenCommand"><?=$lh("AdminTab.Forms.editTokenCommand"); ?></span> </label><br />
        <input type="text" name="projectCmdEditToken" placeholder="<?=$lh("AdminTab.Forms.dummyCommand"); ?>" data-trans-placeholder-id="AdminTab.Forms.dummyCommand" size="60" class="mform" />
      </p>
      <p>
        <label for="projectCmdImport"><span data-trans-id="AdminTab.Forms.importCommand"><?=$lh("AdminTab.Forms.importCommand"); ?></span> </label><br />
        <input type="text" name="projectCmdImport" placeholder="<?=$lh("AdminTab.Forms.dummyCommand"); ?>" data-trans-placeholder-id="AdminTab.Forms.dummyCommand" size="60" class="mform" />
      </p>
      <p><label data-trans-id="AdminTab.Forms.assignedUsers"><?=$lh("AdminTab.Forms.assignedUsers"); ?></label></p>
      <div class="userSelectPlaceholder"></div>
      <p data-trans-id="AdminTab.Forms.assignUserInfo">
        <?=$lh("AdminTab.Forms.assignUserInfo"); ?>
      </p>
      <p><label data-trans-id="AdminTab.Forms.associatedTagsets"><?=$lh("AdminTab.Forms.associatedTagsets"); ?></label></p>
      <div class="tagsetSelectPlaceholder"></div>
      <p data-trans-id="AdminTab.Forms.ssociatedTagsetsInfo">
        <?=$lh("AdminTab.Forms.associatedTagsetsInfo"); ?>
      </p>
    </div>

    <div id="projectCreateForm">
      <p>
        <label for="project_name" class="ra" data-trans-id="AdminTab.Forms.projectName">
          <?=$lh("AdminTab.Forms.projectName"); ?>
        </label>
        <input type="text" name="project_name" value="" class="mform" />
      </p>
    </div>

    <div id="annotatorCreateForm">
      <p>
        <label for="annotator_name" class="ra" data-trans-id="AdminTab.Forms.taggerName">
          <?=$lh("AdminTab.Forms.taggerName"); ?>
        </label>
        <input type="text" name="annotator_name" value="" class="mform" />
      </p>
    </div>

    <span id="tagsetImportForm_title" data-trans-id="AdminTab.Forms.importTagsetForm"><?=$lh("AdminTab.Forms.importTagsetForm"); ?></span>
    <div id="tagsetImportForm">
      <form action="request.php" id="newTagsetImportForm" method="post" accept-charset="utf-8" enctype="multipart/form-data">
      <p>
        <label for="tagset_name" data-trans-id="AdminTab.Forms.tagsetName">
          <?=$lh("AdminTab.Forms.tagsetName"); ?>
        </label>
        <input type="text" name="tagset_name" value="" size="40" maxlength="255" data-required />
      </p>
      <p>
        <label for="tagset_class"><span data-trans-id="AdminTab.Forms.tagsetClass">
          <?=$lh("AdminTab.Forms.tagsetClass"); ?>
        </span> </label>
        <select size="1" name="tagset_class"></select>
      </p>
      <p>
        <label for="txtFile"><span data-trans-id="AdminTab.Forms.tagsetFile">
          <?=$lh("AdminTab.Forms.tagsetFile"); ?>
        </span> </label>
        <input type="file" name="txtFile" data-required />
      </p>
      <p style="max-width:32em;" data-trans-id="AdminTab.Forms.tagsetInfo">
        <?=$lh("AdminTab.Forms.tagsetInfo"); ?>
      </p>
      <p>
        <input type="hidden" name="action" value="importTagsetTxt" />
        <input type="hidden" name="via" value="iframe" />
        <input type="hidden" name="tagset_settype" value="closed" />
      </p>
      <p style="text-align:right;">
        <input type="submit" value="<?=$lh("AdminTab.Forms.importTagsetBtn"); ?>" data-trans-value-id="AdminTab.Forms.importTagsetBtn"/>
      </p>
      </form>
    </div>

    <span id="adminTagsetBrowser_title" data-trans-id="AdminTab.Forms.tagsetBrowserForm"><?=$lh("AdminTab.Forms.tagsetBrowserForm"); ?></span>
    <div id="adminTagsetBrowser">
      <p>
        <select id="aTBtagset" class="mform">
	<?php foreach($tlist as $set):?>
          <option value="<?php echo $set['shortname'];?>"><?php echo $set['longname'];?></option>
        <?php endforeach;?>
        </select>
        <button id="aTBview" type="button" class="mform" data-trans-id="AdminTab.Forms.preview"><?=$lh("AdminTab.Forms.preview"); ?>
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
        <td class="centered adminUserAdminStatusTD" title="<?=$lh("AdminTab.isNotAdminTitle"); ?>" data-trans-title-id="AdminTab.isNotAdminTitle"><span class="oi oi-shadow oi-green adminUserAdminStatus" data-glyph="check" aria-hidden="true"></span>
        </td>
        <td class="adminUserLastactiveCell"></td>
        <td class="adminUserActivityCell"></td>
        <td class="adminUserEmailCell"></td>
        <td class="adminUserCommentCell"></td>
        <td>
          <a class="adminUserEditButton"><span class="oi oi-shadow" data-glyph="cog" aria-hidden="true"></span> <span data-trans-id="AdminTab.options"><?=$lh("AdminTab.options"); ?></span></a>
        </td>
        <td class="adminUserDelete"><a class="deletion-link"><span class="oi oi-shadow" data-glyph="delete" title="<?=$lh("AdminTab.deleteUser"); ?>" data-trans-title-id="AdminTab.deleteUser" aria-hidden="true"></span></a></td>
      </tr>
    </table>

    <table>
      <tr id="templateProjectInfoRow" class="adminProjectInfoRow">
        <td class="adminProjectNameCell"></td>
        <td class="adminProjectUsersCell"></td>
        <td class="adminProjectTagsetsCell"></td>
        <td class="centered adminProjectCmdEdittoken" title="<?=$lh("AdminTab.editScript"); ?>" data-trans-title-id="AdminTab.editScript">
          <span class="oi oi-shadow oi-green" data-glyph="check" aria-hidden="true"></span></td>
        <td class="centered adminProjectCmdImport" title="<?=$lh("AdminTab.importScript"); ?>" data-trans-title-id="AdminTab.importScript">
          <span class="oi oi-shadow oi-green" data-glyph="check" aria-hidden="true"></span></td>
        <td>
          <a class="adminProjectEditButton"><span class="oi oi-shadow" data-glyph="cog" aria-hidden="true"></span> <span data-trans-id="AdminTab.projectOptions"><?=$lh("AdminTab.projectOptions"); ?></a>
        </td>
        <td><a class="adminProjectDelete deletion-link"><span class="oi oi-shadow" data-glyph="delete" title="<?=$lh("AdminTab.deleteProject"); ?>" data-trans-title-id="AdminTab.deleteProject" aria-hidden="true"></span></a></td>
      </tr>
    </table>

    <table>
      <tr id="templateNoticeInfoRow" class="adminNoticeInfoRow">
        <td class="adminNoticeIDCell"></td>
        <td class="adminNoticeTextCell"></td>
        <td class="adminNoticeTypeCell"></td>
        <td class="adminNoticeExpiresCell"></td>
        <td class="adminNoticeDelete"><a class="deletion-link"><span class="oi oi-shadow" data-glyph="delete" title="<?=$lh("AdminTab.deleteMsg"); ?>" data-trans-title-id="AdminTab.deleteMsg" aria-hidden="true"></span></a></td>
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
            <span data-trans-id="AdminTab.options"><?=$lh("AdminTab.options"); ?></span>
          </a>
          <a class="deletion-link"><span class="oi oi-shadow" data-glyph="delete" title="<?=$lh("AdminTab.deleteTagger"); ?>" data-trans-title-id="AdminTab.deleteTagger" aria-hidden="true"></span></a></td>
      </tr>
    </table>
  </div>
</div>

<?php endif; ?>
