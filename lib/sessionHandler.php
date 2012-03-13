<?php

/** @file sessionHandler.php
 * Manage session-specific settings.
 *
 * @author Marcel Bollmann
 * @date January 2012
 */

require_once( "connect.php" );

/** Manages session-specific data.
 *
 * This class keeps track of user settings and general session
 * data. It wraps most of the methods of the database interface
 * (DBInterface), inserting session data and checking for
 * administrator privileges if needed.
 */
class SessionHandler {
  private $db; /**< A DBInterface object. */

  /** Create a new SessionHandler.
   *
   * Initializes a session, constructs a new DBInterface, and sets
   * defaults for various session values if required.
   */
  function __construct() {
    @session_start();

    $this->db = new DBInterface();

    $defaults = array( "lang"        => DEFAULT_LANGUAGE,
		       "loggedIn"    => "false",
		       "admin"       => "false",
		       "failedLogin" => "false" );

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

  public function setUserEditorSettings($lpp,$cl){
	if($this->db->setUserEditorSettings($_SESSION['user'],$lpp,$cl)){
		$_SESSION['noPageLines'] = $lpp;
		$_SESSION['contextLines'] = $cl;
		return true;
	}
	return false;
  }

  /** Wraps DBInterface::getLanguageArray(), passing the current
      language code. */
  public function getLanguageArray() {
    return $this->db->getLanguageArray( $_SESSION["lang"] );
  }

  /** Wraps DBInterface::getTagsets(), passing the current language
      code. */
  public function getTagsetList() {
    return $this->db->getTagsets( $_SESSION["lang"] );
  }

  /** Wraps DBInterface::getTagset(), passing the current language
      code. */
  public function getTagset( $tagset ) {
    return $this->db->getTagset( $tagset, $_SESSION["lang"] );
  }

  /** Wraps DBInterface::saveTagset(), passing the current language
      code. */
  public function saveTagset( $data ) {
    $this->db->saveTagset( $data, $_SESSION["lang"] );
  }

  public function saveCopyTagset( $data, $originTagset, $newTagset ) {
	$this->db->saveCopyTagset( $data, $originTagset, $newTagset, $_SESSION["lang"] );
  }

  /** Wraps DBInterface::lockTagset(). */
  public function lockTagset( $tagset ) {
    return $this->db->lockTagset( $tagset );
  }

  /** Wraps DBInterface::unlockTagset(). */
  public function unlockTagset( $tagset ) {
    return $this->db->unlockTagset( $tagset );
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

  	/** Wraps DBInterface::saveNewFile() */
	public function saveNewFile( $name, $user, $pos_tagged, $morph_tagged, $norm, $tagset, &$data ) {
		return $this->db->saveNewFile( $name, $user, $pos_tagged, $morph_tagged, $norm, $tagset, $data );
	}
  	
	/** Wraps DBInterface::saveAddData() */	
	public function saveAddData( $id, $tagType, &$data ) {
		return $this->db->saveAddData( $id, $tagType, $data );
	}
	
  	/** Wraps DBInterface::deleteFile() */	
	public function deleteFile($fileid){
		return $this->db->deleteFile($fileid);
	}

  	/** Wraps DBInterface::lockFile() */	
	public function lockFile( $fileid ) {
	    return $this->db->lockFile( $fileid , $_SESSION['user']);
	  }

    /** Wraps DBInterface::unlockFile(), unset the session data of the file */ 
	public function unlockFile( $fileid ) {
	    if($ans = $this->db->unlockFile( $fileid )){
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
		if($line>$_SESSION['noPageLines']) // if there are more lines than fit on a single page ...
			$page = @ceil(($line - $_SESSION['contextLines'])/($_SESSION['noPageLines']-$_SESSION['contextLines']));
		else
			$page = 0;
		return $page;
	}
	  
  /** Wraps DBInterface::openFile(), set the session data for the file */    
	public function openFile( $fileid ){
		$lock = $this->db->openFile($fileid);
		if($lock['success']){
			$_SESSION['currentFileId'] = $lock['data']['file_id'];
			$_SESSION['currentName'] = $lock['data']['file_name'];
			$lock['lastPage'] = $this->calculateEditorPage($lock['lastEditedRow']);
		}
		
		return $lock;		
	}
	
	/** Wraps DBInterface::getFiles() */
	public function getFiles(){
		return $this->db->getFiles();
	}
	
	/** Wraps DBInterface::getLastImportedFile() */
	public function getLastImportedFile(){
		return $this->db->getLastImportedFile($_SESSION['user']);
	}
	
	/** Wraps DBInterface::getLines(), calculating start line and limit first */
	public function getLines($page){
		if($page==0) $page++;

		$end = $page*($_SESSION['noPageLines']-$_SESSION['contextLines'])+$_SESSION['contextLines'];
		$start = $end - $_SESSION['noPageLines'];
		$lim = $_SESSION['noPageLines'];

		return $this->db->getLines($_SESSION['currentFileId'],$start,$lim);
	}
	
	/** Wraps DBInterface::getToken() */
	public function getToken($fileid){
		return $this->db->getToken($fileid);
	}
	
	/** Get the total number of pages for the currently open document. */
	public function getMaxLinesNo(){
		$anz = $this->db->getMaxLinesNo($_SESSION['currentFileId']);
		return $this->calculateEditorPage($anz);
	}
	
	/** Wraps DBInterface::saveTag() */
	public function saveTag($tagvalue,$tagname,$fileid,$lineid){
		return $this->db->saveTag($tagvalue,$tagname,$fileid,$lineid);
	}		
	
	/** Wraps DBInterface::highlightError() */
	public function highlightError($line){		
		return $this->db->highlightError($_SESSION['currentFileId'],$line,$_SESSION['user']);
	}

	/** Wraps DBInterface::unhighlightError() */
	public function unhighlightError($line){		
		return $this->db->unhighlightError($_SESSION['currentFileId'],$line,$_SESSION['user']);
	}
	
	/** Wraps DBInterface::markLastPosition() */
	public function markLastPosition($line){
		return $this->db->markLastPosition($_SESSION['currentFileId'],$line,$_SESSION['user']);
	}
	
	/** Wraps DBInterface::undoLastEdit() */
	public function undoLastEdit(){
		return $this->db->undoLastEdit($_SESSION['currentFileId'],$_SESSION['user']);
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
  public function login( $user, $pw ) {	
	
    $data = $this->db->getUserData( $user, $pw );
    if ($data) {  // login successful
      $_SESSION["loggedIn"] = true;
      $_SESSION["user"] = $user;
      $_SESSION["failedLogin"] = false;
      $_SESSION["admin"] = ($data['admin'] == "y");
	  // file already opened?
	  $data = $this->db->getLockedFiles( $user );
	  if(!empty($data)){
		$_SESSION['currentFileId'] = $data[0];
		$_SESSION['currentName'] = $data[1];
	  }

	  //editor settings
	  $data = $this->db->getUserEditorSettings( $user );
	  if($data){
		$_SESSION['noPageLines'] = (isset($data['noPageLines']) && $data['noPageLines']>0)? $data['noPageLines'] : '30';
		$_SESSION['contextLines'] = (isset($data['contextLines']))? $data['contextLines'] : '5';
	  } else {
		$_SESSION['noPageLines'] = '30';
		$_SESSION['contextLines'] = '5';
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
    $_SESSION["currentName"] = null;
    $_SESSION["currentFileId"] = null;
  }

}

?>