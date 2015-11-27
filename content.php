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

/** @file content.php
 * Define the site content.
 *
 * This file defines the actual content of the site. It fills the
 * #$menu variable, thereby setting up references to all web pages
 * that can be displayed to the user.
 *
 * Furthermore, this file now contains some global functions for page
 * rendering to separate them from the HTML rendering in the gui/*.php
 * files.  This is a step towards a cleaner separation of content and
 * layout, but obviously not an ideal solution yet.
 *
 * @author Marcel Bollmann
 * @date January 2012 - September 2014
 */

require_once( "lib/contentModel.php" );

/** @copybrief index.php::$menu */
$menu = new Menu();
if($_SESSION["loggedIn"]) {
  $menu->addMenuItem("file", "gui/file.php", "", "Menu.file", "Menu.fileTooltip");
  $menu->addMenuItem("edit", "gui/edit.php", "", "Menu.edit", "Menu.editTooltip");
  $menu->addMenuItem("search", "gui/search.php", "", "Menu.search", "Menu.searchTooltip");
  $menu->addMenuItem("settings", "gui/settings.php", "", "Menu.settings", "Menu.settingsTooltip");
  if ($_SESSION["admin"]) {
    $menu->addMenuItem("admin", "gui/admin.php", "", "Menu.admin", "Menu.adminTooltip");
  }
  $menu->addMenuItem("help", "gui/help.php", "", "Menu.help", "Menu.helpTooltip");
} else {
  $menu->addMenuItem("login", "gui/login.php", "", "Menu.login", "Menu.loginTooltip");
}

///////////////////////////////////////////////////////////////////
// Global functions for page generation
// Basically all dirty hacks, but collected in one place now
///////////////////////////////////////////////////////////////////

function embedCSS($filename, $media="all", $withtimestamp=false) {
    if($withtimestamp) {
        $filename .= '?' . filemtime(dirname(__FILE__) . "/" . $filename);
    }
    echo "<link rel='stylesheet' type='text/css' href='$filename' media='$media' />";
}

function embedJS($filename, $withtimestamp=false) {
    if($withtimestamp) {
        $filename .= '?' . filemtime(dirname(__FILE__) . "/" . $filename);
    }
    echo "<script type='text/javascript' src='$filename'></script>";
}

function embedSessionVars($svars) {
    echo "var userdata = { ";
    $userdata = array();
    if(isset($_SESSION['user'])) {
      $userdata[] = 'name: "'.$_SESSION['user'].'"';
    }
    foreach($svars as $key => $quoted) {
        if($quoted)
            $userdata[] = $key.': "'.$_SESSION[$key].'"';
        else
            $userdata[] = $key.': '.$_SESSION[$key];
    }
    echo join(", ", $userdata);
    echo " };\n";
}

function embedTagsets($tagsets_all) {
    echo "var PHP_tagsets = [ ";
    $php_tagsets = array();
    foreach($tagsets_all as $set) {
        $set['id'] = $set['shortname'];
        $php_tagsets[] = json_encode($set);
    }
    echo join(", ", $php_tagsets);
    echo " ];\n";
}

$tagsets = $sh->getTagsetList();
$tagsets_all = $sh->getTagsetList(false, "class");
?>
