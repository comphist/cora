<?php

/**
 * @file index.php
 * The main file.
 *
 * This is the index file for the application. It should be called by
 * the user and loads all other components of the tool.
 *
 * @author Marcel Bollmann
 * @date   January 2012
 */

header( "Content-Type: text/html; charset=utf-8" );

/* Includes */
require_once "lib/cfg.php";
require_once "lib/connect.php";
require_once "lib/localeHandler.php";
require_once "lib/xmlHandler.php";
require_once "lib/requestHandler.php";
require_once "lib/sessionHandler.php";
require_once "lib/exporter.php";

$dbi;  /**< An instance of the DBInterface object. */
$lh;   /**< An instance of the LocaleHandler object. */
$sh;   /**< An instance of the SessionHandler object. */
$rq;   /**< An instance of the RequestHandler object. */
$menu; /**< A Menu object containing the menu items and references to
            the corresponding web pages, instantiated in
            content.php. */

/* Initiate session */
$dbi = new DBInterface(Cfg::get('dbinfo'));
$lh = new LocaleHandler();
$sh = new CoraSessionHandler($dbi, $lh);
$rq = new RequestHandler($sh);
$rq->handleRequests($_GET, $_POST);

/* Define site content */
include "content.php";
/* Load the actual HTML page */
include "gui.php";

?>
