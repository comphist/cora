<?php

/** @file sessionHandler.php
 * Manage session-specific settings.
 *
 * @author Marcel Bollmann
 * @date January 2012
 */

require_once( "connect.php" );
require_once( "xmlHandler.php" );
require_once( "commandHandler.php" );
require_once( "exporter.php" );

/** Manages session-specific data.
 *
 * This class keeps track of user settings and general session
 * data. It wraps most of the methods of the database interface
 * (DBInterface), inserting session data and checking for
 * administrator privileges if needed.
 */
class CoraSessionHandler {
  private $db; /**< A DBInterface object. */
  private $xml; /**< An XMLHandler object. */
  private $ch; /**< A CommandHandler object. */
  private $exporter; /**< An Exporter object. */

  private $timeout = 30; // session timeout in minutes

  /** Create a new CoraSessionHandler.
   *
   * Initializes a session, constructs a new DBInterface, and sets
   * defaults for various session values if required.
   */
  function __construct($db, $xml, $exp, $ch) {
    session_name("PHPSESSID_CORA");
    session_start();

    $this->db = $db;
    $this->xml = $xml;
    $this->exporter = $exp;
    $this->ch = $ch;

    $defaults = array( "lang"        => DEFAULT_LANGUAGE,
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
  }

  /** Get the language that is currently @em not active.
   *
   * @return One of the codes from {@c de, @c en}, depending on which
   * one is not the current language code.
   */
  public function getInactiveLanguage() {
    if ($_SESSION["lang"] == "de") {
      return "en";
    } else {
      return "de";
    }
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
  	 if($this->db->setUserSetting($_SESSION['user'],$name,$value)){
		$_SESSION[$name] = $value;
		return true;
	}
	return false;
  }

  /** Wraps DBInterface::getTagsets(). */
  public function getTagsetList($class="POS", $orderby="name") {
    return $this->db->getTagsets($class, $orderby);
  }

  /** Wraps DBInterface::getTagset(). */
  public function getTagset($tagset, $limit) {
    $data = $this->db->getTagset($tagset, $limit);
    return array('success' => true, 'data' => $data);
  }

  /** Wraps DBInterface::getTagsetsForFile() and filters it to create
      a list of IDs only. */
  public function getTagsetsForFile($fileid){
    $tagsets = $this->db->getTagsetsForFile($fileid);
    $tlist = array();
    foreach($tagsets as $tagset) {
      $tlist[] = $tagset['id'];
    }
    return array('success' => true, 'data' => $tlist);
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
      return $this->db->getUserList($this->timeout);
    }
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
  public function changePassword( $username, $password ) {
    if (!$_SESSION["admin"])
      return array('success' => false);
    $status = $this->db->changePassword($username, $password);
    return array('success' => (bool) $status);
  }

  /** Wraps DBInterface::changePassword(), intended for users
      changing their own passwords. */
  public function changeUserPassword( $oldpw, $newpw ) {
    if ($this->db->getUserData($_SESSION["user"], $oldpw)) {
      $this->db->changePassword($_SESSION["user"], $newpw);
      return array("success"=>true);
    }
    return array("success"=>false, "errcode"=>"oldpwmm");
  }

  /** Wraps DBInterface::changeProjectUsers(), checking for
      administrator privileges first. */
  public function changeProjectUsers( $pid, $userlist ) { 
    if ($_SESSION["admin"]) {
      return $this->db->changeProjectUsers($pid, $userlist);
    }
  }

  /** Wraps DBInterface::deleteUser(), checking for administrator
      privileges first. */
  public function deleteUser( $username ) {
    if (!$_SESSION["admin"])
      return array('success' => false);
    $status = $this->db->deleteUser($username);
    return array('success' => (bool) $status);
  }

  /** Wraps DBInterface::toggleAdminStatus(), checking for
      administrator privileges first. */
  public function toggleAdminStatus( $username ) {
    if (!$_SESSION["admin"])
      return array('success' => false);
    $status = $this->db->toggleAdminStatus($username);
    return array('success' => (bool) $status);
  }

  /** Wraps XMLHandler::import() */
  public function importFile($xmldata, $options) {
    return $this->xml->import($xmldata, $options);
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
    $localname = $transdata['tmp_name'];
    $logfile = fopen($options['logfile'], 'a');
    // convert to utf-8
    fwrite($logfile, "~BEGIN CHECK\n");
    $errors = $this->ch->checkMimeType($localname, 'text/plain');
    if(empty($errors)) {
      $errors = $this->ch->convertToUtf($localname, $options['encoding']);
    }
    if(empty($errors)) {
      $errors = $this->ch->checkFile($localname);
    }
    if(!empty($errors)) {
      fwrite($logfile, "~ERROR CHECK\n");
      fwrite($logfile, implode("\n", $errors) . "\n\n");
      fwrite($logfile, "(HINWEIS: Transkriptionsdateien müssen immer als .txt-Datei im Bonner Transkriptionsformat hochgeladen werden.)\n");
      fclose($logfile);
      return false;
    }
    fwrite($logfile, "~SUCCESS CHECK\n");
    $options['trans_file'] = file_get_contents($localname);
    // run through XML conversion (& tagging)
    $xmlname = null;
    fwrite($logfile, "~BEGIN XMLCALL\n");
    fclose($logfile);
    $autotag = in_array($project_specific_hacks['autotag'], $options['tagsets']);
    $errors = $this->ch->convertTransToXML($localname, $xmlname, $options['logfile'], $autotag);
    $logfile = fopen($options['logfile'], 'a');
    if(!empty($errors)) {
      fwrite($logfile, "~ERROR XML\n");
      fwrite($logfile, implode("\n", $errors) . "\n");
      fclose($logfile);
      return false;
    }
    if(!isset($xmlname) || empty($xmlname)) {
      fwrite($logfile, "~ERROR XML\n");
      fwrite($logfile, "Fehler beim Erzeugen einer temporären Datei.\n");
      fclose($logfile);
      return false;
    }
    fwrite($logfile, "~SUCCESS XMLCALL\n");
    // perform import
    fwrite($logfile, "~BEGIN IMPORT\n");
    $xmldata = array("tmp_name" => $xmlname, "name" => $transdata['name']);
    $status = $this->xml->import($xmldata, $options);
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
  
  /** Wraps DBInterface::importTagList() */
  public function importTagList($taglist, $tagsetname){
    if(!$_SESSION['admin']) {
      return array('success'=>false, 'errors'=>array("Keine Berechtigung."));
    }
    return $this->db->importTagList($taglist, $tagsetname);
  }

  /** Wraps DBInterface::deleteFile() */	
  public function deleteFile($fileid){
    if(!$_SESSION['admin'] && !$this->db->isAllowedToDeleteFile($fileid, $_SESSION['user'])) {
      return array("success" => false, "error_msg" => "Keine Berechtigung.");
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

    $lock = $this->db->openFile($fileid);
    if($lock['success']){
      $_SESSION['currentFileId'] = $lock['data']['id'];
      $_SESSION['currentName'] = $lock['data']['fullname'];
      $lock['lastPage'] = $this->calculateEditorPage($lock['lastEditedRow']);
      $lock['maxLinesNo'] = $this->getMaxLinesNo();
    }
    
    return $lock;		
  }

  /** Wraps Exporter::export() */
  public function exportFile( $fileid, $format ){
    if(!$_SESSION['admin'] && !$this->db->isAllowedToOpenFile($fileid, $_SESSION['user'])) {
      return false;
    }

    $output = fopen('php://output', 'wb');
    header("Cache-Control: public");
    header("Content-Type: " . ExportType::mapToContentType($format));
    // header("Content-Transfer-Encoding: Binary");
    // header("Content-Length:".filesize($attachment_location));
    header("Content-Disposition: attachment; filename=".$fileid.ExportType::mapToExtension($format));
    $this->exporter->export($fileid, $format, $output);
    return true;
  }
	
  /** Wraps DBInterface::getFiles() */
  public function getFiles(){
    $this->db->releaseOldLocks($this->timeout);
    if ($_SESSION["admin"]) {
      $data = $this->db->getFiles();
    } else {
      $data = $this->db->getFilesForUser($_SESSION["user"]);
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
    $anz = $this->db->getMaxLinesNo($_SESSION['currentFileId']);
    return $anz;
  }
  
  /** Save file data to the database.
   *
   * Calls DBInterface::saveLines().
   *
   * @param string $lasteditedrow Line number of the last edited row
   * @param array  $data		Array of lines to be saved
   */
  public function saveData($lasteditedrow,$data){
    $status = $this->db->saveLines($_SESSION['currentFileId'],
				   $lasteditedrow,
				   $data,
				   $_SESSION['user']);

    if($status) {
      return array("success"=>false, "errors"=>array($status));
    } else {
      return array("success"=>true);
    }
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
      $errors[] = "Token konnte nicht gelöscht werden, da es nicht zur momentan geöffneten Datei gehört.";
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
      $errors[] = "Token konnte nicht geändert werden, da es nicht zur momentan geöffneten Datei gehört.";
      return array("success" => false, "errors" => $errors);
    }
    // check and convert transcription
    $converted = null;
    $status = $this->checkConvertTranscription($tokenid, $value, $converted);
    if($status) {
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
      $errors[] = "Token konnte nicht hinzugefügt werden, da es nicht zur momentan geöffneten Datei gehört.";
      return array("success" => false, "errors" => $errors);
    }
    // check and convert transcription
    $converted = null;
    $status = $this->checkConvertTranscription($tokenid, $value, $converted);
    if($status!==null) {
      return $status;
    }

    // then, call a DBInterface function to make the change
    $status = $this->db->addToken($textid, $tokenid, $value, $converted, $userid);
    return $status;
  }

  /** Check a transcription and return an array with its converted diplomatic and modern tokens.
   *
   * @param string $tokenid ID of the token to be edited
   * @param string $value   Transcription for the token
   */
  private function checkConvertTranscription($tokenid, $value, &$converted) {
    $errors = array();
    // call the check script
    $check = $this->ch->checkToken($value);
    if(!empty($check)) {
      array_unshift($check, "Bei der Prüfung der Transkription ist ein Fehler aufgetreten.", "");
      return array("success" => false, "errors" => $check);
    }
    // call the conversion script(s)
    $converted = $this->ch->convertToken($value, $errors);
    if(!empty($errors)) {
      array_unshift($errors, "Bei der Konvertierung des Tokens ist ein Fehler aufgetreten.", "");
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
      $_SESSION["user"] = $user;
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
		$_SESSION['showTooltips'] = 'true';
		$_SESSION['showInputErrors'] = (isset($data['show_error']))? ($data['show_error']==1 ? 'true' : 'false') : 'true';
	  } else {
		$_SESSION['noPageLines'] = '30';
		$_SESSION['contextLines'] = '5';
		$_SESSION['editTableDragHistory'] = '';
		$_SESSION['hiddenColumns'] = '';
		$_SESSION['showTooltips'] = 'true';
		$_SESSION['showInputErrors'] = 'true';
	  }
    } else {      // login failed
      $_SESSION["failedLogin"] = true;
    }
  }

  /** Perform user logout. */
  public function logout() {
    $this->updateLastactive();
    if(isset($_SESSION["currentFileId"]) && !empty($_SESSION["currentFileId"])) {
      $this->unlockFile($_SESSION["currentFileId"]);
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