<?php

 /** @file connect.php
  * Provide objects for database access.
  *
  * @author Marcel Bollmann
  * @date January 2012
  *
  * @todo Database access information should be moved outside of the
  * web server directory!
  */

 /** The database settings. */
 require_once 'globals.php';
 require_once 'documentModel.php';
 require_once 'commandHandler.php';
 require_once 'connect/DocumentAccessor.php';
 require_once 'connect/DocumentWriter.php';

 /** An interface for application-specific database requests.
  *
  * This class implements all application-specific functionality for
  * database access.  If some part of the application requires that one
  * or more SQL queries be sent to the database, these queries should
  * be encapsulated in a member function of this class.
  */
 class DBInterface {
   private $db;
   private $timeout = 30; // timeout value in minutes

   private $dbo; /* new PDO object for database interaction */

   /** Create a new DBInterface.
    *
    * Also sets the default database to the MAIN_DB constant.
    */
   function __construct($db_server, $db_user, $db_password, $db_name) {
     $this->dbo = new PDO('mysql:host='.$db_server.';dbname='.$db_name.';charset=utf8',
			  $db_user, $db_password);
     $this->dbo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
     $this->db = $db_name;
   }

   /** Return the hash of a given password string. */
   private function hashPassword($pw) {
     return md5(sha1($pw));
   }

   /** Look up username and password. 
    *
    * @param string $user Username to be looked up.
    * @param string $pw Password corresponding to the username.
    *
    * @return An @em array containing the database entry for the given
    * user. If the username is not valid, or if the password is not
    * correct, the query will fail, and an empty object is returned.
    */
   public function getUserData( $user, $pw ) {
     $pw_hash = $this->hashPassword($pw);
     $qs = "SELECT `id`, name, admin, lastactive FROM users"
       . "  WHERE  name=:name AND password=:pw AND `id`!=1 LIMIT 1";
     $stmt = $this->dbo->prepare($qs);
     $stmt->bindValue(':name', $user, PDO::PARAM_STR);
     $stmt->bindValue(':pw', $pw_hash, PDO::PARAM_STR);
     $stmt->execute();
     return $stmt->fetch(PDO::FETCH_ASSOC);
   }

   /** Get user info by id.
    */
   public function getUserById($uid) {
     $qs = "SELECT `id`, name, admin, lastactive FROM users"
       . "  WHERE  `id`=:id";
     $stmt = $this->dbo->prepare($qs);
     $stmt->bindValue(':id', $uid, PDO::PARAM_INT);
     $stmt->execute();
     return $stmt->fetch(PDO::FETCH_ASSOC);
   }

   /** Get user info by name.
    */
   public function getUserByName($uname) {
     $qs = "SELECT `id`, name, admin, lastactive FROM users"
       . "  WHERE name=:name";
     $stmt = $this->dbo->prepare($qs);
     $stmt->bindValue(':name', $uname, PDO::PARAM_STR);
     $stmt->execute();
     return $stmt->fetch(PDO::FETCH_ASSOC);
   }

   /** Get user ID by name.  Often used because formerly, users were
    *  always identified by name (and therefore the app still uses
    *  usernames in most places), while now, users must be identified
    *  by ID in all tables.
    */
   public function getUserIDFromName($uname) {
     $qs = "SELECT `id` FROM users WHERE name=:name";
     $stmt = $this->dbo->prepare($qs);
     $stmt->bindValue(':name', $uname, PDO::PARAM_STR);
     $stmt->execute();
     return $stmt->fetch(PDO::FETCH_COLUMN);
   }

   /** Return settings for the current user. 
    *
    * @param string $user Username
    *
    * @return An array with the database entries from the table 'user_settings' for the given user.
    */
   public function getUserSettings($user){
     $qs = "SELECT lines_per_page, lines_context, "
       . "         columns_order, columns_hidden, show_error "
       . "    FROM users WHERE name=:name";
     $stmt = $this->dbo->prepare($qs);
     $stmt->bindValue(':name', $user, PDO::PARAM_STR);
     $stmt->execute();
     return $stmt->fetch(PDO::FETCH_ASSOC);
   }

   /** Get a list of all users.
    *
    * @return An @em array containing all usernames in the database and
    * information about their admin status.
    */
   public function getUserList($to) {
     $qs = "SELECT `id`, name, admin, lastactive,"
       ."          CASE WHEN lastactive BETWEEN DATE_SUB(NOW(), INTERVAL {$to} MINUTE)"
       ."                                   AND NOW()"
       ."               THEN 1 ELSE 0 END AS active"
       ."     FROM users "
       ."    WHERE `id`!=1"
       ."    ORDER BY name";
     $stmt = $this->dbo->query($qs);
     $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
     return $users;
   }

   /** Get a list of all tagsets.
    *
    * @param string $class Class of the tagset, or false if all classes
    *
    * @return A list of associative arrays, containing the names
    * and IDs of the tagset.
    */
   public function getTagsets($class="POS", $orderby="name") {
     $qs = "SELECT `id`, `id` AS shortname, name AS longname, class, set_type"
       . "    FROM tagset";
     if($class) {
       $qs .= " WHERE `class`=:class";
     }
     $qs .= " ORDER BY {$orderby}";
     $stmt = $this->dbo->prepare($qs);
     if($class) {
       $stmt->bindValue(':class', $class, PDO::PARAM_STR);
     }
     $stmt->execute();
     return $stmt->fetchAll(PDO::FETCH_ASSOC);
   }

   /** Build and return an array containing a full tagset.
    *
    * This function retrieves all valid tags of a given tagset.
    *
    * @param string $tagset The id of the tagset to be retrieved
    * @param string $limit String argument containing "none" or "legal", 
    *                 indicating whether tags marked with "needs_revision"
    *                 should be included or not
    *
    * @return An associative @em array containing the tagset information.
    */
   public function getTagset($tagset, $limit="none") {
     $tags = array();
     $qs  = "SELECT `id`, `value`, `needs_revision` FROM tag "
       . "    WHERE `tagset_id`=:tagsetid";
     if($limit=='legal') {
       $qs .= " AND `needs_revision`=0";
     }
     $qs .= " ORDER BY `value`";
     $stmt = $this->dbo->prepare($qs);
     $stmt->bindValue(':tagsetid', $tagset, PDO::PARAM_INT);
     $stmt->execute();
     return $stmt->fetchAll(PDO::FETCH_ASSOC);
   }

   /** Fetch all tagsets for a given file.
    *
    * Retrieves information about tagsets associated with a given
    * file, including all tags for each closed class tagset.
    *
    * @param string $fileid A file ID
    */
   public function fetchTagsetsForFile($fileid) {
     $da = new DocumentAccessor($this, $this->dbo, $fileid);
     $da->retrieveTagsetInformation();
     return $da->getTagsets();
   }

   /** Build and return an array containing a full tagset.
    *
    * This function retrieves all valid tags of a given tagset; the
    * difference to the @c getTagset() is that this function returns an
    * array mapping tags to their IDs in the database, which is useful
    * when importing new documents.
    *
    * @param string $tagset The id of the tagset to be retrieved
    */
   public function getTagsetByValue($tagset) {
     $qs = "SELECT `value`, `id` FROM tag WHERE `tagset_id`=:tagsetid";
     $stmt = $this->dbo->prepare($qs);
     $stmt->bindValue(':tagsetid', $tagset, PDO::PARAM_INT);
     $stmt->execute();
     return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
   }

   /** Updates the "last active" timestamp for a user.
    *
    * @param string  $userid   ID of the user to be updated
    */
   public function updateLastactive($userid) {
     try {
       $qs = "UPDATE users SET `lastactive`=NOW() WHERE `id`=:id";
       $stmt = $this->dbo->prepare($qs);
       $stmt->bindValue(':id', $userid, PDO::PARAM_INT);
       $stmt->execute();
       return true;
     }
     catch (PDOException $ex) {
       return false;
     }
   }

   /** Create a new user.
    *
    * @param string  $username The username to be created
    * @param string  $password The desired password
    * @param boolean $admin    Whether the user should have administrator status
    *
    * @return The result of the corresponding @c mysql_query() command.
    */ 
   public function createUser($username, $password, $admin) {
     $user = $this->getUserByName($username);
     if(!empty($user)) { // username already exists
       return false;
     }
     $hashpw = $this->hashPassword($password);
     $adm = $admin ? 1 : 0;
     $qs = "INSERT INTO users (name, password, admin) "
       . "  VALUES (:name, :pw, {$adm})";
     $stmt = $this->dbo->prepare($qs);
     $stmt->bindValue(':name', $username, PDO::PARAM_STR);
     $stmt->bindValue(':pw', $hashpw, PDO::PARAM_STR);
     $stmt->execute();
     return $stmt->rowCount();
   }

   /** Change the password for a user.
    *
    * @param string $username The username for which the password
    * should be changed.
    * @param string $password The new password
    *
    * @return The result of the corresponding @c mysql_query() command.
    */
   public function changePassword($username, $password) {
     $hashpw = $this->hashPassword($password);
     $qs = "UPDATE users SET password=:pw WHERE name=:name";
     $stmt = $this->dbo->prepare($qs);
     $stmt->bindValue(':name', $username, PDO::PARAM_STR);
     $stmt->bindValue(':pw', $hashpw, PDO::PARAM_STR);
     $stmt->execute();
     return $stmt->rowCount();
   }

   /** Change project<->user associations.
    *
    * @param string $pid the project ID of the project to change
    * @param array $userlist an array of user names
    *
    * @return The result of the corresponding @c mysql_query() command
    */
   public function changeProjectUsers($pid, $userlist) {
     try {
       $this->dbo->beginTransaction();
       $qs = "DELETE FROM user2project WHERE project_id=:pid";
       $stmt = $this->dbo->prepare($qs);
       $stmt->bindValue(':pid', $pid, PDO::PARAM_INT);
       $stmt->execute();
       if (!empty($userlist)) {
	 $uid = null;
	 $qs = "INSERT INTO user2project (project_id, user_id) "
	   . "  VALUES (:pid, :uid)";
	 $stmt = $this->dbo->prepare($qs);
	 $stmt->bindValue(':pid', $pid, PDO::PARAM_INT);
	 $stmt->bindParam(':uid', $uid, PDO::PARAM_INT);
	 foreach ($userlist as $uname) {
	   $uid = $this->getUserIDFromName($uname);
	   $stmt->execute();
	 }
       }
       $this->dbo->commit();
       return array('success' => true);
     }
     catch(PDOException $ex) {
       $this->dbo->rollBack();
       return array('success' => false, 'message' => $ex->getMessage());
     }
   }

   /** Drop a user record from the database.
    *
    * @param string $username The username to be deleted
    *
    * @return The result of the corresponding @c mysql_query() command.
    */
   public function deleteUser($username) {
     $qs = "DELETE FROM users WHERE name=:name AND `id`!=1";
     $stmt = $this->dbo->prepare($qs);
     $stmt->bindValue(':name', $username, PDO::PARAM_STR);
     $stmt->execute();
     return $stmt->rowCount();
   }

   /** Toggle administrator status of a user.
    *
    * @param string $username The username for which the administrator
    * status should be changed
    *
    * @return The result of the corresponding @c mysql_query() command.
    */
   public function toggleAdminStatus($username) {
     return $this->toggleUserStatus($username, 'admin');
   }

   /** Helper function for @c toggleAdminStatus(). */
   public function toggleUserStatus($username, $statusname) {
     $qs = "SELECT {$statusname} FROM users WHERE name=:name";
     $stmt = $this->dbo->prepare($qs);
     $stmt->execute(array(':name' => $username));
     $oldstat = $stmt->fetch(PDO::FETCH_COLUMN);
     $newstat = ($oldstat == 1) ? 0 : 1;
     $qs = "UPDATE users SET {$statusname}={$newstat} WHERE name=:name";
     $stmt = $this->dbo->prepare($qs);
     $stmt->execute(array(':name' => $username));
     return $stmt->rowCount();
   }

   /** Lock a file for editing.
    *
    * This function ensures restricted access when editing file
    * data. In detail this is done by a lock table where first all
    * entries for the given user are deleted and then tries to insert
    * a new entry for the given file id. Note that if the file is
    * already locked by another user, the operation will fail und it
    * is impossible to lock the file at the moment.
    *
    * Should be called before editing any database entries concerning the file content.
    *
    * $locksCount indicates the number of file which were locked by
    * the given user and are unlocked now.
    *
    * @param string $fileid file id
    * @param string $user username
    *
    * @return An @em array which minimally contains the key @c success,
    * which is set to @c true if the lock was successful. If set to @c
    * false, a key named @c lock contains further information about the
    * already-existing, conflicting lock.
    */	  
   public function lockFile($fileid,$uname) {
     // first, check if file exists
     $stmt = $this->dbo->prepare("SELECT COUNT(*) FROM text WHERE `id`=:textid");
     $stmt->execute(array(':textid' => $fileid));
     if($stmt->fetch(PDO::FETCH_COLUMN)==0) {
       return array("success" => false);
     }
     // then, delete all locks by the current user
     $user = $this->getUserIDFromName($uname);
     $stmt = $this->dbo->prepare("DELETE FROM locks WHERE user_id=:uid");
     $stmt->execute(array(':uid' => $user));
     $locksCount = $stmt->rowCount();
     // then, check if file is still/already locked
     $stmt = $this->dbo->prepare("SELECT * FROM locks WHERE text_id=:textid");
     $stmt->execute(array(':textid' => $fileid));
     if($stmt->rowCount()>0) {
       // if file is locked, return info about user currently locking the file
       $qs  = "SELECT a.lockdate as 'locked_since', b.name as 'locked_by' "
	 . "     FROM locks a, users b "
	 . "    WHERE a.text_id=:textid AND a.user_id=b.id";
       $stmt = $this->dbo->prepare($qs);
       $stmt->execute(array(':textid' => $fileid));
       return array("success" => false, "lock" => $stmt->fetch(PDO::FETCH_ASSOC));
     }
     // otherwise, perform the lock
     $qs = "INSERT INTO locks (text_id, user_id) VALUES (:textid, :uid)";
     $stmt = $this->dbo->prepare($qs);
     $stmt->execute(array(':textid' => $fileid, ':uid' => $user));
     return array("success" => true, "lockCounts" => (string) $locksCount);
   }

   /** Get locked file for given user.
    *
    * Retrieves the lock entry for a given user from the file lock table.
    *
    * @param string $user username
    *
    * @return An @em array with file id and file name of the locked file
    */	
   public function getLockedFiles($uname){
     $uid = $this->getUserIDFromName($uname);
     $qs  = "SELECT a.text_id as 'file_id', b.fullname as 'file_name' "
       . "     FROM locks a, text b "
       . "    WHERE a.user_id={$uid} AND a.text_id=b.id LIMIT 1";
     $stmt = $this->dbo->prepare($qs);
     $stmt->execute();
     return $stmt->fetch(PDO::FETCH_ASSOC);
   }

   /** Unlock a file for the current user.
    *
    * Deletes the lock entry for the current user from the file lock table.
    *
    * @todo move SESSION data out of this function
    *
    * @param string $fileid file id
    * @param string $user username (defaults to current session user)
    *
    * @return @em array result of the mysql query
    */		
   public function unlockFile($fileid,$uname="",$force=false) {
     if(empty($uname)) {
       $userid = $_SESSION['user_id'];
     } else {
       $userid = $this->getUserIDFromName($uname);
     }
     $qs = "DELETE FROM locks WHERE text_id=:tid";
     if (!$force) {
       $qs .= " AND user_id={$userid}";
     }
     $stmt = $this->dbo->prepare($qs);
     $stmt->execute(array(':tid' => $fileid));
     return $stmt->rowCount();
   }

   /** Locks a project for automatic annotation.
    *
    * @param string $pid The project ID 
    * @param string $tid The tagger ID
    *
    * @return A boolean indicating whether the lock was successful
    */
   public function lockProjectForTagger($pid,$tid) {
     $qs = "SELECT COUNT(*) FROM tagger_locks WHERE `project_id`=:pid";
     $stmt = $this->dbo->prepare($qs);
     $stmt->execute(array(':pid' => $pid));
     if($stmt->fetch(PDO::FETCH_COLUMN)!=0) {
       return false;
     }
     $qs = "INSERT INTO tagger_locks (tagger_id, project_id)"
       . "  VALUES (:tid, :pid)";
     $stmt = $this->dbo->prepare($qs);
     $stmt->execute(array(':tid' => $tid, ':pid' => $pid));
     return true;
   }

   /** Releases the annotation lock for a given project.
    *
    * @param string $pid The project ID 
    */
   public function unlockProjectForTagger($pid) {
     $qs = "DELETE FROM tagger_locks WHERE project_id=:pid";
     $stmt = $this->dbo->prepare($qs);
     $stmt->execute(array(':pid' => $pid));
   }

   /** Get metadata for a list of tagsets.
    *
    * @param array $idlist An array of tagset IDs
    *
    * @return An array containing one associative array with metadata
    * for each tagset in the input list.
    */
   public function getTagsetMetadata($idlist) {
     $qs  = "SELECT ts.id, ts.name, ts.class, ts.set_type "
       . "     FROM tagset ts "
       . "    WHERE ts.id=?";
     $stmt = $this->dbo->prepare($qs);
     $stmt->execute($idlist);
     return $stmt->fetchAll(PDO::FETCH_ASSOC);
   }

   /** Get tagsets associated with a file.
    *
    * Retrieves a list of tagsets with their respective class for the
    * given file.
    */
   public function getTagsetsForFile($fileid) {
     $qs  = "SELECT ts.id, ts.name, ts.class, ts.set_type "
       . "     FROM text2tagset ttt "
       . "     LEFT JOIN tagset ts  ON ts.id=ttt.tagset_id "
       . "    WHERE ttt.text_id=:tid";
     $stmt = $this->dbo->prepare($qs);
     $stmt->execute(array(':tid' => $fileid));
     return $stmt->fetchAll(PDO::FETCH_ASSOC);
   }

   /** Get a list of all taggers with their associated tagset links.
    */
   public function getTaggerList() {
     $tlist = array();
     $qs = "SELECT t.id, t.name, (t.cmd_train IS NOT NULL) AS trainable, "
       . "         t.cmd_train, t.cmd_tag, ts.tagset_id "
       . "    FROM tagger2tagset ts "
       . "    LEFT JOIN tagger t ON t.id=ts.tagger_id";
     $stmt = $this->dbo->prepare($qs);
     $stmt->execute();
     while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
       if(array_key_exists($row['id'], $tlist)) {
	 $tlist[$row['id']]['tagsets'][] = $row['tagset_id'];
       }
       else {
	 $tlist[$row['id']] = array('name' => $row['name'],
				    'trainable' => $row['trainable']==1 ? true : false,
				    'cmd_train' => $row['cmd_train'],
				    'cmd_tag' => $row['cmd_tag'],
				    'tagsets' => array($row['tagset_id']));
       }
     }
     return $tlist;
   }

   /** Get applicable taggers for a given file.
    *
    * Returns information about taggers where all associated tagsets
    * are also associated with the given file.
    */
   public function getTaggersForFile($fileid) {
     $applicable = array();
     $tslist = array();
     $taggers = $this->getTaggerList();
     $tagsets = $this->getTagsetsForFile($fileid);
     foreach($tagsets as $ts) {
       $tslist[] = $ts['id'];
     }
     foreach($taggers as $id => $tagger) {
       $is_applicable = true;
       foreach($tagger['tagsets'] as $ts) {
	 if(!in_array($ts, $tslist)) {
	   $is_applicable = false;
	 }
       }
       if($is_applicable) {
	 $applicable[] = array('id' => $id,
			       'name' => $tagger['name'],
			       'trainable' => $tagger['trainable'],
			       'tagsets' => $tagger['tagsets']);
       }
     }
     return $applicable;
   }

   /** Open a file.
    *
    * Retrieves metadata and users progress data for the given file.
    *
    * @todo move SESSION data out of this function
    *
    * @param string $fileid file id
    *
    * @return an @em array with at least the file meta data. If exists, the user's last edited row is also transmitted.
    */
   public function openFile($fileid){
     $locked = $this->lockFile($fileid, $_SESSION["user"]);
     if(!$locked['success']) {
       return array('success' => false);
     }

     $qs  = "SELECT text.id, text.sigle, text.fullname, text.project_id, ";
     $qs .= "       text.currentmod_id, text.header, tagset.id AS 'tagset_id' ";
     $qs .= "  FROM (text, text2tagset ttt) ";
     $qs .= "  LEFT JOIN tagset ON ttt.tagset_id=tagset.id ";
     $qs .= " WHERE text.id=:tid AND tagset.class='POS' AND ttt.text_id=:tid";
     $stmt = $this->dbo->prepare($qs);
     $stmt->execute(array(':tid' => $fileid));
     $metadata = $stmt->fetch(PDO::FETCH_ASSOC);
     $cmid = $metadata['currentmod_id'];
     $lock['lastEditedRow'] = -1;
     if(!empty($cmid)) {
       // calculate position of currentmod_id
       $qs  = "SELECT position FROM ";
       $qs .= " (SELECT x.id, @rownum := @rownum + 1 AS position FROM ";
       $qs .= "   (SELECT a.id FROM (modern a, token b) ";
       $qs .= "    WHERE a.tok_id=b.id AND b.text_id=:tid ";
       $qs .= "    ORDER BY b.ordnr ASC, a.id ASC) x ";
       $qs .= "  JOIN (SELECT @rownum := 0) r) y ";
       $qs .= "WHERE y.id = {$cmid}";
       $stmt = $this->dbo->prepare($qs);
       $stmt->execute(array(':tid' => $fileid));
       $position = $stmt->fetch(PDO::FETCH_COLUMN);
       $lock['lastEditedRow'] = intval($position) - 1;
     }

     $metadata['tagsets'] = $this->getTagsetsForFile($fileid);
     $metadata['taggers'] = $this->getTaggersForFile($fileid);
     $lock['data'] = $metadata;
     $lock['success'] = true;
     return $lock;
   }

   /** Check whether a user is allowed to open a file.
    *
    * @param string $fileid file id
    * @param string $user username
    *
    * @return boolean value indicating whether user may open the
    *         file
    */
   public function isAllowedToOpenFile($fileid, $uname){
     $uid = $this->getUserIDFromName($uname);
     $qs  = "SELECT a.fullname FROM (text a, user2project b) ";
     $qs .= "WHERE a.id=:tid AND b.user_id=:uid AND a.project_id=b.project_id";
     $stmt = $this->dbo->prepare($qs);
     $stmt->execute(array(':tid' => $fileid, ':uid' => $uid));
     return ($stmt->fetch()) ? true : false;
   }

   /** Check whether a user is allowed to delete a file.
    *
    * @param string $fileid file id
    * @param string $user username
    *
    * @return boolean value indicating whether user may delete the
    *         file
    */
   public function isAllowedToDeleteFile($fileid, $user){
     return $this->isAllowedToOpenFile($fileid, $user);
   }

   /** Delete a file.
    *
    * Deletes ALL database entries linked with the given file id.
    *
    * @param string $fileid file id
    *
    * @return bool @c true 
    */	
   public function deleteFile($fileid){
     // first, get all open class tags associated with this file as
     // they have to be deleted separately
     $qs  = "SELECT tag.id AS tag_id FROM tag "
       . "     LEFT JOIN tag_suggestion ts ON tag.id=ts.tag_id "
       . "     LEFT JOIN tagset            ON tagset.id=tag.tagset_id "
       . "     LEFT JOIN modern            ON modern.id=ts.mod_id "
       . "     LEFT JOIN token             ON token.id=modern.tok_id "
       . "     LEFT JOIN text              ON text.id=token.text_id "
       . "   WHERE  tagset.set_type='open' AND text.id=:tid";
     $stmt = $this->dbo->prepare($qs);
     try {
       $stmt->execute(array(':tid' => $fileid));
       $deletetag = $stmt->fetchAll(PDO::FETCH_COLUMN);
     }
     catch(PDOException $ex) {
       return "Ein interner Fehler ist aufgetreten (Code: 1040).\n" . $ex->getMessage();
     }

     $this->dbo->beginTransaction();

     // delete associated open class tags
     if(!empty($deletetag)) {
       $qs = "DELETE FROM tag_suggestion WHERE `tag_id` IN (" 
	 . implode(",", $deletetag) . ")";
       try {
	 $stmt = $this->dbo->prepare($qs);
	 $stmt->execute();
       }
       catch(PDOException $ex) {
	 $this->dbo->rollBack();
	 return "Ein interner Fehler ist aufgetreten (Code: 1041).\n" . $ex->getMessage();
       }
       $qs = "DELETE FROM tag WHERE `id` IN (" . implode(",", $deletetag) . ")";
       try {
	 $stmt = $this->dbo->prepare($qs);
	 $stmt->execute();
       }
       catch(PDOException $ex) {
	 $this->dbo->rollBack();
	 return "Ein interner Fehler ist aufgetreten (Code: 1043).\n" . $ex->getMessage();
       }
     }

     // delete text---deletions in all other tables are triggered
     // automatically in the database via ON DELETE CASCADE
     try {
       $qs = "DELETE FROM text WHERE `id`=:tid";
       $stmt = $this->dbo->prepare($qs);
       $stmt->execute(array(':tid' => $fileid));
     }
     catch(PDOException $ex) {
       $this->dbo->rollBack();
       return "Ein interner Fehler ist aufgetreten (Code: 1042).\n" . $ex->getMessage();
     }

     $this->dbo->commit();
     return false;
   }

   /** Get a list of all files.
    *
    * Retrieves meta information such as filename, created by
    * user, locked by user, etc for all files.  Should only be
    * called for administrators; otherwise, use @c
    * getFilesForUser() function.
    *
    * @return an two-dimensional @em array with the meta data
    */		
   public function getFiles(){
     $qs = "SELECT a.id, a.sigle, a.fullname, a.created, "
       . "         a.creator_id, a.changer_id, "
       . "         a.changed, a.currentmod_id, c.name as opened, "
       . "         d.id as project_id, d.name as project_name, "
       . "         e.name as creator_name, f.name as changer_name "
       . "     FROM text a "
       . "LEFT JOIN locks b ON a.id=b.text_id "
       . "LEFT JOIN users c ON b.user_id=c.id "
       . "LEFT JOIN project d ON a.project_id=d.id "
       . "LEFT JOIN users e ON a.creator_id=e.id "
       . "LEFT JOIN users f ON a.changer_id=f.id "
       . "ORDER BY sigle, fullname";
     $stmt = $this->dbo->prepare($qs);
     $stmt->execute();
     return $stmt->fetchAll(PDO::FETCH_ASSOC);
   }

   /** Get a list of all files in projects accessible by a given
    * user.
    *
    * Retrieves meta information such as filename, created by
    * user, locked by user, etc for all files.
    *
    * @param string $user username
    * @return an two-dimensional @em array with the meta data
    */		
   public function getFilesForUser($uname){
     $uid = $this->getUserIDFromName($uname);
     $qs = "SELECT a.id, a.sigle, a.fullname, a.created, "
       . "         a.creator_id, a.changer_id, "
       . "         a.changed, a.currentmod_id, c.name as opened, "
       . "         d.id as project_id, d.name as project_name, "
       . "         e.name as creator_name, f.name as changer_name "
       . "    FROM (text a, user2project g) "
       . "LEFT JOIN locks b ON a.id=b.text_id "
       . "LEFT JOIN users c ON b.user_id=c.id "
       . "LEFT JOIN project d ON a.project_id=d.id "
       . "LEFT JOIN users e ON a.creator_id=e.id "
       . "LEFT JOIN users f ON a.changer_id=f.id "
       . "WHERE (a.project_id=g.project_id AND g.user_id={$uid}) "
       . "ORDER BY sigle, fullname";
     $stmt = $this->dbo->prepare($qs);
     $stmt->execute();
     return $stmt->fetchAll(PDO::FETCH_ASSOC);
   }

   /** Get a list of all projects.  
    *
    * Should only be called for administrators; otherwise, use @c
    * getProjectsForUser() function.
    *
    * @param string $user username
    * @return a two-dimensional @em array with the project id and name
    */
   public function getProjects(){
     $stmt = $this->dbo->prepare("SELECT * FROM project ORDER BY name");
     $stmt->execute();
     return $stmt->fetchAll(PDO::FETCH_ASSOC);
   }

   /** Get a list of all project user groups.  
    *
    * Should only be called for administrators.
    *
    * @return a two-dimensional @em array with the project id and name
    */
   public function getProjectUsers(){
     $qs = "SELECT user2project.project_id, users.name AS username "
       . "    FROM user2project "
       . "    LEFT JOIN users ON user2project.user_id=users.id";
     $stmt = $this->dbo->prepare($qs);
     $stmt->execute();
     return $stmt->fetchAll(PDO::FETCH_ASSOC);
   }

   /** Get a list of all projects accessible by a given user.
    *
    * @param string $user username
    * @return a two-dimensional @em array with the project id and name
    */
   public function getProjectsForUser($uname){
     $uid = $this->getUserIDFromName($uname);
     $qs = "SELECT a.* FROM (project a, user2project b) "
       . "   WHERE (a.id=b.project_id AND b.user_id='{$uid}') ORDER BY `id`";
     $stmt = $this->dbo->prepare($qs);
     $stmt->execute();
     return $stmt->fetchAll(PDO::FETCH_ASSOC);
   }

   /** Get the project ID of a given file.
    *
    * @param int $fileid A file ID
    * @return the project ID of the given file
    */
   public function getProjectForFile($fileid) {
     $qs = "SELECT `project_id` FROM text WHERE `id`=:fileid";
     $stmt = $this->dbo->prepare($qs);
     $stmt->bindParam(':fileid', $fileid, PDO::PARAM_INT);
     $stmt->execute();
     return $stmt->fetch(PDO::FETCH_COLUMN);
   }

   /** Create a new project.
    *
    * @param string $name project name
    * @return the project ID of the newly generated project
    */
   public function createProject($name){
     try {
       $qs = "INSERT INTO project (`name`) VALUES (:name)";
       $stmt = $this->dbo->prepare($qs);
       $stmt->bindValue(':name', $name, PDO::PARAM_STR);
       $stmt->execute();
       return array("success" => true, "pid" => $this->dbo->lastInsertId());
     }
     catch (PDOException $ex) {
       return array("success" => false, "errors" => array($ex->getMessage()));
     }
   }

   /** Deletes a project.  Will fail unless no document is
    * assigned to the project.
    *
    * @param string $pid the project id
    * @return a boolean value indicating success
    */
   public function deleteProject($pid){
     try {
       $stmt = $this->dbo->prepare("DELETE FROM project WHERE `id`=:pid");
       $stmt->bindValue(':pid', $pid, PDO::PARAM_INT);
       $stmt->execute();
       $result = ($stmt->rowCount()>0) ? true : false;
     }
     catch (PDOException $ex) {
       return false;
     }
     return $result;
   }

   /** Save settings for given user.
    *
    * @param string $user username
    * @param string $lpp number of lines per page
    * @param string $cl number of context lines
    *
    * @return bool result of the mysql query
    */			
   public function setUserSettings($user,$lpp,$cl){
     $qs = "UPDATE users SET lines_per_page=:lpp, lines_context=:cl"
       . "   WHERE name=:name AND `id`!=1";
     $stmt = $this->dbo->prepare($qs);
     $stmt->bindValue(':lpp', $lpp, PDO::PARAM_INT);
     $stmt->bindValue(':cl', $cl, PDO::PARAM_INT);
     $stmt->bindValue(':name', $user, PDO::PARAM_STR);
     $stmt->execute();
     return $stmt->rowCount();
   }

   /** Save a setting for given user.
    *
    * @param string $user username
    * @param string $name name of the setting, e.g. "contextLines"
    * @param string $value new value of the setting
    *
    * @return bool result of the mysql query
    */
   public function setUserSetting($user,$name,$value) {
     $validnames = array("lines_context", "lines_per_page", "show_error",
			 "columns_order", "columns_hidden");
     if (in_array($name,$validnames)) {
       $qs = "UPDATE users SET {$name}=:value WHERE name=:user AND `id`!=1";
       $stmt = $this->dbo->prepare($qs);
       $stmt->bindValue(':value', $value, PDO::PARAM_STR);
       $stmt->bindValue(':user', $user, PDO::PARAM_STR);
       $stmt->execute();
       return $stmt->rowCount();
     }
     return false;
   }

   /** Return the total number of lines of a given file.
    *
    * @param string $fileid the ID of the file
    *
    * @return The number of lines for the given file
    */
   public function getMaxLinesNo($fileid){
     $qs  = "SELECT COUNT(modern.id) FROM token ";
     $qs .= "LEFT JOIN modern ON modern.tok_id=token.id ";
     $qs .= "WHERE token.text_id=:tid";
     $stmt = $this->dbo->prepare($qs);
     $stmt->execute(array(':tid' => $fileid));
     return $stmt->fetch(PDO::FETCH_COLUMN);
   }

   /** Return all error annotations for a given mod.
    *
    * @param string $mid A mod ID
    *
    * @return An array of all selected error annotations
    */
   protected function getErrorsForModern($mid) {
     $qs  = "SELECT error_types.name ";
     $qs .= "FROM   modern ";
     $qs .= "  LEFT JOIN mod2error ON modern.id=mod2error.mod_id ";
     $qs .= "  LEFT JOIN error_types ON mod2error.error_id=error_types.id ";
     $qs .= "WHERE  modern.id=" . $mid;
     $stmt = $this->dbo->prepare($qs);
     $stmt->execute();
     return $stmt->fetchAll(PDO::FETCH_COLUMN);
   }

   /** Retrieves all layout information for a given document. */
   public function getLayoutInfo($fileid) {
     $pages = array();
     $columns = array();
     $lines = array();
     $currentpage = null;
     $currentcolumn = null;

     $qs = "SELECT p.id AS page_id, p.name AS page_name, "
       ."          p.side AS page_side, p.num AS page_num, "
       ."          c.id AS col_id,  c.name AS col_name,  c.num AS col_num, "
       ."          l.id AS line_id, l.name AS line_name, l.num AS line_num "
       ."     FROM page p "
       ."LEFT JOIN col  c ON c.page_id=p.id "
       ."LEFT JOIN line l ON l.col_id=c.id "
       ."    WHERE p.text_id=:tid "
       ." ORDER BY p.num ASC, c.num ASC, l.num ASC";
     $stmt = $this->dbo->prepare($qs);
     $stmt->execute(array(':tid' => $fileid));
     $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
     foreach($result as $row) {
       if($row['page_id'] != $currentpage) {
	 $currentpage = $row['page_id'];
	 $pages[] = array('db_id' => $row['page_id'],
			  'name'  => $row['page_name'],
			  'side'  => $row['page_side'],
			  'num'   => $row['page_num']);
       }
       if($row['col_id'] != $currentcolumn) {
	 $currentcolumn = $row['col_id'];
	 $columns[] = array('db_id' => $row['col_id'],
			    'name'  => $row['col_name'],
			    'num'   => $row['col_num']);
       }
       $lines[] = array('db_id' => $row['line_id'],
			'name'  => $row['line_name'],
			'num'   => $row['line_num']);
     }

     return array($pages, $columns, $lines);
   }

   /** Retrieves all shift tags for a given document. */
   public function getShiftTags($fileid) {
     $shifttags = array();
     $qs = "SELECT shifttags.tok_from, shifttags.tok_to, shifttags.tag_type "
       ."     FROM shifttags "
       ."     JOIN token ON token.id=shifttags.tok_from "
       ."    WHERE token.text_id=:tid";
     $stmt = $this->dbo->prepare($qs);
     $stmt->execute(array(':tid' => $fileid));
     $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
     foreach($result as $row) {
       $tag = array("type_letter" => $row['tag_type'],
		    "db_range" => array($row['tok_from'], $row['tok_to']));
       $shifttags[] = $tag;
     }
     return $shifttags;
   }

   /** Retrieves all (non-CorA) comments for a given document. */
   public function getComments($fileid) {
     $qs = "SELECT comment.tok_id AS parent_db_id, comment.value AS text,"
       ."          comment.comment_type AS type "
       ."     FROM comment "
       ."     JOIN token ON token.id=comment.tok_id "
       ."    WHERE token.text_id=:tid";
     $stmt = $this->dbo->prepare($qs);
     $stmt->execute(array(':tid' => $fileid));
     return $stmt->fetchAll(PDO::FETCH_ASSOC);
   }

   /** Retrieves all tokens from a file.
    *
    * This function returns an array with all tokens belonging to a
    * given file, in correct order, containing all information
    * associated with the respective token entries.  It is mainly
    * intended to be used during export.
    *
    * @param string $fileid ID of the file
    *
    * @return an @em array containing the tokens
    */
   public function getAllTokens($fileid) {
     $tokens  = array();
     $dipls   = array();
     $moderns = array();

     // tokens
     $qs = "SELECT token.id AS db_id, token.trans"
       ."     FROM token "
       ."    WHERE token.text_id=:tid"
       ."    ORDER BY token.ordnr ASC";
     $stmt = $this->dbo->prepare($qs);
     $stmt->execute(array(':tid' => $fileid));
     $tokens = $stmt->fetchAll(PDO::FETCH_ASSOC);

     // dipls
     $qs = "SELECT token.id AS parent_tok_db_id, dipl.id AS db_id, "
       ."          dipl.line_id AS parent_line_db_id, dipl.utf, dipl.trans "
       ."     FROM token "
       ."    INNER JOIN dipl ON dipl.tok_id=token.id "
       ."    WHERE token.text_id=:tid "
       ."    ORDER BY token.ordnr ASC, dipl.id ASC ";
     $stmt = $this->dbo->prepare($qs);
     $stmt->execute(array(':tid' => $fileid));
     $dipls = $stmt->fetchAll(PDO::FETCH_ASSOC);

     /* TODO: this is temporary until "verified" status is marked
	directly with the modern */
     $qs = "SELECT `currentmod_id` FROM text WHERE `id`=:tid";
     $stmt = $this->dbo->prepare($qs);
     $stmt->execute(array(':tid' => $fileid));
     $currentmod_id = $stmt->fetch(PDO::FETCH_COLUMN);
     $verified = ($currentmod_id && $currentmod_id != null && !empty($currentmod_id));

     // moderns
     $qs = "SELECT token.id AS parent_tok_db_id, modern.id AS db_id, "
       ."          modern.trans, modern.utf, modern.ascii, c1.value AS comment "
       ."     FROM token "
       ."    INNER JOIN modern ON modern.tok_id=token.id "
       ."     LEFT JOIN comment c1 ON  c1.tok_id=token.id "
       ."           AND c1.subtok_id=modern.id AND c1.comment_type='C' "
       ."    WHERE token.text_id=:tid "
       ."    ORDER BY token.ordnr ASC, modern.id ASC";
     $stmt = $this->dbo->prepare($qs);
     $stmt->execute(array(':tid' => $fileid));
     $moderns = $stmt->fetchAll(PDO::FETCH_ASSOC);
     foreach($moderns as &$row) {
       // Annotations
       $qs  = "SELECT tag.value AS tag, ts.score, ts.selected, ts.source,"
	 ."           tt.class AS type "
	 ."      FROM   modern"
	 ."      LEFT JOIN (tag_suggestion ts, tag) "
	 ."             ON (ts.tag_id=tag.id AND ts.mod_id=modern.id) "
	 ."      LEFT JOIN tagset tt ON tag.tagset_id=tt.id "
	 ."     WHERE modern.id=" . $row['db_id'];
       $stmt = $this->dbo->prepare($qs);
       $stmt->execute();
       $row['tags'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
       $row['errors'] = $this->getErrorsForModern($row['db_id']);

       /* TODO: this is temporary until "verified" status is marked
	  directly with the modern */
       $row['verified'] = $verified;
       if($row['db_id'] == $currentmod_id) {
	 $verified = false;
       }
     }
     unset($row);

     return array($tokens, $dipls, $moderns);
   }

   /** Retrieves a specified number of lines from a file.
    *
    * This function is intended to be called via AJAX requests from
    * the client.  The values it returns are specifically adapted to
    * the requirements of the client interface.
    *
    * @param string $fileid the file id
    * @param string $start line id of the first line to be retrieved
    * @param string $lim numbers of lines to be retrieved
    *
    * @return an @em array containing the lines
    */ 	
   public function getLines($fileid,$start,$lim){
     $qs  = "SELECT x.* FROM ";
     $qs .= "  (SELECT q.*, @rownum := @rownum + 1 AS num FROM ";
     $qs .= "    (SELECT modern.id, modern.trans, modern.utf, ";
     $qs .= "            modern.tok_id, token.trans AS full_trans, "; // full_trans is currently being overwritten later!
     $qs .= "            c1.value AS comment "; // ,c2.value AS k_comment
     $qs .= "     FROM   token ";
     $qs .= "       LEFT JOIN modern  ON modern.tok_id=token.id ";
     $qs .= "       LEFT JOIN comment c1 ON  c1.tok_id=token.id ";
     $qs .= "             AND c1.subtok_id=modern.id AND c1.comment_type='C' ";
     //$qs .= "       LEFT JOIN comment c2 ON  c2.tok_id=token.id ";
     //$qs .= "                                        AND c2.comment_type='K' ";
     $qs .= "     WHERE  token.text_id=:tid ";
     $qs .= "     ORDER BY token.ordnr ASC, modern.id ASC) q ";
     $qs .= "   JOIN (SELECT @rownum := -1) r WHERE q.id IS NOT NULL) x ";
     if($lim && $lim != null && $lim>0) {
       $qs .= "LIMIT {$start},{$lim}";
     }
     $stmt = $this->dbo->prepare($qs);
     $stmt->execute(array(':tid' => $fileid));
     $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

     /* Prepare statements for loop */
     $qs  = "SELECT `trans`, `line_id` FROM dipl ";
     $qs .= " WHERE `tok_id`=:tokid ORDER BY `id` ASC";
     $stmt_trans = $this->dbo->prepare($qs);
     $qs  = "SELECT l.name AS line_name, c.name AS col_name, l.num AS line_num, ";
     $qs .= "       p.name AS page_name, p.side AS page_side, p.num AS page_num  ";
     $qs .= "FROM   dipl d ";
     $qs .= "  LEFT JOIN line l ON l.id=d.line_id ";
     $qs .= "  LEFT JOIN col  c ON c.id=l.col_id ";
     $qs .= "  LEFT JOIN page p ON p.id=c.page_id ";
     $qs .= "WHERE  d.tok_id=:tokid ";
     $qs .= " ORDER BY d.id ASC LIMIT 1";
     $stmt_layout = $this->dbo->prepare($qs);
     $qs  = "SELECT tag.value, ts.score, ts.selected, ts.source, tt.class ";
     $qs .= "FROM   modern";
     $qs .= "  LEFT JOIN (tag_suggestion ts, tag) ";
     $qs .= "         ON (ts.tag_id=tag.id AND ts.mod_id=modern.id) ";
     $qs .= "  LEFT JOIN tagset tt ON tag.tagset_id=tt.id ";
     $qs .= "WHERE  modern.id=:modid ";
     $stmt_anno = $this->dbo->prepare($qs);

     /* The following loop separately performs all queries on a
	line-by-line basis that can potentially return more than one
	result row.  Integrating this in the SELECT above might yield
	better performance, but will be more complicated to process (as
	the 1:1 relation between rows and modern tokens is no longer
	guaranteed).  Change this only if performance becomes an issue.
      */
     foreach($data as &$line) {
       $mid = $line['id'];

       // Transcription including spaces for line breaks
       $ttrans = "";
       $lastline = null;
       $stmt_trans->execute(array(':tokid' => $line['tok_id']));
       while($row = $stmt_trans->fetch(PDO::FETCH_ASSOC)) {
	 if($lastline!==null && $lastline!==$row['line_id']) {
	   $ttrans .= " ";
	 }
	 $ttrans .= $row['trans'];
	 $lastline = $row['line_id'];
       }
       $line['full_trans'] = $ttrans;
       unset($lastline);

       // Layout info
       $stmt_layout->execute(array(':tokid' => $line['tok_id']));
       $row = $stmt_layout->fetch(PDO::FETCH_ASSOC);
       if($row) {
	 $line['line_name'] = $row['line_name'] ? $row['line_name'] : $row['line_num'];
	 $line['col_name']  = $row['col_name']  ? $row['col_name']  : "";
	 $line['page_name'] = $row['page_name'] ? $row['page_name'] : $row['page_num'];
	 $line['page_side'] = $row['page_side'] ? $row['page_side'] : "";
       }

       // Error annotations
       $errors = $this->getErrorsForModern($mid);
       if(in_array('general error', $errors)) {
	   $line['general_error'] = 1;
       }
       if(in_array('lemma verified', $errors)) {
	   $line['lemma_verified'] = 1;
       }

       // Annotations
       $stmt_anno->execute(array(':modid' => $mid));
       
       // prepare results for CorA---this is less flexible, but
       // probably faster than doing it on the client side
       $line['suggestions'] = array();
       while($row = $stmt_anno->fetch(PDO::FETCH_ASSOC)){
	 if($row['class']=='norm' && $row['selected']=='1') {
	   $line['anno_norm'] = $row['value'];
	 }
	 else if($row['class']=='norm_broad' && $row['selected']=='1') {
	   $line['anno_mod'] = $row['value'];
	 }
	 else if($row['class']=='norm_type' && $row['selected']=='1') {
	   $line['anno_modtype'] = $row['value'];
	 }
	 else if($row['class']=='lemma' && $row['selected']=='1') {
	   $line['anno_lemma'] = $row['value'];
	 }
	 else if($row['class']=='lemmaPOS' && $row['selected']=='1') {
	   $line['anno_lemmaPOS'] = $row['value'];
	 }
	 else if($row['class']=='POS') {
	   $tag = $row['value'];
	   if((substr($tag, -1)=='.') || (substr_count($tag, '.')==0)) {
	     $pos = $tag;
	     $morph = "--";
	   }
	   else {
	     $attribs = explode('.',$tag,2);
	     $pos = $attribs[0];
	     $morph = $attribs[1];
	   }

	   if($row['selected']=='1') {
	     $line['anno_POS'] = $pos;
	     $line['anno_morph'] = $morph;
	   }
	   if($row['source']=='auto') {
	     $line['suggestions'][] = array('POS' => $pos,
					    'morph' => $morph,
					    'score' => $row['score']);
	   }
	 }
       }
     }
     unset($line);

     return $data;
   }

   /** Retrieves error types and indexes them by name. */
   public function getErrorTypes() {
     $stmt = $this->dbo->prepare("SELECT `name`, `id` FROM error_types");
     $stmt->execute();
     return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
   }

   /** Delete locks if the locking user has been inactive for too
    * long; currently, this is set to be >30 minutes. */
   public function releaseOldLocks($to) {
     $qs  = "DELETE locks FROM locks";
     $qs .= "  LEFT JOIN users ON users.id=locks.user_id";
     $qs .= "  WHERE users.lastactive < (NOW() - INTERVAL {$to} MINUTE)";
     $this->dbo->exec($qs);
   }

   /** Saves changed lines.
    *
    * This function is called from the session handler during the
    * saving process.  
    *
    * Important: Empty annotations in $lines will cause the respective
    * entry in the database to be deleted (if any), but missing
    * annotations will cause no modifications in the database. Illegal
    * POS tags or POS+morph combinations are ignored, and the
    * respective DB entry is not modified.
    * 
    * @param string $fileid the file id
    * @param string $lasteditedrow the id of the mod which
    *               should receive the progress marker
    * @param array  $lines an array of mods to be saved
    * @param string $uname the username, used for the locking the
    *               file and updating the last_edited timestamp
    *
    * @return @bool the result of the mysql query
    */ 		
   public function saveLines($fileid,$lasteditedrow,$lines,$uname) {
     $locked = $this->lockFile($fileid, $uname);
     if(!$locked['success']) {
       return "lock failed";
     }
     
     $warnings = $this->performSaveLines($fileid,$lines);
     $this->markLastPosition($fileid,$lasteditedrow);
     $userid = $this->getUserIDFromName($uname);
     $this->updateChangedTimestamp($fileid,$userid);

     if(!empty($warnings)) {
       return "Der Speichervorgang wurde abgeschlossen, einige Informationen wurden jedoch möglicherweise nicht gespeichert.  Das System meldete:\n" . implode("\n", $warnings);
     }
     return False;
   }

   public function performSaveLines($fileid, $lines) {
     $dw = new DocumentWriter($this, $this->dbo, $fileid);
     if(!empty($lines)) {
       $dw->saveLines($lines);
     }
     return $dw->getWarnings();
   }

   /** Updates "last edited" information for a file.
    */
   public function updateChangedTimestamp($fileid,$userid) {
     $qs = "UPDATE text SET `changer_id`=:uid, "
       . "                  `changed`=CURRENT_TIMESTAMP WHERE `id`=:tid";
     $stmt = $this->dbo->prepare($qs);
     $stmt->execute(array(':uid' => $userid, ':tid' => $fileid));
     return $stmt->rowCount();
   }


   /** Save progress on the given file.
    *
    * Progress is shown by a green bar at the left side of the editor and indicates the last line for which changes have been made.
    *
    * This function is called during the saving process.
    * 
    * @param string $file the file id
    * @param string $line the current mod id
    *
    * @return bool the result of the mysql query
    */
   public function markLastPosition($fileid,$line){
     $qs = "UPDATE text SET `currentmod_id`=:line WHERE `id`=:tid";
     $stmt = $this->dbo->prepare($qs);
     $stmt->execute(array(':line' => $line, ':tid' => $fileid));
     return $stmt->rowCount();
   }

   /** Delete a token
    *
    * @param string $textid  The ID of the document to which the token belongs.
    * @param string $tokenid The ID of the token to be changed.
    * @param string $userid  The ID of the user making the change
    *
    * @return array A status array
    */
   public function deleteToken($textid, $tokenid, $userid) {
     $errors = array();
     $prevtokenid = null;
     $nexttokenid = null;
     $oldmodcount = 0;

     // get current mod count
     $qs = "SELECT COUNT(*) FROM modern WHERE `tok_id`=:tokid ORDER BY `id` ASC";
     $stmt = $this->dbo->prepare($qs);
     $stmt->execute(array(':tokid' => $tokenid));
     $oldmodcount = $stmt->fetch(PDO::FETCH_COLUMN);

     // find IDs of next and previous tokens
     $qs  = "SELECT a.id FROM token a ";
     $qs .= "WHERE  a.ordnr > (SELECT b.ordnr FROM token b ";
     $qs .= "                  WHERE  b.id=:tokid) ";
     $qs .= "       AND a.text_id=:tid ";
     $qs .= "ORDER BY a.ordnr ASC LIMIT 1 ";
     try {
       $stmt = $this->dbo->prepare($qs);
       $stmt->execute(array(':tokid' => $tokenid, ':tid' => $textid));
       $row = $stmt->fetch(PDO::FETCH_ASSOC);
     }
     catch (PDOException $ex) {
       $errors[] = "Ein interner Fehler ist aufgetreten (Code: 1220).";
       $errors[] = $ex->getMessage() . "\n" . $qs;
       return array("success" => false, "errors" => $errors);
     }
     if($row && array_key_exists('id', $row)) {
       $nexttokenid = $row['id'];
     }
     $qs  = "SELECT a.id FROM token a ";
     $qs .= "WHERE  a.ordnr < (SELECT b.ordnr FROM token b ";
     $qs .= "                  WHERE  b.id=:tokid) ";
     $qs .= "       AND a.text_id=:tid ";
     $qs .= "ORDER BY a.ordnr DESC LIMIT 1 ";
     try {
       $stmt = $this->dbo->prepare($qs);
       $stmt->execute(array(':tokid' => $tokenid, ':tid' => $textid));
       $row = $stmt->fetch(PDO::FETCH_ASSOC);
     }
     catch (PDOException $ex) {
       $errors[] = "Ein interner Fehler ist aufgetreten (Code: 1220).";
       $errors[] = $ex->getMessage() . "\n" . $qs;
       return array("success" => false, "errors" => $errors);
     }
     if($row && array_key_exists('id', $row)) {
       $prevtokenid = $row['id'];
     }

     // find shift tags attached to this token
     $stinsert = array();
     $qs  = "SELECT `id`, `tok_from`, `tok_to` FROM shifttags ";
     $qs .= "WHERE  `tok_from`=:tokfrom OR `tok_to`=:tokto";
     try {
       $stmt = $this->dbo->prepare($qs);
       $stmt->execute(array(':tokfrom' => $tokenid, ':tokto' => $tokenid));
       $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
     }
     catch (PDOException $ex) {
       $errors[] = "Ein interner Fehler ist aufgetreten (Code: 1222).";
       $errors[] = $ex->getMessage() . "\n" . $qs;
       return array("success" => false, "errors" => $errors);
     }
     // if necessary, move these shift tags around to prevent them from getting deleted
     foreach($result as $row) {
       // if both refer to the current token, do nothing
       if($row['tok_from'] != $row['tok_to']) {
	 if($row['tok_from'] == $tokenid) {
	   $stinsert[] = array(':id' => $row['id'],
			       ':tokfrom' => $nexttokenid,
			       ':tokto' => $row['tok_to']);
	 }
	 else {
	   $stinsert[] = array(':id' => $row['id'],
			       ':tokfrom' => $row['tok_from'],
			       ':tokto' => $prevtokenid);
	 }
       }
     }

     // perform modifications
     $this->dbo->beginTransaction();

     if(!empty($stinsert)) {
       $qs  = "INSERT INTO shifttags (`id`, `tok_from`, `tok_to`) ";
       $qs .= "               VALUES (:id,  :tokfrom,   :tokto) ";
       $qs .= " ON DUPLICATE KEY UPDATE `tok_from`=VALUES(tok_from), `tok_to`=VALUES(tok_to)";
       try {
	 $stmt = $this->dbo->prepare($qs);
	 foreach($stinsert as $param) {
	   $stmt->execute($param);
	 }
       }
       catch (PDOException $ex) {
	 $errors[] = "Ein interner Fehler ist aufgetreten (Code: 1223).";
	 $errors[] = $ex->getMessage() . "\n" . $qs;
	 $this->dbo->rollBack();
	 return array("success" => false, "errors" => $errors);
       }
     }
     // move any (non-internal) comments attached to this token
     if($prevtokenid!==null || $nexttokenid!==null) {
       $commtokenid = ($prevtokenid===null ? $nexttokenid : $prevtokenid);
       $qs  = "UPDATE comment SET `tok_id`=:ctokid ";
       $qs .= "WHERE  `tok_id`=:tokid AND `comment_type`!='C' ";
       try {
	 $stmt = $this->dbo->prepare($qs);
	 $stmt->execute(array(':ctokid' => $commtokenid, ':tokid' => $tokenid));
       }
       catch (PDOException $ex) {
	 $errors[] = "Ein interner Fehler ist aufgetreten (Code: 1224).";
	 $errors[] = $ex->getMessage() . "\n" . $qs;
	 $this->dbo->rollBack();
	 return array("success" => false, "errors" => $errors);
       }
     }
     // only now, delete the token
     $qs = "DELETE FROM token WHERE `id`=:tokid";
     try {
       $stmt = $this->dbo->prepare($qs);
       $stmt->execute(array(':tokid' => $tokenid));
     }
     catch (PDOException $ex) {
       $errors[] = "Ein interner Fehler ist aufgetreten (Code: 1225).";
       $errors[] = $ex->getMessage() . "\n" . $qs;
       $this->dbo->rollBack();
       return array("success" => false, "errors" => $errors);
     }
     
     $this->dbo->commit();
     $this->updateChangedTimestamp($textid,$userid);
     return array("success" => true, "oldmodcount" => $oldmodcount);
   }

   /** Add a token.
    *
    * Contrary to editToken(), transcriptions are expected NOT to
    * contain line breaks for this method!
    *
    * @param string $textid  The ID of the document to which the token belongs.
    * @param string $oldtokenid The ID of the token before which the new one is inserted.
    * @param string $userid  The ID of the user making the change
    *
    * @return array A status array
    */
   public function addToken($textid, $oldtokenid, $toktrans, $converted, $userid) {
     $errors = array();
     $ordnr = null; $lineid = null;

     // fetch ordnr for token
     $qs = "SELECT `ordnr` FROM token WHERE `id`=:id";
     $stmt = $this->dbo->prepare($qs);
     $stmt->execute(array(':id' => $oldtokenid));
     $ordnr = $stmt->fetch(PDO::FETCH_COLUMN);
     // fetch line for first dipl
     $qs  = "SELECT `line_id` FROM dipl WHERE `tok_id`=:id ";
     $qs .= "ORDER BY `id` ASC LIMIT 1";
     $stmt = $this->dbo->prepare($qs);
     $stmt->execute(array(':id' => $oldtokenid));
     $lineid = $stmt->fetch(PDO::FETCH_COLUMN);

     $this->dbo->beginTransaction();
     // add token
     $qs  = "INSERT INTO token (`text_id`, `trans`, `ordnr`) ";
     $qs .= "           VALUES (:tid,      :trans,  :ordnr)";
     try {
       $stmt = $this->dbo->prepare($qs);
       $stmt->execute(array(':tid' => $textid,
			    ':trans' => $toktrans,
			    ':ordnr' => $ordnr));
     }
     catch (PDOException $ex) {
       $errors[] = "Ein interner Fehler ist aufgetreten (Code: 1230).";
       $errors[] = $ex->getMessage() . "\n" . $qs;
       $this->dbo->rollBack();
       return array("success" => false, "errors" => $errors);
     }
     $tokenid = $this->dbo->lastInsertId();
     
     // re-order tokens
     $qs  = "UPDATE token SET `ordnr`=`ordnr`+1 ";
     $qs .= "WHERE `text_id`=:tid AND (`id`=:id OR `ordnr`>:ordnr)";
     try {
       $stmt = $this->dbo->prepare($qs);
       $stmt->execute(array(':tid' => $textid,
			    ':id' => $oldtokenid,
			    ':ordnr' => $ordnr));
     }
     catch (PDOException $ex) {
       $errors[] = "Ein interner Fehler ist aufgetreten (Code: 1231).";
       $errors[] = $ex->getMessage() . "\n" . $qs;
       $this->dbo->rollBack();
       return array("success" => false, "errors" => $errors);
     }

     // insert dipl
     try {
       $qs  = "INSERT INTO dipl (`tok_id`, `line_id`, `utf`, `trans`) ";
       $qs .= "          VALUES (:tokid,   :lineid,   :utf,  :trans) ";
       $stmt = $this->dbo->prepare($qs);
       $diplcount = count($converted['dipl_trans']);
       for($i = 0; $i < $diplcount; $i++) { // loop by index because two arrays are involved
	 $stmt->execute(array(':tokid' => $tokenid,
			      ':lineid' => $lineid,
			      ':utf' => $converted['dipl_utf'][$i],
			      ':trans' => $converted['dipl_trans'][$i]));
       }
     }
     catch (PDOException $ex) {
       $errors[] = "Ein interner Fehler ist aufgetreten (Code: 1232).";
       $errors[] = $ex->getMessage() . "\n" . $qs;
       $this->dbo->rollBack();
       return array("success" => false, "errors" => $errors);
     }

     // insert mod
     try {
       $qs  = "INSERT INTO modern (`tok_id`, `ascii`, `utf`, `trans`) ";
       $qs .= "            VALUES (:tokid,   :ascii,  :utf,  :trans) ";
       $stmt = $this->dbo->prepare($qs);
       $modcount  = count($converted['mod_trans']);
       for($j = 0; $j < $modcount; $j++) {
	 $stmt->execute(array(':tokid' => $tokenid,
			      ':ascii' => $converted['mod_ascii'][$j],
			      ':utf' => $converted['mod_utf'][$j],
			      ':trans' => $converted['mod_trans'][$j]));
       }
     }
     catch (PDOException $ex) {
       $errors[] = "Ein interner Fehler ist aufgetreten (Code: 1233).";
       $errors[] = $ex->getMessage() . "\n" . $qs;
       $this->dbo->rollBack();
       return array("success" => false, "errors" => $errors);
     }

     // done!
     $this->dbo->commit();
     $this->updateChangedTimestamp($textid,$userid);
     return array("success" => true, "newmodcount" => $modcount);
   }

   /** Change a token.
    *
    * @param string $textid  The ID of the document to which the token belongs.
    * @param string $tokenid The ID of the token to be changed.
    * @param string $userid  The ID of the user making the change
    *
    * @return array A status array
    */
   public function editToken($textid, $tokenid, $toktrans, $converted, $userid) {
     $errors = array();
     $olddipl = array();
     $oldmod  = array();
     $lineids = array(); // all possible line IDs for this token

     // delete newlines in the token transcription
     $newlinecount = substr_count($toktrans, "\n") + 1;
     $toktrans = str_replace("\n", "", $toktrans);

     // get current dipls
     $qs = "SELECT * FROM dipl WHERE `tok_id`=:tokid ORDER BY `id` ASC";
     $stmt = $this->dbo->prepare($qs);
     $stmt->execute(array(':tokid' => $tokenid));
     $lastline = "";
     while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
       $olddipl[] = $row;
       if($row['line_id'] !== $lastline) {
	 $lineids[] = $row['line_id'];
	 $lastline  = $row['line_id'];
       }
     }
     $oldlinecount = count($lineids);

     // does the token span more lines (in the diplomatic transcription) than before?
     // --- this takes up an awful lot of space ...
     if($newlinecount > $oldlinecount) {
       if(($newlinecount - $oldlinecount) > 1) {
	 $errors[] = "Token enthält zuviele Zeilenumbrüche.";
	 $errors[] = "Die neue Transkription enthält {$newlinecount} Zeilenumbrüche, "
	   . "die alte Transkription enthielt jedoch nur {$oldlinecount}.";
	 return array("success" => false, "errors" => $errors);
       }

       // fetch the first dipl of the next token and check if it is on
       // a different line than the last dipl of the current token -->
       // if so, this works, if not, then it's an error
       $qs  = "SELECT d.line_id FROM dipl d ";
       $qs .= "  WHERE d.tok_id IN (SELECT t.id AS tok_id FROM token t ";
       $qs .= "                      WHERE t.text_id=:tid ";
       $qs .= "                        AND t.ordnr > (SELECT u.ordnr FROM token u ";
       $qs .= "                                        WHERE u.id=:tokid) ";
       $qs .= "                      ORDER BY t.ordnr ASC) ";
       $qs .= " ORDER BY d.id ASC LIMIT 1";
       try {
	 $stmt = $this->dbo->prepare($qs);
	 $stmt->execute(array(':tid' => $textid, ':tokid' => $tokenid));
	 $row = $stmt->fetch(PDO::FETCH_ASSOC);
       }
       catch (PDOException $ex) {
	 $errors[] = "Ein interner Fehler ist aufgetreten (Code: 1210).";
	 $errors[] = $ex->getMessage() . "\n" . $qs;
	 return array("success" => false, "errors" => $errors);
       }
       if(empty($row) || !isset($row['line_id'])) {
	 $errors[] = "Die neue Transkription enthält einen Zeilenumbruch mehr als die vorherige, es konnte jedoch keine passende Zeile gefunden werden. (Befindet sich die Transkription in der letzten Zeile des Dokuments?)";
	 return array("success" => false, "errors" => $errors);
       }
       if($row['line_id'] == $lastline) {
	 $errors[] = "Die neue Transkription enthält einen Zeilenumbruch mehr als die vorherige, steht jedoch nicht am Ende einer Zeile.";
	 return array("success" => false, "errors" => $errors);
       }
       $lineids[] = $row['line_id'];
     }

     // prepare dipl queries
     $diplinsert  = array();
     $dipldelete  = array();
     $currentline = 0;
     $diplcount = count($converted['dipl_trans']);
     for($i = 0; $i < $diplcount; $i++) { // loop by index because three arrays are involved
       $diplid = (isset($olddipl[$i]) ? $olddipl[$i]['id'] : "NULL");
       $dipltrans = $converted['dipl_trans'][$i];
       $diplinsert[] = array(':diplid' => $diplid,
			     ':tokid'  => $tokenid,
			     ':lineid' => $lineids[$currentline],
			     ':utf'    => $converted['dipl_utf'][$i],
			     ':trans'  => $dipltrans);
       if(Transcription::endsWithSeparator($dipltrans)) {
	 $currentline++;
       }
     }
     // are there dipls that need to be deleted?
     while(isset($olddipl[$i])) {
       $dipldelete[] = $olddipl[$i]['id'];
       $i++;
     }

     // get current mods
     $qs = "SELECT * FROM modern WHERE `tok_id`=:tokid ORDER BY `id` ASC";
     $stmt = $this->dbo->prepare($qs);
     $stmt->execute(array(':tokid' => $tokenid));
     $oldmod = $stmt->fetchAll(PDO::FETCH_ASSOC);
     
     // prepare mod queries
     $modinsert = array();
     $moddelete = array();
     $modcount  = count($converted['mod_trans']);
     for($j = 0; $j < $modcount; $j++) {
       $modid = (isset($oldmod[$j]) ? $oldmod[$j]['id'] : "NULL");
       $modinsert[] = array(':modid' => $modid,
			    ':tokid' => $tokenid,
			    ':trans' => $converted['mod_trans'][$j],
			    ':ascii' => $converted['mod_ascii'][$j],
			    ':utf'   => $converted['mod_utf'][$j]);
     }
     // are there mods that need to be deleted?
     while(isset($oldmod[$j])) {
       $moddelete[] = $oldmod[$j]['id'];
       $j++;
     }

     // perform actual queries
     $this->dbo->beginTransaction();
     // dipl
     if(!empty($diplinsert)) { // e.g., standalone edition numberings have no dipl
       $qs  = "INSERT INTO dipl (`id`, `tok_id`, `line_id`, `utf`, `trans`) ";
       $qs .= "          VALUES (:diplid, :tokid, :lineid,  :utf,  :trans) ";
       $qs .= " ON DUPLICATE KEY UPDATE `line_id`=VALUES(line_id), ";
       $qs .= "                    `utf`=VALUES(utf), `trans`=VALUES(trans)";
       try {
	 $stmt = $this->dbo->prepare($qs);
	 foreach($diplinsert as $param) {
	   $stmt->execute($param);
	 }
       }
       catch (PDOException $ex) {
	 $errors[] = "Ein interner Fehler ist aufgetreten (Code: 1211).";
	 $errors[] = $ex->getMessage() . "\n" . $qs;
	 $this->dbo->rollBack();
	 return array("success" => false, "errors" => $errors);
       }
     }
     if(!empty($dipldelete)) {
       $qs = "DELETE FROM dipl WHERE `id` IN (" . implode(", ", $dipldelete) . ")";
       try {
	 $this->dbo->exec($qs);
       }
       catch (PDOException $ex) {
	 $errors[] = "Ein interner Fehler ist aufgetreten (Code: 1212).";
	 $errors[] = $ex->getMessage() . "\n" . $qs;
	 $this->dbo->rollBack();
	 return array("success" => false, "errors" => $errors);
       }
     }
     // modern
     if(!empty($modinsert)) { // this can happen for struck words, e.g. *[vnd*]
       $qs  = "INSERT INTO modern (`id`, `tok_id`, `trans`, `ascii`, `utf`) ";
       $qs .= "            VALUES (:modid, :tokid, :trans,  :ascii,  :utf) ";
       $qs .= " ON DUPLICATE KEY UPDATE `trans`=VALUES(trans), ";
       $qs .= "                    `ascii`=VALUES(ascii), `utf`=VALUES(utf)";
       try {
	 $stmt = $this->dbo->prepare($qs);
	 foreach($modinsert as $param) {
	   $stmt->execute($param);
	 }
       }
       catch (PDOException $ex) {
	 $errors[] = "Ein interner Fehler ist aufgetreten (Code: 1213).";
	 $errors[] = $ex->getMessage() . "\n" . $qs;
	 $this->dbo->rollBack();
	 return array("success" => false, "errors" => $errors);
       }
     }
     if(!empty($moddelete)) {
       $qs = "DELETE FROM modern WHERE `id` IN (" . implode(", ", $moddelete) . ")";
       try {
	 $this->dbo->exec($qs);
       }
       catch (PDOException $ex) {
	 $errors[] = "Ein interner Fehler ist aufgetreten (Code: 1214).";
	 $errors[] = $ex->getMessage() . "\n" . $qs;
	 $this->dbo->rollBack();
	 return array("success" => false, "errors" => $errors);
       }
       // delete CorA comments attached to this modern token
       $qs  = "DELETE FROM comment WHERE `tok_id`=:tokid AND `comment_type`='C' ";
       $qs .= " AND `subtok_id` IN (" . implode(", ", $moddelete) . ")";
       $stmt = $this->dbo->prepare($qs);
       $stmt->execute(array(':tokid' => $tokenid));
       // if 'currentmod_id' is set to one of the deleted tokens, set it to something feasible
       $qs  = "SELECT currentmod_id FROM text WHERE `id`=:tid ";
       $qs .= "  AND `currentmod_id` IN (" . implode(", ", $moddelete) . ")";
       $stmt = $this->dbo->prepare($qs);
       $stmt->execute(array(':tid' => $textid));
       if($stmt->rowCount() > 0) {
	 $cmid = $oldmod[0]['id'];
	 $qs = "UPDATE text SET `currentmod_id`=:cmid WHERE `id`=:tid";
	 $stmt = $this->dbo->prepare($qs);
	 $stmt->execute(array(':cmid' => $cmid, ':tid' => $textid));
       }
     }
     // token
     $qs = "UPDATE token SET `trans`=:trans WHERE `id`=:tokid";
     try {
       $stmt = $this->dbo->prepare($qs);
       $stmt->execute(array(':tokid' => $tokenid,
			    ':trans' => $toktrans));
     }
     catch (PDOException $ex) {
       $errors[] = "Ein interner Fehler ist aufgetreten (Code: 1215).";
       $errors[] = $ex->getMessage() . "\n" . $qs;
       $this->dbo->rollBack();
       return array("success" => false, "errors" => $errors);
     }

     $this->dbo->commit();
     $this->updateChangedTimestamp($textid,$userid);
     return array("success" => true, "oldmodcount" => count($oldmod), "newmodcount" => $modcount);
   }

  /** Add a list of tags as a new POS tagset. */
  public function importTagList($taglist, $tagsetname){
    $tagarray = array();
    $poslist = array();
    $errors = array();
    $warnings = array();
    $pos = "";
    $numattr = 0;
    
    foreach($taglist as $tag) {
      $tag = trim($tag);
      if(empty($tag)) {
	continue;
      }
      if(strlen($tag)>255) {
	$errors[] = "Tag ist über 255 Zeichen lang: {$tag}";
	continue;
      }
      $attribs = array();
      // check for "^" (indicates that a tag needs correction)
      if(substr($tag, 0, 1) == '^') {
	$needscorrection = true;
	$tag = substr($tag, 1);
      }
      else {
	$needscorrection = false;
      }
      // check for duplicates
      if(array_key_exists($tag, $tagarray)) {
	$errors[] = "Tag ist doppelt vorhanden: {$tag}";
	continue;
      }
      // check number and length of (morphological) attributes
      if((substr($tag, -1)=='.') && (substr_count($tag, '.')==1)) {
	$pos = $tag;
	$numattr = 0;
      }
      else {
	$numattr = substr_count($tag, '.');
	$attribs = explode('.',$tag);
	foreach($attribs as $attrib) {
	  if(strlen($attrib) < 1) {
	    $errors[] = "Tag hat leere Attribute: {$tag}";
	    break;
	  }
	}
	$pos = $attribs[0];
      }
      if(array_key_exists($pos, $poslist) && ($poslist[$pos]!=$numattr)) {
	$expected = $poslist[$pos];
	$errors[] = "POS-Tag mit ungleicher Anzahl von Attributen gefunden (jetzt {$numattr}, vormals {$expected}): {$tag}";
      }
      else {
	$poslist[$pos] = $numattr;
      }
      // construct entry for the database
      $tagarray[$tag] = $needscorrection;
    }
    if(empty($tagarray)) {
      $errors[] = "Keine Tags zum Importieren gefunden.";
    }
    
    // did errors occur? then abort
    if(!empty($errors)) {
      return array("success"=>false, "errors"=>$errors);
    }
    
    // otherwise, perform the import
    try{
      $this->dbo->beginTransaction();
      $qs  = "INSERT INTO tagset (name, set_type, class) ";
      $qs .= "VALUES (:name, 'closed', 'POS')";
      $stmt = $this->dbo->prepare($qs);
      $stmt->execute(array(':name' => $tagsetname));
      $tagsetid = $this->dbo->lastInsertId();

      $qs  = "INSERT INTO tag (`value`, `needs_revision`, ";
      $qs .= "`tagset_id`) VALUES (:value, :needrev, :tagset) ";
      $stmt = $this->dbo->prepare($qs);
      foreach($tagarray as $tagname => $tagnc) {
	$stmt->execute(array(':value' => $tagname,
			     ':needrev' => ($tagnc ? '1' : '0'),
			     ':tagset' => $tagsetid));
      }
      $this->dbo->commit();
    } catch (DBOException $ex) {
      $this->dbo->rollBack();
      return array("success"=>false, "errors"=>array($ex->getMessage()));
    }
    
    // done!
    return array("success"=>true, "warnings"=>$warnings);
  }

  /** Find the text ID for a given token ID. */
  public function getTextIdForToken($tokenid) {
    $qs = "SELECT `text_id` FROM token WHERE `id`=:tokid";
    $stmt = $this->dbo->prepare($qs);
    $stmt->execute(array(':tokid' => $tokenid));
    return $stmt->fetch(PDO::FETCH_COLUMN);
  }

  /** Find the project ID and ascii value for a given modern ID. */
  private function getProjectAndAscii($modid) {
    $qs = "SELECT text.project_id, modern.ascii FROM text "
      . "    LEFT JOIN token ON token.text_id=text.id "
      . "    LEFT JOIN modern ON modern.tok_id=token.id "
      . "  WHERE  modern.id=:modid LIMIT 1";
    $stmt = $this->dbo->prepare($qs);
    $stmt->execute(array(':modid' => $modid));
    return $stmt->fetch(PDO::FETCH_ASSOC);
  }

  /** Insert a new document.
   *
   * @param array $options Metadata for the document
   * @param array $data A CoraDocument object
   *
   * @return An error message if insertion failed, False otherwise
   */
  public function insertNewDocument(&$options, &$data){
    // Find tagset IDs
    $tagset_ids = array();
    $tagsets = $this->getTagsets(null);
    foreach($tagsets as $tagset) {
      // consider only tagsets that are given in the options
      if(in_array($tagset['id'], $options['tagsets'])) {
	if($tagset['class'] == "lemma") {
	  if($tagset['set_type'] == "open") {
	    $tagset_ids[$tagset['class']] = $tagset['id'];
	  }
	}
	else if($tagset['class'] == "POS") {
	  $tagset_ids['pos'] = $tagset['id'];
	}
	else {
	  $tagset_ids[$tagset['class']] = $tagset['id'];
	}
      }
    }
    // Load POS tagset
    if(!array_key_exists('pos', $tagset_ids)) {
      return "Es wurde kein POS-Tagset angegeben (Code: 1089).";
    }
    $tagset_pos = $this->getTagsetByValue($tagset_ids['pos']);
    if(array_key_exists('norm_type', $tagset_ids)) {
      $tagset_norm_type = $this->getTagsetByValue($tagset_ids['norm_type']);
    }

    // Start insertions
    $this->dbo->beginTransaction();

    // Table 'text'
    $qstr  = "INSERT INTO text ";
    $qstr .= "  (`sigle`, `fullname`, `project_id`, `created`, `creator_id`, ";
    $qstr .= "   `currentmod_id`, `header`, `fullfile`) VALUES ";
    $qstr .= "  (:sigle, :name, :project, CURRENT_TIMESTAMP, :uid, ";
    $qstr .= "     NULL, :header, :fullfile) ";
    if(isset($options['trans_file']) && !empty($options['trans_file'])) {
      $fullfile = $options['trans_file'];
    } else {
      $fullfile = NULL;
    }
    try {
      $stmt = $this->dbo->prepare($qstr);
      $stmt->execute(array(':sigle' => $options['sigle'],
			   ':name'  => $options['name'],
			   ':project' => $options['project'],
			   ':uid' => $_SESSION['user_id'],
			   ':header' => $data->getHeader(),
			   ':fullfile' => $fullfile));
      $fileid = $this->dbo->lastInsertId();
    }
    catch (PDOException $ex) {
      $this->dbo->rollBack();
      return "Beim Importieren in die Datenbank ist ein Fehler aufgetreten (Code: 1091).\n" . $ex->getMessage();
    }

    // Table 'text2tagset'
    $qstr  = "INSERT INTO text2tagset ";
    $qstr .= "  (`text_id`, `tagset_id`, `complete`) VALUES ";
    $qstr .= "  (:tid, :tagset, :complete)";
    try {
      $stmt = $this->dbo->prepare($qstr);
      foreach($options['tagsets'] as $tagsetid) {
	$stmt->execute(array(':tid' => $fileid,
			     ':tagset' => $tagsetid,
			     ':complete' => 0));
      }
    }
    catch (PDOException $ex) {
      $this->dbo->rollBack();
      return "Beim Importieren in die Datenbank ist ein Fehler aufgetreten (Code: 1092).\n" . $ex->getMessage();
    }
    
    /* Note: The next blocks all follow a very similar structure. Can
       we refactor this into a loop? */

    // Table 'page'
    $qstr  = "INSERT INTO page (`name`, `side`, `text_id`, `num`) VALUES ";
    $qstr .= "                 (:name,  :side,  :tid,      :num)";
    $pages = $data->getPages();
    $first_id = null;
    try {
      $stmt = $this->dbo->prepare($qstr);
      foreach($pages as $page) {
	$stmt->execute(array(':name' => $page['name'],
			     ':side' => $page['side'],
			     ':tid'  => $fileid,
			     ':num'  => $page['num']));
	if(is_null($first_id)) {
	  $first_id = $this->dbo->lastInsertId();
	}
      }
    }
    catch (PDOException $ex) {
      $this->dbo->rollBack();
      return "Beim Importieren in die Datenbank ist ein Fehler aufgetreten (Code: 1093).\n" . $ex->getMessage();
    }
    $data->fillPageIDs($first_id);

    // Table 'col'
    $qstr  = "INSERT INTO col (`name`, `num`, `page_id`) VALUES ";
    $qstr .= "                (:name,  :num,  :pageid) ";
    $first_id = null;
    $cols  = $data->getColumns();
    try {
      $stmt = $this->dbo->prepare($qstr);
      foreach($cols as $col) {
	$stmt->execute(array(':name' => $col['name'],
			     ':num'  => $col['num'],
			     ':pageid' => $col['parent_db_id']));
	if(is_null($first_id)) {
	  $first_id = $this->dbo->lastInsertId();
	}
      }
    }
    catch (PDOException $ex) {
      $this->dbo->rollBack();
      return "Beim Importieren in die Datenbank ist ein Fehler aufgetreten (Code: 1094).\n" . $ex->getMessage();
    }
    $data->fillColumnIDs($first_id);
    
    // Table 'line'
    $qstr  = "INSERT INTO line (`name`, `num`, `col_id`) VALUES ";
    $qstr .= "                 (:name,  :num,  :colid) ";
    $first_id = null;
    $lines = $data->getLines();
    try {
      $stmt = $this->dbo->prepare($qstr);
      foreach($lines as $line) {
	$stmt->execute(array(':name' => $line['name'],
			     ':num'  => $line['num'],
			     ':colid' => $line['parent_db_id']));
	if(is_null($first_id)) {
	  $first_id = $this->dbo->lastInsertId();
	}
      }
    }
    catch (PDOException $ex) {
      $this->dbo->rollBack();
      return "Beim Importieren in die Datenbank ist ein Fehler aufgetreten (Code: 1095).\n" . $ex->getMessage();
    }
    $data->fillLineIDs($first_id);
    
    // Table 'token'
    $qstr  = "INSERT INTO token (`trans`, `ordnr`, `text_id`) VALUES ";
    $qstr .= "                  (:trans,  :ordnr,  :tid) ";
    $first_id = null;
    $tokens = $data->getTokens();
    try {
      $stmt = $this->dbo->prepare($qstr);
      foreach($tokens as $token) {
	$stmt->execute(array(':trans' => $token['trans'],
			     ':ordnr' => $token['ordnr'],
			     ':tid'   => $fileid));
	if(is_null($first_id)) {
	  $first_id = $this->dbo->lastInsertId();
	}
      }
    }
    catch (PDOException $ex) {
      $this->dbo->rollBack();
      return "Beim Importieren in die Datenbank ist ein Fehler aufgetreten (Code: 1096).\n" . $ex->getMessage();
    }
    $data->fillTokenIDs($first_id);

    // Table 'dipl'
    $qstr  = "INSERT INTO dipl (`trans`, `utf`, `tok_id`, `line_id`) VALUES ";
    $qstr .= "                 (:trans,  :utf,  :tokid,   :lineid) ";
    $first_id = null;
    $dipls = $data->getDipls();
    try {
      $stmt = $this->dbo->prepare($qstr);
      foreach($dipls as $dipl) {
	$stmt->execute(array(':trans' => $dipl['trans'],
			     ':utf'   => $dipl['utf'],
			     ':tokid' => $dipl['parent_tok_db_id'],
			     ':lineid' => $dipl['parent_line_db_id']));
	if(is_null($first_id)) {
	  $first_id = $this->dbo->lastInsertId();
	}
      }
    }
    catch (PDOException $ex) {
      $this->dbo->rollBack();
      return "Beim Importieren in die Datenbank ist ein Fehler aufgetreten (Code: 1097).\n" . $ex->getMessage();
    }
    $data->fillDiplIDs($first_id);

    // Table 'modern'
    $qstr  = "INSERT INTO modern (`trans`, `utf`, `ascii`, `tok_id`) VALUES ";
    $qstr .= "                   (:trans,  :utf,  :ascii,  :tokid) ";
    $first_id = null;
    $moderns = $data->getModerns();
    try {
      $stmt = $this->dbo->prepare($qstr);
      foreach($moderns as $mod) {
	$stmt->execute(array(':trans' => $mod['trans'],
			     ':utf'   => $mod['utf'],
			     ':tokid' => $mod['parent_db_id'],
			     ':ascii' => $mod['ascii']));
	$last_insert_id = $this->dbo->lastInsertId();
	if(array_key_exists('comment', $mod) && !empty($mod['comment'])) {
	  $data->addComment($mod['parent_db_id'], null, $mod['comment'], "C", $last_insert_id);
	}
	if(is_null($first_id)) {
	  $first_id = $last_insert_id;
	}
      }
    }
    catch (PDOException $ex) {
      $this->dbo->rollBack();
      return "Beim Importieren in die Datenbank ist ein Fehler aufgetreten (Code: 1098).\n" . $ex->getMessage();
    }
    $data->fillModernIDs($first_id);

    // Table 'tag_suggestion'
    $qstr  = "INSERT INTO tag_suggestion ";
    $qstr .= "  (`score`, `selected`, `source`, `tag_id`, `mod_id`) VALUES ";
    $qstr .= "  (:score,  :selected,  :source,  :tagid,   :modid)";
    $tistr  = "INSERT INTO tag ";
    $tistr .= "  (`value`, `needs_revision`, `tagset_id`) VALUES ";
    $tistr .= "  (:value,  :needrev,         :tagset)";
    $stmt_ts  = $this->dbo->prepare($qstr);
    $stmt_tag = $this->dbo->prepare($tistr);

    $moderns = $data->getModerns();
    foreach($moderns as $mod) {
      foreach($mod['tags'] as $sugg) {
	// for POS tags, just refer to the respective tag ID
	if($sugg['type']==='pos') {
	  if(!array_key_exists($sugg['tag'], $tagset_pos)) {
	    $this->dbo->rollBack();
	    return "Beim Importieren in die Datenbank ist ein Fehler aufgetreten (Code: 1099). Der folgende POS-Tag ist ungültig: " . $sugg['tag'];
	  }
	  $tag_id = $tagset_pos[$sugg['tag']];
	}
	// for modernisation types, too
	else if($sugg['type']==='norm_type') {
	  if(!array_key_exists($sugg['tag'], $tagset_norm_type)) {
	    $this->dbo->rollBack();
	    return "Beim Importieren in die Datenbank ist ein Fehler aufgetreten (Code: 1099). Der folgende Modernisierungstyp ist ungültig: " . $sugg['tag'];
	  }
	  $tag_id = $tagset_norm_type[$sugg['tag']];
	}
	// for all other tags, create a new tag entry first
	else {
	  try {
	    $stmt_tag->execute(array(':value' => $sugg['tag'],
				     ':needrev' => 0,
				     ':tagset' => $tagset_ids[$sugg['type']]));
	    $tag_id = $this->dbo->lastInsertId();
	  }
	  catch (PDOException $ex) {
	    $this->dbo->rollBack();
	    return "Beim Importieren in die Datenbank ist ein Fehler aufgetreten (Code: 1100, Tagset-Typ: " . $sugg['type'] . ").\n" . $ex->getMessage();
	  }
	}
	// then append the proper values
	try {
	  $stmt_ts->execute(array(':score' => $sugg['score'],
				  ':selected' => $sugg['selected'],
				  ':source' => $sugg['source'],
				  ':tagid' => $tag_id,
				  ':modid' => $mod['db_id']));
	  }
	  catch (PDOException $ex) {
	    $this->dbo->rollBack();
	    return "Beim Importieren in die Datenbank ist ein Fehler aufgetreten (Code: 1101).\n" . $ex->getMessage();
	  }
      }
    }

    // Table 'shifttags'
    $qstr  = "INSERT INTO {$this->db}.shifttags ";
    $qstr .= "  (`tok_from`, `tok_to`, `tag_type`) VALUES ";
    $qstr .= "  (:tokfrom,   :tokto,   :type)";
    $shifttags = $data->getShifttags();
    if(!empty($shifttags)) {
      try {
	$stmt = $this->dbo->prepare($qstr);
	foreach($shifttags as $shtag) {
	  $stmt->execute(array(':tokfrom' => $shtag['db_range'][0],
			       ':tokto'   => $shtag['db_range'][1],
			       ':type'    => $shtag['type_letter']));
	}
      }
      catch (PDOException $ex) {
	$this->dbo->rollBack();
	return "Beim Importieren in die Datenbank ist ein Fehler aufgetreten (Code: 1102).\n" . $ex->getMessage();
      }
    }

    // Table 'comment'
    $qstr  = "INSERT INTO {$this->db}.comment ";
    $qstr .= "  (`tok_id`, `value`, `comment_type`, `subtok_id`) VALUES ";
    $qstr .= "  (:tokid,   :value,  :ctype,         :subtokid)";
    $comments = $data->getComments();
    if(!empty($comments)) {
      try {
	$stmt = $this->dbo->prepare($qstr);
	foreach($comments as $comment) {
	  $stmt->execute(array(':tokid' => $comment['parent_db_id'],
			       ':value' => $comment['text'],
			       ':ctype' => $comment['type'],
			       ':subtokid' => $comment['subtok_db_id']));
	}
      }
      catch (PDOException $ex) {
	$this->dbo->rollBack();
	return "Beim Importieren in die Datenbank ist ein Fehler aufgetreten (Code: 1103).\n" . $ex->getMessage() . "\n" . $qstr;
      }
    }

    $this->dbo->commit();
    return False;
  }

  /** Retrieve suggestions for lemma annotation.
   *
   * @param string $fileid ID of the file to which the annotation belongs
   * @param string $linenum ID of the modern to which the annotation belongs
   * @param string $q Query string to search for in the closed lemma tagset
   * @param int $limit Number of results to return
   *
   * @return An array containing lemma suggestions, represented as
   * arrays with tag IDs ('tag'), lemma ('value'), and suggestion type
   * ('t') as either one of 's' (automatic suggestion stored in the
   * suggestion table), 'c' (confirmed lemma entries from other tokens
   * within the same project with the same ASCII value), or 'q' (query
   * matches from the closed lemma tagset)
   */
  public function getLemmaSuggestion($fileid, $linenum, $q, $limit) {
    $suggestions = array();

    // get automatic suggestions stored with the line number
    $qstr  = "SELECT ts.id, ts.tag_id, ts.source, tag.value, tagset.class ";
    $qstr .= "FROM   tag_suggestion ts ";
    $qstr .= "  LEFT JOIN tag ON tag.id=ts.tag_id ";
    $qstr .= "  LEFT JOIN tagset ON tagset.id=tag.tagset_id ";
    $qstr .= "WHERE  ts.mod_id=:modid AND tagset.class='lemma' AND ts.source='auto'";
    $stmt = $this->dbo->prepare($qstr);
    $stmt->execute(array(':modid' => $linenum));
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
      $suggestions[] = array("id" => $row['tag_id'], "v" => $row['value'], "t" => "s");
    }

    // get confirmed selected lemmas from tokens with identical simplification
    $errortypes = $this->getErrorTypes();
    if(array_key_exists('lemma verified', $errortypes)) {
      $lemma_verified = $errortypes['lemma verified'];
      $paa = $this->getProjectAndAscii($linenum);
      if($paa && !empty($paa)) {
	$line_ascii = $paa['ascii'];
	$line_project = $paa['project_id'];
	$qstr  = "SELECT tag.id, tag.value, ts.id AS ts_id "
	  . "     FROM   tag "
	  . "       LEFT JOIN tagset ON tag.tagset_id=tagset.id "
	  . "       LEFT JOIN tag_suggestion ts ON ts.tag_id=tag.id "
	  . "       LEFT JOIN modern ON modern.id=ts.mod_id "
	  . "       LEFT JOIN token ON modern.tok_id=token.id "
	  . "       LEFT JOIN text ON token.text_id=text.id "
	  . "       LEFT JOIN mod2error ON mod2error.mod_id=modern.id "
	  . "     WHERE  mod2error.error_id=:errid "
	  . "        AND UPPER(modern.ascii)=UPPER(:ascii) "
	  . "        AND text.project_id=:projectid "
	  . "        AND tagset.class='lemma' "
	  . "        AND ts.selected=1 ";
	$stmt = $this->dbo->prepare($qstr);
	$stmt->execute(array(':errid' => $lemma_verified,
			     ':ascii' => $line_ascii,
			     ':projectid' => $line_project));
	$processed_lemmas = array();
	while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
	  if(!in_array($row['value'], $processed_lemmas)) {
	    $suggestions[] = array("id" => $row['id'], "v" => $row['value'], "t" => "c");
	    $processed_lemmas[] = $row['value'];
	  }
	}
      }
    }

    // get lemma matches for query string
    if(strlen($q)>0) {
      // strip ID for the search, if applicable
      $q = preg_replace('/ \[.*\]$/', '', $q);
      $tslist = $this->getTagsetsForFile($fileid);
      $tsid = 0;
      foreach($tslist as $tagset) {
	if($tagset['class']=="lemma_sugg") {
	  $tsid = $tagset['id'];
	}
      }
      if($tsid && $tsid != 0) {
	$qs = "SELECT `id`, `value` FROM tag "
	  . "  WHERE `tagset_id`='{$tsid}' AND `value` LIKE '{$q}%' "
	  . "  ORDER BY `value` LIMIT {$limit}";
	$stmt = $this->dbo->prepare($qs);
	$stmt->execute();
	while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
	  $suggestions[] = array("id" => $row['id'], "v" => $row['value'], "t" => "q");
	}
      }
    }
    
    return $suggestions;
  }


}


?>