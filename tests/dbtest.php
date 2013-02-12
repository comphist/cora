<?php
require_once "PHPUnit/Extensions/Database/TestCase.php";
require_once "../lib/connect.php";
//require_once "array_dataset.php";

/** Base class for all DB Tests */
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

    public function last_error($result) {
        return $result->errorInfo();
    }

    // copypasta from DBConnector
    public function last_insert_id() {
        $q = $this->query("SELECT LAST_INSERT_ID()");
        $r = $this->fetch_array($q);
        return $r[0];
    }
}


class interfaceTest extends Cora_Tests_DbTestCase {
    protected $dbi;
    protected $backupGlobalsBlacklist = array('_SESSION');

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
        // creating a user that already exists should fail
        $this->assertFalse($this->dbi->createUser("test", "blabla", "0"));

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
        $this->assertFalse($this->dbi->setUserSetting("test", "invalid_field", "somevalue"));

        // toggleNormStatus
        // isAllowedToDeleteFile($fid, $user)
        // isAllowedToOpenFile($fid, $user)
    }

    public function testTextQuery() {
        $expected_t1 = array(
            "id" => "3",
            "sigle" => "t1",
            "fullname" => "test-dummy",
            "project_id" => "1",
            "created" => "2013-01-22 14:30:30",
            "creator_id" => "1",
            "changed" => "0000-00-00 00:00:00",
            "changer_id" => "3",
            "currentmod_id" => null,
            "header" => null
        );
        $expected_t2 = array(
            "id" => "4",
            "sigle" => "t2",
            "fullname" => "yet another dummy",
            "project_id" => "1",
            "created" => "2013-01-31 13:13:20",
            "creator_id" => "1",
            "changed" => "0000-00-00 00:00:00",
            "changer_id" => "1",
            "currentmod_id" => null,
            "header" => null
        );

        $actual = $this->dbi->queryForMetadata("sigle", "t1");
        $this->assertEquals($expected_t1, $actual);
        $actual = $this->dbi->queryForMetadata("fullname", "yet another dummy");
        $this->assertEquals($expected_t2, $actual);

        $this->dbi->markLastPosition("3", "2");
        $this->assertEquals("2",
            $this->getConnection()->createQueryTable("currentpos",
            "SELECT currentmod_id FROM text WHERE id=3;")->getValue(0, "currentmod_id"));


        $this->assertEquals(array($expected_t1),
                            $this->dbi->getLockedFiles("bollmann"));

        //getFiles();
        //getFilesForUser($uname);
    }

    public function testLockUnlock() {
        // locking a file that doesn't exist
        // TODO currently this will succeed since the test db doesn't have
        // fk constraints.
        //$lock_result = $this->dbi->lockFile("512", "test");
        //$this->assertEquals(array(),
                            //$lock_result);

        // locking a file that is already locked returns info on the lock
        $lock_result = $this->dbi->lockFile("3", "test");
        $this->assertEquals(array("success" => false,
                                  "lock" => array("2013-02-05 13:00:40",
                                                  "bollmann")),
                            $lock_result);
        // check if the database still has the lock belonging to bollmann
        $this->assertEquals("3",
            $this->getConnection()->createQueryTable("testlock",
            "SELECT user_id FROM locks WHERE text_id=3;")->getValue(0, "user_id"));


        // test force unlock with specification of user name
        $this->dbi->unlockFile("3", "bollmann", "true");
        $this->assertEquals("0",
            $this->getConnection()->createQueryTable("locks",
            "SELECT * FROM locks WHERE text_id=3;")->getRowCount());

        // test locking a new file
        $lock_result = $this->dbi->lockFile("4", "test");
        $this->assertEquals(array("success" => true, "lockCounts" => 0),
                            $lock_result);
        $this->assertEquals("4",
            $this->getConnection()->createQueryTable("testlock",
            "SELECT text_id FROM locks WHERE user_id=5;")->getValue(0, "text_id"));

        // test unlocking with force=false
        // fake login as bollmann
        $_SESSION["user_id"] = "3";
        // this should fail
        $lock_result = $this->dbi->unlockFile("4");
        $this->assertEquals("1",
            $this->getConnection()->createQueryTable("testlock",
            "SELECT * FROM locks WHERE text_id=4;")->getRowCount());

        // fake login as test
        $_SESSION["user_id"] = "5";
        // this should succeed
        $lock_result = $this->dbi->unlockFile("4");
        $this->assertEquals("0",
            $this->getConnection()->createQueryTable("testlock",
            "SELECT * FROM locks WHERE text_id=4;")->getRowCount());
    }

    public function testOpenText() {
        // test file opening
        $_SESSION["user"] = "bollmann";
        $_SESSION["user_id"] = "3";
        $this->assertEquals(
            array("lastEditedRow" => -1,
                  "data" => array('id' => '3',
                                  'sigle' => 't1',
                                  'fullname' => 'test-dummy',
                                  'project_id' => '1',
                                  'created' => '2013-01-22 14:30:30',
                                  'creator_id' => '1',
                                  'changed' => '0000-00-00 00:00:00',
                                  'changer_id' => '3',
                                  'currentmod_id' => null,
                                  'header' => null,
                                  'tagset_id' => '1'),
                  "success" => true),
            $this->dbi->openFile("3")
        );

        $_SESSION["user"] = "test";
        $_SESSION["user_id"] = "5";
        //$this->query("UPDATE coratest.text SET currentmod_id=1 WHERE id=4");
        $this->assertEquals(
            array("lastEditedRow" => 1,
                  "data" => array('id' => '4',
                                  'sigle' => 't2',
                                  'fullname' => 'yet another dummy',
                                  'project_id' => '1',
                                  'created' => '2013-01-31 13:13:20',
                                  'creator_id' => '1',
                                  'changed' => '0000-00-00 00:00:00',
                                  'changer_id' => '1',
                                  'currentmod_id' => '14',
                                  'header' => null,
                                  'tagset_id' => '1'),
                  "success" => true),
            $this->dbi->openFile("4")
        );

        // opening a file that's already opened by someone else must fail
        $this->assertEquals(array("success" => false),
                            $this->dbi->openFile("3"));
    }
    public function testGetLines() {
        $lines_expected = array(
           array (
                'id' => '1',
                'trans' => '*{A*4}n$helm%9',
                'utf' => 'Anshelm\'',
                'tok_id' => '1',
                'full_trans' => '*{A*4}n$helm%9',
                'num' => '0',
                'suggestions' => array (
                    array ( 'POS' => 'VVFIN',
                            'morph' => '3.Pl.Past.Konj',
                            'score' => '0.97')
                ),
                'anno_POS' => 'VVFIN',
                'anno_morph' => '3.Pl.Past.Konj'
            ),
            array(
                'id'          => '2',
                'trans'       => 'pi$t||',
                'utf'         => 'pist',
                'tok_id'      => '2',
                'full_trans'  => 'pi$t||u||s',
                'num'         => '1',
                'suggestions' => array()
            ),
            array(
                'id'          => '3',
                'trans'       => 'u||',
                'utf'         => 'u',
                'tok_id'      => '2',
                'full_trans'  => 'pi$t||u||s',
                'num'         => '2',
                'general_error' => 1,
                'suggestions' => array()
            ),
            array(
                'id'          => '4',
                'trans'       => 's',
                'utf'         => 's',
                'tok_id'      => '2',
                'full_trans'  => 'pi$t||u||s',
                'num'         => '3',
                'suggestions' => array(),
                'anno_POS'    => 'VVFIN',
                'anno_morph'  => '3.Pl.Pres.Konj'
            ),
            array(
                'id'          => '5',
                'trans'       => 'aller#lieb$tev',
                'utf'         => 'allerliebstev',
                'tok_id'      => '3',
                'full_trans'  => 'aller#lieb$tev',
                'num'         => '4',
                'suggestions' => array(),
                'anno_POS'    => 'PDS',
                'anno_morph'  => '*.Gen.Pl'
            ),
            array(
                'id'          => '6',
                'trans'       => 'vunf=tusent#vnd#vierhundert#vn-(=)sechzig',
                'utf'         => 'vunftusentvndvierhundertvnsechzig',
                'tok_id'      => '4',
                'full_trans'  => 'vunf=tusent#vnd#vierhundert#vn-(=)sechzig',
                'num'         => '5',
                'suggestions' => array()
            ),
            array(
                'id' => '7',
                'trans' => 'kunnen',
                'utf' => 'kunnen',
                'tok_id' => '5',
                'full_trans' => 'kunnen.(.)',
                'num' => '6',
                'general_error' => 1,
                'suggestions' => Array ()
            ),
            array(
                'id' => '8',
                'trans' => '.',
                'utf' => '.',
                'tok_id' => '5',
                'full_trans' => 'kunnen.(.)',
                'num' => '7',
                'suggestions' => Array ()
            ),
            array(
                'id' => '9',
                'trans' => '(.)',
                'utf' => '.',
                'tok_id' => '5',
                'full_trans' => 'kunnen.(.)',
                'num' => '8',
                'suggestions' => Array ()
            ));

        $this->assertEquals($lines_expected,
                            $this->dbi->getAllLines("3"));

        $this->assertEquals($lines_expected,
                            $this->dbi->getLines("3", "0", "10"));

        $lines_chunk = array_chunk($lines_expected, 3);
        $this->assertEquals($lines_chunk[0],
                            $this->dbi->getLines("3", "0", "3"));
        $this->assertEquals($lines_chunk[1],
                            $this->dbi->getLines("3", "3", "3"));

        // querying over the maximum lines number gives an empty array
        $this->assertEquals(array(),
                            $this->dbi->getLines("3", "500", "10"));
        // querying a file that has no tokens gives an empty array as well
        $this->assertEquals(array(),
                            $this->dbi->getLines("5", "0", "10"));

        $this->assertEquals("9", $this->dbi->getMaxLinesNo("3"));
        $this->assertEquals("0", $this->dbi->getMaxLinesNo("5"));

        //insertNewDocument($options, $data);
        //deleteFile($fid);
        //getAllSuggestions($fid, $line_id);
        //saveLines($fid, $lasteditedrow, $lines);
    }

}

?>
