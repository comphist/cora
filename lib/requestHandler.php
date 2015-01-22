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
	  $user = $post["loginform"]["un"];
	  $pw   = $post["loginform"]["pw"];
	  $this->sh->login($user, $pw);
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
      $_SESSION["lang"] = $get["lang"];
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
   * object and return (or rather echo) an associative array encoded
   * as a JSON string.  This array always contains at least the key
   * 'success' indicating the return status of the request.
   *
   * Only a few requests deviate from this behaviour:
   * - "fetchLemmaSugg" outputs an array with lemma suggestions
   *   (without a 'success' value)
   * - "exportFile" directly outputs the exported file
   *
   * The actual request handling is done in @c performJSONRequest() --
   * this function mainly serves as a wrapper to catch all exceptions
   * and ensure that there is always a valid JSON response.
   */
  public function handleJSONRequest($get, $post) {
    // any request causes the user issuing it to be marked as active
    $this->sh->updateLastactive();

    try {
      $status = $this->performJSONRequest($get, $post);
      // make sure a success value is set
      if(!array_key_exists('success', $status) || !is_bool($status['success'])) {
	$status['success'] = false;
      }
    }
    catch (PDOException $ex) {
      $status = array('success' => false,
		      'errors' => array("Unbekannter Datenbankfehler:\n" . $ex->getMessage())
		      );
    }
    catch (Exception $ex) {
      $status = array('success' => false,
		      'errors' => array("Unbekannter Serverfehler:\n" . $ex->getMessage())
		      );
    }

    // return JSON object
    echo json_encode($status);
  }

  public function performJSONRequest($get, $post) {
    /*** POST REQUESTS ***/
    /* Some POST requests are handled in the GET section if they
       happen to signal their action via a URL parameter... this is a
       bit quirky and should probably be changed at some point. */
    if(array_key_exists("action", $post)) {
      switch( $post["action"]) {
      case "changeUserPassword":
	return $this->sh->changeUserPassword($post["oldpw"],
					     $post["newpw"]);

      case "importTagsetTxt":
	$errmsg = $this->checkFileUpload($_FILES['txtFile']);
	if($errmsg) {
	  return array("errors"=>array("Fehler beim Upload: ".$errmsg));
	}
	if(empty($post['tagset_name'])) {
	  return array("errors"=>array("Kein Tagset-Name angegeben."));
	}
	$taglist = explode("\n", file_get_contents($_FILES['txtFile']['tmp_name']));
	return $this->sh->importTagList($taglist, $post['tagset_name']);

      case "importXMLFile":
	$errmsg = $this->checkFileUpload($_FILES['xmlFile']);
	if($errmsg) {
	  return array("errors"=>array("Fehler beim Upload: ".$errmsg));
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
	$options['tagsets'] = $post['linktagsets'];
	$options['project'] = $post['project'];
	return $this->sh->importFile($data,$options);

      case "importTransFile":
	return $this->importTranscription($post);

      default:
	return array("errors" => array("Unknown POST request."));
      }
    }

    /*** GET REQUESTS ***/
    if(array_key_exists("do", $get)) {
      switch($get["do"]) {
      case "keepalive":
        return $this->sh->keepalive();

      case "login":  // we are already logged in
        return array('success' => true);

      case "getLinesById":
	$data = $this->sh->getLinesById($get['start_id'], $get['end_id']);
	return array('success' => true, 'data' => $data);

      case "getAllModernIDs":
        return $this->sh->getAllModernIDs();

      case "getImportStatus":
	return $this->sh->getImportStatus();

      case "fetchTagset":
	return $this->sh->getTagset($get["tagset_id"], $get["limit"]);

      case "changeTagsetsForFile":
        return $this->sh->changeTagsetsForFile($get["file_id"],
                                               $get["linktagsets"]);

      case "fetchTagsetsForFile":
	return $this->sh->fetchTagsetsForFile($get["fileid"]);

      case "fetchLemmaSugg":
	return $this->sh->getLemmaSuggestion($get["linenum"],
					     $get["q"],
					     $get["limit"]);

      case "getUserList":
        return $this->sh->getUserList();

      case "createUser":
	return $this->sh->createUser($post["username"],
				     $post["password"],
				     false);

      case "deleteUser":
	return $this->sh->deleteUser($post["id"]);

      case "toggleAdmin":
	return $this->sh->toggleAdminStatus($post["id"]);

      case "changePassword":
	return $this->sh->changePassword($post["id"],
					 $post["password"]);

      case "getProjectsAndFiles":
	return $this->sh->getProjectsAndFiles();

      case "saveProjectSettings":
        return $this->sh->saveProjectSettings($post);

      case "saveUserSettings":
        return $this->sh->saveUserSettings($post);

      case "lockFile":
	return $this->sh->lockFile($get["fileid"]);

      case "unlockFile":
	return $this->sh->unlockFile($get["fileid"]);

      case "openFile":
	return $this->sh->openFile($get['fileid']);

      case "deleteFile":
	return $this->sh->deleteFile($post["file_id"]);

      case "saveMetadata":
	return $this->sh->saveMetadata($post);

      case "createProject":
	return $this->sh->createProject($get['project_name']);

      case "deleteProject":
	return $this->sh->deleteProject($get['project_id']);

      case "saveData":
	return $this->sh->saveData(json_decode(file_get_contents("php://input"), true));

      case "saveEditorUserSettings":
	$status = $this->sh->setUserSettings($get['noPageLines'],$get['contextLines']);
	return array('success' => $status);

      case "setUserEditorSetting":
	$status = $this->sh->setUserSetting($get['name'],$get['value']);
	return array('success' => $status);

      case "editToken":
	return $this->sh->editToken($get['token_id'], $get['value']);

      case "deleteToken":
	return $this->sh->deleteToken($get['token_id']);

      case "addToken":
	return $this->sh->addToken($get['token_id'], $get['value']);

      case "exportFile":
	$this->sh->exportFile($get['fileid'], $get['format']);
	exit;

      case "performAnnotation":
	return $this->sh->performAnnotation($get['tagger'], $get['action']);

      case "search":
        return $this->sh->searchDocument($get);

      case "adminCreateNotice":
	return $this->sh->createNotice($post);

      case "adminDeleteNotice":
	return $this->sh->deleteNotice($get['id']);

      case "adminGetAllNotices":
	return $this->sh->getAllNotices();

      case "adminGetAllAnnotators":
	return $this->sh->getAllAnnotators();

      case "adminCreateAnnotator":
	return $this->sh->createAnnotator($post);

      case "adminDeleteAnnotator":
	return $this->sh->deleteAnnotator($get['id']);

      case "adminChangeAnnotator":
	return $this->sh->changeAnnotator($post);

      default:
	return array("errors" => array("Unknown GET request."));
      }
    }

    return array("errors" => array("Unknown request."));
  }

  /** Closes the connection to the client without exiting.
   *
   * Sends a JSON response to the client and closes the connection
   * immediately afterwards, so that we can continue performing
   * time-consuming operations in the background.
   *
   * @param object $response Object that will be returned to the
   *                         user in JSON-encoded form
   */
  private function releaseConnection($response) {
    // see:
    // <http://stackoverflow.com/questions/138374/close-a-connection-early>
    // <http://php.net/manual/en/features.connection-handling.php#93441>
    session_write_close();
    set_time_limit(1800);
    ob_end_clean();
    header("Connection: close\r\n");
    header("Content-Encoding: none\r\n");
    ignore_user_abort(true);
    ob_start();
    echo json_encode($response);
    $response_size = ob_get_length();
    header("Content-Length: " . $response_size);
    ob_end_flush();
    flush();
  }

  /** Imports a transcription file into the database.
   *
   * Due to the time-consuming nature of the import process, the
   * client connection is closed before the actual import is started.
   * The status of the import is written to a log file which can be
   * accessed with the "getImportStatus" GET request.
   */
  private function importTranscription($post) {
    $tmpfname = tempnam(sys_get_temp_dir(), 'coraIL');
    $_SESSION['importLogFile'] = $tmpfname;
    $_SESSION['importInProgress'] = true;

    $errmsg = $this->checkFileUpload($_FILES['transFile']);
    if($errmsg) {
      return array("errors"=>array("Fehler beim Upload: ".$errmsg));
    }

    $this->releaseConnection(array("success" => true));

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
    $options['tagsets'] = $post['linktagsets'];
    $options['project'] = $post['project'];
    $options['logfile'] = $tmpfname;

    try {
      $success = $this->sh->importTranscriptionFile($data,$options);
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

    @session_start();
    $_SESSION["importInProgress"] = false;
    exit(); // we don't return because there's no client connection left
  }

}

?>
