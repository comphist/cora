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
require_once( "lib/globals.php" );
require_once( "lib/connect.php" );      // provides DB interface
require_once"lib/xmlHandler.php";
require_once( "lib/requestHandler.php" );
require_once( "lib/sessionHandler.php" );
require_once( "lib/exporter.php" );

$sh;   /**< An instance of the SessionHandler object. */
$rq;   /**< An instance of the RequestHandler object. */
$menu; /**< A Menu object containing the menu items and references to
            the corresponding web pages, instantiated in
            content.php. */

/* Initiate session */
$dbi = new DBInterface(DB_SERVER, DB_USER, DB_PASSWORD, MAIN_DB);
$xml = new XMLHandler($dbi);
$exp = new Exporter($dbi);
$sh = new CoraSessionHandler($dbi, $xml, $exp);
$rq = new RequestHandler( $sh );
$rq->handleRequests($_GET, $_POST);

/* Define site content */
include( "content.php" );
/* Load the actual HTML page */
include( "gui.php" );

?>
