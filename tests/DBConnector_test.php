<?php
require_once "DB_fixture.php";
require_once "../lib/connect.php";

class Cora_Tests_DBConnector_test extends Cora_Tests_DbTestCase {
    protected $dbc;
    protected $backupGlobalsBlacklist = array('_SESSION');

    protected function setUp() {
        $this->dbc = new DBConnector("localhost",
                                     $GLOBALS["DB_USER"],
                                     $GLOBALS["DB_PASSWD"],
                                     $GLOBALS["DB_DBNAME"]);
        parent::setUp();
    }

    public function testIsConnected() {
        $this->assertTrue($this->dbc->isConnected());
    }
    public function testSetGetDatabase() {
        $this->assertEquals($GLOBALS["DB_DBNAME"],
            $this->dbc->getDatabase());

        $this->dbc->setDatabase("bla");
        $this->assertEquals("bla",
            $this->dbc->getDatabase());
    }

    public function testQuery() {
        $dbname = $this->dbc->getDatabase();

        // test insert
        $result = $this->dbc->query(
            "INSERT INTO {$dbname}.error_types (name) VALUES ('testerror'), ('test2')"
        );
        $this->assertTrue($result);
        // TODO should probably test the table after this

        // test delete
        $result = $this->dbc->query(
            "DELETE FROM {$dbname}.error_types WHERE name='testerror' OR name='test2'"
        );
        $this->assertTrue($result);
        $expected = $this->getDataSet()->getTable("error_types");
        $this->assertTablesEqual($expected,
            $this->getConnection()->createQueryTable("error_types",
            "SELECT * FROM error_types"));

        // test drop
        // should fail but doesn't ??
        //$result = $this->dbc->query(
            //"DROP TABLE {$dbname}.error_types"
        //);
        //$this->assertFalse($result);

        // test update
        $result = $this->dbc->query(
            "UPDATE {$dbname}.error_types SET name='newname' WHERE id='1'"
        );
        $this->assertTrue($result);
        $this->assertEquals("newname",
            $this->getConnection()->createQueryTable("error_types",
            "SELECT name FROM error_types WHERE id=1")->getValue(0,"name"));

        // test alter
    }

    public function testSelectFetch() {
        $dbname = $this->dbc->getDatabase();

        // test select
        $result = $this->dbc->query("SELECT * FROM {$dbname}.col WHERE id=1");
        $this->assertEquals(array('id' => '1',
                                  'page_id' => '1',
                                  'num' => '0',
                                  'name' => null),
            $this->dbc->fetch_assoc($result));

        $result = $this->dbc->query("SELECT * FROM {$dbname}.col WHERE id=1");
        $this->assertEquals(array('1', '1', '0', null),
            $this->dbc->fetch_array($result));

        $result = $this->dbc->query("SELECT * FROM {$dbname}.col WHERE id=1");
        $this->assertEquals(array('id' => '1',
                                  'page_id' => '1',
                                  'num' => '0',
                                  'name' => null,
                                  0 => '1',
                                  1 => '1',
                                  2 => '0',
                                  3 => null),
            $this->dbc->fetch($result));

        $result = $this->dbc->query("SELECT * FROM {$dbname}.col");
        $this->assertEquals("3", $this->dbc->row_count($result));

        $result = $this->dbc->query("INSERT INTO {$dbname}.error_types (name) VALUES ('test')");
        $this->assertTrue($result);
        $this->assertEquals("1", $this->dbc->row_count());

        $this->assertEquals("3", $this->dbc->last_insert_id());
        //criticalQuery();
    }

    public function testTransactionRollback() {
        $dbname = $this->dbc->getDatabase();

        $this->dbc->startTransaction();
        $this->dbc->query("INSERT INTO {$dbname}.error_types (name) VALUES ('test1')");
        $this->dbc->rollback();
        $this->assertEquals("0",
            $this->getConnection()->createQueryTable("error_types",
            "SELECT * FROM {$dbname}.error_types WHERE name='test1'")->getRowCount());
    }

    public function testTransactionCommit() {
        $dbname = $this->dbc->getDatabase();

        $this->dbc->startTransaction();
        $this->dbc->query("INSERT INTO {$dbname}.error_types (name) VALUES ('test2')");
        $this->dbc->commitTransaction();
        $this->assertEquals("1",
            $this->getConnection()->createQueryTable("error_types",
            "SELECT * FROM {$dbname}.error_types WHERE name='test2'")->getRowCount());
    }

    public function testEscapeSQL() {
        // escapeSQL($stringorarray);
    }
}
?>
