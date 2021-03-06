<?php 
/*
 * Copyright (C) 2015-2017 Marcel Bollmann <bollmann@linguistics.rub.de>
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
$CORA_DIR = include 'cora_config_webdir.php';
require_once "{$CORA_DIR}/lib/cfg.php";
require_once "{$CORA_DIR}/lib/connect.php";
require_once "{$CORA_DIR}/lib/connect/ProjectAccessor.php";
require_once "{$CORA_DIR}/lib/exporter.php";
require_once "{$CORA_DIR}/lib/localeHandler.php";
require_once "{$CORA_DIR}/lib/xmlHandler.php";

class CloningTools {
  private $dbo;
  private $dbi;
  private $lh;
  private $exp = null;
  private $xml = null;
  private $pa = null;
  private $all_files = null;
  private $all_projects = null;
  private $tmp_files = array();

  public function __construct() {
    $dbinfo = Cfg::get('dbinfo');
    $this->lh = new LocaleHandler();
    $this->dbo = new PDO('mysql:host='.$dbinfo['HOST']
                         .';dbname='.$dbinfo['DBNAME']
                         .';charset=utf8',
                         $dbinfo['USER'],
                         $dbinfo['PASSWORD']);
    $this->dbo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $this->dbi = new DBInterface($dbinfo, $this->lh);
  }

  function __destruct() {
    try {
      foreach ($this->tmp_files as $tmpfile) {
        unlink($tmpfile);
      }
    }
    catch (Exception $ex) { }
  }

  public function getTempnam() {
    $tmpnam = tempnam(sys_get_temp_dir(), 'cora_clf');
    $this->tmp_files[] = $tmpnam;
    return $tmpnam;
  }

  public function getFile($fid) {
    if (is_null($this->all_files)) {
      $this->all_files = $this->dbi->getFiles();
    }
    foreach ($this->all_files as $file) {
      if ($file['id'] == $fid)
        return $file;
    }
  }

  public function getProject($pid) {
    if (is_null($this->all_projects)) {
      $this->all_projects = $this->projectAccessor()->getAllProjects();
    }
    foreach ($this->all_projects as $project) {
      if ($project['id'] == $pid)
        return $project;
    }
  }

  public function exporter() {
    if (is_null($this->exp))
        $this->exp = new Exporter($this->dbi, $this->lh);
    return $this->exp;
  }

  public function xmlHandler() {
    if (is_null($this->xml))
        $this->xml = new XMLHandler($this->dbi, $this->lh);
    return $this->xml;
  }

  public function projectAccessor() {
    if (is_null($this->pa))
      $this->pa = new ProjectAccessor($this->dbi, $this->dbo);
    return $this->pa;
  }

  public function fetchFileOptions($fid, $pid) {
    $options = array();
    $creator = 1;
    $file = $this->getFile($fid);
    $options = array(
      "name" => $file['fullname'],
      "sigle" => $file['sigle'],
      "project" => (is_null($pid) ? $file['project_id'] : $pid),
      "tagsets" => array()
    );
    if (is_null($pid)) {
      $options["name"] .= " (clone)";
    }
    $creator = $file['creator_id'];
    $tagsets = $this->dbi->getTagsetsForFile($fid);
    foreach ($tagsets as $ts) {
      $options["tagsets"][] = $ts['id'];
    }
    return array($options, $creator);
  }

  public function cloneFile($fid, $pid) {
    list($options, $uid) = $this->fetchFileOptions($fid, $pid);

    print "Cloning file {$fid}: [{$options['sigle']}] {$options['name']}\n";
    print "  - exporting...";
    $tmpfile = $this->getTempnam();
    $io = fopen($tmpfile, 'w');
    $this->exporter()->export($fid, ExportType::CoraXML, null, $io);
    fclose($io);

    print "\n  - importing...";
    $data = array('tmp_name' => $tmpfile, 'name' => $tmpfile);
    $status = $this->xmlHandler()->import($data, $options, $uid);
    if (!isset($status['success']))
      throw new Exception("Unexpected return value of import()");
    if (!$status['success']) {
      $message = "Import returned success=false\n";
      $message .= implode("\n", $status['errors']) . "\n";
      throw new Exception($message);
    }
    print "\nDone cloning file {$fid}.\n";

    if (count($status['warnings']) > 0) {
      $n = count($status['warnings']);
      print "There were {$n} warning(s):\n";
      print implode("\n", $status['warnings']);
      print "\n";
    }
  }

  public function cloneProject($pid, $name, $with_users=false, $with_files=false) {
    $project = $this->getProject($pid);
    $pa = $this->pa;

    print "Cloning project {$pid}: {$project['name']}\n";
    if (is_null($name)) {
      $name = $project['name'] . " (clone)";
    }
    $status = $this->dbi->createProject($name);
    if (!isset($status['success']))
      throw new Exception("Unexpected return value of createProject()");
    if (!$status['success']) {
      $message = "createProject returned success=false\n";
      $message .= implode("\n", $status['errors']) . "\n";
      throw new Exception($message);
    }
    $pid_new = $status['pid'];

    $pa->setSettingsArray($pid_new, $pa->getSettings($pid));
    $pa->setAssociatedTagsetDefaults($pid_new,
                                     $pa->getAssociatedTagsetDefaults($pid));

    if ($with_users) {
      print "  - cloning user2project associations...";
      $pa->setAssociatedUsers($pid_new, $pa->getAssociatedUserIDs($pid));
      print "\n";
    }
    if ($with_files) {
      print "  - cloning all files...\n";
      foreach ($pa->getAssociatedFiles($pid) as $file) {
        $this->cloneFile($file['id'], $pid_new);
      }
    }
    print "Done cloning project {$pid}.\n";
  }

}

?>
