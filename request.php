<?php
ini_set('memory_limit', '536870912');

/** @file request.php
 * Handle requests sent via JavaScript.
 *
 * This file is intended to be called from JavaScript code to perform database
 * requests.  It will usually pass them on to a RequestHandler object, which
 * then outputs a JSON object string (depending on the nature of the request).
 */

require_once "lib/globals.php";
require_once "lib/connect.php";
require_once "lib/localeHandler.php";
require_once "lib/sessionHandler.php";
require_once "lib/requestHandler.php";

$dbi;  /**< An instance of the DBInterface object. */
$lh;   /**< An instance of the LocaleHandler object. */
$sh;   /**< An instance of the SessionHandler object. */
$rq;   /**< An instance of the RequestHandler object. */

/* Initiate session */
$dbi = new DBInterface(DB_SERVER, DB_USER, DB_PASSWORD, MAIN_DB);
$lh = new LocaleHandler();
$sh = new CoraSessionHandler($dbi, $lh);
$rq = new RequestHandler($sh);

if ($_SESSION["loggedIn"]) {
  $rq->handleJSONRequest($_GET, $_POST);
}
else if ($_GET['do'] == "login") {
  $sh->login($_GET['user'], $_GET['pw']);
  header('Content-Type: application/json');
  echo json_encode(array('success' => !$_SESSION["failedLogin"]));
}
else {
  header('Content-Type: application/json');
  echo json_encode(array('success' => false, 'errcode' => -1));
}

?>
