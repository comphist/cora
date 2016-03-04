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
          <input type="radio" name="language" value="en-US" id="lang_en" />
          <label for="lang_en">
            <span data-trans-id="SettingsTab.editorLanguage.english">
              <?=$lh("SettingsTab.editorLanguage.english");?>
            </span>
          </label>
          <input type="radio" name="language" value="de-DE" id="lang_de" />
          <label for="lang_de">
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
          <input type="checkbox" name="displayedColumns" id="eshc-tokenid" value="tokenid" checked="yes" />
            <label for="eshc-tokenid" data-trans-id="Columns.lineNo">
              <?=$lh("Columns.lineNo"); ?>
            </label>
          <input type="checkbox" name="displayedColumns" id="eshc-tok_trans" value="tok_trans" checked="yes" />
            <label for="eshc-tok_trans" data-trans-id="Columns.transTok">
              <?=$lh("Columns.transTok"); ?>
            </label>
          <input type="checkbox" name="displayedColumns" id="eshc-token" value="token" checked="yes" />
            <label for="eshc-token" data-trans-id="Columns.utfTok">
              <?=$lh("Columns.utfTok"); ?>
            </label>
          <input type="checkbox" name="displayedColumns" id="eshc-norm" value="norm" checked="yes" />
            <label for="eshc-norm" data-trans-id="Columns.norm">
              <?=$lh("Columns.norm"); ?>
            </label>
          <input type="checkbox" name="displayedColumns" id="eshc-norm_broad" value="norm_broad" checked="yes" />
            <label for="eshc-norm_broad" data-trans-id="Columns.mod">
              <?=$lh("Columns.mod"); ?>
            </label>
          <input type="checkbox" name="displayedColumns" id="eshc-norm_type" value="norm_type" checked="yes" />
            <label for="eshc-norm_type" data-trans-id="Columns.modType">
              <?=$lh("Columns.modType"); ?>
            </label>
          <input type="checkbox" name="displayedColumns" id="eshc-pos" value="pos" checked="yes" />
            <label for="eshc-pos" data-trans-id="Columns.pos">
              <?=$lh("Columns.pos"); ?>
            </label>
          <input type="checkbox" name="displayedColumns" id="eshc-morph" value="morph" checked="yes" />
            <label for="eshc-morph" data-trans-id="Columns.morph">
              <?=$lh("Columns.morph"); ?>
            </label>
          <input type="checkbox" name="displayedColumns" id="eshc-lemma" value="lemma" checked="yes" />
            <label for="eshc-lemma" data-trans-id="Columns.lemma">
              <?=$lh("Columns.lemma"); ?>
            </label>
          <input type="checkbox" name="displayedColumns" id="eshc-lemma_sugg" value="lemma_sugg" checked="yes" />
            <label for="eshc-lemma_sugg" data-trans-id="Columns.lemmaLink">
              <?=$lh("Columns.lemmaLink"); ?>
            </label>
          <input type="checkbox" name="displayedColumns" id="eshc-lemmapos" value="lemmapos" checked="yes" />
            <label for="eshc-lemmapos" data-trans-id="Columns.lemmaTag">
              <?=$lh("Columns.lemmaTag"); ?>
            </label>
          <input type="checkbox" name="displayedColumns" id="eshc-comment" value="comment" checked="yes" />
            <label for="eshc-comment" data-trans-id="Columns.comment">
              <?=$lh("Columns.comment"); ?>
            </label>
          <input type="checkbox" name="displayedColumns" id="eshc-sec_comment" value="sec_comment" checked="yes" />
            <label for="eshc-sec_comment" data-trans-id="Columns.secondaryComment">
              <?=$lh("Columns.secondaryComment"); ?>
            </label>
        </p>
      </div>

      <div id="editorSettingsTextPreview">
        <h4 data-trans-id="SettingsTab.horizontalPreview">
          <?=$lh("SettingsTab.horizontalPreview"); ?>
        </h4>
        <p>
          <input type="radio" name="es_text_preview" value="off" id="estp-off" />
            <label for="estp-off" data-trans-id="SettingsTab.noPreview">
              <?=$lh("SettingsTab.noPreview"); ?>
            </label>
          <input type="radio" name="es_text_preview" value="trans" id="estp-trans" />
            <label for="estp-trans" data-trans-id="Columns.transTok">
              <?=$lh("Columns.transTok"); ?>
            </label>
          <input type="radio" name="es_text_preview" value="utf" id="estp-utf" />
            <label for="estp-utf" data-trans-id="Columns.utfTok">
              <?=$lh("Columns.utfTok"); ?>
            </label>
        </p>
      </div>

      <div id="editorSettingsInputAids">
        <h4 data-trans-id="SettingsTab.editSupport">
          <?=$lh("SettingsTab.editSupport"); ?>
        </h4>
        <p>
          <input type="checkbox" name="show_error" value="show_error" checked="yes" id="esia-showerror" />
            <label for="esia-showerror" data-trans-id="SettingsTab.highlightErrorTags">
              <?=$lh("SettingsTab.highlightErrorTags"); ?>
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
