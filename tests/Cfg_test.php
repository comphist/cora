<?php

require_once "{$GLOBALS['CORA_WEB_DIR']}/lib/cfg.php";

class Cora_Tests_Cfg_test extends PHPUnit_Framework_TestCase {
  protected function setUp() {
    Cfg::load_user_opts(__DIR__ . "/data/test_config.php");

    $this->tmpfile = tempnam(sys_get_temp_dir(), 'MyFileName');
    register_shutdown_function(function() {
      if(file_exists($this->tmpfile)) {
        unlink($this->tmpfile);
      }
    });
  }

  public function testGetDefaultDBInfo() {
    $dbinfo = Cfg::get('dbinfo');
    $this->assertEquals("cora", $dbinfo['USER']);
    $this->assertEquals("cora", $dbinfo['DBNAME']);
    $this->assertEquals("Corpus Annotator", Cfg::get("longtitle"));
  }

  public function testGetUserSettings() {
    $this->assertEquals("zh-ZH", Cfg::get("default_language"));
    $this->assertEquals("My fancy title", Cfg::get("title"));
  }

  public function testSetSettingAlreadyUserDefined() {
    $this->assertEquals("zh-ZH", Cfg::get("default_language"));
    Cfg::set("default_language", "xy-XY");
    $this->assertEquals("xy-XY", Cfg::get("default_language"));
  }

  public function testSetSettingOverrideDefault() {
    $this->assertEquals("Corpus Annotator", Cfg::get("longtitle"));
    Cfg::set("longtitle", "My fancy long title");
    $this->assertEquals("My fancy long title", Cfg::get("longtitle"));
  }

  public function testSaveUserSettings() {
    Cfg::set("default_language", "xy-XY");
    Cfg::set("longtitle", "My fancy long title");
    Cfg::save_user_opts($this->tmpfile);
    $this->assertTrue(file_exists($this->tmpfile));
    $datafile = file_get_contents($this->tmpfile);
    $this->assertEquals("<?php ", substr($datafile, 0, 6));
    $saved_opts = eval(substr($datafile, 6));
    $this->assertEquals("xy-XY", $saved_opts["default_language"]);
    $this->assertEquals("My fancy long title", $saved_opts["longtitle"]);
    $this->assertEquals("My fancy title", $saved_opts["title"],
                        "Previously defined user setting should be retained");
  }
}

?>
