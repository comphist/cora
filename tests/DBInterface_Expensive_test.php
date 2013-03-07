<?php
require_once"DB_fixture.php";

require_once"../lib/connect.php";

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
    protected $backupGlobalsBlacklist = array('_SESSION');

    protected function setUp() {
        $this->dbi = new DBInterface($this);
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
