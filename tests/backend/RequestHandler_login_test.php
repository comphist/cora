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
require_once 'db_fixture/fixture.php';
require_once 'mocks/CoraSessionWrapper_mock.php';
require_once "{$GLOBALS['CORA_WEB_DIR']}/lib/sessionHandler.php";
require_once "{$GLOBALS['CORA_WEB_DIR']}/lib/localeHandler.php";
require_once "{$GLOBALS['CORA_WEB_DIR']}/lib/requestHandler.php";

class Cora_Tests_RequestHandler_login_test extends Cora_Tests_DbTestCase {
    protected static $lh;
    protected static $dbi;
    private $sh;
    private $rq;
    protected $dbCleanInsertBeforeEveryTest = false;
    static protected $fixtureSet = false;

    public static function setUpBeforeClass() {
        parent::setUpBeforeClass();
        $dbinfo = array(
            "HOST" => $GLOBALS['DB_HOST'],
            "DBNAME" => $GLOBALS['DB_DBNAME'],
            "USER" => $GLOBALS['DB_USER'],
            "PASSWORD" => $GLOBALS['DB_PASSWD']
        );
        self::$lh = new LocaleHandler();
        self::$dbi = new DBInterface($dbinfo, self::$lh);
    }

    public function setUp() {
        $this->sh = new CoraSessionHandler(self::$dbi, self::$lh, new CoraSessionWrapper_Mock());
        $this->rq = new RequestHandler($this->sh, self::$lh);
        parent::setUp();
    }

    public function testLoginAsAdmin() {
        $this->assertFalse($_SESSION["loggedIn"]);
        $post = ["action" => "login",
                 "loginform" => ["un" => "admin",
                                 "pw" => "admin"]
        ];
        $this->rq->handleRequests([], $post);
        $this->assertTrue($_SESSION["loggedIn"]);
        $this->assertTrue($_SESSION["admin"]);
    }

    public function testLoginAsUser() {
        $this->assertFalse($_SESSION["loggedIn"]);
        $post = ["action" => "login",
                 "loginform" => ["un" => "mustermann",
                                 "pw" => "passwort1"]
        ];
        $this->rq->handleRequests([], $post);
        $this->assertTrue($_SESSION["loggedIn"]);
        $this->assertFalse($_SESSION["admin"]);
    }

    public function testLoginWithInvalidPassword() {
        $this->assertFalse($_SESSION["loggedIn"]);
        $post = ["action" => "login",
                 "loginform" => ["un" => "mustermann",
                                 "pw" => "passwort"]
        ];
        $this->rq->handleRequests([], $post);
        $this->assertFalse($_SESSION["loggedIn"]);
        $this->assertFalse($_SESSION["admin"]);
        $post = ["action" => "login",
                 "loginform" => ["un" => "mustermann",
                                 "pw" => "admin"]
        ];
        $this->rq->handleRequests([], $post);
        $this->assertFalse($_SESSION["loggedIn"]);
        $this->assertFalse($_SESSION["admin"]);
    }

    public function testLogoutFromAdmin() {
        $this->sh->login("admin", "admin");
        $this->assertTrue($_SESSION["loggedIn"]);
        $this->assertTrue($_SESSION["admin"]);
        $get = ["do" => "logout"];
        $this->rq->handleRequests($get, []);
        $this->assertFalse($_SESSION["loggedIn"]);
        $this->assertFalse($_SESSION["admin"]);
    }

    public function testLogoutFromUser() {
        $this->sh->login("mustermann", "passwort1");
        $this->assertTrue($_SESSION["loggedIn"]);
        $this->assertFalse($_SESSION["admin"]);
        $get = ["do" => "logout"];
        $this->rq->handleRequests($get, []);
        $this->assertFalse($_SESSION["loggedIn"]);
        $this->assertFalse($_SESSION["admin"]);
    }
}

?>
