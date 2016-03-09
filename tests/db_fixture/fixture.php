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
/** 02/2013 Florian Petran
 *  Abstract fixture for all Cora Database Tests
 */

require_once "{$GLOBALS['CORA_WEB_DIR']}/lib/cfg.php";

$dbinfo = Cfg::get('dbinfo');
$testsuffix = Cfg::get('test_suffix');
$GLOBALS['DB_USER'] = $dbinfo['USER'];
$GLOBALS['DB_PASSWD'] = $dbinfo['PASSWORD'];
$GLOBALS['DB_HOST'] = $dbinfo['HOST'];
$GLOBALS['DB_DBNAME'] = "{$dbinfo['DBNAME']}_{$testsuffix}";
$GLOBALS['DB_ROOTUSER'] = "{$dbinfo['USER']}_{$testsuffix}";
$GLOBALS['DB_ROOTPW'] = "{$dbinfo['PASSWORD']}_{$testsuffix}";
$GLOBALS['DB_DSN'] = "mysql:dbname={$GLOBALS['DB_DBNAME']};host={$GLOBALS['DB_HOST']}";

$GLOBALS['DB_SCHEMA_INNODB'] = __DIR__ . "/coratest_schema_innodb.sql";
$GLOBALS['DB_SCHEMA_MYISAM'] = __DIR__ . "/coratest_schema_myisam.sql";
$GLOBALS['DB_DATAFILE_XML']  = __DIR__ . "/coratest_data.xml";

/** 03/2016 Marcel Bollmann
 *  Derive schema automatically from main database schema
 */
function _check_and_prepare_database_schema() {
    if (!file_exists($GLOBALS['DB_SCHEMA_INNODB'])) {
        copy($GLOBALS['CORA_WEB_DIR'] . "/db/coradb.sql",
             $GLOBALS['DB_SCHEMA_INNODB']);
    }
    if (!file_exists($GLOBALS['DB_SCHEMA_MYISAM'])) {
        $schema = file_get_contents($GLOBALS['DB_SCHEMA_INNODB']);
        $myisam = str_replace("InnoDB", "MyISAM", $schema);
        file_put_contents($GLOBALS['DB_SCHEMA_MYISAM'], $myisam);
    }
}
_check_and_prepare_database_schema();

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
 *
 * 03/2016 Marcel Bollmann
 *
 * Class now supports clean-inserting the data either before each
 * individual test case (the default), or only once during class
 * setup.  The latter speeds up the testing tremendously and can
 * be achieved by setting
 *
 *   protected $dbCleanInsertBeforeEveryTest = false;
 *
 * in the derived class.  Just be aware that changes made to the DB
 * will be persistent between tests.
 */
abstract class Cora_Tests_DbTestCase
    extends PHPUnit_Extensions_Database_TestCase {
    static protected $pdo = null;
    static protected $dataset;
    static protected $fixtureSet = false;
    protected $dbCleanInsertBeforeEveryTest = true;
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
                $this->createDefaultDBConnection(self::$pdo, 'mysql::memory:');
        }
        return $this->conn;
    }

    final public function getDataSet() {
        if (!isset(self::$dataset)) {
            self::$dataset = $this->createMySQLXMLDataSet($GLOBALS["DB_DATAFILE_XML"]);
        }
        return self::$dataset;
    }

    protected function getSetUpOperation() {
        $cls = get_class($this);
        if ($this->dbCleanInsertBeforeEveryTest || !$cls::$fixtureSet) {
            $cls::$fixtureSet = true;
            return \PHPUnit_Extensions_Database_Operation_Factory::CLEAN_INSERT(true);
        }
        else {
            return \PHPUnit_Extensions_Database_Operation_Factory::NONE();
        }
    }

    /** Create the coratest db and fill it with structure.
     */
    public static function setUpBeforeClass() {
        $mysqlcall = "{$GLOBALS["MYSQL_EXEC"]} -u{$GLOBALS["DB_ROOTUSER"]} -p{$GLOBALS["DB_ROOTPW"]}";
        system("echo CREATE DATABASE IF NOT EXISTS {$GLOBALS["DB_DBNAME"]} | ".$mysqlcall);
        system($mysqlcall." {$GLOBALS["DB_DBNAME"]} < {$GLOBALS["DB_SCHEMA_MYISAM"]}" );
    }

    /** Drop the coratest db
     */
    public static function tearDownAfterClass() {
        system("echo DROP DATABASE {$GLOBALS["DB_DBNAME"]} |"
              ." {$GLOBALS["MYSQL_EXEC"]} -u{$GLOBALS["DB_ROOTUSER"]} -p{$GLOBALS["DB_ROOTPW"]}");
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

    public function getSetUpOperation() {
        $cascadeTruncates = false;

        $cls = get_class($this);
        if ($this->dbCleanInsertBeforeEveryTest || !$cls::$fixtureSet) {
            $cls::$fixtureSet = true;
            return new PHPUnit_Extensions_Database_Operation_Composite(array(
                new TruncateOperation($cascadeTruncates),
                PHPUnit_Extensions_Database_Operation_Factory::INSERT()
            ));
        }
        else {
            return \PHPUnit_Extensions_Database_Operation_Factory::NONE();
        }
    }

    /** Create the coratest db and fill it with structure.
     */
    public static function setUpBeforeClass() {
        $mysqlcall = "{$GLOBALS["MYSQL_EXEC"]} -u{$GLOBALS["DB_ROOTUSER"]} -p{$GLOBALS["DB_ROOTPW"]}";
        system("echo CREATE DATABASE {$GLOBALS["DB_DBNAME"]} | ".$mysqlcall);
        system($mysqlcall." {$GLOBALS["DB_DBNAME"]} < {$GLOBALS["DB_SCHEMA_INNODB"]}" );
    }

}

?>
