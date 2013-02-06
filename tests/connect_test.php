<?php
require_once "PHPUnit/Framework/TestCase.php";
require_once "../lib/connect.php";

class connectTest extends PHPUnit_Framework_TestCase {
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

        createUser($name, $passwd, $admin);
        changePassword($name, $passwd);
        changeProjectUsers($pid, $users);
        deleteUser($name);
        toggleAdminStatus($name);

        $this->dbi->getUserSettings($name);
        setUserSettings($uname, $linesperpage, $linescontext);
        setUserSetting($uname, $key, $value);

        markLastPosition($file, $line, $uname);

        // toggleNormStatus

        $this->assertEquals(1, 1);
    }
    /*
    public function testDocument() {
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
    public function testProjects() {
        getProjects();
        getProjectUsers();
        getProjectsForUser();
        createProject($name);
        deleteProject($pid);
    }
    public function testTagsAndTagsets() {
        getAllSuggestions($fid, $line_id);
        importTaglist($taglist, $tagset_name);

    }*/

}
?>
