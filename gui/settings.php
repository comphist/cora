<?php
  /** @file settings.php
   * The user settings page.
   */

?>

<div id="settingsDiv" class="content" style="display: none;">
  <div class="panel">
    <div class="btn-toolbar">
      <span class="btn-toolbar-entry" id="changePasswordLink">Passwort ändern...</span>
    </div>

    <div>
      <div id="editorSettingsNumberOfLines">
        <h4>Zeilenanzahl</h4>
        <form action="request.php" id="editUserSettings" method="get" accept-charset="utf-8">
          <p>
            <label for="noPageLines">Zeilen pro Seite:</label>
            <input type="text" name="noPageLines" value="<?php echo $_SESSION['noPageLines'];?>" size="2" maxlength="3" data-number="" />
          </p>
          <p>
            <label for="contextLines">Überlappende Zeilen:</label>
            <input type="text" name="contextLines" value="<?php echo $_SESSION['contextLines'];?>" size="2" maxlength="2" data-number="" />
          </p>
          <p><input type="submit" value="Zeilen-Einstellungen übernehmen" /></p>
        </form>
      </div>

      <div id="editorSettingsHiddenColumns">
        <h4>Sichtbare Spalten</h4>
        <p>
          <input type="checkbox" name="displayedColumns" id="eshc-tokenid" value="tokenid" checked="yes" /><label for="eshc-tokenid">Zeilennummer</label>
          <input type="checkbox" name="displayedColumns" id="eshc-tok_trans" value="tok_trans" checked="yes" /><label for="eshc-tok_trans">Token (Trans)</label>
          <input type="checkbox" name="displayedColumns" id="eshc-token" value="token" checked="yes" /><label for="eshc-token">Token (UTF)</label>
          <input type="checkbox" name="displayedColumns" id="eshc-norm" value="norm" checked="yes" /><label for="eshc-norm">Normalisierung</label>
          <input type="checkbox" name="displayedColumns" id="eshc-norm_broad" value="mod" checked="yes" /><label for="eshc-norm_broad">Modernisierung</label>
          <input type="checkbox" name="displayedColumns" id="eshc-norm_type" value="mod" checked="yes" /><label for="eshc-norm_type">Modernisierungstyp</label>
          <input type="checkbox" name="displayedColumns" id="eshc-pos" value="pos" checked="yes" /><label for="eshc-pos">POS-Tag</label>
          <input type="checkbox" name="displayedColumns" id="eshc-morph" value="morph" checked="yes" /><label for="eshc-morph">Morphologie-Tag</label>
          <input type="checkbox" name="displayedColumns" id="eshc-lemma" value="lemma" checked="yes" /><label for="eshc-lemma">Lemma</label>
          <input type="checkbox" name="displayedColumns" id="eshc-lemmapos" value="lemmapos" checked="yes" /><label for="eshc-lemmapos">Lemma-Tag</label>
          <input type="checkbox" name="displayedColumns" id="eshc-Comment" value="Comment" checked="yes" /><label for="eshc-Comment">Kommentar</label>
        </p>
      </div>

      <div id="editorSettingsTextPreview">
        <h4>Horizontale Textvorschau</h4>
        <p>
          <input type="radio" name="es_text_preview" value="off" id="estp-off" /><label for="estp-off">Aus</label>
          <input type="radio" name="es_text_preview" value="trans" id="estp-trans" /><label for="estp-trans">Token (Trans)</label>
          <input type="radio" name="es_text_preview" value="utf" id="estp-utf" /><label for="estp-utf">Token (UTF)</label>
        </p>
      </div>

      <div id="editorSettingsInputAids">
        <h4>Editierhilfen</h4>
        <p>
          <input type="checkbox" name="show_error" value="show_error" checked="yes" id="esia-showerror" /><label for="esia-showerror">Fehlerhafte Tags hervorheben</label>
        </p>
      </div>

      <div id="generalSettings" style="display: none;">
        <h4>Allgemeine Einstellungen</h4>
        <p style="display: none;">
          <input type="checkbox" name="showTooltips" value="showTooltips" checked="yes" /> Tooltips anzeigen
        </p>
      </div>
    </div>

    <div class="templateHolder">
      <div id="changePasswordFormDiv">
        <form action="request.php" id="changePasswordForm" method="post">
           <p>
            <label for="oldpw">Altes Passwort: </label>
            <input name="oldpw" type="password" size="30" data-required="" />
          </p>
          <p>
            <label for="newpw">Neues Passwort: </label>
            <input name="newpw" type="password" size="30" data-required="" />
          </p>
          <p>
            <label for="newpw2">Neues Passwort (wdh.): </label>
            <input name="newpw2" type="password" size="30" data-required="" />
          </p>
          <p id="changePasswordErrorNew" class="error_text">Die beiden Passwörter stimmen nicht überein!</p>
          <p id="changePasswordErrorOld" class="error_text">Altes Passwort ist nicht korrekt!</p>
          <p><input type="hidden" name="action" value="changeUserPassword" /></p>
          <p style="text-align:right;">
            <input type="submit" value="Passwort ändern" />
          </p>
        </form>
      </div>
    </div>
  </div>
</div>
