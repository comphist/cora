<?php
/** 02/2013 Florian Petran
 *  Abstract fixture for all Cora Database Tests
 */
require_once "PHPUnit/Extensions/Database/TestCase.php";

/**
 * Disable foreign key checks temporarily
 * see http://stackoverflow.com/questions/10331445/phpunit-and-mysql-truncation-error
 */
class TruncateOperation extends PHPUnit_Extensions_Database_Operation_Truncate {
    public function execute(PHPUnit_Extensions_Database_DB_IDatabaseConnection $connection,
                            PHPUnit_Extensions_Database_DataSet_IDataSet $dataset) {
        $connection->getConnection()->query("SET foreign_key_checks = 0");
        parent::execute($connection, $dataset);
        $connection->getConnection()->query("SET foreign_key_checks = 1");
    }
}

/** Base class for all Database Related Tests
 *
 * 02/2012 Florian Petran
 *
 * TODO
 * currently, the tests are wildly inefficient and take forever to run, because
 * of the FOREIGN_KEY_CHECKS query at each setUp operation. if there is no help
 * from the citizens of #phpunit, i'll make one fixture for testing with foreign
 * keys, and one without, so that all the FK tests can be moved to their own fixture,
 * and the rest can run quicker.
 */
abstract class Cora_Tests_DbTestCase
    extends PHPUnit_Extensions_Database_TestCase {
    static private $pdo = null;
    private $conn = null;
    private $lastquery = null;


    public function getSetUpOperation() {
        $cascadeTruncates = false;

        return new PHPUnit_Extensions_Database_Operation_Composite(array(
            new TruncateOperation($cascadeTruncates),
            PHPUnit_Extensions_Database_Operation_Factory::INSERT()
        ));
    }

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
     */
    public static function setUpBeforeClass() {
        $mysqlcall = "mysql -uroot -p{$GLOBALS["DB_ROOTPW"]}";
        system("echo CREATE DATABASE {$GLOBALS["DB_DBNAME"]} | ".$mysqlcall);
        system($mysqlcall." {$GLOBALS["DB_DBNAME"]} < coratest.sql" );
    }

    /** Drop the coratest db
     */
    public static function tearDownAFterClass() {
        system("echo DROP DATABASE {$GLOBALS["DB_DBNAME"]} |"
              ." mysql -uroot -p{$GLOBALS["DB_ROOTPW"]}");
    }

    //////////////////// from here on mockup of DBConnector /////////////////////
    public function getDatabase() {
        return $GLOBALS["DB_DBNAME"];
    }

    public function query($qs) {
        //return $this->getConnection()->createQueryTable("result",$qs);
        try {
            $this->lastquery = $this->getConnection()->getConnection()->query($qs);
            return $this->lastquery;
        } catch(Exception $e) {
            return false;
        }
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
