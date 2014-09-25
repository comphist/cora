<?php
/** @file gui.php
 * Display the graphical user interface.
 */
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">
  <head>
    <title><?php echo TITLE . " (" . LONGTITLE . ") " . VERSION; ?></title>
    <meta name="description" content="<?php echo DESCRIPTION; ?>" />
    <meta name="keywords" content="<?php echo KEYWORDS; ?>" />
    <meta http-equiv="Content-Type" content="application/xhtml+xml; charset=utf-8" />

    <?php
    /**************** Cascading Style Sheets ****************/
      embedCSS("gui/css/MultiSelect.css", "all", true);

      $master_css = file_exists(dirname(__FILE__)."/gui/css/master.min.css")
                    ? "gui/css/master.min.css"
                    : "gui/css/master.css";
      embedCSS($master_css, "all", true);
      embedCSS("gui/css/print.css", "print", false);  // do we even need this anymore?

    /********************** JavaScript **********************/
    ?>
    <script type="text/javascript">
        var cora = {};
        var default_tab = "<?php echo $menu->getDefaultItem(); ?>";
        <?php
          if($_SESSION['loggedIn']) {
              $svars = array('noPageLines' => false,
                             'contextLines' => false,
                             'editTableDragHistory' => true,
                             'hiddenColumns' => true,
                             'textPreview' => true,
                             'admin' => true,
                             'currentFileId' => true,
                             'currentName' => true,
                             'showTooltips' => false,
                             'showInputErrors' => false
                             );
              embedSessionVars($svars);
              embedTagsets($tagsets_all);
          }
          else {
              echo "var userdata = {};\n";
          }
        ?>
    </script>
    <?php
      embedJS("gui/js/mootools-core-1.4.5-full-compat-yc.js");
      embedJS("gui/js/mootools-more-1.4.0.1-min.js");

      if($_SESSION['loggedIn']) {
          include("project_specific_hacks.php");
          embedJS("gui/js/cerabox/cerabox.min.js", true);
          embedJS("gui/js/mbox/mBox.Core.js", true);
          embedJS("gui/js/mbox/mBox.Modal.js", true);
          embedJS("gui/js/mbox/mBox.Notice.js", true);
          embedJS("gui/js/mbox/mBox.Modal.Confirm.js", true);
          embedJS("gui/js/mbox/mBox.Tooltip.js", true);
          embedJS("gui/js/mbox/mForm.Core.js", true);
          embedJS("gui/js/mbox/mForm.Submit.js", true);
          embedJS("gui/js/mbox/mForm.Element.js", true);
          embedJS("gui/js/mbox/mForm.Element.Select.js", true);
          embedJS("gui/js/MultiSelect.js", true);
          
          if(file_exists(dirname(__FILE__) . "/gui/js/master.min.js")) {
              embedJS("gui/js/master.min.js", true);
          } else {
              embedJS("gui/js/baseBox.js", true);
              embedJS("gui/js/ProgressBar.js", true);
              embedJS("gui/js/dragtable_hack.js", true);
              embedJS("gui/js/iFrameFormRequest.js", true);
              embedJS("gui/js/Meio.Autocomplete.js", true);
              embedJS("gui/js/gui.js", true);
              embedJS("gui/js/file.js", true);
              embedJS("gui/js/edit.js", true);
              embedJS("gui/js/settings.js", true);
          }

          if($_SESSION['admin']) {
              embedJS("gui/js/admin.js");
          }
      }
    ?>
  </head>

  <body <?php if($_SESSION['loggedIn']): ?>onload="onLoad();" onbeforeunload="return onBeforeUnload();"<?php endif; ?>>
    <div id="overlay"></div>
    <div id="spin-overlay"></div>

    <!-- topbar & header -->
    <div id="topbar" class="no-print">
      <div id="header">
        <div id="titelzeile">
          <span id="otto">
            <object><h1><?php echo TITLE; ?></h1><h2><?php echo VERSION; ?></h2></object>
          </span>
          <span id="currentfile"></span>
        </div>

        <div id="controls">
          <?php if($_SESSION["loggedIn"]): ?>
            <a href="index.php?do=logout" id="logoutLink"><img src="gui/images/logout.png" alt="Logout" width="60" height="30" /></a>
          <?php endif; ?>
        </div>
        <?php include( "gui/menu.php" ); ?>
      </div>
    </div>

    <!-- main content -->
    <div id="main" class="no-print">
      <?php foreach ($menu->getItems() as $item) {
                include( $menu->getItemFile($item) );
	    }
      ?>
      <div id="footer"></div>
    </div>

    <!-- templates -->
    <div class="templateHolder">
      <div id="genericTextMsgPopup">
        <p></p>
        <p><textarea cols="80" rows="10" readonly="readonly"></textarea></p>
      </div>
    </div>
  </body>
</html>
