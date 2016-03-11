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
  /** @file settings.php
   * The user settings page.
   */

?>

<div id="settingsDiv" class="content">
  <div class="panel">
    <div class="btn-toolbar">
      <span class="btn-toolbar-entry" id="changePasswordLink">
        <span data-trans-id="SettingsTab.changePassword">
          <?=$lh("SettingsTab.changePassword"); ?>
        </span>
      </span>
    </div>

    <div>
      <div id="editorLanguageSettings">
        <h4>
          <span data-trans-id="SettingsTab.language">
            <?=$lh("SettingsTab.language");?>
          </span>
        </h4>
        <p>
          <label>
            <input type="radio" name="language" value="en-US" />
            <span data-trans-id="SettingsTab.editorLanguage.english">
              <?=$lh("SettingsTab.editorLanguage.english");?>
            </span>
          </label>
          <label>
            <input type="radio" name="language" value="de-DE" />
            <span data-trans-id="SettingsTab.editorLanguage.german">
            <?=$lh("SettingsTab.editorLanguage.german");?>
            </span>
          </label>
        </p>
      </div>

      <div id="editorSettingsNumberOfLines">
        <h4>
          <span data-trans-id="SettingsTab.numberOfLines">
            <?=$lh("SettingsTab.numberOfLines"); ?>
          </span>
        </h4>
        <form action="request.php" id="editLineSettings" method="get" accept-charset="utf-8">
          <p>
            <label for="noPageLines">
              <span data-trans-id="SettingsTab.linesPerPage">
                <?=$lh("SettingsTab.linesPerPage"); ?>
              </span>

            </label>
            <input type="text" name="noPageLines" value="<?php echo $_SESSION['noPageLines'];?>" size="2" maxlength="3" data-number="" />
          </p>
          <p>
            <label for="contextLines">
              <span data-trans-id="SettingsTab.overlappingLines">
                <?=$lh("SettingsTab.overlappingLines"); ?>
              </span>

            </label>
            <input type="text" name="contextLines" value="<?php echo $_SESSION['contextLines'];?>" size="2" maxlength="2" data-number="" />
          </p>
          <p>
            <input type="submit" value="<?=$lh("SettingsTab.applyLineSettings"); ?>" data-trans-value-id="SettingsTab.applyLineSettings" />
          </p>
        </form>
      </div>

      <div id="editorSettingsHiddenColumns">
        <h4 data-trans-id="SettingsTab.visibleCols">
          <?=$lh("SettingsTab.visibleCols"); ?>
        </h4>
        <p>
            <label id="eshc-tokenid">
              <input type="checkbox" name="displayedColumns" value="tokenid" checked="yes" />
              <span data-trans-id="Columns.lineNo"><?=$lh("Columns.lineNo"); ?></span>
            </label>
            <label id="eshc-tok_trans">
              <input type="checkbox" name="displayedColumns" value="tok_trans" checked="yes" />
              <span data-trans-id="Columns.transTok"><?=$lh("Columns.transTok"); ?></span>
            </label>
            <label id="eshc-token">
              <input type="checkbox" name="displayedColumns" value="token" checked="yes" />
              <span data-trans-id="Columns.utfTok"><?=$lh("Columns.utfTok"); ?></span>
            </label>
            <label id="eshc-norm">
              <input type="checkbox" name="displayedColumns" value="norm" checked="yes" />
              <span data-trans-id="Columns.norm"><?=$lh("Columns.norm"); ?></span>
            </label>
            <label id="eshc-norm_broad">
              <input type="checkbox" name="displayedColumns" value="norm_broad" checked="yes" />
              <span data-trans-id="Columns.mod"><?=$lh("Columns.mod"); ?></span>
            </label>
            <label id="eshc-norm_type">
              <input type="checkbox" name="displayedColumns" value="norm_type" checked="yes" />
              <span data-trans-id="Columns.modType"><?=$lh("Columns.modType"); ?></span>
            </label>
            <label id="eshc-pos">
              <input type="checkbox" name="displayedColumns" value="pos" checked="yes" />
              <span data-trans-id="Columns.pos"><?=$lh("Columns.pos"); ?></span>
            </label>
            <label id="eshc-morph">
              <input type="checkbox" name="displayedColumns" value="morph" checked="yes" />
              <span data-trans-id="Columns.morph"><?=$lh("Columns.morph"); ?></span>
            </label>
            <label id="eshc-lemma">
              <input type="checkbox" name="displayedColumns" value="lemma" checked="yes" />
              <span data-trans-id="Columns.lemma"><?=$lh("Columns.lemma"); ?></span>
            </label>
            <label id="eshc-lemma_sugg">
              <input type="checkbox" name="displayedColumns" value="lemma_sugg" checked="yes" />
              <span data-trans-id="Columns.lemmaLink"><?=$lh("Columns.lemmaLink"); ?></span>
            </label>
            <label id="eshc-lemmapos">
              <input type="checkbox" name="displayedColumns" value="lemmapos" checked="yes" />
              <span data-trans-id="Columns.lemmaTag"><?=$lh("Columns.lemmaTag"); ?></span>
            </label>
            <label id="eshc-comment">
              <input type="checkbox" name="displayedColumns" value="comment" checked="yes" />
              <span data-trans-id="Columns.comment"><?=$lh("Columns.comment"); ?></span>
            </label>
            <label id="eshc-sec_comment">
              <input type="checkbox" name="displayedColumns" value="sec_comment" checked="yes" />
              <span data-trans-id="Columns.secondaryComment"><?=$lh("Columns.secondaryComment"); ?></span>
            </label>
        </p>
      </div>

      <div id="editorSettingsTextPreview">
        <h4 data-trans-id="SettingsTab.horizontalPreview">
          <?=$lh("SettingsTab.horizontalPreview"); ?>
        </h4>
        <p>
          <label>
            <input type="radio" name="es_text_preview" value="off" />
            <span data-trans-id="SettingsTab.noPreview"><?=$lh("SettingsTab.noPreview"); ?></span>
          </label>
          <label>
            <input type="radio" name="es_text_preview" value="trans" />
            <span data-trans-id="Columns.transTok"><?=$lh("Columns.transTok"); ?></span>
          </label>
          <label>
            <input type="radio" name="es_text_preview" value="utf" />
            <span data-trans-id="Columns.utfTok"><?=$lh("Columns.utfTok"); ?></span>
          </label>
        </p>
      </div>

      <div id="editorSettingsInputAids">
        <h4 data-trans-id="SettingsTab.editSupport">
          <?=$lh("SettingsTab.editSupport"); ?>
        </h4>
        <p>
          <label>
            <input type="checkbox" name="show_error" value="show_error" checked="yes" />
            <span data-trans-id="SettingsTab.highlightErrorTags"><?=$lh("SettingsTab.highlightErrorTags"); ?></span>
          </label>
        </p>
      </div>
    </div>

    <div class="templateHolder">
      <span id="changePasswordForm_title" data-trans-id="SettingsTab.passwordForm.changePasswordTitle"><?=$lh("SettingsTab.passwordForm.changePasswordTitle");?></span>
      <div id="changePasswordFormDiv">
        <form action="request.php" id="changePasswordForm" method="post">
           <p>
            <label for="oldpw" data-trans-id="SettingsTab.passwordForm.oldPassword" class="ra"><?=$lh("SettingsTab.passwordForm.oldPassword"); ?></label>
            <input name="oldpw" type="password" size="30" data-required="" />
          </p>
          <p>
            <label for="newpw" data-trans-id="SettingsTab.passwordForm.newPassword" class="ra"><?=$lh("SettingsTab.passwordForm.newPassword"); ?></label>
            <input name="newpw" type="password" size="30" data-required="" />
          </p>
          <p>
            <label for="newpw2" data-trans-id="SettingsTab.passwordForm.newPasswordRepeat" class="ra"><?=$lh("SettingsTab.passwordForm.newPasswordRepeat"); ?></label>
            <input name="newpw2" type="password" size="30" data-required="" />
          </p>
          <p id="changePasswordErrorNew" class="error_text" data-trans-id="SettingsTab.passwordForm.doNotMatch"><?=$lh("SettingsTab.passwordForm.doNotMatch"); ?></p>
          <p id="changePasswordErrorOld" class="error_text" data-trans-id="SettingsTab.passwordForm.oldInvalid"><?=$lh("SettingsTab.passwordForm.oldInvalid"); ?></p>
          <p><input type="hidden" name="action" value="changeUserPassword" /></p>
          <p style="text-align:right;">
            <input type="submit" value="<?=$lh("SettingsTab.passwordForm.changePasswordBtn"); ?>" data-trans-value-id="SettingsTab.passwordForm.changePasswordBtn"/>
          </p>
        </form>
      </div>
    </div>
  </div>
</div>
