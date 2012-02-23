<?php

/** @file requestHandler.php
 * Handle GET and POST requests.
 *
 * @author Marcel Bollmann
 * @date January 2012
 */


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
      	case "login":	$user = self::escapeSQL( $post["loginform"]["un"] );
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
   * @arg @c lockTagset - Lock the tagset specified in @c name
   * @arg @c unlockTagset - Unlock the tagset specified in @c name
   * @arg @c fetchTagset - Retrieve the tagset specified in @c name
   * @arg @c saveTagset - Save modifications to a tagset; expects the
   * data as a JSON string via POST.
   * @arg @c createUser - Create a new user.
   * @arg @c deleteUser - Delete a user.
   * @arg @c toggleAdmin - Toggle administrator status of a user.
   * @arg @c changePassword - Change the password a user.
   * 
   * @return Depending on the type of request, either:
   * @li a @em string in JavaScript Object Notation (JSON);
   * @li an error in the form of HTTP status code 400 or 500;
   * @li or nothing.
   */
  public function handleJSONRequest( $get, $post ) {
	if (array_key_exists("action", $post)) {
		switch( $post["action"]) {
			case "importFile":	$data = file_get_contents($_FILES['textFile']['tmp_name']);
								$name = empty($post['textName']) ? str_replace(".txt","",$_FILES['textFile']['name']) : $post['textName'];
								$pos_tagged = isset($post['tagPOSStatus']) ? 1 : 0;
								$morph_tagged = isset($post['tagMorphStatus']) ? 1 : 0;
								$norm = isset($post['tagNormStatus']) ? 1 : 0;
								$fM = new FileModel($this->sh);
								$importStatus = $fM->importFile($post['tagset'],$pos_tagged,$morph_tagged,$norm,$data);
								if($importStatus['status']){										$this->sh->saveNewFile($name,$_SESSION['user'],$pos_tagged,$morph_tagged,$norm,$post['tagset'],self::escapeSQL($importStatus['data']));
									echo json_encode(array("status"=>true));
								} else {
									// look if tagset is locked, if not lock it!
									$lock = $this->sh->lockTagset(self::escapeSQL($post["tagset"]));
									if(!$lock['success'])
										echo json_encode(array_merge($importStatus,array('locked'=>true,'byuser'=>$lock['lock'][0],'since'=>$lock['lock'][1])));
									else {
										echo json_encode($importStatus);
									}
								}
								
								exit; 
			case "addFileData": $data = file_get_contents($_FILES['textFile']['tmp_name']);
								 $pos_tagged = false; $morph_tagged = false; $norm = false;
								if(strtolower($post['tagType']) == 'morph') {$morph_tagged = true;}
								if(strtolower($post['tagType']) == 'pos') {$pos_tagged = true;}
								if(strtolower($post['tagType']) == 'norm') {$norm = true;}
								
								 $fM = new FileModel($this->sh);
								 $importStatus = $fM->addData($post['fileID'],$post['tagset'],$pos_tagged,$morph_tagged,$norm,$data);
								 if($importStatus['status']){
									$this->sh->saveAddData($post['fileID'],$post['tagType'],self::escapeSQL($importStatus['data']));
									echo json_encode(array("status"=>true));
								} else {
									// look if tagset is locked, if not lock it!
									$lock = $this->sh->lockTagset(self::escapeSQL($post["tagset"]));
									if(!$lock['success'])
										echo json_encode(array_merge($importStatus,array('locked'=>true,'byuser'=>$lock['lock'][0],'since'=>$lock['lock'][1])));
									else {
										echo json_encode($importStatus);
									}
								}
			default:	exit();
		}
	}
	
    if (array_key_exists("do", $get)) {
      switch ( $get["do"] ) {
		case "getHighestTagId": echo $this->sh->getHighestTagId(self::escapeSQL($get["tagset"]));
								 exit;
	
      	case "lockTagset":	$data = $this->sh->lockTagset(self::escapeSQL($get["name"]));
							echo json_encode($data);
							exit;

        case "unlockTagset": $this->sh->unlockTagset(self::escapeSQL($get["name"]));
							exit;

        case "fetchTagset": $data = $this->sh->getTagset(self::escapeSQL($get["name"]));
							echo json_encode($data);
							exit;
							
		case "getTagsetTags": $data = $this->sh->getTagset(self::escapeSQL($get["tagset"]));
							  foreach($data['tags'] as $tag){
								echo "<option>".trim($tag['shortname'])."</option>";
							  }
							  // echo json_encode($data['tags']);
							 exit;

        case "saveTagset":    if ($_SESSION["admin"]) {
	  							$data = json_decode(file_get_contents("php://input"), true);
	  							$this->sh->saveTagset(self::escapeSQL($data));
							}
							exit;
	   case "saveCopyTagset": if ($_SESSION["admin"]) {
						  	  	$data = json_decode($post['tags'], true);
								echo $post['originTagset'];
								echo $post['name'];
						  		$this->sh->saveCopyTagset(self::escapeSQL($data),self::escapeSQL($post['originTagset']),self::escapeSQL($post['name']));
							  }
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
	  							self::returnError(500, "Could not toggle admin status.");
							exit;
														
	    case "listFiles":  $data = $this->sh->getFiles();
						   echo json_encode($data);
						   exit;
		case "getLastImportedFile": return json_encode($this->sh->getLastImportedFile());
						
		case "lockFile":   $data = $this->sh->lockFile(self::escapeSQL($get["fileid"]));
						   $data = json_encode($data);
						   echo $data;
						   exit;
		
		case "unlockFile": $data = $this->sh->unlockFile(self::escapeSQL($get["fileid"]));
						   echo json_encode($data);
						   exit;
		
		case "openFile":   echo json_encode($this->sh->openFile($get['fileid']));
						   exit;
						
		case "deleteFile": $status = $this->sh->deleteFile(self::escapeSQL($post["file_id"]));
						   if (!$status)
					  	   		self::returnError(500, "Could not delete file.");
						   exit;
						
		case "getLines":   $data = $this->sh->getLines(self::escapeSQL($get['page']));
						   echo json_encode($data);
						   exit;
		case "getMaxLinesNo": echo $this->sh->getMaxLinesNo(); exit;
						
	    case "saveTag":	   return $this->sh->saveTag(self::escapeSQL($post['tag_value']),self::escapeSQL($post['tag_name']),$_SESSION['currentFileId'],$post['lineid']);
						   exit;
						
	    case "copyTagset":	exit;
						
		case "importFile": $f = new FileModel($this->sh); $f->importFile();
		
		case "saveEditorUserSettings": return $this->sh->setUserEditorSettings($get['noPageLines'],$get['contextLines']); exit;
		
		case "highlightError": return $this->sh->highlightError($get['line']); exit;
		
		case "unhighlightError": return $this->sh->unhighlightError($get['line']); exit;
		
		case "markLastPosition": return $this->sh->markLastPosition($get['line']); exit;
		
		case "undoLastEdit": $row = $this->sh->undoLastEdit(); echo $row['new_line_id']; exit;
		
		

        default:           self::returnError(400, "Unknown request: " + $get["do"]);
     }
   }

   self::returnError(400, "Unknown request.");
  }

}

?>