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
 *
 * @note Languages are currently hardcoded (German and English); this
 * is reflected in database structure, the way language selection
 * works in gui.php, and some helper functions (e.g. in
 * sessionHandler.php). Further languages cannot be added without
 * considerable modifications.
 */

header( "Content-Type: text/html; charset=utf-8" );

/* Includes */
require_once "lib/cfg.php";
require_once "lib/connect.php";
require_once "lib/xmlHandler.php";
require_once "lib/requestHandler.php";
require_once "lib/sessionHandler.php";
require_once "lib/exporter.php";

$sh;   /**< An instance of the SessionHandler object. */
$rq;   /**< An instance of the RequestHandler object. */
$menu; /**< A Menu object containing the menu items and references to
            the corresponding web pages, instantiated in
            content.php. */

/* Initiate session */
$dbi = new DBInterface(Cfg::get('dbinfo'));
$xml = new XMLHandler($dbi);
$exp = new Exporter($dbi);
$sh = new CoraSessionHandler($dbi, $xml, $exp);
$rq = new RequestHandler( $sh );
$rq->handleRequests($_GET, $_POST);

/* Define site content */
include "content.php";
/* Load the actual HTML page */
include "gui.php";

?>
