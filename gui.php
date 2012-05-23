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

		<!-- Main stylesheets on top --> 
		<link rel="stylesheet" type="text/css" href="gui/css/screen.css" media="all" />
		<link rel="stylesheet" type="text/css" href="gui/css/myCss.css" media="all" />
		<link rel="stylesheet" type="text/css" href="gui/css/imagezoom.css" media="all" />
        <link rel="stylesheet" href="gui/js/cerabox/style/cerabox.css" media="screen" />        
        <link rel="stylesheet" href="gui/css/baseBox.css" type="text/css" media="screen" />
        <link rel="stylesheet" href="gui/js/mbox/assets/mBoxCore.css" media="screen" />        
        <link rel="stylesheet" href="gui/js/mbox/assets/mBoxModal.css" media="screen" />        
        <link rel="stylesheet" href="gui/js/mbox/assets/mBoxNotice.css" media="screen" />        
        <link rel="stylesheet" href="gui/js/mbox/assets/mBoxTooltip.css" media="screen" />        
        <link rel="stylesheet" href="gui/js/mbox/assets/mForm_mod.css" media="screen" />        
        <link rel="stylesheet" href="gui/js/mbox/assets/mFormElement-Select.css" media="screen" />        
         

		<!-- Print only, on bottom --> 
		<link rel="stylesheet" type="text/css" href="gui/css/print.css" media="print" />
		
		<script src="gui/js/mootools-core-1.4.5.js" type="text/javascript" charset="utf-8"></script>
		<script src="gui/js/mootools-more-1.4.0.1.js" type="text/javascript" charset="utf-8"></script>
		<script src="gui/js/cerabox/cerabox.min.js"></script>
		<script src="gui/js/baseBox.js"></script>

		<script src="gui/js/mbox/mBox.Core.js"></script>
		<script src="gui/js/mbox/mBox.Modal.js"></script>
		<script src="gui/js/mbox/mBox.Notice.js"></script>
		<script src="gui/js/mbox/mBox.Tooltip.js"></script>
		<script src="gui/js/mbox/mBox.Modal.Confirm.js"></script>
		<script src="gui/js/mbox/mForm.Core.js"></script>
		<script src="gui/js/mbox/mForm.Submit.js"></script>
		<script src="gui/js/mbox/mForm.Element.js"></script>
		<script src="gui/js/mbox/mForm.Element.Select.js"></script>

		<script src="gui/js/dragtable_hack.js"></script>
		<script src="gui/js/iFrameFormRequest.js"></script>

		<!-- JavaScript -->
		<script type="text/javascript">
			// Makes variables from PHP accessible to JS
            var default_tab = "<?php echo $menu->getDefaultItem(); ?>";
            var lang_strings = <?php echo json_encode($lang); ?>;
			var userdata = {
<?php if($_SESSION['loggedIn']): ?>
name: "<?php echo $_SESSION['user']; ?>" , 
noPageLines: <?php echo $_SESSION['noPageLines']; ?>,
contextLines: <?php echo $_SESSION['contextLines']; ?>,
editTableDragHistory: '<?php echo $_SESSION['editTableDragHistory']; ?>',
hiddenColumns: '<?php echo $_SESSION['hiddenColumns']; ?>',
admin: "<?php echo $_SESSION['admin']; ?>" ,
currentFileId: "<?php echo $_SESSION['currentFileId']; ?>",
currentName: "<?php echo $_SESSION['currentName']; ?>",
showTooltips: <?php echo $_SESSION['showTooltips']; ?>,
showInputErrors: <?php echo $_SESSION['showInputErrors']; ?>
<?php endif; ?>
						   };
									
		</script>

		<script src="gui/js/navigation.js" type="text/javascript" charset="utf-8"></script>
		<?php if($_SESSION['admin']): ?>
			<script src="gui/js/admin.js" type="text/javascript" charset="utf-8"></script>
		<?php endif; ?>

		<?php foreach($menu->getItems() as $item): ?>
			<?php $js = $menu->getItemJSFile($item); if(!empty($js)): ?>
		         <script src="<?php echo $js; ?>" type="text/javascript" charset="utf-8"></script>
			<?php endif; ?>
		<?php endforeach; ?>

		<style type="text/css">
			<!--
			#main {
				max-width : 95%; //haupt css wert ueberschreiben
			}
			-->
		</style>
		
	</head>
	<body onload="onLoad();" onbeforeunload="return onBeforeUnload();">
	        <div id="overlay"></div>

<!--		<div id="tools" class="no-print">
			$$toolsLinks$$
		</div>
-->
		<div id="topbar" class="no-print">
			<div id="header">
				<div id="controls" style="right: 5px">
				  <?php if($_SESSION["loggedIn"]): ?>
				  <a href="index.php?do=logout" id="logoutLink"><img src="gui/images/logout.png" alt="Logout" width="60" height="30" /></a>
				  <?php endif; ?>
<!--				  <a href="index.php?lang=<?php echo $sh->getInactiveLanguage(); ?>"><img src="gui/images/lang_de_en.png" alt="Change Language" width="60" height="30" /></a>
-->
<!--					<a href="#"><img id="HelpMe" src="gui/images/help.png" height="30" alt="Help" class="tipz" title="Help::Click here to toggle help mode."  /></a>
-->
				</div>
				<div id="titelzeile">
					<span id="otto"><object><h1><?php echo TITLE; ?></h1><h2><?php echo VERSION; ?></h2></object></span>
					<span id="currentfile"><?php if(isset($_SESSION['currentName']) && !empty($_SESSION['currentName'])) echo $_SESSION['currentName']; ?></span>

				</div>
			</div><!-- end Header -->
			<?php include( "gui/menu.php" ); ?>
		</div>

		<div id="main" class="no-print">
			<?php foreach ($menu->getItems() as $item) {
			         include( $menu->getItemFile($item) );
			      } ?>
			<div id="footer">
			</div>
		</div><!-- end main -->
	</body>
</html>
