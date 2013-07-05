<?php

/** @file content.php
 * Define the site content.
 *
 * This file defines the actual content of the site. It fills the
 * #$menu variable, thereby setting up references to all web pages
 * that can be displayed to the user.
 *
 * EDIT: JavaScript files are now embedded directly in gui.php,
 * to support combination and minimization!
 *
 * @author Marcel Bollmann
 * @date January 2012
 */

require_once( "lib/contentModel.php" );

/** @copybrief index.php::$menu */
$menu = new Menu();
if( $_SESSION["loggedIn"] === true ) {
  $menu->addMenuItem( "file", "gui/file.php", "gui/js/file.js", "Datei", "Dokumente öffnen oder hinzufügen" );
  $menu->addMenuItem( "edit", "gui/edit.php", "gui/js/edit.js", "Editor", "Geöffnetes Dokument bearbeiten" );
  $menu->addMenuItem( "settings", "gui/settings.php", "gui/js/settings.js", "Einstellungen", "Einstellungen von CorA ändern" );
  if ( $_SESSION["admin"] ) {
    $menu->addMenuItem( "admin", "gui/admin.php", "", "Administration", "Benutzer und Projekte verwalten" );
  }
} else {
  $menu->addMenuItem( "login", "gui/login.php", "", "Anmeldung", "In CorA anmelden" );
}

?>