<?php

/** @file sessionHandler.php
 * Manage session-specific settings.
 *
 * @author Marcel Bollmann
 * @date January 2012
 */

require_once( "connect.php" );
require_once( "xmlHandler.php" );

/** Manages session-specific data.
 *
 * This class keeps track of user settings and general session
 * data. It wraps most of the methods of the database interface
 * (DBInterface), inserting session data and checking for
 * administrator privileges if needed.
 */
class SessionHandler {
  private $db; /**< A DBInterface object. */
  private $xml; /**< An XMLHandler object. */

  /** Create a new SessionHandler.
   *
   * Initializes a session, constructs a new DBInterface, and sets
   * defaults for various session values if required.
   */
  function __construct() {
    session_name("PHPSESSID_CORA");
    @session_start();

    $dbconn = new DBConnector();
    $this->db = new DBInterface($dbconn);
    $this->xml = new XMLHandler($this->db);

    $defaults = array( "lang"        => DEFAULT_LANGUAGE,
		       "loggedIn"    => false,
		       "admin"       => false,
		       "normvisible" => true,
		       "failedLogin" => false );

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
  public function getTagsetList() {
    return $this->db->getTagsets();
  }

  /** Wraps DBInterface::getTagset(). */
  public function getTagset($tagset) {
    return $this->db->getTagset($tagset);
  }

  /** Wraps DBInterface::getUserList(), checking for administrator
      privileges first. */
  public function getUserList() {
    if ($_SESSION["admin"]) {
      return $this->db->getUserList();
    }
  }

  /** Wraps DBInterface::createUser(), checking for administrator
      privileges first. */
  public function createUser( $username, $password, $admin ) {
    if (!$_SESSION["admin"])
      return false;
    return $this->db->createUser($username, $password, $admin);
  }

  /** Wraps DBInterface::changePassword(), checking for administrator
      privileges first. */
  public function changePassword( $username, $password ) {
    if (!$_SESSION["admin"])
      return false;
    return $this->db->changePassword($username, $password);
  }

  /** Wraps DBInterface::changePassword(), intended for users
      changing their own passwords. */
  public function changeUserPassword( $oldpw, $newpw ) {
    if ($this->db->getUserData($_SESSION["user"], $oldpw)) {
      $this->db->changePassword($_SESSION["user"], $newpw);
      return array("success"=>True);
    }
    return array("success"=>False, "errcode"=>"oldpwmm");
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
      return false;
    return $this->db->deleteUser($username);
  }

  /** Wraps DBInterface::toggleAdminStatus(), checking for
      administrator privileges first. */
  public function toggleAdminStatus( $username ) {
    if (!$_SESSION["admin"])
      return false;
    return $this->db->toggleAdminStatus($username);
  }

  /** Wraps DBInterface::toggleNormStatus(), checking for
      administrator privileges first. */
  public function toggleNormStatus( $username ) {
    if (!$_SESSION["admin"])
      return false;
    return $this->db->toggleNormStatus($username);
  }
  
  /** Wraps XMLHandler::import() */
  public function importFile($xmldata, $options) {
    return $this->xml->import($xmldata, $options);
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
      return "Keine Berechtigung.";
    }
    return $this->db->deleteFile($fileid);
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
    if($ans = $this->db->unlockFile( $fileid, $_SESSION['user'], $force )){
      unset($_SESSION['currentName']);
      unset($_SESSION['currentFileId']);
      return true;
    }
    return $ans;
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

  /** Wraps XMLHandler::export() */
  public function exportFile( $fileid, $format ){
    if(!$_SESSION['admin'] && !$this->db->isAllowedToOpenFile($fileid, $_SESSION['user'])) {
      return false;
    }

    $this->xml->export($fileid,$format);
    return true;
  }
	
  /** Wraps DBInterface::getFiles() */
  public function getFiles(){
    if ($_SESSION["admin"]) {
      return $this->db->getFiles();
    } else {
      return $this->db->getFilesForUser($_SESSION["user"]);
    }
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
  }

  /** Wraps DBInterface::deleteProject(), checking for administrator
      privileges first */
  public function deleteProject($name) {
    if ($_SESSION["admin"]) {
      return array("success" => $this->db->deleteProject($name));
    }
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
    // was: return $this->calculateEditorPage($anz);
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
				   $data);

    if($status) {
      return array("success"=>false, "errors"=>array($status));
    } else {
      return array("success"=>true);
    }
  }

  /** Wraps DBInterface::getHighestTagId() */
  public function getHighestTagId($tagset){
    return $this->db->getHighestTagId($tagset);
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
      //$_SESSION["normvisible"] = ($data['normvisible'] == 1);

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