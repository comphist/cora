<?php
/** 02/2013 Florian Petran
 *  Abstract fixture for all Cora Database Tests
 */
require_once "PHPUnit/Extensions/Database/TestCase.php";

/** Base class for all Database Related Tests
 *
 * 02/2013 Florian Petran
 *
 * This class serves a dual purpose. First, it provides DB access
 * and DB related asserts for the tests, and second, it provides
 * a mockup of DBConnector for all classes that need it.
 *
 * This version of the fixture uses a DB with MyISAM tables to
 * speed up testing. As such, tests that require foreign keys
 * can't be done here.
 */
abstract class Cora_Tests_DbTestCase
    extends PHPUnit_Extensions_Database_TestCase {
    static private $pdo = null;
    private $conn = null;
    private $lastquery = null;

    final public function getConnection() {
        if ($this->conn === null) {
            if (self::$pdo == null) {
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
        return $this->createMySQLXMLDataSet('data/coradb.xml');
    }

    /** Create the coratest db and fill it with structure.
     */
    public static function setUpBeforeClass() {
        $mysqlcall = "mysql -uroot -p{$GLOBALS["DB_ROOTPW"]}";
        system("echo CREATE DATABASE {$GLOBALS["DB_DBNAME"]} | ".$mysqlcall);
        system($mysqlcall." {$GLOBALS["DB_DBNAME"]} < data/coratest-myisam.sql" );
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
            $errinfo = self::$pdo->errorInfo();
            return $errinfo[1] != 0;
        }
        $errinfo = $result->errorInfo();
        return $errinfo[1] != 0;
    }

    public function escapeSQL($obj) {
        if(is_string($obj)) {
            return preg_replace( "/^'/", "",
                        preg_replace( "/'$/", "", $this->getConnection()->getConnection()->quote($obj)));
        } elseif (is_array($obj)) {
            $newarray = array();
            foreach($obj as $k => $v) {
                $newarray[$k] = self::escapeSQL($v);
            }
            return $newarray;
        } elseif (is_object($obj) && get_class($obj) == "SimpleXMLElement") {
            return self::escapeSQL((string) $obj);
        } else {
            return $obj;
        }
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

/** FK Aware Database Fixture.
 *
 * 02/2013 Florian Petran
 *
 * FK need special attention, because MySQL >=5.5 doesn't allow
 * truncate operations on InnoDB Tables with foreign keys, yet
 * setting the FK checks on and off for each setUp inflates 
 * test running time. So any tests that need FK should subclass this,
 * while others subclass just Cora_Tests_DbTestCase
 */
class Cora_Tests_DbTestCase_FKAware
    extends Cora_Tests_DbTestCase {
    private $db_skeleton = "data/coratest-innobdb.sql";

    public function getSetUpOperation() {
        $cascadeTruncates = false;

        return new PHPUnit_Extensions_Database_Operation_Composite(array(
            new TruncateOperation($cascadeTruncates),
            PHPUnit_Extensions_Database_Operation_Factory::INSERT()
        ));
    }

    /** Create the coratest db and fill it with structure.
     */
    public static function setUpBeforeClass() {
        $mysqlcall = "mysql -uroot -p{$GLOBALS["DB_ROOTPW"]}";
        system("echo CREATE DATABASE {$GLOBALS["DB_DBNAME"]} | ".$mysqlcall);
        system($mysqlcall." {$GLOBALS["DB_DBNAME"]} < data/coratest-innodb.sql" );
    }

}

?>
