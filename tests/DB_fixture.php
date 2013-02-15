<?php
/** 02/2013 Florian Petran
 *  Abstract fixture for all Cora Database Tests
 */
require_once "PHPUnit/Extensions/Database/TestCase.php";

abstract class Cora_Tests_DbTestCase
    extends PHPUnit_Extensions_Database_TestCase {
    static private $pdo = null;
    private $conn = null;
    private $lastquery = null;

    final public function getConnection() {
        if ($this->conn === null) {
            if (self::$pdo == null) {
                //self::$pdo = new PDO("sqlite::memory:");
                self::$pdo = new PDO($GLOBALS["DB_DSN"],
                                     $GLOBALS["DB_USER"],
                                     $GLOBALS["DB_PASSWD"]);
            }
            $this->conn =
                $this->createDefaultDBConnection(self::$pdo, ':memory:');
        }
        return $this->conn;
    }

    final public function getDataSet() {
        return $this->createMySQLXMLDataSet('coradb.xml');
    }

    /** Create the coratest db and fill it with structure.
     *
     * Note that coratest is set to MyISAM engine, since the
     * FK constraints make it difficult to fill the db otherwise.
     * XXX
     */
    public static function setUpBeforeClass() {
        $mysqlcall = "mysql -uroot -p{$GLOBALS["DB_ROOTPW"]} ";
        system($mysqlcall." < coratest-setup.sql");
        system($mysqlcall." {$GLOBALS["DB_DBNAME"]} < coratest.sql" );
    }

    /** Drop the coratest db
     */
    public static function tearDownAFterClass() {
        system("mysql -uroot -p{$GLOBALS["DB_ROOTPW"]} < coratest-teardown.sql");
    }

    //////////////////// from here on mockup of DBConnector /////////////////////
    public function getDatabase() {
        return $GLOBALS["DB_DBNAME"];
    }

    public function query($qs) {
        //return $this->getConnection()->createQueryTable("result",$qs);
        $this->lastquery = $this->getConnection()->getConnection()->query($qs);
        return $this->lastquery;
    }

    public function criticalQuery($qs) {
        return $this->query($qs);
    }

    public function fetch($result) {
        //return $result->getRow($result->getRowCount()-1);
        return $result->fetch();
    }
    public function fetch_assoc($result) {
        return $result->fetch(PDO::FETCH_ASSOC);
    }
    public function fetch_array($result) {
        return $result->fetch(PDO::FETCH_NUM);
    }

    public function row_count($result = null) {
        if ($result === null) {
            return $this->lastquery->rowCount();
        } else {
            return $result->rowCount();
        }
    }

    public function last_error($result = null) {
        if ($result == null) {
            $errinfo = $this->lastquery->errorInfo();
            return $errinfo[0] != "00000";
        }
        $errinfo = $result->errorInfo();
        return $errinfo[0] != "00000";
    }

    public function startTransaction() {}
    public function commitTransaction() {}
    public function rollback() {}

    // copypasta from DBConnector
    public function last_insert_id() {
        $q = $this->query("SELECT LAST_INSERT_ID()");
        $r = $this->fetch_array($q);
        return $r[0];
    }
}

?>
