<?php
require_once "PHPUnit/Extensions/Database/TestCase.php";
require_once "../lib/connect.php";
require_once "array_dataset.php";

/** Base class for all DB Tests */
abstract class Cora_Tests_DbTestCase 
    extends PHPUnit_Extensions_Database_TestCase {
    static private $pdo = null;
    private $conn = null;

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
        return $this->getConnection()->getConnection()->query($qs);
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
}


class interfaceTest extends Cora_Tests_DbTestCase {
    protected $dbi;
    protected $mysqlcall;

    protected function setUp() {
        $this->dbi = new DBInterface($this);
        parent::setUp();
    }

    public function testGetUser() {
        $user_system = array("id" => "1",
                             "name" => "system",
                             "admin" => "1",
                             "lastactive" => "2013-01-16 14:22:57");
        $user_test = array("id" => "5",
                           "name" => "test",
                           "admin" => "0",
                           "lastactive" => "2013-01-22 15:38:32");
        $user_bollmann = array("id" => "3",
                               "name" => "bollmann",
                               "admin" => "1",
                               "lastactive" => "2013-02-04 11:29:04");


        $this->assertEquals($user_system,
                            $this->dbi->getUserById(1));
        $this->assertEquals($user_test,
                            $this->dbi->getUserById(5));

        $this->assertEquals($user_system,
                            $this->dbi->getUserByName('system'));
        $this->assertEquals($user_test,
                            $this->dbi->getUserByName('test'));


        $this->assertEquals(1, $this->dbi->getUserIDFromName('system'));
        $this->assertEquals(5, $this->dbi->getUserIDFromName('test'));

        // TODO can't test this without the unhashed password
        // $this->dbi->getUserData($user,$pw);

        $this->assertEquals(array($user_bollmann, $user_test),
                            $this->dbi->getUserList());
    }
    public function testUserActions() {
        // create user
        $this->dbi->createUser("anselm", "blabla", "0");
        $expected = $this->createXMLDataSet("created_user.xml");

        // TODO password hash breaks table equality, idk why
        $this->assertTablesEqual($expected->getTable("users"),
                                 $this->getConnection()->createQueryTable("users",
                                    "SELECT id,name,admin FROM users WHERE name='anselm';"));

        //changePassword($name, $passwd);
        //changeProjectUsers($pid, $users);

        $this->dbi->deleteUser("anselm");
        $this->assertEquals(0, $this->getConnection()->createQueryTable("users",
                               "SELECT id,name,admin FROM users WHERE name='anselm';")->getRowCount());

        $this->dbi->toggleAdminStatus("test");
        $this->assertEquals(1, $this->getConnection()->createQueryTable("testuser",
                               "SELECT admin FROM users WHERE name='test';")->getValue(0, "admin"));

        $this->dbi->toggleAdminStatus("test");
        $this->assertEquals(0, $this->getConnection()->createQueryTable("testuser",
                               "SELECT admin FROM users WHERE name='test';")->getValue(0, "admin"));
    }

    public function testUserSettings() {
        $test_settings = array("lines_per_page" => "30",
                               "lines_context" => "5",
                               "columns_order" => null,
                               "columns_hidden" => null,
                               "show_error" => "1");
        $this->assertEquals($test_settings,
                            $this->dbi->getUserSettings("test"));

        $this->dbi->setUserSettings("test", "50", "3");
        $this->assertEquals(50,
            $this->getConnection()->createQueryTable("settings",
            "SELECT lines_per_page FROM users WHERE name='test';")->getValue(0, "lines_per_page"));
        $this->assertEquals(3,
            $this->getConnection()->createQueryTable("settings",
            "SELECT lines_context FROM users WHERE name='test';")->getValue(0, "lines_context"));

        $this->dbi->setUserSetting("test", "columns_order", "7/6,6/7");
        $this->assertEquals("7/6,6/7",
            $this->getConnection()->createQueryTable("settings",
            "SELECT columns_order FROM users WHERE name='test';")->getValue(0, "columns_order"));

        //$this->dbi->markLastPosition($file, $line, $uname);

        // toggleNormStatus
    }

}

?>
