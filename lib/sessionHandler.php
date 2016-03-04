<?php 
/*
 * Copyright (C) 2015 Marcel Bollmann <bollmann@linguistics.rub.de>
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

/** @file sessionHandler.php
 * Manage session-specific settings.
 *
 * @author Marcel Bollmann
 * @date January 2012
 */

require_once( "cfg.php" );
require_once( "connect.php" );
require_once( "xmlHandler.php" );
require_once( "commandHandler.php" );
require_once( "exporter.php" );
require_once( "automaticAnnotation.php" );

/** Manages session-specific data.
 *
 * This class keeps track of user settings and general session
 * data. It wraps most of the methods of the database interface
 * (DBInterface), inserting session data and checking for
 * administrator privileges if needed.
 */
class CoraSessionHandler {
  private $db; /**< A DBInterface object. */
  private $lh; /**< A LocaleHandler object. */

  private $timeout = 30; // session timeout in minutes

  /** Create a new CoraSessionHandler.
   *
   * Initializes a session, constructs a new DBInterface, and sets
   * defaults for various session values if required.
   */
  function __construct(DBInterface $db, LocaleHandler $lh) {
    session_name(strtoupper(Cfg::get('session_name')));
    session_start();

    $this->db = $db;
    $this->lh = $lh;

    $defaults = array( "locale"      => Cfg::get('default_language'),
		       "loggedIn"    => false,
		       "admin"       => false,
		       "failedLogin" => false,
		       "currentName" => null,
		       "currentFileId" => null );

    foreach($defaults as $key => $default) {
      if(!array_key_exists($key, $_SESSION)) {
        $_SESSION[$key] = $default;
      }
    }

    $this->setLocale($_SESSION["locale"]);
  }

  /** Set a locale.
   */
  public function setLocale($locale=null) {
    $locale = $this->lh->set($locale);
    $_SESSION["locale"] = $locale;
    return $locale;
  }

  public function setUserSettings($lpp,$cl){
    if($this->db->setUserSettings($_SESSION['user'],$lpp,$cl)){
      $_SESSION['noPageLines'] = $lpp;
      $_SESSION['contextLines'] = $cl;
      return true;
    }
    return false;
  }

  public function setUserSetting($name,$value) {
    $mapToSVAR = array("columns_hidden" => "hiddenColumns",
                       "text_preview" => "textPreview",
                       "show_error" => "showInputErrors",
                       "columns_order" => "editTableDragHistory");
    if($this->db->setUserSetting($_SESSION['user'],$name,$value)){
      if($name === "locale") {
        $value = $this->setLocale($value);
      } else {
        $key = array_key_exists($name, $mapToSVAR) ? $mapToSVAR[$name] : $name;
        $_SESSION[$key] = $value;
      }
      return true;
    }
    return false;
  }

  /** Wraps DBInterface::getTagsets(). */
  public function getTagsetList($class="pos", $orderby="name") {
    return $this->db->getTagsets($class, $orderby);
  }

  /** Wraps DBInterface::getTagset(). */
  public function getTagset($tagset) {
    $data = $this->db->getTagset($tagset);
    return array('success' => true, 'data' => $data);
  }

  /** Changes tagset associations for a file. */
  public function changeTagsetsForFile($fileid, $tagset_ids){
      if(!$_SESSION["admin"]) {
          return array('success' => false,
                       'errors' => array("Administrator privileges are "
                                         ." required for this action."));  //$LOCALE
      }
      // find out what changed
      $oldlinks = $this->db->getTagsetsForFile($fileid);
      $to_be_deleted = array();
      foreach($oldlinks as $tagset) {
          if(($key = array_search($tagset['id'], $tagset_ids)) !== false)
              unset($tagset_ids[$key]);  // delete IDs that needn't be changed
          else
              $to_be_deleted[] = $tagset['id'];
      }
      $to_be_added = $tagset_ids;  // all remaining IDs must be new

      // perform changes
      if(!empty($to_be_deleted)) {
          // security check
          foreach($to_be_deleted as $tagset) {
              if($num = $this->db->doAnnotationsExist($fileid, $tagset))
                  return array('success' => false,
                               'errors'  => array("Für das Tagset mit der ID "
                                                  ."{$tagset} gibt es derzeit noch "
                                                  ."{$num} eingetragene Annotationen."
                                                  ." Verknüpfungen für Tagsets, mit "
                                                  ."denen noch Annotationen verbunden"
                                                  ." sind, können aus Sicherheitsgründen"
                                                  ." über dieses Interface nicht"
                                                  ." aufgehoben werden."));  //$LOCALE
          }

          if(!$this->db->deleteTagsetsForFile($fileid, $to_be_deleted))
              return array('success' => false,
                           'errors'  => array("Konnte bestehende Tagset-"
                                              ."Verknüpfungen nicht aufheben. "));  //$LOCALEs
      }
      if(!empty($to_be_added))
          $this->db->addTagsetsForFile($fileid, $to_be_added);
      return array('success' => true);
  }

  /** Wraps DBInterface::fetchTagsetsForFile(). */
  public function fetchTagsetsForFile($fileid) {
    $data = $this->db->fetchTagsetsForFile($fileid);
    return array('success' => true, 'data' => $data);
  }

  /** Wraps DBInterface::getLemmaSuggestion(). */
  public function getLemmaSuggestion($linenum, $q, $limit) {
    $data = $this->db->getLemmaSuggestion($_SESSION['currentFileId'],
					  $linenum, $q, $limit);
    return $data;
  }

  /** Wraps DBInterface::getUserList(), checking for administrator
      privileges first. */
  public function getUserList() {
    if ($_SESSION["admin"]) {
        return array('success' => true,
                     'data' => $this->db->getUserList($this->timeout));
    }
    return array('success' => false,
                 'errors' => array("Administrator privileges are required "
                                   ." for this action."));  //$LOCALE
  }

  /** Wraps DBInterface::updateLastactive(), updating "last active"
      information for currently logged-in user, if any. */
  public function updateLastactive() {
    if(isset($_SESSION["user_id"]) && !empty($_SESSION["user_id"])) {
      $this->db->updateLastactive($_SESSION["user_id"]);
    }
  }

  /** Wraps DBInterface::createUser(), checking for administrator
      privileges first. */
  public function createUser( $username, $password, $admin ) {
    if (!$_SESSION["admin"])
      return array('success' => false);
    $status = $this->db->createUser($username, $password, $admin);
    return array('success' => (bool) $status);
  }

  /** Wraps DBInterface::changePassword(), checking for administrator
      privileges first. */
  public function changePassword($uid, $password) {
    if (!$_SESSION["admin"])
      return array('success' => false);
    $status = $this->db->changePassword($uid, $password);
    return array('success' => (bool) $status);
  }

  /** Wraps DBInterface::changePassword(), intended for users
      changing their own passwords. */
  public function changeUserPassword( $oldpw, $newpw ) {
    if ($this->db->getUserData($_SESSION["user"], $oldpw)) {
      $this->db->changePassword($_SESSION["user_id"], $newpw);
      return array("success"=>true);
    }
    return array("success"=>false, "errcode"=>"oldpwmm");
  }

  /** Wraps DBInterface::deleteUser(), checking for administrator
      privileges first. */
  public function deleteUser($uid) {
    if (!$_SESSION["admin"])
        return array('success' => false);
    $status = $this->db->deleteUser($uid);
    return array('success' => (bool) $status);
  }

  /** Wraps DBInterface::toggleAdminStatus(), checking for
      administrator privileges first. */
  public function toggleAdminStatus($uid) {
    if (!$_SESSION["admin"])
      return array('success' => false);
    $status = $this->db->toggleAdminStatus($uid);
    return array('success' => (bool) $status);
  }

  /** Wraps XMLHandler::import() */
  public function importFile($xmldata, $options) {
    $xml = new XMLHandler($this->db);
    return $xml->import($xmldata, $options, $_SESSION["user_id"]);
  }

  /** Checks and parses the logfile for the current file import. */
  public function getImportStatus() {
    $logstat = $_SESSION['importInProgress'];
    $status = array('in_progress'=>(bool)$logstat);
    try {
      $logcontent = file_get_contents($_SESSION['importLogFile']);
      $log = explode("\n", $logcontent);
    }
    catch(Exception $e) {
      return $status;
    }
    $output = array();
    foreach($log as $lnr=>$entry) {
      if(preg_match('/^~BEGIN (.*)/', $entry, $matches)) {
	$status['status_'.$matches[1]] = "begun";
      }
      else if(preg_match('/^~SUCCESS (.*)/', $entry, $matches)) {
	$status['status_'.$matches[1]] = "success";
      }
      else if(preg_match('/^~ERROR (.*)/', $entry, $matches)) {
	$status['status_'.$matches[1]] = "error";
      }
      else if(preg_match('/^~FINISHED/', $entry, $matches)) {
	$status['progress'] = 1.0;
      }
      else if(preg_match('/^~PROGRESS (.*)/', $entry, $matches)) {
	$status['progress'] = floatval($matches[1]);
      }
      else if($entry == "reading parameter file...finished") {}
      else {
	$entry = trim($entry);
	if(!empty($entry) && !preg_match('/^\d+$/', $entry, $matches)) {
	  $output[] = $entry;
	}
      }
    }
    $status['output'] = implode("\n", $output);
    $status['success'] = true;
    return $status;
  }

  /** Checks file for validity, converts it to XML, then calls
      XMLHandler::import(). */
  public function importTranscriptionFile($transdata, $options) {
    $ch_options = $this->db->getProjectOptions($options['project']);
    $ch = new CommandHandler($ch_options);
    $xml = new XMLHandler($this->db);

    $localname = $transdata['tmp_name'];
    $logfile = fopen($options['logfile'], 'a');
    // convert to utf-8
    fwrite($logfile, "~BEGIN CHECK\n");
    $errors = $ch->checkMimeType($localname, 'text/plain');
    if(empty($errors)) {
      $errors = $ch->convertToUtf($localname, $options['encoding']);
    }
    if(!empty($errors)) {
      fwrite($logfile, "~ERROR CHECK\n");
      fwrite($logfile, "Datei ist keine Textdatei und/oder falsches Encoding angegeben.\n\n");  //$LOCALE
      fwrite($logfile, implode("\n", $errors) . "\n\n");
      fclose($logfile);
      return false;
    }
    $options['trans_file'] = file_get_contents($localname);
    // import script call
    fclose($logfile);
    $xmlname = null;
    $errors = $ch->callImport($localname, $xmlname, $options['logfile']);
    $logfile = fopen($options['logfile'], 'a');
    if(!empty($errors)) {
      fwrite($logfile, "~ERROR XML\n");
      fwrite($logfile, implode("\n", $errors) . "\n");
      fclose($logfile);
      return false;
    }
    if(!isset($xmlname) || empty($xmlname)) {
      fwrite($logfile, "~ERROR XML\n");
      fwrite($logfile, "Fehler beim Erzeugen einer temporären Datei.\n");  //$LOCALE
      fclose($logfile);
      return false;
    }
    // perform import
    fwrite($logfile, "~BEGIN IMPORT\n");
    $xmldata = array("tmp_name" => $xmlname, "name" => $transdata['name']);
    $status = $xml->import($xmldata, $options, $_SESSION["user_id"]);
    if(!isset($status['success']) || !$status['success']) {
      fwrite($logfile, "~ERROR IMPORT\n");
      fwrite($logfile, implode("\n", $status['errors']) . "\n");
      fclose($logfile);
      return false;
    }
    fwrite($logfile, "~SUCCESS IMPORT\n");
    fwrite($logfile, implode("\n", $status['warnings']) . "\n");
    fclose($logfile);
    return true;
  }

  /** Wraps DBInterface::importTaglist() */
  public function importTaglist($taglist, $cls, $settype, $name) {
    if(!$_SESSION['admin']) {
      return array('success'=>false, 'errors'=>array("Keine Berechtigung."));  //$LOCALE
    }
    return $this->db->importTaglist($taglist, $cls, $settype, $name);
  }

  /** Wraps DBInterface::deleteFile() */
  public function deleteFile($fileid){
    if(!$_SESSION['admin'] && !$this->db->isAllowedToDeleteFile($fileid, $_SESSION['user'])) {
      return array("success" => false, "error_msg" => "Keine Berechtigung.");  //$LOCALE
    }
    $status = $this->db->deleteFile($fileid);
    if($status) {
      return array("success" => false, "error_msg" => $status);
    }
    else {
      return array("success" => true);
    }
  }

  /** Wraps DBInterface::lockFile() */
  public function lockFile( $fileid ) {
    if(!$_SESSION['admin'] && !$this->db->isAllowedToOpenFile($fileid, $_SESSION['user'])) {
      return array('success' => false);
    }

    return $this->db->lockFile( $fileid , $_SESSION['user']);
  }

  /** Wraps DBInterface::unlockFile(), unset the session data of the file */
  public function unlockFile( $fileid ) {
    $force = (bool) $_SESSION["admin"]; // admins can unlock any file
    $ans = $this->db->unlockFile($fileid, $_SESSION['user'], $force);
    if($ans) {
      unset($_SESSION['currentName']);
      unset($_SESSION['currentFileId']);
    }
    return array('success' => (bool) $ans);
  }

  public function saveMetadata($post) {
    if(!$_SESSION['admin'] && !$this->db->isAllowedToDeleteFile($fileid, $_SESSION['user'])) {
      return array("success" => false, "error_msg" => "Keine Berechtigung.");  //$LOCALE
    }
    $status = $this->db->changeMetadata($post);
    if($status) {
      return array("success" => false, "error_msg" => $status);
    }
    else {
      return array("success" => true);
    }
  }

  /** Calculate the page number on which a given line appears.
   *
   * @param int $line The line number
   * @return The page number where the line appears, taking
   * 	   into consideration the current user settings,
   *	   or 0 if there is only one page at all
   */
  public function calculateEditorPage($line){
    $uniquelines = $_SESSION['noPageLines'] - $_SESSION['contextLines'];
    if($line>$uniquelines) // if there are more lines than fit on a single page ...
      $page = @ceil($line/$uniquelines);
    else
      $page = 0;
    return $page;
  }

  /** Wraps DBInterface::openFile(), set the session data for the file */
  public function openFile( $fileid ){
    if(!$_SESSION['admin'] && !$this->db->isAllowedToOpenFile($fileid, $_SESSION['user'])) {
      return array('success' => false);
    }

    $lock = $this->db->openFile($fileid, $_SESSION['user']);
    if($lock['success']){
      $_SESSION['currentFileId'] = $lock['data']['id'];
      $_SESSION['currentName'] = $lock['data']['fullname'];
      $lock['lastPage'] = $this->calculateEditorPage($lock['lastEditedRow']);
      $lock['maxLinesNo'] = $this->getMaxLinesNo();
    }

    return $lock;
  }

  private function makeExportFilename($data, $format) {
      $name = $data['fullname'];
      if($data['sigle'])
          $name = "[".$data['sigle']."] ".$name;
      $name .= ExportType::mapToExtension($format);
      return $name;
  }

  /** Wraps Exporter::export() */
  public function exportFile($fileid, $format, $GET){
    if(!$_SESSION['admin'] && !$this->db->isAllowedToOpenFile($fileid, $_SESSION['user'])) {
      header("HTTP/1.1 404 Not Found");
      return false;
    }

    $exp = new Exporter($this->db);
    $options = array();
    if(array_key_exists('ccsv', $GET))
      $options = $GET['ccsv'];
    $filename = $this->makeExportFilename($this->db->getFilenameFromID($fileid), $format);
    $output = fopen('php://output', 'wb');
    header("Cache-Control: public");
    header("Content-Type: " . ExportType::mapToContentType($format));
    // header("Content-Transfer-Encoding: Binary");
    // header("Content-Length:".filesize($attachment_location));
    header("Content-Disposition: attachment; filename=".$filename);
    $exp->export($fileid, $format, $options, $output);
    return true;
  }

  /** Wraps DBInterface::getProjectsAndFiles() */
  public function getProjectsAndFiles(){
    $this->db->releaseOldLocks($this->timeout);
    $uid  = $_SESSION["admin"] ? null : $_SESSION["user_id"];
    try {
        $data = $this->db->getProjectsAndFiles($uid);
    }
    catch(Exception $ex) {
        return array('success' => false, 'errors' => array($ex->getMessage()));
    }
    return array('success' => true, 'data' => $data);
  }

  /** Wraps DBInterface::getProjects() */
  public function getProjectList(){
    if ($_SESSION["admin"]) {
      return $this->db->getProjects();
    } else {
      return $this->db->getProjectsForUser($_SESSION["user"]);
    }
  }

  /** Wraps DBInterface::getProjectUsers(), indexing the results by
      project id */
  public function getProjectUsers() {
    if ($_SESSION["admin"]) {
      $indexed_by_project = array();
      $projectuser = $this->db->getProjectUsers();
      foreach ($projectuser as $pu) {
	if (!array_key_exists($pu['project_id'],$indexed_by_project)) {
	  $indexed_by_project[$pu['project_id']] = array();
	}
	$indexed_by_project[$pu['project_id']][] = $pu['username'];
      }
      return $indexed_by_project;
    }
  }

  /** Wraps DBInterface::createProject(), checking for administrator
      privileges first */
  public function createProject($name) {
    if ($_SESSION["admin"]) {
      return $this->db->createProject($name);
    }
    else {
      return array("success" => false, "errors" => array());
    }
  }

  /** Wraps DBInterface::deleteProject(), checking for administrator
      privileges first */
  public function deleteProject($name) {
    if ($_SESSION["admin"]) {
      return array("success" => $this->db->deleteProject($name));
    }
    return array("success" => false);
  }

  /** Wraps DBInterface::saveProjectSettings(), checking for
      administrator privileges first */
  public function saveProjectSettings($data) {
    if (!$_SESSION["admin"])
      return array("success" => false, "errors" => ["Permission denied."]); //$LOCALE
    if(!array_key_exists('id', $data))
      return array("success" => false, "errors" => ["No project ID given."]);  //$LOCALE

    return $this->db->saveProjectSettings($data['id'], $data);
  }

  /** Wraps DBInterface::saveUserSettings(), checking for
      administrator privileges first */
  public function saveUserSettings($data) {
    if (!$_SESSION["admin"])
      return array("success" => false, "errors" => ["Permission denied."]);  //$LOCALE
    if(!array_key_exists('id', $data))
      return array("success" => false, "errors" => ["No user ID given."]);  //$LOCALE
    if($this->db->saveUserSettings($data['id'], $data) < 1)
      return array("success" => false, "errors" => ["Unknown error."]);  //$LOCALE
    return array("success" => true);
  }

  public function getAllModernIDs() {
    $data = $this->db->getAllModernIDs($_SESSION['currentFileId']);
    return array("success" => true, "data" => $data);
  }

  /** Wraps DBInterface::getLines(), calculating start line and limit first */
  public function getLines($page){
    if($page==0) $page++;

    $end = $page*($_SESSION['noPageLines']-$_SESSION['contextLines'])+$_SESSION['contextLines'];
    $start = $end - $_SESSION['noPageLines'];
    $lim = $_SESSION['noPageLines'];

    return $this->db->getLines($_SESSION['currentFileId'],$start,$lim);
  }

  /** Wraps DBInterface::getLines(), given a start and end line */
  public function getLinesById($start,$end) {
    $lim = $end - $start;
    return $this->db->getLines($_SESSION['currentFileId'],$start,$lim);
  }

  /** Get the total number of lines for the currently open document. */
  public function getMaxLinesNo(){
    return $this->db->getMaxLinesNo($_SESSION['currentFileId']);
  }

  /** Perform a search query on the currently opened document. */
  public function searchDocument($query) {
    return $this->db->searchDocument($_SESSION['currentFileId'], $query);
  }

  /** Save file data to the database.
   *
   * Calls DBInterface::saveData().
   *
   * @param array $data Data to be saved
   */
  public function saveData($data){
    $status = $this->db->saveData($_SESSION['currentFileId'],
				  $data,
				  $_SESSION['user']);

    if($status) {
      return array("success"=>false, "errors"=>array($status));
    } else {
      return array("success"=>true);
    }
  }

  /** Perform automatic training and/or annotation.
   *
   * @param string $taggerid Database ID of the tagger to be used
   * @param string $action   "anno"  => perform annotation
   *                         "train" => perform training
   *                         "both"  => perform both (not currently used)
   */
  public function performAnnotation($taggerid, $action) {
    $userid = $_SESSION['user_id'];
    $fid = $_SESSION['currentFileId'];
    $pid = $this->db->getProjectForFile($fid);
    if(!$this->db->lockProjectForTagger($pid, $taggerid)) {
        // TODO: find more elegant way to handle this
        return array("success"=>false,
                     "errors"=>array("Für dieses Projekt wird derzeit bereits ein Tagger"
                                     ." ausgeführt.  Bitte warten Sie einen Moment und"
                                     ." führen dann den Vorgang erneut aus."));  //$LOCALE
    }

    try {
        $exp = new Exporter($this->db);
        $aa = new AutomaticAnnotationWrapper($this->db, $exp, $taggerid, $pid);
        if($action == "train" || $action == "both") {
            $aa->train($fid);
        }
        if($action == "anno"  || $action == "both") {
            $aa->annotate($fid);
            $this->db->updateChangedTimestamp($fid,$userid);
        }
    }
    catch(Exception $e) {
        $this->db->unlockProjectForTagger($pid);
        return array("success"=>false,
                     "errors"=>$e->getMessage());
    }

    $this->db->unlockProjectForTagger($pid);
    return array("success"=>true);
  }

  /** Delete a token from a file.
   *
   * @param string $tokenid ID of the token to be deleted
   */
  public function deleteToken($tokenid) {
    $errors = array();
    $userid = $_SESSION["user_id"];
    // check whether tokenid belongs to currently opened file
    $textid = $this->db->getTextIdForToken($tokenid);
    if($textid !== $_SESSION['currentFileId']) {
      $errors[] = "Token konnte nicht gelöscht werden, da es nicht zur momentan geöffneten Datei gehört.";  //$LOCALE
      return array("success" => false, "errors" => $errors);
    }
    // call the DB interface to make the change
    $status = $this->db->deleteToken($textid, $tokenid, $userid);
    return $status;
  }

  /** Edit transcription in the database.
   *
   * @param string $tokenid ID of the token to be edited
   * @param string $value   New transcription for the token
   */
  public function editToken($tokenid, $value) {
    $errors = array();
    $userid = $_SESSION["user_id"];
    // check whether tokenid belongs to currently opened file
    $textid = $this->db->getTextIdForToken($tokenid);
    if($textid !== $_SESSION['currentFileId']) {
      $errors[] = "Token konnte nicht geändert werden, da es nicht zur momentan geöffneten Datei gehört.";  //$LOCALE
      return array("success" => false, "errors" => $errors);
    }
    // check and convert transcription
    $converted = null;
    $status = $this->checkConvertTranscription($textid, $tokenid, $value, $converted);
    if($status!==null) {
      return $status;
    }

    // then, call a DBInterface function to make the change
    $status = $this->db->editToken($textid, $tokenid, $value, $converted, $userid);
    return $status;
  }

  /** Add transcription to the database.
   *
   * @param string $tokenid ID of the token before which the new token should be added
   * @param string $value   Transcription for the new token
   */
  public function addToken($tokenid, $value) {
    $errors = array();
    $userid = $_SESSION["user_id"];
    // check whether tokenid belongs to currently opened file
    $textid = $this->db->getTextIdForToken($tokenid);
    if($textid !== $_SESSION['currentFileId']) {
      $errors[] = "Token konnte nicht hinzugefügt werden, da es nicht zur momentan geöffneten Datei gehört.";  //$LOCALE
      return array("success" => false, "errors" => $errors);
    }
    // check and convert transcription
    $converted = null;
    $status = $this->checkConvertTranscription($textid, $tokenid, $value, $converted);
    if($status!==null) {
      return $status;
    }

    // then, call a DBInterface function to make the change
    $status = $this->db->addToken($textid, $tokenid, $value, $converted, $userid);
    return $status;
  }

  /** Check a transcription and return an array with its converted diplomatic and modern tokens.
   *
   * @param string $textid  ID of the text to which the token belongs
   * @param string $tokenid ID of the token to be edited
   * @param string $value   Transcription for the token
   * @param array  $converted  The converted transcription
   */
  private function checkConvertTranscription($textid, $tokenid, $value, &$converted) {
    // instantiate CommandHandler
    $pid = $this->db->getProjectForFile($textid);
    $options = $this->db->getProjectOptions($pid);
    $ch = new CommandHandler($options);

    $errors = array();
    $converted = $ch->checkConvertToken($value, $errors);
    if(!empty($errors)) {
      array_unshift($errors, "Bei der Konvertierung des Tokens ist ein Fehler aufgetreten.", "");  //$LOCALE
      return array("success" => false, "errors" => $errors);
    }
    return null;
  }

  /** Sets the session timeout (in minutes). */
  public function setTimeout($to) {
    $this->timeout = $to;
  }

  /** Gets the session timeout (in minutes). */
  public function getTimeout() {
    return $this->timeout;
  }

  /** Handle a keepalive request, checking for any new server notices.
   */
  public function keepalive() {
    $notices = array();
    foreach($this->db->checkForNotices() as $notice) {
        $skey = "notice_".$notice['id'];
        if(!array_key_exists($skey, $_SESSION)) {
            $_SESSION[$skey] = true;
            $notices[] = $notice;
        }
    }
    if(!empty($notices))
        return array("success" => true, "notices" => $notices);
    return array("success" => true);
  }

  /** Creates a new server notice.
   */
  public function createNotice($notice) {
    if ($_SESSION["admin"]) {
        $status = $this->db->addNotice($notice['type'],
                                       $notice['text'],
                                       $notice['expires']);
        return array('success' => (bool) $status);
    }
    return array('success' => false,
                 'errors' => array("Administrator privileges are required "
                                   ." for this action."));  //$LOCALE
  }

  /** Deletes a server notice.
   */
  public function deleteNotice($notice_id) {
    if ($_SESSION["admin"]) {
        $status = $this->db->deleteNotice($notice_id);
        return array('success' => (bool) $status);
    }
    return array('success' => false,
                 'errors' => array("Administrator privileges are required "
                                   ." for this action.")); //$LOCALE
  }

  /** Fetches all server notices, checking for admin privileges first.
   */
  public function getAllNotices() {
    if ($_SESSION["admin"]) {
        $notices = $this->db->getAllNotices();
        return array('success' => true, 'notices' => $notices);
    }
    return array('success' => false,
                 'errors' => array("Administrator privileges are required "
                                   ." for this action."));  //$LOCALE
  }

  /** Creates a new automatic annotator.
   */
  public function createAnnotator($data) {
    if ($_SESSION["admin"]) {
        return $this->db->addTagger($data['name'],
                                    $data['class']);
    }
    return array('success' => false,
                 'errors' => array("Administrator privileges are required "
                                   ." for this action."));  //$LOCALE
  }

  /** Deletes an automatic annotator.
   */
  public function deleteAnnotator($tagger_id) {
    if ($_SESSION["admin"]) {
        $status = $this->db->deleteTagger($tagger_id);
        return array('success' => (bool) $status);
    }
    return array('success' => false,
                 'errors' => array("Administrator privileges are required "
                                   ." for this action."));  //$LOCALE
  }

  /** Changes the settings of an automatic annotator.
   */
  public function changeAnnotator($data) {
    if ($_SESSION["admin"]) {
        try {
            $this->db->setTaggerSettings($data);
            return array('success' => true);
        } catch(Exception $ex) {
            return array('success' => false, 'errors' => array($ex->getMessage()));
        }
    }
    return array('success' => false,
                 'errors' => array("Administrator privileges are required "
                                   ." for this action."));  //$LOCALE
  }

  /** Fetches all automatic annotators, checking for admin privileges first.
   */
  public function getAllAnnotators() {
    if ($_SESSION["admin"]) {
        $taggers = $this->db->getTaggerListAndOptions();
        return array('success' => true, 'taggers' => $taggers);
    }
    return array('success' => false,
                 'errors' => array("Administrator privileges are required "
                                   ." for this action."));  //$LOCALE
  }

  /** Perform user login.
   *
   * Calls DBInterface::getUserData() to verify access information,
   * and sets session variables accordingly if the login was
   * successful.
   *
   * @param string $user The username to be used for logging in
   * @param string $pw   The corresponding password
   */
  public function login($user, $pw) {
    $data = $this->db->getUserData($user, $pw);
    if ($data) {  // login successful
      $_SESSION["loggedIn"] = true;
      $_SESSION["user"] = $data['name'];
      $_SESSION["user_id"] = $data['id'];
      $_SESSION["failedLogin"] = false;
      $_SESSION["admin"] = ($data['admin'] == 1);
      $this->db->updateLastactive($data['id']);

	  // file already opened?
	  $data = $this->db->getLockedFiles($user);
	  if(!empty($data)){
		$_SESSION['currentFileId'] = $data['file_id'];
		$_SESSION['currentName'] = $data['file_name'];
	  }

	  //editor settings
	  $data = $this->db->getUserSettings( $user );
	  if($data){
		$_SESSION['noPageLines'] = (isset($data['lines_per_page']) && $data['lines_per_page']>0)? $data['lines_per_page'] : '30';
		$_SESSION['contextLines'] = (isset($data['lines_context']))? $data['lines_context'] : '5';
		$_SESSION['editTableDragHistory'] = (isset($data['columns_order']))? $data['columns_order'] : '';
		$_SESSION['hiddenColumns'] = (isset($data['columns_hidden']))? $data['columns_hidden'] : '';
		$_SESSION['textPreview'] = (isset($data['text_preview']))? $data['text_preview'] : 'off';
		$_SESSION['showInputErrors'] = (isset($data['show_error']))? ($data['show_error']==1 ? 'true' : 'false') : 'true';
                $this->setLocale((isset($data['locale']))? $data['locale'] : null);
	  } else {
		$_SESSION['noPageLines'] = '30';
		$_SESSION['contextLines'] = '5';
		$_SESSION['editTableDragHistory'] = '';
		$_SESSION['textPreview'] = 'off';
		$_SESSION['hiddenColumns'] = '';
		$_SESSION['showInputErrors'] = 'true';
                $this->setLocale();
	  }
    } else {      // login failed
      $_SESSION["failedLogin"] = true;
    }
  }

  /** Perform user logout. */
  public function logout() {
    $this->updateLastactive();
    if(isset($_SESSION["currentFileId"]) && !empty($_SESSION["currentFileId"])) {
        $this->unlockFile($_SESSION["currentFileId"], $_SESSION["user"]);
    }
    session_destroy();
    // setcookie();
    $_SESSION["loggedIn"] = false;
    $_SESSION["failedLogin"] = false;
    $_SESSION["admin"] = false;
    $_SESSION["user"] = null;
    $_SESSION["user_id"] = null;
    $_SESSION["currentName"] = null;
    $_SESSION["currentFileId"] = null;
  }

}

?>
