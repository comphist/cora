<?php

/** @file content.php
 * Define the site content.
 *
 * This file defines the actual content of the site. It fills the
 * #$menu variable, thereby setting up references to all web pages
 * that can be displayed to the user.
 *
 * @author Marcel Bollmann
 * @date January 2012
 */

require_once( "lib/contentModel.php" );

/** @copybrief index.php::$menu */
$menu = new Menu();
if( $_SESSION["loggedIn"] === true ) {
  $menu->addMenuItem( "file", "gui/file.php", "gui/js/file.js" );
	// $menu->addMenuItem( "file", "gui/file.php" );
  $menu->addMenuItem( "edit", "gui/edit.php", "gui/js/edit.js" );
	// $menu->addMenuItem( "edit", "gui/edit.php" );
  $menu->addMenuItem( "settings", "gui/settings.php", "gui/js/settings.js" );
  if ( $_SESSION["admin"] ) {
    $menu->addMenuItem( "admin", "gui/admin.php" );
  }
} else {
  $menu->addMenuItem( "login", "gui/login.php" );
}

?>