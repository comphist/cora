<?php
/** unit tests for connect php
 *  02/2012 Florian Petran
 *  TODO
 *  - refactor the fixture - it takes too long to run the tests
 *  - base on dbunit - methods writing to the database currently
 *    test against the output of methods reading from it, while
 *    the database state should be tested separately
 **/
require_once "PHPUnit/Framework/TestCase.php";
require_once "../lib/connect.php";

class DBInterfaceTest extends PHPUnit_Framework_TestCase {
    protected $dbi;
    protected $dbc;
    protected $mysqlcall;

    protected function setUp() {
        $this->mysqlcall = "mysql -u{$GLOBALS["DB_USER"]} -p{$GLOBALS["DB_PASSWD"]}";
        system($this->mysqlcall." < coratest-setup.sql" );
        system($this->mysqlcall." {$GLOBALS["DB_DBNAME"]} < coratest.sql" );
        $this->dbc = new DBConnector("localhost",
                                     $GLOBALS["DB_USER"],
                                     $GLOBALS["DB_PASSWD"],
                                     $GLOBALS["DB_DBNAME"]);
        $this->dbi = new DBInterface($this->dbc);
    }

    protected function tearDown() {
        system($this->mysqlcall." < coratest-teardown.sql");
    }

    public function testGetUser() {
        // stupid: php converts the dates into localtime,
        // so all hours are actually -1 in the db
        $user_system = array("id" => "1",
                             "name" => "system",
                             "admin" => "1",
                             "lastactive" => "2013-01-16 15:22:57");
        $user_bollmann = array("id" => "3",
                               "name" => "bollmann",
                               "admin" => "1",
                               "lastactive" => "2013-02-04 12:29:04");
        $user_test = array("id" => "5",
                           "name" => "test",
                           "admin" => "0",
                           "lastactive" => "2013-01-22 16:38:32");


        $this->assertEquals($user_system,
                            $this->dbi->getUserById(1));
        $this->assertEquals($user_bollmann,
                            $this->dbi->getUserById(3));

        $this->assertEquals($user_system,
                            $this->dbi->getUserByName('system'));
        $this->assertEquals($user_bollmann,
            $this->dbi->getUserByName('bollmann'));


        $this->assertEquals(1, $this->dbi->getUserIDFromName('system'));
        $this->assertEquals(3, $this->dbi->getUserIDFromName('bollmann'));
        $this->assertEquals(5, $this->dbi->getUserIDFromName('test'));

        // TODO can't test this without the password
        // $this->dbi->getUserData($user,$pw);

        $this->assertEquals(array($user_bollmann, $user_test),
                            $this->dbi->getUserList());
    }

    /*
    public function testGetTagsets() {
        $this->dbi->getTagsets(); // list of all tagsets
        $this->dbi->getTagset(); // array with a full tagset
    }
    */

    public function testUserActions() {
        // create user
        $this->dbi->createUser("anselm", "blabla", "0");
        $created = $this->dbi->getUserByName("anselm");
        $this->assertEquals($created["name"], "anselm");
        $this->assertEquals($created["id"], "6");
        $this->assertEquals($created["admin"], "0");

        //changePassword($name, $passwd);
        //changeProjectUsers($pid, $users);

        $this->dbi->deleteUser("bollmann");
        $this->assertEquals(null, $this->dbi->getUserByName("bollmann"));

        $this->dbi->toggleAdminStatus("test");
        $user = $this->dbi->getUserByName("test");
        $this->assertEquals(1, $user["admin"]);

        $this->dbi->toggleAdminStatus("test");
        $user = $this->dbi->getUserByName("test");
        $this->assertEquals(0, $user["admin"]);
    }

    public function testUserSettings() {
        $test_settings = array("lines_per_page" => "30",
                               "lines_context" => "5",
                               "columns_order" => null,
                               "columns_hidden" => null,
                               "show_error" => "1");

        $this->assertEquals($test_settings,
                            $this->dbi->getUserSettings("test"));

        $test_settings["lines_per_page"] = "50";
        $test_settings["lines_context"] = "3";
        $this->dbi->setUserSettings("test", "50", "3");

        $this->assertEquals($test_settings,
                            $this->dbi->getUserSettings("test"));

        $this->dbi->setUserSetting("test", "columns_order", "7/6,6/7");
        $test_settings["columns_order"] = "7/6,6/7";
        $this->assertEquals($test_settings,
                            $this->dbi->getUserSettings("test"));


        //$this->dbi->markLastPosition($file, $line, $uname);

        // toggleNormStatus
    }

    /*
    public function testDocument() {
        insertNewDocument
        queryForMetadata($key, $value); // e.g. find doc by sigle or name
        insertNewDocument($options, $data);
        lockFile($fid, $uname);
        getLockedFiles($uname);
        unlockFile($fid,$uname,$force);
        unlockFile($fid);
        openFile();
        deleteFile();
        getFiles();
        getFilesForUser($uname);

        getMaxLinesNo($fid);
        getAllLines($fid);

        getAllSuggestions($fid, $line_id);
        getLines($fid, $from, $number);

        saveLines($fid, $lasteditedrow, $lines);
    }
    public function testUserPermissions() {
        isAllowedToDeleteFile($fid, $user);
        isAllowedToOpenFile($fid, $user);
    }
     */
    public function testProjects() {
        $this->assertEquals(array(array("id" => "1",
                                  "name" => "Default-Gruppe")),
                            $this->dbi->getProjects());
        //$this->dbi->getProjectUsers();
        //$this->dbi->getProjectsForUser();
        //$this->dbi->createProject($name);
        //$this->dbi->deleteProject($pid);
    }
/*
    public function testTagsAndTagsets() {
        getAllSuggestions($fid, $line_id);
        importTaglist($taglist, $tagset_name);

    }*/

}
?>
