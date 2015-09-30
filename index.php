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

try {

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

} /* Catch all unexpected errors */
catch (Exception $ex) {
  if (file_exists("error.php")) {
    include "error.php";
  } else {
    echo "<p>There was an error instantiating CorA.</p>";
    echo "<p>Additionally, there was an error accessing the error page.</p>";
  }
}

?>
