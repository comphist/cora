<?php
/** @file gui.php
 * Display the graphical user interface.
 */

$projects = $sh->getProjectList();

function embedCSSwithTimestamp($filename) {
  $filemtime = filemtime(dirname(__FILE__) . "/" . $filename);
  echo "<link rel='stylesheet' type='text/css' href='$filename?$filemtime' media='all' />";
}

function embedJSwithTimestamp($filename) {
  $filemtime = filemtime(dirname(__FILE__) . "/" . $filename);
  echo "<script type='text/javascript' src='$filename?$filemtime'></script>";
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">
	<head>
		<title><?php echo TITLE . " (" . LONGTITLE . ") " . VERSION; ?></title>
		<meta name="description" content="<?php echo DESCRIPTION; ?>" />
		<meta name="keywords" content="<?php echo KEYWORDS; ?>" />
		<meta http-equiv="Content-Type" content="application/xhtml+xml; charset=utf-8" />

<?php
  if(file_exists(dirname(__FILE__) . "/gui/css/master.min.css")) {
    embedCSSwithTimestamp("gui/css/master.min.css");
  } else {
    embedCSSwithTimestamp("gui/css/master.css");
  }
?>
		<link rel="stylesheet" type="text/css" href="gui/css/print.css" media="print" />
		
		<script src="gui/js/mootools-core-1.4.5-full-compat-yc.js" type="text/javascript" charset="utf-8"></script>
		<script src="gui/js/mootools-more-1.4.0.1-min.js" type="text/javascript" charset="utf-8"></script>

		<!-- JavaScript -->
		<script type="text/javascript">
			// Makes variables from PHP accessible to JS
            var default_tab = "<?php echo $menu->getDefaultItem(); ?>";
			var userdata = {
                        <?php if($_SESSION['loggedIn']): ?>
			      name: "<?php echo $_SESSION['user']; ?>" , 
			      noPageLines: <?php echo $_SESSION['noPageLines']; ?>,
			      contextLines: <?php echo $_SESSION['contextLines']; ?>,
			      editTableDragHistory: '<?php echo $_SESSION['editTableDragHistory']; ?>',
			      hiddenColumns: '<?php echo $_SESSION['hiddenColumns']; ?>',
			      textPreview: '<?php echo $_SESSION['textPreview']; ?>',
			      admin: "<?php echo $_SESSION['admin']; ?>" ,
			      currentFileId: "<?php echo $_SESSION['currentFileId']; ?>",
			      currentName: "<?php echo $_SESSION['currentName']; ?>",
			      showTooltips: <?php echo $_SESSION['showTooltips']; ?>,
			      showInputErrors: <?php echo $_SESSION['showInputErrors']; ?>
			<?php endif; ?>
			};
		</script>
  <?php include( "project_specific_hacks.php" ); ?>

<?php
    embedJSwithTimestamp("gui/js/cerabox/cerabox.min.js");
    embedJSwithTimestamp("gui/js/mbox/mBox.Core.js");
    embedJSwithTimestamp("gui/js/mbox/mBox.Modal.js");
    embedJSwithTimestamp("gui/js/mbox/mBox.Notice.js");
    embedJSwithTimestamp("gui/js/mbox/mBox.Modal.Confirm.js");
    embedJSwithTimestamp("gui/js/mbox/mBox.Tooltip.js");
    embedJSwithTimestamp("gui/js/mbox/mForm.Core.js");
    embedJSwithTimestamp("gui/js/mbox/mForm.Submit.js");
    embedJSwithTimestamp("gui/js/mbox/mForm.Element.js");
    embedJSwithTimestamp("gui/js/mbox/mForm.Element.Select.js");

  if(file_exists(dirname(__FILE__) . "/gui/js/master.min.js")):
    embedJSwithTimestamp("gui/js/master.min.js");
  else:
    embedJSwithTimestamp("gui/js/baseBox.js");
    embedJSwithTimestamp("gui/js/ProgressBar.js");
    embedJSwithTimestamp("gui/js/dragtable_hack.js");
    embedJSwithTimestamp("gui/js/iFrameFormRequest.js");
    embedJSwithTimestamp("gui/js/Meio.Autocomplete.js");
    embedJSwithTimestamp("gui/js/gui.js");
    embedJSwithTimestamp("gui/js/file.js");
    embedJSwithTimestamp("gui/js/edit.js");
    embedJSwithTimestamp("gui/js/settings.js");
  endif;
?>

		<?php if($_SESSION['admin']):
embedJSwithTimestamp("gui/js/admin.js"); ?>
<script type="text/javascript">
project_editor.project_users = new Object();
<?php
$project_users = $sh->getProjectUsers();
foreach ($project_users as $pid => $userlist):
    $arrstr = '"' . implode('","', $userlist) . '"';
?>
project_editor.project_users[<?php echo $pid; ?>] = new Array(<?php echo $arrstr; ?>);

<?php endforeach; ?>
</script>
	<?php endif; ?>
	</head>
	<body onload="onLoad();" onbeforeunload="return onBeforeUnload();">
	        <div id="overlay"></div>
	        <div id="spin-overlay"></div>

<!--		<div id="tools" class="no-print">
			$$toolsLinks$$
		</div>
-->
		<div id="topbar" class="no-print">
			<div id="header">
				<div id="titelzeile">
					<span id="otto"><object><h1><?php echo TITLE; ?></h1><h2><?php echo VERSION; ?></h2></object></span>
					<span id="currentfile"><!-- filled by JavaScript --></span>

				</div>
				<div id="controls">
				  <?php if($_SESSION["loggedIn"]): ?>
				  <a href="index.php?do=logout" id="logoutLink"><img src="gui/images/logout.png" alt="Logout" width="60" height="30" /></a>
				  <?php endif; ?>
<!--					<a href="#"><img id="HelpMe" src="gui/images/help.png" height="30" alt="Help" class="tipz" title="Help::Click here to toggle help mode."  /></a>
-->
				</div>
  			<?php include( "gui/menu.php" ); ?>
			</div><!-- end Header -->
		</div>

		<div id="main" class="no-print">
			<?php foreach ($menu->getItems() as $item) {
			         include( $menu->getItemFile($item) );
			      } ?>
			<div id="footer">
			</div>
		</div><!-- end main -->

                <div class="templateHolder">
                  <div id="genericTextMsgPopup">
                   <p></p>
                   <p><textarea cols="80" rows="10" readonly="readonly"></textarea></p>
                  </div>
                </div>
	</body>
</html>
