<?php 
/*
 * Copyright (C) 2016 Marcel Bollmann <bollmann@linguistics.rub.de>
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
require_once "{$GLOBALS['CORA_WEB_DIR']}/lib/sessionHandler.php";

class CoraSessionWrapper_Mock extends CoraSessionWrapper {
    public $currentSession = null;
    public $headers = array();
    public $stream = null;

    public function __construct() {
        $this->headers = array();
    }

    public function startSession($name) {
        $this->currentSession = $name;
        $_SESSION = array();
    }

    public function destroySession() {
        $this->currentSession = null;
        $_SESSION = array();
    }

    public function sendHeader($header) {
        $this->headers[] = $header;
    }

    public function openOutput() {
        $this->stream = fopen('php://temp', 'wb');
        return $this->stream;
    }
}
?>
