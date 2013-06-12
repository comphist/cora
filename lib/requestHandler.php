<?php

/** @file requestHandler.php
 * Handle GET and POST requests.
 *
 * @author Marcel Bollmann
 * @date January 2012
 */

// @todo App is not 100% secured against SQL injection at the moment.

/** Handles all GET and POST requests.
 */
class RequestHandler {
  private $sh; /**< Reference to a SessionHandler object. */

  /** Create a new RequestHandler.
   *
   * @param SessionHandler $sessionHandler The SessionHandler object
   * that will be used to perform the requests.
   */
  function __construct( $sessionHandler ) {
    $this->sh = $sessionHandler;
  }

  /** Escape all SQL-specific characters.
   *
   * Calls @c stripslashes() and @c mysql_real_escape_string() to
   * remove potentially dangerous characters before doing an SQL
   * query.
   *
   * @param object $obj The object to be escaped; can be a @em string
   * or an @em array of strings. If an array is given, this function
   * is called recursively for all array entries.
   *
   * @return The modified string or array.
   */
  private function escapeSQL( $obj ) {
    if(is_string($obj)) {
      return mysql_real_escape_string(stripslashes($obj));
    }
    else if(is_array($obj)) {
      $newarray = array();
      foreach($obj as $k => $v) {
	$newarray[$k] = self::escapeSQL($v);
      }
      return $newarray;
    }
    else {
      return $obj;
    }
  }

  /** Send an HTTP status code indicating that an error has occured,
   * then exit.
   *
   * @note JavaScript functions that are sending requests should
   * typically watch out for and catch these errors.
   *
   * @param integer $code An HTTP status code (default: 500)
   * @param string  $msg  An error message
   */
  private function returnError($code, $msg) {
    switch($code) {
    case 400:
      header("HTTP/1.1 400 Bad Request");
      break;
    case 500:
    default:
      header("HTTP/1.1 500 Internal Server Error");
      break;
    }

    echo($msg);
    die();
  }

  /** Handle requests sent to index.php.
   *
   * Supports the following GET requests:
   * <ul>
   *   <li><code>do=logout</code> - Log out the current user.</li>
   *   <li><code>lang</code> - Set the language code to the given
   *     argument.</li>
   * </ul>
   *
   * Supports the following POST request:
   * <ul>
   *   <li><code>action=login</code> - Perform a login.</li>
   * </ul>
   *
   * @return Nothing.
   *
   * @note Requests sent to index.php involve a reload of the whole
   * page, and should only be needed in a few circumstances, e.g.\ a
   * user logging in, or changing the language setting.
   */
  public function handleRequests( $get, $post ) {
    if(array_key_exists("action", $post)) {
      switch ( $post["action"] ) {
      	case "login":
	  $user = self::escapeSQL( $post["loginform"]["un"] );
	  $pw   = self::escapeSQL( $post["loginform"]["pw"] );
	  $this->sh->login( $user, $pw );
	  break;
      }
    }

    if(array_key_exists("do", $get)) {
      switch ( $get["do"] ) {
      case "logout":
	$this->sh->logout();
	break;
      }
    }

    if(array_key_exists("lang", $get)) {
      $_SESSION["lang"] = self::escapeSQL( $get["lang"] );
    }
  }

  /** Check whether a file was uploaded correctly.
   *
   * @return An error message, or 'false' if everything was ok
   */
  private function checkFileUpload($file) {
    if(empty($file['name']) || $file['error']!=UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
      switch ($file['error']) {
      case 1:
      case 2:
	$errmsg = "Die Datei überschreitet die maximal erlaubte Dateigröße.";
	break;
      case 3:
	$errmsg = "Die Datei wurde nur unvollständig übertragen.";
	break;
      case 4:
	$errmsg = "Die Datei konnte nicht übertragen werden.";
	break;
      default:
	$errmsg = "Ein interner Fehler ist aufgetreten (Fehlernummer: ".$file['error'].").  Bitte kontaktieren Sie einen Administrator unter Angabe der Fehlernummer.";
      }
      return $errmsg;
    }
    return false;
  }

  /** Handle requests sent to request.php.
   *
   * Intended for requests sent through JavaScript. This will
   * typically call the respective functions in the SessionHandler
   * object. If data is to be returned, it will be converted to a JSON
   * string.
   *
   * Requests should typically send a GET value for the key @c do that
   * specifies the type of request, regardless of whether the content
   * of the request is sent via GET or POST.
   *
   * Supports the following GET requests (as values of @c do):
   * *** THIS LIST IS NO LONGER UP-TO-DATE ***
   * @arg @c getHighestTagId - Retrieve maximal tag id from specified tagset
   * @arg @c lockTagset - Lock the tagset specified in @c name
   * @arg @c unlockTagset - Unlock the tagset specified in @c name
   * @arg @c fetchTagset - Retrieve the tagset specified in @c name
   * @arg @c getTagsetTags - Return string with tagset tags as html <option> objects
   * @arg @c saveTagset - Save modifications to a tagset; expects the
   * data as a JSON string via POST.
   * @arg @c saveCopyTagset - Copy a specified tagset and saves changes to the copy;
   * expects the data as a JSON string.
   * @arg @c createUser - Create a new user.
   * @arg @c deleteUser - Delete a user.
   * @arg @c toggleAdmin - Toggle administrator status of a user.
   * @arg @c changePassword - Change the password a user.
   * @arg @c listFiles - List all saved files.
   * @arg @c getLastImportedFile - Return information about the last imported file.
   * @arg @c lockFile - Lock the file specified in @c fileid.
   * @arg @c unlockFile - Unlock the file specified in @c fileid.
   * @arg @c openFile - Open the file specified in @c fileid.
   * @arg @c deleteFile - Delete the file specified in @c fileid.
   * @arg @c getLines - Retrieve all lines for the page specified in @c page.
   * @arg @c getMaxLinesNo - Return the total number of lines for the current opened file.
   * @arg @c copyTagset - @c not @c implemented @c yet
   * @arg @c saveEditorUserSettings - Save user settings specified in
   * @c noPageLines (number of lines per page) and @c contextLines
   * (number of lines to show the context) to the database.
   * @arg @c markLastPosition - Save user progress indicated by the @line id for the current opened file.
   * @arg @c undoLastEdit - Undo last progress change.
   *
   * Supports the following POST requests (as values of @c action):
   * @arg @c insertXMLData - Add a new document from XML format.
   * 
   * @return Depending on the type of request, either:
   * @li a @em string in JavaScript Object Notation (JSON);
   * @li an error in the form of HTTP status code 400 or 500;
   * @li or nothing.
   */
  public function handleJSONRequest( $get, $post ) {
    $this->sh->updateLastactive();

	if (array_key_exists("action", $post)) {
	  switch( $post["action"]) {
	  case "changeUserPassword":$status = $this->sh->changeUserPassword(
								    self::escapeSQL($post["oldpw"]),
								    self::escapeSQL($post["newpw"])
								    );
	    echo json_encode($status);
	    exit;

	  case "changeProjectUsers":
	    $status = $this->sh->changeProjectUsers($post["project_id"], $post["allowedUsers"]);
	    echo json_encode($status);
	    exit;

	  case "importTagsetTxt":
	    $errmsg = $this->checkFileUpload($_FILES['txtFile']);
	    if($errmsg) {
	      echo json_encode(array("success"=>false,
				     "errors"=>array("Fehler beim Upload: ".$errmsg)));
	      exit;
	    }
	    if(empty($post['tagset_name'])) {
	      echo json_encode(array("success"=>false,
				     "errors"=>array("Kein Tagset-Name angegeben.")));
	    }
	    $taglist = explode('\n', self::escapeSQL(file_get_contents($_FILES['txtFile']['tmp_name'])));
	    $result = $this->sh->importTagList($taglist,self::escapeSQL($post['tagset_name']));
	    echo json_encode($result);
	    exit;

	  case "importXMLFile":
	    $errmsg = $this->checkFileUpload($_FILES['xmlFile']);
	    if($errmsg) {
	      echo json_encode(array("success"=>false,
				     "errors"=>array("Fehler beim Upload: ".$errmsg)));
	      exit;
	    }

	    $data = $_FILES['xmlFile'];
	    $options = array();
	    if(!empty($post['xmlName'])) {
	      $options['name'] = $post['xmlName'];
	    }
	    if(!empty($post['sigle'])) {
	      $options['sigle'] = $post['sigle'];
	    }
	    if(!empty($post['extid'])) {
	      $options['ext_id'] = $post['extid'];
	    }
	    $options['tagset'] = $post['tagset'];
	    $options['project'] = $post['project'];

	    try {
	      $result = $this->sh->importFile($data,self::escapeSQL($options));
	      echo json_encode($result);
	    }
	    catch (Exception $e) {
	      echo json_encode(array("success"=>false,
				     "errors"=>array("PHPException: ".
						     $e->getMessage())));
	    }
	    exit;

	  case "importTransFile":
	    $tmpfname = tempnam(sys_get_temp_dir(), 'coraIL');
	    $_SESSION['importLogFile'] = $tmpfname;
	    $_SESSION['importInProgress'] = true;

	    // we perform a lengthy operation, so release the session
	    session_write_close();

	    $errmsg = $this->checkFileUpload($_FILES['transFile']);
	    if($errmsg) {
	      echo json_encode(array("success"=>false,
				     "errors"=>array("Fehler beim Upload: ".$errmsg)));
	      exit;
	    }
	    
	    // send a JSON message indicating successful file upload,
	    // then close the connection and proceed with the import
	    // in background
	    $response = json_encode(array("success"=>true));
	    ignore_user_abort(true);
	    set_time_limit(1800);
	    ob_end_clean();
	    header("Connection: close");
	    ob_start();
	    echo $response;
	    $response_size = ob_get_length();
	    header("Content-Length: " . $response_size);
	    ob_end_flush();
	    flush();

	    // from here on, the client connection should be closed
	    $data = $_FILES['transFile'];
	    $options = array();
	    if(!empty($post['fileEnc'])) {
	      $options['encoding'] = $post['fileEnc'];
	    }
	    if(!empty($post['transName'])) {
	      $options['name'] = $post['transName'];
	    }
	    if(!empty($post['sigle'])) {
	      $options['sigle'] = $post['sigle'];
	    }
	    $options['tagset'] = $post['tagset'];
	    $options['project'] = $post['project'];
	    $options['logfile'] = $tmpfname;

	    try {
	      $success = $this->sh->importTranscriptionFile($data,self::escapeSQL($options));
	      if($success) {
		$logfile = fopen($tmpfname, 'a');
		fwrite($logfile, "~FINISHED\n");
		fclose($logfile);
	      }
	    }
	    catch (Exception $e) {
	      $logfile = fopen($tmpfname, 'a');
	      fwrite($logfile, "~ERROR PHPEXCEPTION\n");
	      fwrite($logfile, $e->getMessage() . "\n");
	      fclose($logfile);
	    }

	    session_start();
	    $_SESSION["importInProgress"] = false;
	    exit;
	    
	  default:	exit();
	  }
	}
	
	if (array_key_exists("do", $get)) {
	  switch ( $get["do"] ) {
	  case "keepalive":
	    exit;

	  case "getImportStatus":
	    echo json_encode($this->sh->getImportStatus());
	    exit;

	  case "getHighestTagId": echo $this->sh->getHighestTagId(self::escapeSQL($get["tagset"]));
	    exit;
	    
	  case "fetchTagset":
	    $data = $this->sh->getTagset(self::escapeSQL($get["tagset_id"]), $get["limit"]);
	    echo json_encode($data);
	    exit;

	  case "fetchLemmaSugg":
	    $data = $this->sh->getLemmaSuggestion(self::escapeSQL($get["linenum"]),
						  self::escapeSQL($get["q"]),
						  $get["limit"]);
	    echo json_encode($data);
	    exit;

	  case "createUser":    $status = $this->sh->createUser(
								self::escapeSQL($post["username"]),
								self::escapeSQL($post["password"]),
								false
								);
	    if(!$status) 
	      self::returnError(500, "Could not perform query. Check if username already exists.");
	    exit;
	    
	  case "deleteUser":    $status = $this->sh->deleteUser(self::escapeSQL($post["username"]));
	    if (!$status)
	      self::returnError(500, "Could not delete user.");
	    exit;
	    
	  case "toggleAdmin":   $status = $this->sh->toggleAdminStatus(self::escapeSQL($post["username"]));
	    if(!$status)
	      self::returnError(500, "Could not toggle admin status.");
	    exit;

	  case "changePassword":$status = $this->sh->changePassword(
								    self::escapeSQL($post["username"]),
								    self::escapeSQL($post["password"])
								    );
	    if (!$status)
	      self::returnError(500, "Could not change password.");
	    exit;

	  case "listFiles":  $data = $this->sh->getFiles();
	    echo json_encode($data);
	    exit;

	  case "lockFile":   $data = $this->sh->lockFile(self::escapeSQL($get["fileid"]));
	    $data = json_encode($data);
	    echo $data;
	    exit;
	    
	  case "unlockFile": $data = $this->sh->unlockFile(self::escapeSQL($get["fileid"]));
	    echo json_encode($data);
	    exit;
	    
	  case "openFile":   echo json_encode($this->sh->openFile($get['fileid']));
	    exit;
	    
	  case "deleteFile": echo json_encode($this->sh->deleteFile(self::escapeSQL($post["file_id"])));
	    exit;
	    
	  case "exportFile":
	    $status = $this->sh->exportFile(self::escapeSQL($get["fileId"]),
					    self::escapeSQL($get["format"]));
	    if (!$status)
	      self::returnError(500, "Could not export file.");
	    exit;
	    
	  case "getLines":   $data = $this->sh->getLines(self::escapeSQL($get['page']));
	    echo json_encode($data);
	    exit;
	  case "getLinesById":   $data = $this->sh->getLinesById(self::escapeSQL($get['start_id']),self::escapeSQL($get['end_id']));
	    echo json_encode($data);
	    exit;
	  case "getMaxLinesNo": echo $this->sh->getMaxLinesNo(); exit;

	  case "createProject": echo json_encode($this->sh->createProject(self::escapeSQL($get['project_name']))); exit;

	  case "deleteProject": echo json_encode($this->sh->deleteProject(self::escapeSQL($get['project_id']))); exit;
	    
	  case "saveData":
	    $status = $this->sh->saveData(self::escapeSQL($get['lastEditedRow']), self::escapeSQL(json_decode(file_get_contents("php://input"), true)));
	    echo json_encode($status);
	    exit;
	      
	  case "saveEditorUserSettings": return $this->sh->setUserSettings(self::escapeSQL($get['noPageLines']),self::escapeSQL($get['contextLines'])); exit;
	    
	  case "setUserEditorSetting": return $this->sh->setUserSetting(self::escapeSQL($get['name']),self::escapeSQL($get['value'])); exit;
	    

	  case "editToken":
	    echo json_encode($this->sh->editToken($get['token_id'], $get['value']));
	    exit;

	  case "deleteToken":
	    echo json_encode($this->sh->deleteToken($get['token_id']));
	    exit;
	    
	  case "addToken":
	    echo json_encode($this->sh->addToken($get['token_id'], $get['value']));
	    exit;

	  default:           self::returnError(400, "Unknown request: " + $get["do"]);
	  }
   }

   self::returnError(400, "Unknown request.");
  }

}

?>