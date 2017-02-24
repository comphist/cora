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
*/
?>
<?php
ini_set('memory_limit', '536870912');

/** @file request.php
 * Handle requests sent via JavaScript.
 *
 * This file is intended to be called from JavaScript code to perform database
 * requests.  It will usually pass them on to a RequestHandler object, which
 * then outputs a JSON object string (depending on the nature of the request).
 */

try {
    require_once "lib/cfg.php";
    require_once "lib/connect.php";
    require_once "lib/localeHandler.php";
    require_once "lib/sessionHandler.php";
    require_once "lib/requestHandler.php";

    $dbi; /**< An instance of the DBInterface object. */
    $lh; /**< An instance of the LocaleHandler object. */
    $sh; /**< An instance of the SessionHandler object. */
    $rq; /**< An instance of the RequestHandler object. */

    /* Initiate session */
    $lh = new LocaleHandler();
    $dbi = new DBInterface(Cfg::get('dbinfo'), $lh);
    $sh = new CoraSessionHandler($dbi, $lh, new CoraSessionWrapper());
    $rq = new RequestHandler($sh, $lh);

    if ($_SESSION["loggedIn"]) {
        $rq->handleJSONRequest($_GET, $_POST);
    } else if ($_GET['do'] == "login") {
        $sh->login($_GET['user'], $_GET['pw']);
        header('Content-Type: application/json');
        echo json_encode(array('success' => !$_SESSION["failedLogin"]));
    } else {
        header('Content-Type: application/json');
        echo json_encode(array('success' => false, 'errcode' => - 1));
    }
}
/* Catch all unexpected errors */
catch(Exception $ex) {
    header('Content-Type: application/json');
    echo json_encode(array('success' => false, 'errcode' => - 2));
}

?>
