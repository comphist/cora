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
require_once"DB_fixture.php";

require_once"{$GLOBALS['CORA_WEB_DIR']}/lib/connect.php";
require_once"{$GLOBALS['CORA_WEB_DIR']}/lib/localeHandler.php";

/** tests for DBInterface that need foreign keys.
 *
 *  03/2012 Florian Petran
 *
 *  these are typically by a magnitude more expensive
 *  than the plain ones, so they've been moved to their
 *  own file where they can dwell without doing harm.
 */

/** Tests that need FK awareness.
 *
 * TODO this needs to be moved to a separate file,
 * since apparently phpunit doesn't allow more than one test
 * class in a file.
 */
class Cora_Tests_DBInterface_FK_test extends Cora_Tests_DbTestCase_FKAware {
    protected $dbi;
    protected $lh;
    protected $backupGlobalsBlacklist = array('_SESSION');

    protected function setUp() {
      $dbinfo = array(
        'HOST' => $GLOBALS["DB_HOST"],
        'USER' => $GLOBALS["DB_USER"],
        'PASSWORD' => $GLOBALS["DB_PASSWD"],
        'DBNAME' => $GLOBALS["DB_DBNAME"]
      );
      $this->lh = new LocaleHandler(); // required by DBInterface
      $this->dbi = new DBInterface($dbinfo, $this->lh);
      parent::setUp();
    }

    public function testDeleteProjectWithUsers() {
        $this->assertFalse($this->dbi->deleteProject("1"));
        $this->assertEquals("1",
            $this->getConnection()->createQueryTable("project",
            "SELECT * FROM project WHERE id=1")->getRowCount());
    }

    public function testLockUnlock() {
        // locking a file that doesn't exist
        $lock_result = $this->dbi->lockFile("512", "test");
        $this->assertEquals(array("success" => false),
            $lock_result);
    }

    public function testDeleteToken() {
        $this->assertEquals(array("success" => true,
                                  "oldmodcount" => 1),
                            $this->dbi->deleteToken("3", "3", "3"));
        $this->assertEquals(0,
            $this->getConnection()->createQueryTable("deleted_token",
            "SELECT * FROM token WHERE id=3")->getRowCount());
        $this->assertEquals(0,
            $this->getConnection()->createQueryTable("deleted_token",
            "SELECT * FROM dipl WHERE tok_id=3")->getRowCount());
        $this->assertEquals(0,
            $this->getConnection()->createQueryTable("deleted_token",
            "SELECT * FROM modern WHERE tok_id=3")->getRowCount());
    }

}
?>
