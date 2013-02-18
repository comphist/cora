<?php
require_once "DB_fixture.php";
require_once "../lib/connect.php";

class Cora_Tests_DBInterface_test extends Cora_Tests_DbTestCase {
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
            "currentmod_id" => "14",
            "header" => null
        );
        $expected_t3 = array(
            "id" => "5",
            "sigle" => "t3",
            "fullname" => "dummy without tokens",
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


        $this->assertEquals(array('file_id' => '3', 'file_name' => 'test-dummy'),
                            $this->dbi->getLockedFiles("bollmann"));

        // getFiles also gives lots of names for display purposes
        $getfiles_expected = array(
            array_merge($expected_t1, array('project_name' => 'Default-Gruppe',
                                            'opened' => 'bollmann',
                                            'creator_name' => 'system',
                                            'changer_name' => 'bollmann')),
            array_merge($expected_t2, array('project_name' => 'Default-Gruppe',
                                            'opened' => null,
                                            'creator_name' => 'system',
                                            'changer_name' => 'system')),
            array_merge($expected_t3, array('project_name' => 'Default-Gruppe',
                                            'opened' => null,
                                            'creator_name' => 'system',
                                            'changer_name' => 'system'))
        );

        $this->assertEquals($getfiles_expected, $this->dbi->getFiles());
        $this->assertEquals($getfiles_expected,
                            $this->dbi->getFilesForUser("bollmann"));

        $this->dbi->markLastPosition("3", "2");
        $this->assertEquals("2",
            $this->getConnection()->createQueryTable("currentpos",
            "SELECT currentmod_id FROM text WHERE id=3;")->getValue(0, "currentmod_id"));
    }

    public function testLockUnlock() {
        // locking a file that doesn't exist
        $lock_result = $this->dbi->lockFile("512", "test");
        $this->assertEquals(array("success" => false),
                            $lock_result);

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
                'anno_morph' => '3.Pl.Past.Konj',
                'comment' => null
            ),
            array(
                'id'          => '2',
                'trans'       => 'pi$t||',
                'utf'         => 'pist',
                'tok_id'      => '2',
                'full_trans'  => 'pi$t||u||s',
                'num'         => '1',
                'suggestions' => array(
                    array( 'POS' => 'PPOSAT',
                           'morph' => 'Fem.Nom.Sg',
                           'score' => null)
                ),
                'anno_POS'    => "PPOSAT",
                'anno_morph'  => "Fem.Nom.Sg",
                'comment' => null
            ),
            array(
                'id'          => '3',
                'trans'       => 'u||',
                'utf'         => 'u',
                'tok_id'      => '2',
                'full_trans'  => 'pi$t||u||s',
                'num'         => '2',
                'general_error' => 1,
                'suggestions' => array(),
                'comment' => null
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
                'anno_morph'  => '3.Pl.Pres.Konj',
                'comment' => null
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
                'anno_morph'  => '*.Gen.Pl',
                'anno_lemma'  => 'lemma',
                'comment' => null
            ),
            array(
                'id'          => '6',
                'trans'       => 'vunf=tusent#vnd#vierhundert#vn-(=)sechzig',
                'utf'         => 'vunftusentvndvierhundertvnsechzig',
                'tok_id'      => '4',
                'full_trans'  => 'vunf=tusent#vnd#vierhundert#vn-(=)sechzig',
                'num'         => '5',
                'suggestions' => array(),
                'anno_norm'   => 'norm',
                'comment' => null
            ),
            array(
                'id' => '7',
                'trans' => 'kunnen',
                'utf' => 'kunnen',
                'tok_id' => '5',
                'full_trans' => 'kunnen.(.)',
                'num' => '6',
                'general_error' => 1,
                'suggestions' => Array (),
                'anno_lemma' => 'deletedlemma',
                'comment' => null
            ),
            array(
                'id' => '8',
                'trans' => '.',
                'utf' => '.',
                'tok_id' => '5',
                'full_trans' => 'kunnen.(.)',
                'num' => '7',
                'suggestions' => Array (),
                'comment' => null
            ),
            array(
                'id' => '9',
                'trans' => '(.)',
                'utf' => '.',
                'tok_id' => '5',
                'full_trans' => 'kunnen.(.)',
                'num' => '8',
                'suggestions' => Array (),
                'anno_norm' => 'deletednorm',
                'comment' => null
            ));

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
        $this->assertEquals("0", $this->dbi->getMaxLinesNo("512"));

        //insertNewDocument($options, $data);
        //getAllSuggestions($fid, $line_id);
        //saveLines($fid, $lasteditedrow, $lines);
    }

    public function testProjects() {
        $this->assertEquals(array(
                                array('id' => '1', 'name' => 'Default-Gruppe')
                            ),
                            $this->dbi->getProjects());
        $this->assertEquals(array(array('project_id' => '1', 'username' => 'bollmann')),
                            $this->dbi->getProjectUsers());

        $this->assertEquals(array(array('id' => '1', 'name' => 'Default-Gruppe')),
                            $this->dbi->getProjectsForUser("bollmann"));

        $this->dbi->createProject("testproject");
        $expected = $this->createXMLDataSet("created_project.xml");

        $this->assertTablesEqual($expected->getTable("project"),
            $this->getConnection()->createQueryTable("project",
            "SELECT * FROM project WHERE name='testproject'"));

        $this->assertTrue($this->dbi->deleteProject("2"));
        $this->assertEquals("0",
            $this->getConnection()->createQueryTable("project",
            "SELECT * FROM project WHERE id=2")->getRowCount());

        $this->assertFalse($this->dbi->deleteProject("1"));
        $this->assertEquals("1",
            $this->getConnection()->createQueryTable("project",
            "SELECT * FROM project WHERE id=1")->getRowCount());

        $users = array("test");
        $this->dbi->changeProjectUsers("1", $users);
        $this->assertEquals("1",
            $this->getConnection()->createQueryTable("projectusers",
            "SELECT * FROM user2project WHERE user_id=5 AND project_id=1")->getRowCount());
    }

    /*
    public function testGetAllLines() {
        $this->assertEquals($lines_expected,
                            $this->dbi->getAllLines("3"));
    }
     */

    public function testSaveLines() {
        //saveLines($fid, $lastedited, $lines);
        $_SESSION["user"] = "bollmann";
        $result = $this->dbi->saveLines("3", "9",
            array(
                array('id' => '2',
                      'anno_POS' => 'PPOSS',
                      'anno_morph' => 'Fem.Nom.Sg'),
                array('id' => '3',
                      'anno_POS' => 'VVFIN',
                      'anno_morph' => '3.Pl.Past.Konj'),
                array('id' => '4',
                      'anno_POS' => null),
                array('id' => '5',
                      'anno_POS' => 'VVPP',
                      'anno_lemma' => 'newlemma'),
                array('id' => '6',
                      'anno_norm' => 'newnorm'),
                array('id' => '7',
                      'anno_POS' => 'NN',
                      'anno_morph' => 'Neut.Dat.Pl',
                      'general_error' => false,
                      'anno_lemma' => null),
                array('id' => '8',
                      'anno_norm' => 'bla',
                      'general_error' => true),
                array('id' => '9',
                      'anno_morph' => 'Neut.Nom.Sg',
                      'anno_lemma' => 'blatest',
                      'anno_norm' => "")
            ));
        $this->assertFalse($result);
        $expected = $this->createXMLDataset("saved_lines.xml");
        $this->assertTablesEqual($expected->getTable("tag_suggestion"),
            $this->getConnection()->createQueryTable("tag_suggestion",
             "SELECT id,selected,source,tag_id,mod_id "
            ."FROM tag_suggestion WHERE mod_id > 2 and mod_id < 9"));
        $this->assertTablesEqual($expected->getTable("tag"),
            $this->getConnection()->createQueryTable("tag",
            "SELECT * FROM tag WHERE id > 509"));
        $this->assertTablesEqual($expected->getTable("mod2error"),
            $this->getConnection()->createQueryTable("mod2error",
            "SELECT * FROM mod2error WHERE mod_id IN (7, 8, 9)"));
    }

    /*
    public function testDeleteFile() {
        $this->dbi->deleteFile("3");
        // TODO of course it needs to test if the tokens, etc. are also
        // deleted, but cora relies on fk constraints for that, which are
        // ignored in our test db
        $this->assertEquals(0,
            $this->query("SELECT * FROM {$GLOBALS["DB_DBNAME"]}.text WHERE ID=3")->getRowCount());
    }*/

}

?>
