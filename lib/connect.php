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

 /** Exception when an SQL query fails. */
 class SQLQueryException extends Exception { }

 /** Class enabling arbitrarily long SQL queries by breaking them up
  *  into smaller parts.
  */
 class LongSQLQuery {
   private $head;       /**< String to be put before each query. */
   private $tail;       /**< String to be put after each query. */
   private $db;         /**< The database connector to be used. */
   private $len;
   private $qa;
   private $delim;

   public function __construct($db, $head="", $tail="", $delim=", ") {
     $this->db   = $db;
     $this->head = $head;
     $this->tail = $tail;
     $this->len  = strlen($head) + strlen($tail) + 2;
     $this->qa   = array();
     $this->delim = $delim;
   }

   /** Append a new list (i.e., comma-separated) item to the query. */
   public function append($item) {
     $newlen = $this->len + strlen($item) + 2;
     // if query length would exceed maximum, perform a flush first:
     if($newlen>DB_MAX_QUERY_LENGTH){
       $this->flush();
       $newlen = $this->len + strlen($item) + 2;
     }
     $this->len  = $newlen;
     $this->qa[] = $item;
     return False;
   }

   /** Send the query to the server. */
   public function flush() {
     if(empty($this->qa)){ return False; }

     $query  = $this->head . ' ';
     $query .= implode($this->delim, $this->qa);
     $query .= ' ' . $this->tail;

     if(!$this->db->query($query)){
       throw new SQLQueryException(mysql_errno().": ".mysql_error());
     }

     $this->len = strlen($this->head) + strlen($this->tail) + 2;
     $this->qa  = array();
     return False;
   }
 }


 /** Class providing a database connection.
  *
  * This class sets up a database connection and provides helper
  * functions to perform database queries.  Access information (server,
  * database name, username, password) is currently hard-coded.
  */
 class DBConnector {
   private $dbobj;                     /**< Database object as returned by @c mysql_connect(). */
   private $db          = MAIN_DB;     /**< Name of the database to be used. */
   private $transaction = false;
   private $last_error   = null;

   /** Create a new DBConnector.
    *
    * Creates a new SQL connection using the access information
    * hard-coded into this class.
    */
   function __construct($db_server, $db_user, $db_password, $db_name) {
       $this->dbobj = mysql_connect( $db_server, $db_user, $db_password );
       $this->db = $db_name;
   }

   /** Check if a connection exists.
    * @return @c true if there is a database connection
    */
   public function isConnected() {
       return ($this->dbobj) ? true : false;
   }

   /** Set the default database. The name of the default database
    * should be referenced in every SQL query string.
    *
    * @param string $name Name of the database to be set as default.
    */
   public function setDatabase( $name ) {
     $this->db = $name;
   }

   public function getDatabase() {
     return $this->db;
   }

   /** Select a database.
    *
    * @param string $name Name of the database to be selected.
    * @deprecated Database names should always be explicitly included
    * in the query string now.
    */
   protected function selectDatabase( $name ) {
     $status = mysql_select_db( $name, $this->dbobj );
     if ($status) {
       $this->query( "SET NAMES 'utf8'", $this->dbobj ); 
     }
     return $status;
   }

   /** Start an SQL transaction. */
   public function startTransaction() {
     $status = $this->query( "START TRANSACTION", $this->dbobj );
     if ($status) {
       $status = $this->selectDatabase( $this->db );
       $this->transaction = true;
     }
     return $status;
   }

   /** Commits an SQL transaction. */
   public function commitTransaction() {
     $this->transaction = false;
     return $this->query( "COMMIT", $this->dbobj );
   }

   /** Rollback an SQL transaction. */
   public function rollback() {
     $this->transaction = false;
     return $this->query( "ROLLBACK", $this->dbobj );
   }

   /** Perform a database query.
    *
    * @param string $query Query string in SQL syntax.
    * @return The result of the respective @c mysql_query() command.
    */
    public function query( $query ) { 
      return mysql_query( $query, $this->dbobj ); 
    }

   /** Fetch an associative array from a query result
    *
    * @param result of a call to $this->query
    * @return associative array
    */
   public function fetch_assoc($result) {
       return mysql_fetch_assoc($result);
   }

   /** Fetch an enumerated array from a query result
    *
    * in the standard php mysql api, fetch_row fetches an array,
    * while fetch_array fetches an array, hash, or both. why this is,
    * nobody knows, but it is believed to be made this way to drive
    * programmers deliberately crazy.
    */
   public function fetch_array($result) {
       return mysql_fetch_row($result);
   }

   public function fetch($result) {
       return mysql_fetch_array($result);
   }

   public function row_count($result = null) {
       if ($result === null) {
	   return mysql_affected_rows();
       } else {
	   return mysql_num_rows($result);
       }
   }

   /** Fetch ID of last inserted record.
    *
    * Calls the SQL command instead of PHP's mysql_insert_id as we are
    * dealing with BIGINTs, which might cause problems otherwise.
    */
   public function last_insert_id() {
     $q = $this->query("SELECT LAST_INSERT_ID()");
     $r = $this->fetch_array($q);
     return $r[0];
   }

   public function last_error() {
     return mysql_error();
   }

   /** Perform a critical database query.
    *
    * Executes a database query which is always expected to succeed and
    * critical for further operation. If the query fails, PHP execution
    * is halted and a critical error is sent.
    *
    * @param string $query Query string in SQL syntax.
    * @return The result of the respective @c mysql_query() command.
    */
   public function criticalQuery($query) {
     $status = $this->query($query);
     if (!$status) {
       if ($this->transaction) {
	 $this->rollback();
       }
       header("HTTP/1.1 500 Internal Server Error");
       echo("Database error while processing the following query: {$query}");
       die();
     }
     return $status;
   }

   /** Clone of RequestHandler::escapeSQL() */
   public function escapeSQL( $obj ) {
     if(is_string($obj)) {
       return mysql_real_escape_string($obj);
     }
     else if(is_array($obj)) {
       $newarray = array();
       foreach($obj as $k => $v) {
	 $newarray[$k] = self::escapeSQL($v);
       }
       return $newarray;
     }
     else if(is_object($obj) && get_class($obj)=='SimpleXMLElement') {
       return self::escapeSQL((string) $obj);
     }
     else {
       return $obj;
     }
   }
 }


 /** An interface for application-specific database requests.
  *
  * This class implements all application-specific functionality for
  * database access.  If some part of the application requires that one
  * or more SQL queries be sent to the database, these queries should
  * be encapsulated in a member function of this class.
  */
 class DBInterface {
   private $dbconn;
   private $db;

   /** Create a new DBInterface.
    *
    * Also sets the default database to the MAIN_DB constant.
    */
   function __construct($dbconn) {
     $this->dbconn = $dbconn;
     $this->db = $this->dbconn->getDatabase();
   }

   private function query($query) {
     return $this->dbconn->query($query);
   }

   private function criticalQuery($query) {
     return $this->dbconn->criticalQuery($query);
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
     $qs = "SELECT `id`, name, admin, lastactive FROM {$this->db}.users "
       . "WHERE name='{$user}' AND password='{$pw_hash}' AND `id`!=1 LIMIT 1";
     $query = $this->query( $qs );
     return $this->dbconn->fetch_assoc( $query );
   }

   /** Get user info by id.
    */
   public function getUserById($uid) {
     $qs = "SELECT `id`, name, admin, lastactive FROM {$this->db}.users "
       . "WHERE `id`={$uid}";
     $query = $this->query( $qs );
     return $this->dbconn->fetch_assoc( $query );
   }

   /** Get user info by name.
    */
   public function getUserByName($uname) {
     $qs = "SELECT `id`, name, admin, lastactive FROM {$this->db}.users "
       . "WHERE name='{$uname}'";
     $query = $this->query( $qs );
     return $this->dbconn->fetch_assoc( $query );
   }

   /** Get user ID by name.  Often used because formerly, users were
    *  always identified by name (and therefore the app still uses
    *  usernames in most places), while now, users must be identified
    *  by ID in all tables.
    */
   public function getUserIDFromName($uname) {
     $qs = "SELECT `id` FROM {$this->db}.users WHERE name='{$uname}'";
     $query = $this->query( $qs );
     $row = $this->dbconn->fetch_assoc( $query );
     return $row['id'];
   }

   /** Return settings for the current user. 
    *
    * @param string $user Username
    *
    * @return An array with the database entries from the table 'user_settings' for the given user.
    */
   public function getUserSettings($user){
	 $qs = "SELECT lines_per_page, lines_context, "
	     . "columns_order, columns_hidden, show_error "
	     . "FROM {$this->db}.users WHERE name='{$user}'";
	 return $this->dbconn->fetch_assoc( $this->query( $qs ) );
   }

   /** Get a list of all users.
    *
    * @return An @em array containing all usernames in the database and
    * information about their admin status.
    */
   public function getUserList() {
     $qs = "SELECT `id`, name, admin, lastactive FROM {$this->db}.users "
       . "  WHERE `id`!=1 ORDER BY name";
     $query = $this->query( $qs );
     $users = array();
     while ( $row = $this->dbconn->fetch_assoc($query) ) {
       $users[] = $row;
     }
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
     $result = array();
     if(!$class) {
       $qs = "SELECT * FROM {$this->db}.tagset ORDER BY `{$orderby}`";
     }
     else {
       $qs = "SELECT * FROM {$this->db}.tagset WHERE `class`='{$class}' ORDER BY `{$orderby}`";
     }
     $query = $this->query($qs);
     while ( $row = $this->dbconn->fetch_assoc($query) ) {
       $data = array();
       $data["id"] = $row["id"];
       $data["class"] = $row["class"];
       $data["set_type"] = $row["set_type"];
       $data["shortname"] = $row["id"];
       $data["longname"] = $row["name"];
       $result[] = $data;
     }
     return $result;
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
     $qs  = "SELECT `id`, `value`, `needs_revision` FROM {$this->db}.tag ";
     $qs .= "WHERE `tagset_id`='{$tagset}' ";
     if($limit=='legal') {
       $qs .= "AND `needs_revision`=0 ";
     }
     $qs .= "ORDER BY `value`";
     $query = $this->query($qs);
     while ( $row = $this->dbconn->fetch_assoc( $query ) ) {
       $tags[] = array('id' => $row['id'],
		       'value' => $row['value'],
		       'needs_revision' => $row['needs_revision']);
     }
     return $tags;
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
     $tags = array();
     $qs = "SELECT `id`, `value` FROM {$this->db}.tag WHERE `tagset_id`='{$tagset}'";
     $query = $this->query($qs);
     while ( $row = $this->dbconn->fetch_assoc( $query ) ) {
       $tags[$row['value']] = $row['id'];
     }
     return $tags;
   }

   /** Updates the "last active" timestamp for a user.
    *
    * @param string  $userid   ID of the user to be updated
    */
   public function updateLastactive($userid) {
     $qs = "UPDATE {$this->db}.users SET `lastactive`=NOW() WHERE `id`={$userid}";
     $this->query($qs);
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
     if(!empty($user)) {
       return false;
     }
     $hashpw = $this->hashPassword($password);
     $adm = $admin ? 1 : 0;
     $qs = "INSERT INTO {$this->db}.users (name, password, admin) "
       . "VALUES ('{$username}', '{$hashpw}', '{$adm}')";
     return $this->query($qs);
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
     $qs = "UPDATE {$this->db}.users SET password='{$hashpw}' "
       . "WHERE name='{$username}'";
     return $this->query($qs);
   }

   /** Change project<->user associations.
    *
    * @param string $pid the project ID of the project to change
    * @param array $userlist an array of user names
    *
    * @return The result of the corresponding @c mysql_query() command
    */
   public function changeProjectUsers($pid, $userlist) {
     $this->dbconn->startTransaction();
     $qs = "DELETE FROM {$this->db}.user2project WHERE project_id='".$pid."'";
     $message = "";
     $status = $this->query($qs);
     if ($status) {
       if (!empty($userlist)) {
	 $qs = "INSERT INTO {$this->db}.user2project (project_id, user_id) VALUES ";
	 $values = array();
	 foreach ($userlist as $uname) {
	   $user = $this->getUserByName($uname);
	   $values[] = "('".$pid."', '".$user['id']."')";
	 }
	 $qs .= implode(", ", $values);
	 $status = $this->query($qs);
       }
       if ($status) {
	 $this->dbconn->commitTransaction();
       } else {
	 $message = $this->dbconn->last_error();
	 $this->dbconn->rollback();
       }
     }
     else {
       $message = $this->dbconn->last_error();
       $this->dbconn->rollback();
     }
     return array('success' => $status, 'message' => $message);
   }

   /** Drop a user record from the database.
    *
    * @param string $username The username to be deleted
    *
    * @return The result of the corresponding @c mysql_query() command.
    */
   public function deleteUser($username) {
     $qs = "DELETE FROM {$this->db}.users WHERE name='{$username}' AND `id`!=1";
     return $this->query($qs);
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
     $qs = "SELECT {$statusname} FROM {$this->db}.users WHERE name='{$username}'";
     $query = $this->query($qs);
     $row = $this->dbconn->fetch($query);
     if (!$row)
       return false;
     $status = ($row[$statusname] == 1) ? 0 : 1;
     $qs = "UPDATE {$this->db}.users SET {$statusname}={$status} WHERE name='{$username}'";
     return $this->query($qs);
   }

   /** Find documents with a certain key/value pair in their metadata,
    *  e.g. documents with specific names or sigles.
    */
   public function queryForMetadata($metakey, $metavalue) {
     $qs  = "SELECT * FROM {$this->db}.text ";
     $qs .= "WHERE {$metakey}='{$metavalue}'";
     $query = $this->query($qs);
     $row = $this->dbconn->fetch_assoc($query);
     return $row;
   }

   /** Lock a file for editing.
    *
    * This function ensures restricted access when editing file data. In detail this is done by
    * a lock table where first all entries for the given user are deleted and then tries to insert
    * a new entry for the given file id. Note that if the file is already locked by another user,
    * the operation will fail und it is impossible to lock the file at the moment.
    *
    * Should be called before editing any database entries concerning the file content.
    *
    * $locksCount indicates the number of file which were locked by the given user and are unlocked now.
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
     // first, delete all locks by the current user
     $user = $this->getUserIDFromName($uname);
     $qs = "DELETE FROM {$this->db}.locks WHERE user_id={$user}";
     $this->query($qs);
     $locksCount = $this->dbconn->row_count();
     // then, check if file is still/already locked
     $qs = "SELECT * FROM {$this->db}.locks WHERE text_id={$fileid}";
     $result = $this->query($qs);
     if($this->dbconn->row_count($result)>0) {
       // if file is locked, return info about user currently locking the file
       $qs  = "SELECT a.lockdate as 'locked_since', b.name as 'locked_by' ";
       $qs .= "FROM {$this->db}.locks a, {$this->db}.users b ";
       $qs .= "WHERE a.text_id={$fileid} AND a.user_id=b.id";
       $query = $this->query($qs);
       $row = $this->dbconn->fetch_assoc( $query );
       return array("success" => false, "lock" => $row);
     }
     // otherwise, perform the lock
     $qs = "INSERT INTO {$this->db}.locks (text_id, user_id) VALUES ('{$fileid}', '{$user}')";
     $this->criticalQuery($qs);
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
     $user = $this->getUserByName($uname);
     $userid = $user['id'];
     $qs  = "SELECT a.text_id as 'file_id', b.fullname as 'file_name' ";
     $qs .= "FROM {$this->db}.locks a, {$this->db}.text b ";
     $qs .= "WHERE a.user_id='{$userid}' AND a.text_id=b.id LIMIT 1";
     return $this->dbconn->fetch_assoc($this->query($qs));
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
     if (empty($uname)) {
       $userid = $_SESSION['user_id'];
     } else {
       $user = $this->getUserByName($uname);
       $userid = $user['id'];
     }
     $qs = "DELETE FROM {$this->db}.locks WHERE text_id='{$fileid}'";
     if (!$force) {
       $qs .= " AND user_id='{$userid}'";
     }
     return $this->query($qs);
   }


   /** Get tagsets associated with a file.
    *
    * Retrieves a list of tagsets with their respective class for the
    * given file.
    */
   public function getTagsetsForFile($fileid) {
     $qs  = "SELECT ts.id, ts.class, ts.set_type ";
     $qs .= "FROM   {$this->db}.text2tagset ttt ";
     $qs .= "  LEFT JOIN {$this->db}.tagset ts  ON ts.id=ttt.tagset_id ";
     $qs .= "WHERE  ttt.text_id='{$fileid}'";
     $q = $this->query($qs);
     $qerr = $this->dbconn->last_error();
     if($qerr) { return $qerr; }

     $tslist = array();
     while($row = $this->dbconn->fetch_assoc($q)) {
       $tslist[] = $row;
     }
     return $tslist;
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
     $qs .= "FROM   ({$this->db}.text, {$this->db}.text2tagset ttt) ";
     $qs .= "  LEFT JOIN {$this->db}.tagset ";
     $qs .= "         ON ttt.tagset_id=tagset.id ";
     $qs .= "WHERE  text.id='{$fileid}' AND tagset.class='POS' AND ttt.text_id='{$fileid}'";
     $metadata = $this->dbconn->fetch_assoc($this->query($qs));
     $cmid = $metadata['currentmod_id'];
     $lock['lastEditedRow'] = -1;
     if(!empty($cmid)) {
       // calculate position of currentmod_id
       $qs  = "SELECT position FROM ";
       $qs .= " (SELECT x.id, @rownum := @rownum + 1 AS position FROM ";
       $qs .= "   (SELECT a.id FROM ({$this->db}.modern a, {$this->db}.token b) ";
       $qs .= "    WHERE a.tok_id=b.id AND b.text_id='{$fileid}' ";
       $qs .= "    ORDER BY b.ordnr ASC, a.id ASC) x ";
       $qs .= "  JOIN (SELECT @rownum := 0) r) y ";
       $qs .= "WHERE y.id = '{$cmid}'";
       if($q = $this->query($qs)){
	 $row = $this->dbconn->fetch_assoc($q);
	 $lock['lastEditedRow'] = intval($row['position']) - 1;
       }
     }
     // fetch information about associated tagsets
     $qs  = "SELECT tagset.id, tagset.name, tagset.class, tagset.set_type "
       . "     FROM   ({$this->db}.tagset, {$this->db}.text2tagset) "
       . "     WHERE  tagset.id=text2tagset.tagset_id AND text2tagset.text_id='{$fileid}'";
     $metadata['tagsets'] = array();
     $q = $this->query($qs);
     while($row = $this->dbconn->fetch_assoc($q)) {
       $metadata['tagsets'][] = $row;
     }
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
     $qs  = "SELECT a.fullname FROM ({$this->db}.text a, {$this->db}.user2project b) ";
     $qs .= "WHERE a.id='{$fileid}' AND b.user_id='{$uid}' AND a.project_id=b.project_id";
     $q = $this->query($qs);
     return ($this->dbconn->fetch_array($q)) ? true : false;
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
     $deletetag = array();
     $qs  = "SELECT tag.id AS tag_id FROM {$this->db}.tag "
       . "     LEFT JOIN {$this->db}.tag_suggestion ts ON tag.id=ts.tag_id "
       . "     LEFT JOIN {$this->db}.tagset            ON tagset.id=tag.tagset_id "
       . "     LEFT JOIN {$this->db}.modern            ON modern.id=ts.mod_id "
       . "     LEFT JOIN {$this->db}.token             ON token.id=modern.tok_id "
       . "     LEFT JOIN {$this->db}.text              ON text.id=token.text_id "
       . "   WHERE  tagset.set_type='open' AND text.id={$fileid}";
     $q = $this->query($qs);
     $qerr = $this->dbconn->last_error($q);
     if($qerr) { return "Ein interner Fehler ist aufgetreten (Code: 1040).\n" . $qerr; }
     while($row = $this->dbconn->fetch_assoc($q)) {
       $deletetag[] = $row['tag_id'];
     }

     $this->dbconn->startTransaction();

     // delete associated open class tags
     if(!empty($deletetag)) {
       $qs = "DELETE FROM {$this->db}.tag_suggestion WHERE `tag_id` IN (" . implode(",", $deletetag) . ")";
       $q = $this->query($qs);
       $qerr = $this->dbconn->last_error($q);
       if($qerr) {
	 $this->dbconn->rollback();
	 return "Ein interner Fehler ist aufgetreten (Code: 1041).\n" . $qerr;
       }
       $qs = "DELETE FROM {$this->db}.tag WHERE `id` IN (" . implode(",", $deletetag) . ")";
       $q = $this->query($qs);
       $qerr = $this->dbconn->last_error($q);
       if($qerr) {
	 $this->dbconn->rollback();
	 return "Ein interner Fehler ist aufgetreten (Code: 1043).\n" . $qerr;
       }
     }

     // delete text---deletions in all other tables are triggered
     // automatically in the database via ON DELETE CASCADE
     $qs = "DELETE FROM {$this->db}.text WHERE `id`={$fileid}";
     $q = $this->query($qs);
     $qerr = $this->dbconn->last_error($q);
     if($qerr) {
       $this->dbconn->rollback();
       return "Ein interner Fehler ist aufgetreten (Code: 1042).\n" . $qerr;
     }

     $this->dbconn->commitTransaction();

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
     $qs = "SELECT a.*, d.id as project_id, d.name as project_name, "
       . "c.name as opened, e.name as creator_name, f.name as changer_name "
       . "     FROM {$this->db}.text a "
       . "LEFT JOIN {$this->db}.locks b ON a.id=b.text_id "
       . "LEFT JOIN {$this->db}.users c ON b.user_id=c.id "
       . "LEFT JOIN {$this->db}.project d ON a.project_id=d.id "
       . "LEFT JOIN {$this->db}.users e ON a.creator_id=e.id "
       . "LEFT JOIN {$this->db}.users f ON a.changer_id=f.id "
       . "ORDER BY sigle, fullname";
     $query = $this->query($qs); 
     $files = array();
     while ( $row = $this->dbconn->fetch_assoc( $query ) ) {
       $files[] = $row;
     }
     return $files;
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
     $user = $this->getUserByName($uname);
     $uid = $user["id"];
     $qs = "SELECT a.*, d.id as project_id, d.name as project_name, "
       . "c.name as opened, e.name as creator_name, f.name as changer_name "
       . "    FROM ({$this->db}.text a, {$this->db}.user2project g) "
       . "LEFT JOIN {$this->db}.locks b ON a.id=b.text_id "
       . "LEFT JOIN {$this->db}.users c ON b.user_id=c.id "
       . "LEFT JOIN {$this->db}.project d ON a.project_id=d.id "
       . "LEFT JOIN {$this->db}.users e ON a.creator_id=e.id "
       . "LEFT JOIN {$this->db}.users f ON a.changer_id=f.id "
       . "WHERE (a.project_id=g.project_id AND g.user_id={$uid}) "
       . "ORDER BY sigle, fullname";
     $query = $this->query($qs); 
     $files = array();
     while ( $row = $this->dbconn->fetch_assoc( $query ) ) {
       $files[] = $row;
     }
     return $files;
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
     $qs = "SELECT * FROM {$this->db}.project ORDER BY name";
     $query = $this->query($qs); 
     $projects = array();
     while ( $row = $this->dbconn->fetch_assoc( $query ) ) {
       $projects[] = $row;
     }
     return $projects;
   }

   /** Get a list of all project user groups.  
    *
    * Should only be called for administrators.
    *
    * @return a two-dimensional @em array with the project id and name
    */
   public function getProjectUsers(){
     $qs = "SELECT * FROM {$this->db}.user2project";
     $query = $this->query($qs); 
     $projects = array();
     while ( $row = $this->dbconn->fetch( $query ) ) {
       $user = $this->getUserById($row["user_id"]);
       $projects[] = array("project_id" => $row["project_id"],
			   "username" => $user["name"]);
     }
     return $projects;
   }

   /** Get a list of all projects accessible by a given user.
    *
    * @param string $user username
    * @return a two-dimensional @em array with the project id and name
    */
   public function getProjectsForUser($uname){
     $user = $this->getUserByName($uname);
     $uid  = $user["id"];
     $qs = "SELECT a.* FROM ({$this->db}.project a, {$this->db}.user2project b) WHERE (a.id=b.project_id AND b.user_id='{$uid}') ORDER BY `id`";
     $query = $this->query($qs); 
     $projects = array();
     while ( $row = $this->dbconn->fetch_assoc( $query ) ) {
       $projects[] = $row;
     }
     return $projects;
   }

   /** Create a new project.
    *
    * @param string $name project name
    * @return the project ID of the newly generated project
    */
   public function createProject($name){
     $qs = "INSERT INTO {$this->db}.project (`name`) VALUES ('{$name}')";
     $query = $this->query($qs);
     $qerr = $this->dbconn->last_error($query);
     if($qerr) {
       return array("success" => false, "errors" => array($qerr));
     }
     return array("success" => true, "pid" => $this->dbconn->last_insert_id());
   }

   /** Deletes a project.  Will fail unless no document is
    * assigned to the project.
    *
    * @param string $pid the project id
    * @return a boolean value indicating success
    */
   public function deleteProject($pid){
     $qs = "DELETE FROM {$this->db}.project WHERE `id`={$pid}";
     return ($this->query($qs)) ? true : false;
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
     $qs = "UPDATE {$this->db}.users SET lines_per_page={$lpp},lines_context={$cl} WHERE name='{$user}' AND `id`!=1";
     return $this->query($qs);
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
       $qs = "UPDATE {$this->db}.users SET {$name}='{$value}' WHERE name='{$user}' AND `id`!=1";
       return $this->query($qs);
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
     $qs  = "SELECT COUNT(modern.id) FROM {$this->db}.token ";
     $qs .= "LEFT JOIN {$this->db}.modern ON modern.tok_id=token.id ";
     $qs .= "WHERE token.text_id='{$fileid}' ";
     $row = $this->dbconn->fetch_array($this->query($qs));
     return $row[0];
   }

   /** Retrieve all lines from a file, including error data,
    *  but not tagger suggestions.
    */
   public function getAllLines($fileid){
     $qs = "SELECT a.*, b.value AS 'errorChk' FROM {$this->db}.files_data a LEFT JOIN {$this->db}.files_errors b ON (a.file_id=b.file_id AND a.line_id=b.line_id) WHERE a.file_id='{$fileid}' ORDER BY line_id";
     $query = $this->query($qs);
     $data = array();
     while($row = $this->dbconn->fetch_assoc($query)){
       $data[] = $row;
     }
     return $data;
   }

   /** Retrieve all tagger suggestions for a line in a file. */
   public function getAllSuggestions($fileid, $lineid){
     $qs = "SELECT * FROM {$this->db}.files_tags_suggestion WHERE file_id='{$fileid}' AND line_id='{$lineid}' ORDER BY tagtype DESC";
     $q = $this->query($qs);

     $tag_suggestions = array();
     while($row = $this->dbconn->fetch_assoc($q)){
       $tag_suggestions[] = $row;
     }
     return $tag_suggestions;
   }

   /** Retrieves a specified number of lines from a file.
    *
    * @param string $fileid the file id
    * @param string $start line id of the first line to be retrieved
    * @param string $lim numbers of lines to be retrieved
    *
    * @return an @em array containing the lines
    */ 	
   public function getLines($fileid,$start,$lim){		
     $data = array();

     $qs  = "SELECT x.* FROM ";
     $qs .= "  (SELECT q.*, @rownum := @rownum + 1 AS num FROM ";
     $qs .= "    (SELECT modern.id, modern.trans, modern.utf, ";
     $qs .= "            modern.tok_id, token.trans AS full_trans, "; // full_trans is currently being overwritten later!
     $qs .= "            c1.value AS comment "; // ,c2.value AS k_comment
     $qs .= "     FROM   {$this->db}.token ";
     $qs .= "       LEFT JOIN {$this->db}.modern  ON modern.tok_id=token.id ";
     $qs .= "       LEFT JOIN {$this->db}.comment c1 ON  c1.tok_id=token.id ";
     $qs .= "             AND c1.subtok_id=modern.id AND c1.comment_type='C' ";
     //$qs .= "       LEFT JOIN {$this->db}.comment c2 ON  c2.tok_id=token.id ";
     //$qs .= "                                        AND c2.comment_type='K' ";
     $qs .= "     WHERE  token.text_id='{$fileid}' ";
     $qs .= "     ORDER BY token.ordnr ASC, modern.id ASC) q ";
     $qs .= "   JOIN (SELECT @rownum := -1) r WHERE q.id IS NOT NULL) x ";
     $qs .= "LIMIT {$start},{$lim}";
     $query = $this->query($qs); 		

     /* The following loop separately performs all queries on a
	line-by-line basis that can potentially return more than one
	result row.  Integrating this in the SELECT above might yield
	better performance, but will be more complicated to process (as
	the 1:1 relation between rows and modern tokens is no longer
	guaranteed).  Change this only if performance becomes an issue.
      */
     while($line = $this->dbconn->fetch_assoc($query)){
       $mid = $line['id'];

       // Transcription including spaces for line breaks
       $ttrans = "";
       $qs  = "SELECT `trans`, `line_id` FROM {$this->db}.dipl ";
       $qs .= " WHERE `tok_id`=" . $line['tok_id'];
       $qs .= " ORDER BY `id` ASC";
       $q = $this->query($qs);
       $lastline = null;
       while($row = $this->dbconn->fetch_assoc($q)) {
	 if($lastline!==null && $lastline!==$row['line_id']) {
	   $ttrans .= " ";
	 }
	 $ttrans .= $row['trans'];
	 $lastline = $row['line_id'];
       }
       $line['full_trans'] = $ttrans;
       unset($lastline);

       // Layout info
       $qs  = "SELECT l.name AS line_name, c.name AS col_name, l.num AS line_num, ";
       $qs .= "       p.name AS page_name, p.side AS page_side, p.num AS page_num  ";
       $qs .= "FROM   {$this->db}.dipl d ";
       $qs .= "  LEFT JOIN {$this->db}.line l ON l.id=d.line_id ";
       $qs .= "  LEFT JOIN {$this->db}.col  c ON c.id=l.col_id ";
       $qs .= "  LEFT JOIN {$this->db}.page p ON p.id=c.page_id ";
       $qs .= "WHERE  d.tok_id=" . $line['tok_id'];
       $qs .= " ORDER BY d.id ASC LIMIT 1";
       $q = $this->query($qs);
       $row = $this->dbconn->fetch_assoc($q);
       if($row) {
	 $line['line_name'] = $row['line_name'] ? $row['line_name'] : $row['line_num'];
	 $line['col_name']  = $row['col_name']  ? $row['col_name']  : "";
	 $line['page_name'] = $row['page_name'] ? $row['page_name'] : $row['page_num'];
	 $line['page_side'] = $row['page_side'] ? $row['page_side'] : "";
       }

       // Error annotations
       $qs  = "SELECT error_types.name ";
       $qs .= "FROM   {$this->db}.modern ";
       $qs .= "  LEFT JOIN {$this->db}.mod2error ON modern.id=mod2error.mod_id ";
       $qs .= "  LEFT JOIN {$this->db}.error_types ON mod2error.error_id=error_types.id ";
       $qs .= "WHERE  modern.id='{$mid}'";
       $q = $this->query($qs);
       while($row = $this->dbconn->fetch_assoc($q)) {
	 if($row['name']=='general error') {
	   $line['general_error'] = 1;
	 } else if($row['name']=='lemma verified') {
	   $line['lemma_verified'] = 1;
	 }
       }

       // Annotations
       $qs  = "SELECT tag.value, ts.score, ts.selected, ts.source, tt.class ";
       $qs .= "FROM   {$this->db}.modern";
       $qs .= "  LEFT JOIN ({$this->db}.tag_suggestion ts, {$this->db}.tag) ";
       $qs .= "         ON (ts.tag_id=tag.id AND ts.mod_id=modern.id) ";
       $qs .= "  LEFT JOIN {$this->db}.tagset tt ON tag.tagset_id=tt.id ";
       $qs .= "WHERE  modern.id='{$mid}' ";
       $q = $this->query($qs);

       // prepare results for CorA---this is less flexible, but
       // probably faster than doing it on the client side
       $line['suggestions'] = array();
       while($row = $this->dbconn->fetch_assoc($q)){
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

       $data[] = $line;
     }

     return $data;
   }

   /** Retrieves error types and indexes them by name. */
   public function getErrorTypes() {
     $qs = "SELECT * FROM {$this->db}.error_types";
     $q = $this->query($qs);
     $errortypes = array();
     while($row = $this->dbconn->fetch_assoc($q)) {
       $errortypes[$row['name']] = $row['id'];
     }
     return $errortypes;
   }

   /** Delete locks if the locking user has been inactive for too
    * long; currently, this is set to be >30 minutes. */
   public function releaseOldLocks() {
     $qs  = "DELETE {$this->db}.locks FROM {$this->db}.locks";
     $qs .= "  LEFT JOIN {$this->db}.users ON users.id=locks.user_id";
     $qs .= "  WHERE users.lastactive < (NOW() - INTERVAL 30 MINUTE)";
     $this->query($qs);
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

     $idlist = array();
     $pos_tags = array();    // maps tags to tag IDs
     $norm_types = array();
     $tagset_ids = array();  // maps tagset classes to tagset IDs
     $comment_ids = array(); // maps modern IDs to comment IDs (of 'C' type comments)
     $token_ids = array();   // maps modern IDs to token IDs

     $warnings = array();

     /* Check if all IDs belong to the currently opened document
	(--this is done because IDs are managed on the client side and
	therefore could potentially be manipulated)
	-- also, hijacked this to extract comment information
     */
     foreach($lines as $line) {
       $idlist[] = $line['id'];
     }
     $modchk  = "SELECT modern.id, comment.id AS comment_id, token.id AS token_id ";
     $modchk .= "FROM {$this->db}.modern ";
     $modchk .= "  LEFT JOIN {$this->db}.token   ON modern.tok_id=token.id ";
     $modchk .= "  LEFT JOIN {$this->db}.text    ON token.text_id=text.id ";
     $modchk .= "  LEFT JOIN {$this->db}.comment ON comment.tok_id=token.id ";
     $modchk .= "                               AND comment.subtok_id=modern.id ";
     $modchk .= "                               AND comment.comment_type='C' ";
     $modchk .= "WHERE text.id='{$fileid}' AND modern.id IN (";
     $modchk .= implode(',', $idlist);
     $modchk .= ")";
     $modchq  = $this->query($modchk);
     $modchn  = $this->dbconn->row_count($modchq);
     if($modchn!=count($idlist)) {
       $diff = count($idlist) - $modchn;
       return "Ein interner Fehler ist aufgetreten (Code: 1074).  Die Anfrage enthielt {$diff} ungültige Token-ID(s) für das derzeit geöffnete Dokument.";
     }
     while($row = $this->dbconn->fetch_assoc($modchq)) {
       $comment_ids[$row['id']] = $row['comment_id'];
       $token_ids[$row['id']]   = $row['token_id'];
     }

     /* Get tagset information for currently opened document */
     $errortypes = $this->getErrorTypes();
     if(array_key_exists('general error', $errortypes)) {
       $error_general = $errortypes['general error'];
     } else {
       $error_general = false;
     }
     if(array_key_exists('lemma verified', $errortypes)) {
       $lemma_verified = $errortypes['lemma verified'];
     } else {
       $lemma_verified = false;
     }
     $tslist = $this->getTagsetsForFile($fileid);
     if(!is_array($tslist)) {
       return "Ein interner Fehler ist aufgetreten (Code: 1075).  Die Datenbank meldete:\n{$tslist}";
     }
     foreach($tslist as $tagset) {
       if($tagset['class'] == "lemma") {
	 if($tagset['set_type'] == "open") {
	   $tagset_ids["lemma"] = $tagset['id'];
	 }
       }
       else {
	 $tagset_ids[$tagset['class']] = $tagset['id'];
       }
     }
     $hasnorm = array_key_exists('norm', $tagset_ids);
     $hasmod = array_key_exists('norm_broad', $tagset_ids) && array_key_exists('norm_type', $tagset_ids);
     $haslemma = array_key_exists('lemma', $tagset_ids);
     $haspos = array_key_exists('POS', $tagset_ids);
     // norm_types
     if($hasmod) {
       $qstr  = "SELECT `id`, `value` FROM {$this->db}.tag ";
       $qstr .= "WHERE `tagset_id`='" . $tagset_ids['norm_type'] . "'";
       $q = $this->query($qstr);
       $qerr = $this->dbconn->last_error();
       if($qerr) {
	 return "Ein interner Fehler ist aufgetreten (Code: 1076).  Die Datenbank meldete:\n{$qerr}";
       }
       while($row = $this->dbconn->fetch_assoc($q)) {
	 $norm_types[$row['value']] = $row['id'];
       }
     }
     // POS
     if($haspos) {
       $qstr  = "SELECT `id`, `value` FROM {$this->db}.tag ";
       $qstr .= "WHERE `tagset_id`='" . $tagset_ids['POS'] . "'";
       $q = $this->query($qstr);
       $qerr = $this->dbconn->last_error();
       if($qerr) {
	 return "Ein interner Fehler ist aufgetreten (Code: 1076).  Die Datenbank meldete:\n{$qerr}";
       }
       while($row = $this->dbconn->fetch_assoc($q)) {
	 $pos_tags[$row['value']] = $row['id'];
       }
     }


     $updatetag = array();    // array with tags to be updated
     $inserttag = array();    // array with tags to be inserted
     $deletetag = array();    // array with tags to be deleted
     $insertts  = array();    // array with inserts/updates to tag_suggestion table
     $deletets  = array();    // array with tag_suggestions to be deleted
     $inserterr = array();
     $deleteerr = array();
     $insertcom = array();    // array with comments to be inserted
     $deletecom = array();

     foreach($lines as $line) {
       /* Get currently selected annotations */
       $selected = array();
       $qstr  = "SELECT ts.id, ts.tag_id, ts.source, tag.value, tagset.class ";
       $qstr .= "FROM   {$this->db}.tag_suggestion ts ";
       $qstr .= "  LEFT JOIN {$this->db}.tag ON tag.id=ts.tag_id ";
       $qstr .= "  LEFT JOIN {$this->db}.tagset ON tagset.id=tag.tagset_id ";
       $qstr .= "WHERE  ts.selected=1 AND ts.mod_id='" . $line['id'] . "'";
       $q = $this->query($qstr);
       $qerr = $this->dbconn->last_error();
       if($qerr) {
	 $this->dbconn->rollback();
	 return "Ein interner Fehler ist aufgetreten (Code: 1077).  Die Datenbank meldete:\n{$qerr}";
       }
       while($row = $this->dbconn->fetch_assoc($q)) {
	 $selected[$row['class']] = $row;
       }

       /* Fill arrays with new annotations */
       // Norm
       if($hasnorm && array_key_exists('anno_norm', $line)) {
	 $tagvalue = $line['anno_norm'];
	 if(!empty($tagvalue)) {
	   if(array_key_exists('norm', $selected)) {
	     $tagid = $selected['norm']['tag_id'];
	     $updatetag[] = "('{$tagid}', '{$tagvalue}')";
	   }
	   else {
	     $inserttag[] = array("query" => "('{$tagvalue}', 0, '" . $tagset_ids['norm'] . "')",
				  "line_id" => $line['id']);
	   }
	 }
	 else if(array_key_exists('norm', $selected)) {
	   $deletetag[] = $selected['norm']['tag_id'];
	 }
       }
       if($hasmod) {
	 if(array_key_exists('anno_mod', $line)) {
	   $tagvalue = $line['anno_mod'];
	   if(!empty($tagvalue)) {
	     if(array_key_exists('norm_broad', $selected)) {
	       $tagid = $selected['norm_broad']['tag_id'];
	       $updatetag[] = "('{$tagid}', '{$tagvalue}')";
	     }
	     else {
	       $inserttag[] = array("query" => "('{$tagvalue}', 0, '" . $tagset_ids['norm_broad'] . "')",
				    "line_id" => $line['id']);
	     }
	   }
	   else if(array_key_exists('norm_broad', $selected)) {
	     $deletetag[] = $selected['norm_broad']['tag_id'];
	   }
	 }
	 if(array_key_exists('anno_modtype', $line)) {
	   $tagvalue = $line['anno_modtype'];
	   if(!empty($tagvalue)) {
	     if(array_key_exists($tagvalue, $norm_types)) { // legal?
	       $newid = $norm_types[$tagvalue];
	       if(array_key_exists('norm_type', $selected)) {
		 $tagid = $selected['norm_type']['tag_id'];
		 if($tagid !== $newid) { // change required?
		   $deletets[] = $selected['norm_type']['id'];
		   $tsstr  = "(NULL, 1, 'user', '{$newid}', '" . $line['id'] . "')";
		   $insertts[] = $tsstr;
		 }
	       }
	       else {
		 // simply insert
		 $tsstr  = "(NULL, 1, 'user', '{$newid}', '" . $line['id'] . "')";
		 $insertts[] = $tsstr;
	       }
	     }
	     else {
	       $warnings[] = "Überspringe illegalen Modernisierungstyp: {$tagvalue}";
	     }
	   }
	   else if(array_key_exists('norm_type', $selected)) {
	     // delete
	     $deletets[] = $selected['norm_type']['id'];
	   }
	 }
       }
       // Lemma
       if($haslemma && array_key_exists('anno_lemma', $line)) {
	 $tagvalue = $line['anno_lemma'];
	 if(!empty($tagvalue)) {
	   if(array_key_exists('lemma', $selected)) {
	     $tagid = $selected['lemma']['tag_id'];
	     $updatetag[] = "('{$tagid}', '{$tagvalue}')";
	   }
	   else {
	     $inserttag[] = array("query" => "('{$tagvalue}', 0, '" . $tagset_ids['lemma'] . "')",
				  "line_id" => $line['id']);
	   }
	 }
	 else if(array_key_exists('lemma', $selected)) {
	   $deletetag[] = $selected['lemma']['tag_id'];
	 }
       }
       // POS
       if($haspos && array_key_exists('anno_POS', $line)) {
	 if(array_key_exists('anno_morph', $line)
	    && !empty($line['anno_morph'])
	    && $line['anno_morph'] != "--") {
	   $tagvalue = $line['anno_POS'] . "." . $line['anno_morph'];
	 } else {
	   $tagvalue = $line['anno_POS'];
	 }
	 if(!empty($tagvalue)) {
	   if(array_key_exists($tagvalue, $pos_tags)) { // legal POS tag?
	     $newid = $pos_tags[$tagvalue];
	     if(array_key_exists('POS', $selected)) {
	       $tagid = $selected['POS']['tag_id'];
	       if($tagid !== $newid) { // change required?
		 if($selected['POS']['source'] == 'auto') {
		   // deselect
		   $tsstr  = "('" . $selected['POS']['id'] . "', 0, 'auto', ";
		   $tsstr .= "'{$tagid}', '" . $line['id'] . "')";
		   $insertts[] = $tsstr;
		 } else {
		   // delete
		   $deletets[] = $selected['POS']['id'];
		 }
		 // insert
		 $tsstr  = "(NULL, 1, 'user', '{$newid}', '" . $line['id'] . "')";
		 $insertts[] = $tsstr;
	       }
	     }
	     else {
	       // simply insert
	       $tsstr  = "(NULL, 1, 'user', '{$newid}', '" . $line['id'] . "')";
	       $insertts[] = $tsstr;
	     }
	   }
	   else {
	     $warnings[] = "Überspringe illegalen POS-Tag: {$tagvalue}";
	   }
	 }
	 else if(array_key_exists('POS', $selected)) {
	   if($selected['POS']['source'] == 'auto') {
	     // deselect
	     $tsstr  = "('" . $selected['POS']['id'] . "', 0, 'auto', ";
	     $tsstr .= "'{$tagid}', '" . $line['id'] . "')";
	     $insertts[] = $tsstr;
	   } else {
	     // delete
	     $deletets[] = $selected['POS']['id'];
	   }
	 }
       }

       // Error annotation
       if(array_key_exists('general_error', $line)) {
	 if($error_general) {
	   if(intval($line['general_error']) == 1) {
	     $inserterr[] = "('" . $line['id'] . "', '" . $error_general . "')";
	   }
	   else {
	     // hack ...
	     $deleteerr[] = "(`mod_id`='" . $line['id'] . "' AND `error_id`='" . $error_general . "')";
	   }
	 }
       }
       if(array_key_exists('lemma_verified', $line)) {
	 if($lemma_verified) {
	   if(intval($line['lemma_verified']) == 1) {
	     $inserterr[] = "('" . $line['id'] . "', '" . $lemma_verified . "')";
	   }
	   else {
	     // same hack ...
	     $deleteerr[] = "(`mod_id`='" . $line['id'] . "' AND `error_id`='" . $lemma_verified . "')";
	   }
	 }
       }

       // CorA comment
       if(array_key_exists('comment', $line)) {
	 $comment_id = $comment_ids[$line['id']];
	 if($comment_id==null || empty($comment_id)) {
	   $comment_id = "NULL";
	 } else {
	   $comment_id = "'{$comment_id}'";
	   if(empty($line['comment'])) {
	     $deletecom[] = $comment_id;
	   }
	 }
	 if(!empty($line['comment'])) {
	   $insertcom[] = "({$comment_id}, '" . $token_ids[$line['id']] . "', '" . $this->dbconn->escapeSQL($line['comment']) . "', 'C', '" . $line['id'] . "')";
	 }
       }

     }

     /* Only now, perform all INSERTs/DELETEs/UPDATEs */

     $this->dbconn->startTransaction();

     try {
       if(!empty($updatetag)) {
	 $qstr  = "INSERT INTO {$this->db}.tag (`id`, `value`) VALUES ";
	 $qstr .= implode(",", $updatetag);
	 $qstr .= " ON DUPLICATE KEY UPDATE `value`=VALUES(value)";
	 $q = $this->query($qstr);
	 $qerr = $this->dbconn->last_error();
         if($qerr) {
             throw new SQLQueryException($qerr."\n".$qstr);
         }
       }
       if(!empty($inserttag)) {
	 foreach($inserttag as $insertdata) {
	   $qstr = "INSERT INTO {$this->db}.tag (`value`, `needs_revision`, `tagset_id`)";
	   $qstr .= "VALUES " . $insertdata['query'];
	   $q = $this->query($qstr);
	   $qerr = $this->dbconn->last_error();
           if($qerr) {
               throw new SQLQueryException($qerr."\n".$qstr);
           }
	   $q = $this->query("SELECT LAST_INSERT_ID()");
	   $row = $this->dbconn->fetch_array($q);
	   $newid = $row[0];
	   $tsstr  = "(NULL, 1, 'user', '{$newid}', '" . $insertdata['line_id'] . "')";
	   $insertts[] = $tsstr;
	 }
       }
       if(!empty($deletetag)) {
	 $qstr  = "DELETE FROM {$this->db}.tag_suggestion WHERE `tag_id` IN ('";
	 $qstr .= implode("','", $deletetag);
	 $qstr .= "')";
	 $q = $this->query($qstr);
	 $qerr = $this->dbconn->last_error();
         if($qerr) {
             throw new SQLQueryException($qerr."\n".$qstr);
         }
	 $qstr  = "DELETE FROM {$this->db}.tag WHERE `id` IN ('";
	 $qstr .= implode("','", $deletetag);
	 $qstr .= "')";
	 $q = $this->query($qstr);
	 $qerr = $this->dbconn->last_error();
         if($qerr) {
             throw new SQLQueryException($qerr."\n".$qstr);
         }
       }
       if(!empty($insertts)) {
	 $qstr  = "INSERT INTO {$this->db}.tag_suggestion ";
	 $qstr .= " (`id`, `selected`, `source`, `tag_id`, `mod_id`) VALUES ";
	 $qstr .= implode(",", $insertts);
	 $qstr .= " ON DUPLICATE KEY UPDATE `selected`=VALUES(selected), ";
	 $qstr .= "                         `tag_id`=VALUES(tag_id)";
	 $q = $this->query($qstr);
	 $qerr = $this->dbconn->last_error();
         if($qerr) {
             throw new SQLQueryException($qerr."\n".$qstr);
         }
       }
       if(!empty($deletets)) {
	 $qstr  = "DELETE FROM {$this->db}.tag_suggestion WHERE `id` IN ('";
	 $qstr .= implode("','", $deletets);
	 $qstr .= "')";
	 $q = $this->query($qstr);
	 $qerr = $this->dbconn->last_error();
         if($qerr) {
             throw new SQLQueryException($qerr."\n".$qstr);
         }
       }
       if(!empty($deleteerr)) {
	 $qstr  = "DELETE FROM {$this->db}.mod2error WHERE ";
	 $qstr .= implode(" OR ", $deleteerr);
	 $q = $this->query($qstr);
	 $qerr = $this->dbconn->last_error();
         if($qerr) {
             throw new SQLQueryException($qerr."\n".$qstr);
         }
       }
       if(!empty($inserterr)) {
	 $qstr  = "INSERT IGNORE INTO {$this->db}.mod2error ";
	 $qstr .= "  (`mod_id`, `error_id`) VALUES ";
	 $qstr .= implode(", ", $inserterr);
	 $q = $this->query($qstr);
	 $qerr = $this->dbconn->last_error();
         if($qerr) {
             throw new SQLQueryException($qerr."\n".$qstr);
         }
       }
       if(!empty($insertcom)) {
	 $qstr  = "INSERT INTO {$this->db}.comment ";
	 $qstr .= "  (`id`, `tok_id`, `value`, `comment_type`, `subtok_id`) VALUES ";
	 $qstr .= implode(",", $insertcom);
	 $qstr .= " ON DUPLICATE KEY UPDATE `value`=VALUES(value)";
	 $q = $this->query($qstr);
	 $qerr = $this->dbconn->last_error();
         if($qerr) {
             throw new SQLQueryException($qerr."\n".$qstr);
         }
       }
       if(!empty($deletecom)) {
	 $qstr  = "DELETE FROM {$this->db}.comment WHERE `id` IN (";
	 $qstr .= implode(", ", $deletecom);
	 $qstr .= ")";
	 $q = $this->query($qstr);
	 $qerr = $this->dbconn->last_error();
         if($qerr) {
             throw new SQLQueryException($qerr."\n".$qstr);
         }
       }
     }
     catch(SQLQueryException $e) {
       $this->dbconn->rollback();
       return "Ein interner Fehler ist aufgetreten (Code: 1080).  Die Datenbank meldete:\n" . $e->getMessage();
     }

     $this->dbconn->commitTransaction();

     // mark the last position
     $this->markLastPosition($fileid,$lasteditedrow);
     // update timestamp
     $userid = $this->getUserIDFromName($uname);
     $this->updateChangedTimestamp($fileid,$userid);

     if(!empty($warnings)) {
       return "Der Speichervorgang wurde abgeschlossen, einige Informationen wurden jedoch möglicherweise nicht gespeichert.  Das System meldete:\n" . implode("\n", $warnings);
     }
     return False;
   }

   /** Updates "last edited" information for a file.
    */
   public function updateChangedTimestamp($fileid,$userid) {
     $qs = "UPDATE {$this->db}.text SET `changer_id`={$userid}, `changed`=CURRENT_TIMESTAMP WHERE `id`={$fileid}";
     return $this->query($qs);
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
     $qs = "UPDATE {$this->db}.text SET `currentmod_id`='{$line}' WHERE `id`='{$fileid}'";
     return $this->query($qs);
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
     $qs = "SELECT * FROM {$this->db}.modern WHERE `tok_id`='{$tokenid}' ORDER BY `id` ASC";
     $q = $this->query($qs);
     $oldmodcount = $this->dbconn->row_count($q);

     // find IDs of next and previous tokens
     $qs  = "SELECT a.id FROM {$this->db}.token a ";
     $qs .= "WHERE  a.ordnr > (SELECT b.ordnr FROM {$this->db}.token b ";
     $qs .= "                  WHERE  b.id={$tokenid}) ";
     $qs .= "       AND a.text_id={$textid} ";
     $qs .= "ORDER BY a.ordnr ASC LIMIT 1 ";
     $q = $this->query($qs);
     $qerr = $this->dbconn->last_error($q);
     if($qerr) {
       $errors[] = "Ein interner Fehler ist aufgetreten (Code: 1220).";
       $errors[] = $qerr . "\n" . $qs;
       return array("success" => false, "errors" => $errors);
     }
     $row = $this->dbconn->fetch_assoc($q);
     if($row && array_key_exists('id', $row)) {
       $nexttokenid = $row['id'];
     }
     $qs  = "SELECT a.id FROM {$this->db}.token a ";
     $qs .= "WHERE  a.ordnr < (SELECT b.ordnr FROM {$this->db}.token b ";
     $qs .= "                  WHERE  b.id={$tokenid}) ";
     $qs .= "       AND a.text_id={$textid} ";
     $qs .= "ORDER BY a.ordnr DESC LIMIT 1 ";
     $q = $this->query($qs);
     $qerr = $this->dbconn->last_error($q);
     if($qerr) {
       $errors[] = "Ein interner Fehler ist aufgetreten (Code: 1221).";
       $errors[] = $qerr . "\n" . $qs;
       return array("success" => false, "errors" => $errors);
     }
     $row = $this->dbconn->fetch_assoc($q);
     if($row && array_key_exists('id', $row)) {
       $prevtokenid = $row['id'];
     }

     // find shift tags attached to this token
     $stinsert = array();
     $qs  = "SELECT `id`, `tok_from`, `tok_to` FROM {$this->db}.shifttags ";
     $qs .= "WHERE  `tok_from`={$tokenid} OR `tok_to`={$tokenid}";
     $q = $this->query($qs);
     $qerr = $this->dbconn->last_error($q);
     if($qerr) {
       $errors[] = "Ein interner Fehler ist aufgetreten (Code: 1222).";
       $errors[] = $qerr . "\n" . $qs;
       return array("success" => false, "errors" => $errors);
     }
     // if necessary, move these shift tags around to prevent them from getting deleted
     while($row = $this->dbconn->fetch_assoc($q)) {
       // if both refer to the current token, do nothing
       if($row['tok_from'] != $row['tok_to']) {
	 if($row['tok_from'] == $tokenid) {
	   $stinsert[] = "(" . $row['id'] . ", {$nexttokenid}, " . $row['tok_to'] . ")";
	 }
	 else {
	   $stinsert[] = "(" . $row['id'] . ", " . $row['tok_from'] . ", {$prevtokenid})";
	 }
       }
     }

     // perform modifications
     $this->dbconn->startTransaction();

     if(!empty($stinsert)) {
       $qs  = "INSERT INTO {$this->db}.shifttags (`id`, `tok_from`, `tok_to`) VALUES ";
       $qs .= implode(", ", $stinsert);
       $qs .= " ON DUPLICATE KEY UPDATE `tok_from`=VALUES(tok_from), `tok_to`=VALUES(tok_to)";
       $q = $this->query($qs);
       $qerr = $this->dbconn->last_error($q);
       if($qerr) {
	 $errors[] = "Ein interner Fehler ist aufgetreten (Code: 1223).";
	 $errors[] = $qerr . "\n" . $qs;
	 $this->dbconn->rollback();
	 return array("success" => false, "errors" => $errors);
       }
     }
     // move any (non-internal) comments attached to this token
     if($prevtokenid!==null || $nexttokenid!==null) {
       $commtokenid = ($prevtokenid===null ? $nexttokenid : $prevtokenid);
       $qs  = "UPDATE {$this->db}.comment SET `tok_id`={$commtokenid} ";
       $qs .= "WHERE  `tok_id`={$tokenid} AND `comment_type`!='C' ";
       $q = $this->query($qs);
       $qerr = $this->dbconn->last_error($q);
       if($qerr) {
	 $errors[] = "Ein interner Fehler ist aufgetreten (Code: 1224).";
	 $errors[] = $qerr . "\n" . $qs;
	 $this->dbconn->rollback();
	 return array("success" => false, "errors" => $errors);
       }
     }
     // only now, delete the token
     $qs = "DELETE FROM {$this->db}.token WHERE `id`={$tokenid}";
     $q = $this->query($qs);
     $qerr = $this->dbconn->last_error($q);
     if($qerr) {
       $errors[] = "Ein interner Fehler ist aufgetreten (Code: 1225).";
       $errors[] = $qerr . "\n" . $qs;
       $this->dbconn->rollback();
       return array("success" => false, "errors" => $errors);
     }
     
     $this->dbconn->commitTransaction();
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
     $qs = "SELECT `ordnr` FROM {$this->db}.token WHERE `id`={$oldtokenid}";
     $q = $this->query($qs);
     $row = $this->dbconn->fetch_assoc($q);
     $ordnr = $row['ordnr'];
     // fetch line for first dipl
     $qs  = "SELECT `line_id` FROM {$this->db}.dipl WHERE `tok_id`={$oldtokenid} ";
     $qs .= "ORDER BY `id` ASC LIMIT 1";
     $q = $this->query($qs);
     $row = $this->dbconn->fetch_assoc($q);
     $lineid = $row['line_id'];

     $this->dbconn->startTransaction();
     // add token
     $qs  = "INSERT INTO {$this->db}.token (`text_id`, `trans`, `ordnr`) VALUES ";
     $qs .= "({$textid}, '" . $this->dbconn->escapeSQL($toktrans) . "', '{$ordnr}')";
     $q = $this->query($qs);
     $qerr = $this->dbconn->last_error($q);
     if($qerr) {
       $errors[] = "Ein interner Fehler ist aufgetreten (Code: 1230).";
       $errors[] = $qerr . "\n" . $qs;
       $this->dbconn->rollback();
       return array("success" => false, "errors" => $errors);
     }
     $tokenid = $this->dbconn->last_insert_id();
     
     // re-order tokens
     $qs  = "UPDATE {$this->db}.token SET `ordnr`=`ordnr`+1 ";
     $qs .= "WHERE `text_id`={$textid} AND (`id`={$oldtokenid} OR `ordnr`>{$ordnr})";
     $q = $this->query($qs);
     $qerr = $this->dbconn->last_error($q);
     if($qerr) {
       $errors[] = "Ein interner Fehler ist aufgetreten (Code: 1231).";
       $errors[] = $qerr . "\n" . $qs;
       $this->dbconn->rollback();
       return array("success" => false, "errors" => $errors);
     }

     // insert dipl
     $diplinsert = array();
     $diplcount = count($converted['dipl_trans']);
     for($i = 0; $i < $diplcount; $i++) { // loop by index because two arrays are involved
       $diplinsert[] = "({$tokenid}, {$lineid}, '" 
	 . $this->dbconn->escapeSQL($converted['dipl_utf'][$i]) . "', '"
	 . $this->dbconn->escapeSQL($converted['dipl_trans'][$i]) . "')";
     }
     $qs  = "INSERT INTO {$this->db}.dipl (`tok_id`, `line_id`, `utf`, `trans`) VALUES ";
     $qs .= implode(", ", $diplinsert);
     $q = $this->query($qs);
     $qerr = $this->dbconn->last_error($q);
     if($qerr) {
       $errors[] = "Ein interner Fehler ist aufgetreten (Code: 1232).";
       $errors[] = $qerr . "\n" . $qs;
       $this->dbconn->rollback();
       return array("success" => false, "errors" => $errors);
     }

     // insert mod
     $modinsert = array();
     $modcount  = count($converted['mod_trans']);
     for($j = 0; $j < $modcount; $j++) {
       $modinsert[] = "({$tokenid}, '" . $this->dbconn->escapeSQL($converted['mod_trans'][$j]) . "', '"
	 . $this->dbconn->escapeSQL($converted['mod_ascii'][$j]) . "', '"
	 . $this->dbconn->escapeSQL($converted['mod_utf'][$j]) . "')";
     }
     $qs  = "INSERT INTO {$this->db}.modern (`tok_id`, `trans`, `ascii`, `utf`) VALUES ";
     $qs .= implode(", ", $modinsert);
     $q = $this->query($qs);
     $qerr = $this->dbconn->last_error($q);
     if($qerr) {
       $errors[] = "Ein interner Fehler ist aufgetreten (Code: 1233).";
       $errors[] = $qerr . "\n" . $qs;
       $this->dbconn->rollback();
       return array("success" => false, "errors" => $errors);
     }

     // done!
     $this->dbconn->commitTransaction();
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
     $qs = "SELECT * FROM {$this->db}.dipl WHERE `tok_id`='{$tokenid}' ORDER BY `id` ASC";
     $q = $this->query($qs);
     $lastline = "";
     while($row = $this->dbconn->fetch_assoc($q)) {
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
       $qs  = "SELECT d.line_id FROM {$this->db}.dipl d ";
       $qs .= "  WHERE d.tok_id IN (SELECT t.id AS tok_id FROM {$this->db}.token t ";
       $qs .= "                      WHERE t.text_id='{$textid}' ";
       $qs .= "                        AND t.ordnr > (SELECT u.ordnr FROM {$this->db}.token u ";
       $qs .= "                                        WHERE u.id='{$tokenid}') ";
       $qs .= "                      ORDER BY t.ordnr ASC) ";
       $qs .= " ORDER BY d.id ASC LIMIT 1";
       $q = $this->query($qs);
       $qerr = $this->dbconn->last_error($q);
       if($qerr) {
	 $errors[] = "Ein interner Fehler ist aufgetreten (Code: 1210).";
	 $errors[] = $qerr . "\n" . $qs;
	 return array("success" => false, "errors" => $errors);
       }
       $row = $this->dbconn->fetch_assoc($q);
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
       $diplinsert[] = "({$diplid}, {$tokenid}, " . $this->dbconn->escapeSQL($lineids[$currentline]) . ", '" 
	 . $this->dbconn->escapeSQL($converted['dipl_utf'][$i]) . "', '"
	 . $this->dbconn->escapeSQL($dipltrans) . "')";
       if(substr($dipltrans, -1)==='=' || substr($dipltrans, -3)==='(=)') {
	 $currentline++;
       }
     }
     // are there dipls that need to be deleted?
     while(isset($olddipl[$i])) {
       $dipldelete[] = $olddipl[$i]['id'];
       $i++;
     }

     // get current mods
     $qs = "SELECT * FROM {$this->db}.modern WHERE `tok_id`='{$tokenid}' ORDER BY `id` ASC";
     $q = $this->query($qs);
     while($row = $this->dbconn->fetch_assoc($q)) {
       $oldmod[] = $row;
     }
     
     // prepare mod queries
     $modinsert = array();
     $moddelete = array();
     $modcount  = count($converted['mod_trans']);
     for($j = 0; $j < $modcount; $j++) {
       $modid = (isset($oldmod[$j]) ? $oldmod[$j]['id'] : "NULL");
       $modinsert[] = "({$modid}, {$tokenid}, '" . $this->dbconn->escapeSQL($converted['mod_trans'][$j]) . "', '"
	 . $this->dbconn->escapeSQL($converted['mod_ascii'][$j]) . "', '"
	 . $this->dbconn->escapeSQL($converted['mod_utf'][$j]) . "')";
     }
     // are there mods that need to be deleted?
     while(isset($oldmod[$j])) {
       $moddelete[] = $oldmod[$j]['id'];
       $j++;
     }

     // perform actual queries
     $this->dbconn->startTransaction();
     // dipl
     if(!empty($diplinsert)) { // e.g., standalone edition numberings have no dipl
       $qs  = "INSERT INTO {$this->db}.dipl (`id`, `tok_id`, `line_id`, `utf`, `trans`) VALUES ";
       $qs .= implode(", ", $diplinsert);
       $qs .= " ON DUPLICATE KEY UPDATE `line_id`=VALUES(line_id), `utf`=VALUES(utf), `trans`=VALUES(trans)";
       $q = $this->query($qs);
       $qerr = $this->dbconn->last_error($q);
       if($qerr) {
	 $errors[] = "Ein interner Fehler ist aufgetreten (Code: 1211).";
	 $errors[] = $qerr . "\n" . $qs;
	 $this->dbconn->rollback();
	 return array("success" => false, "errors" => $errors);
       }
     }
     if(!empty($dipldelete)) {
       $qs = "DELETE FROM {$this->db}.dipl WHERE `id` IN (" . implode(", ", $dipldelete) . ")";
       $q = $this->query($qs);
       $qerr = $this->dbconn->last_error($q);
       if($qerr) {
	 $errors[] = "Ein interner Fehler ist aufgetreten (Code: 1212).";
	 $errors[] = $qerr . "\n" . $qs;
	 $this->dbconn->rollback();
	 return array("success" => false, "errors" => $errors);
       }
     }
     // modern
     if(!empty($modinsert)) { // this can happen for struck words, e.g. *[vnd*]
       $qs  = "INSERT INTO {$this->db}.modern (`id`, `tok_id`, `trans`, `ascii`, `utf`) VALUES ";
       $qs .= implode(", ", $modinsert);
       $qs .= " ON DUPLICATE KEY UPDATE `trans`=VALUES(trans), `ascii`=VALUES(ascii), `utf`=VALUES(utf)";
       $q = $this->query($qs);
       $qerr = $this->dbconn->last_error($q);
       if($qerr) {
	 $errors[] = "Ein interner Fehler ist aufgetreten (Code: 1213).";
	 $errors[] = $qerr . "\n" . $qs;
	 $this->dbconn->rollback();
	 return array("success" => false, "errors" => $errors);
       }
     }
     if(!empty($moddelete)) {
       $qs = "DELETE FROM {$this->db}.modern WHERE `id` IN (" . implode(", ", $moddelete) . ")";
       $q = $this->query($qs);
       $qerr = $this->dbconn->last_error($q);
       if($qerr) {
	 $errors[] = "Ein interner Fehler ist aufgetreten (Code: 1214).";
	 $errors[] = $qerr . "\n" . $qs;
	 $this->dbconn->rollback();
	 return array("success" => false, "errors" => $errors);
       }
       // delete CorA comments attached to this modern token
       $qs  = "DELETE FROM {$this->db}.comment WHERE `tok_id`={$tokenid} AND `comment_type`='C' ";
       $qs .= " AND `subtok_id` IN (" . implode(", ", $moddelete) . ")";
       $q = $this->query($qs);
       // if 'currentmod_id' is set to one of the deleted tokens, set it to something feasible
       $qs  = "SELECT currentmod_id FROM {$this->db}.text WHERE `id`={$textid} AND `currentmod_id` IN (";
       $qs .= implode(", ", $moddelete) . ")";
       $q = $this->query($qs);
       if($this->dbconn->row_count($q) > 0) {
	 $cmid = $oldmod[0]['id'];
	 $qs = "UPDATE {$this->db}.text SET `currentmod_id`={$cmid} WHERE `id`={$textid}";
	 $q = $this->query($qs);
       }
     }
     // token
     $qs = "UPDATE {$this->db}.token SET `trans`='"
       . $this->dbconn->escapeSQL($toktrans) . "' WHERE `id`={$tokenid}";
     $q = $this->query($qs);
     $qerr = $this->dbconn->last_error($q);
     if($qerr) {
       $errors[] = "Ein interner Fehler ist aufgetreten (Code: 1215).";
       $errors[] = $qerr . "\n" . $qs;
       $this->dbconn->rollback();
       return array("success" => false, "errors" => $errors);
     }

     $this->dbconn->commitTransaction();
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
      $this->dbconn->startTransaction();
      $qs  = "INSERT INTO {$this->db}.tagset (name, set_type, class) ";
      $qs .= "VALUES ('{$tagsetname}', 'closed', 'POS')";
      if(!$this->query($qs)) {
	return array("success"=>false, "errors"=>array($this->dbconn->last_error()));
      }
      $tagsetid = $this->dbconn->last_insert_id();
      $qhead  = "INSERT INTO {$this->db}.tag (`value`, `needs_revision`, ";
      $qhead .= "`tagset_id`) VALUES";
      $query  = new LongSQLQuery($this->dbconn, $qhead, '');
      foreach($tagarray as $tagname => $tagnc) {
	$qs = "('" . $tagname . "', " . ($tagnc ? '1' : '0') . ", {$tagsetid})";
	$query->append($qs);
      }
      $query->flush();
      $this->dbconn->commitTransaction();
    } catch (SQLQueryException $e) {
      $this->dbconn->rollback();
      return array("success"=>false, "errors"=>array($e->getMessage()));
    }
    
    // done!
    return array("success"=>true, "warnings"=>$warnings);
  }

  /** Find the text ID for a given token ID. */
  public function getTextIdForToken($tokenid) {
    $textid = null;
    $qs = "SELECT `text_id` FROM {$this->db}.token WHERE `id`='{$tokenid}'";
    $q = $this->query($qs);
    if($row = $this->dbconn->fetch_assoc($q)) {
      $textid = $row['text_id'];
    }
    return $textid;
  }

  /** Find the project ID and ascii value for a given modern ID. */
  private function getProjectAndAscii($modid) {
    $qs = "SELECT text.project_id, modern.ascii FROM {$this->db}.text "
      . "    LEFT JOIN {$this->db}.token ON token.text_id=text.id "
      . "    LEFT JOIN {$this->db}.modern ON modern.tok_id=token.id "
      . "  WHERE  modern.id='{$modid}' LIMIT 1";
    $q = $this->query($qs);
    if($row = $this->dbconn->fetch_assoc($q)) {
      return $row;
    }
    return array();
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
    $this->dbconn->startTransaction();

    // Table 'text'
    $qstr  = "INSERT INTO {$this->db}.text ";
    $qstr .= "  (`sigle`, `fullname`, `project_id`, `created`, `creator_id`, ";
    $qstr .= "   `currentmod_id`, `header`, `fullfile`) VALUES ";
    $qstr .= "('" . $this->dbconn->escapeSQL($options['sigle']) . "', ";
    $qstr .= "'" . $this->dbconn->escapeSQL($options['name']) . "', ";
    $qstr .= "'" . $options['project'] . "', CURRENT_TIMESTAMP, ";
    $qstr .= "'" . $_SESSION['user_id'] . "', NULL, ";
    $qstr .= "'" . $this->dbconn->escapeSQL($data->getHeader()) . "', ";
    if(isset($options['trans_file']) && !empty($options['trans_file'])) {
      // $qstr .= "LOAD_FILE('" . $options['trans_file'] . "')";
      $qstr .= "'" . $this->dbconn->escapeSQL($options['trans_file']) . "'";
    } else {
      $qstr .= "NULL";
    }
    $qstr .= ")";
    $q = $this->query($qstr);
    if($qerr = $this->dbconn->last_error()) {
      $this->dbconn->rollback();
      return "Beim Importieren in die Datenbank ist ein Fehler aufgetreten (Code: 1091).\n" . $qerr;
    }
    $fileid = $this->dbconn->last_insert_id();

    // Table 'text2tagset'
    $qstr  = "INSERT INTO {$this->db}.text2tagset ";
    $qstr .= "  (`text_id`, `tagset_id`, `complete`) VALUES ";
    $qarr  = array();
    foreach($options['tagsets'] as $tagsetid) {
      $qarr[] = "('{$fileid}', '{$tagsetid}', 0)";
    }
    $qstr .= implode(",", $qarr);
    $q = $this->query($qstr);
    if($qerr = $this->dbconn->last_error()) {
      $this->dbconn->rollback();
      return "Beim Importieren in die Datenbank ist ein Fehler aufgetreten (Code: 1092).\n" . $qerr;
    }
    
    /* Note: The next blocks all follow a very similar structure. Can
       we refactor this into a loop? */

    // Table 'page'
    $qstr  = "INSERT INTO {$this->db}.page (`name`, `side`, `text_id`, `num`) VALUES ";
    $qarr  = array();
    $pages = $data->getPages();
    foreach($pages as $page) {
      $qarr[] = "('" . $this->dbconn->escapeSQL($page['name']) . "', '" 
	. $this->dbconn->escapeSQL($page['side']) . "', '{$fileid}', '" 
	. $page['num'] . "')";
    }
    $qstr .= implode(",", $qarr);
    $q = $this->query($qstr);
    if($qerr = $this->dbconn->last_error()) {
      $this->dbconn->rollback();
      return "Beim Importieren in die Datenbank ist ein Fehler aufgetreten (Code: 1093).\n" . $qerr;
    }
    $first_id = $this->dbconn->last_insert_id();
    $data->fillPageIDs($first_id);

    // Table 'col'
    $qstr  = "INSERT INTO {$this->db}.col (`name`, `num`, `page_id`) VALUES ";
    $qarr  = array();
    $cols  = $data->getColumns();
    foreach($cols as $col) {
      $qarr[] = "('" . $this->dbconn->escapeSQL($col['name']) . "', '" 
	. $col['num'] . "', '" . $col['parent_db_id'] . "')";
    }
    $qstr .= implode(",", $qarr);
    $q = $this->query($qstr);
    if($qerr = $this->dbconn->last_error()) {
      $this->dbconn->rollback();
      return "Beim Importieren in die Datenbank ist ein Fehler aufgetreten (Code: 1094).\n" . $qerr;
    }
    $first_id = $this->dbconn->last_insert_id();
    $data->fillColumnIDs($first_id);
    
    // Table 'line'
    $qstr  = "INSERT INTO {$this->db}.line (`name`, `num`, `col_id`) VALUES ";
    $qarr  = array();
    $lines = $data->getLines();
    foreach($lines as $line) {
      $qarr[] = "('" . $this->dbconn->escapeSQL($line['name']) . "', '" 
	. $line['num'] . "', '"	. $line['parent_db_id'] . "')";
    }
    $qstr .= implode(",", $qarr);
    $q = $this->query($qstr);
    if($qerr = $this->dbconn->last_error()) {
      $this->dbconn->rollback();
      return "Beim Importieren in die Datenbank ist ein Fehler aufgetreten (Code: 1095).\n" . $qerr;
    }
    $first_id = $this->dbconn->last_insert_id();
    $data->fillLineIDs($first_id);
    
    // Table 'token'
    $qstr  = "INSERT INTO {$this->db}.token (`trans`, `ordnr`, `text_id`) VALUES ";
    $qarr  = array();
    $tokens = $data->getTokens();
    foreach($tokens as $token) {
      $qarr[] = "('" . $this->dbconn->escapeSQL($token['trans']) . "', '" 
	. $token['ordnr'] . "', '{$fileid}')";
    }
    $qstr .= implode(",", $qarr);
    $q = $this->query($qstr);
    if($qerr = $this->dbconn->last_error()) {
      $this->dbconn->rollback();
      return "Beim Importieren in die Datenbank ist ein Fehler aufgetreten (Code: 1096).\n" . $qerr;
    }
    $first_id = $this->dbconn->last_insert_id();
    $data->fillTokenIDs($first_id);

    // Table 'dipl'
    $qstr  = "INSERT INTO {$this->db}.dipl (`trans`, `utf`, `tok_id`, `line_id`) VALUES ";
    $qarr  = array();
    $dipls = $data->getDipls();
    foreach($dipls as $dipl) {
      $qarr[] = "('" . $this->dbconn->escapeSQL($dipl['trans']) . "', '" 
	. $this->dbconn->escapeSQL($dipl['utf']) . "', '"
	. $dipl['parent_tok_db_id'] . "', '" . $dipl['parent_line_db_id'] . "')";
    }
    $qstr .= implode(",", $qarr);
    $q = $this->query($qstr);
    if($qerr = $this->dbconn->last_error()) {
      $this->dbconn->rollback();
      return "Beim Importieren in die Datenbank ist ein Fehler aufgetreten (Code: 1097).\n" . $qerr;
    }
    $first_id = $this->dbconn->last_insert_id();
    $data->fillDiplIDs($first_id);

    // Table 'modern'
    $qstr  = "INSERT INTO {$this->db}.modern (`trans`, `utf`, `ascii`, `tok_id`) VALUES ";
    $qarr  = array();
    $moderns = $data->getModerns();
    foreach($moderns as $mod) {
      $qarr[] = "('" . $this->dbconn->escapeSQL($mod['trans']) . "', '" 
	. $this->dbconn->escapeSQL($mod['utf']) . "', '"
	. $this->dbconn->escapeSQL($mod['ascii']) . "', '" . $mod['parent_db_id'] . "')";
    }
    $qstr .= implode(",", $qarr);
    $q = $this->query($qstr);
    if($qerr = $this->dbconn->last_error()) {
      $this->dbconn->rollback();
      return "Beim Importieren in die Datenbank ist ein Fehler aufgetreten (Code: 1098).\n" . $qerr;
    }
    $first_id = $this->dbconn->last_insert_id();
    $data->fillModernIDs($first_id);

    // Table 'tag_suggestion'
    $qstr  = "INSERT INTO {$this->db}.tag_suggestion ";
    $qstr .= "  (`score`, `selected`, `source`, `tag_id`, `mod_id`) VALUES ";
    $qarr  = array();
    $moderns = $data->getModerns();
    $tistr  = "INSERT INTO {$this->db}.tag ";
    $tistr .= "  (`value`, `needs_revision`, `tagset_id`) VALUES ";
    foreach($moderns as $mod) {
      foreach($mod['tags'] as $sugg) {
	// for POS tags, just refer to the respective tag ID
	if($sugg['type']==='pos') {
	  if(!array_key_exists($sugg['tag'], $tagset_pos)) {
	    $this->dbconn->rollback();
	    return "Beim Importieren in die Datenbank ist ein Fehler aufgetreten (Code: 1099). Der folgende POS-Tag ist ungültig: " . $sugg['tag'];
	  }
	  $tag_id = $tagset_pos[$sugg['tag']];
	}
	// for modernisation types, too
	else if($sugg['type']==='norm_type') {
	  if(!array_key_exists($sugg['tag'], $tagset_norm_type)) {
	    $this->dbconn->rollback();
	    return "Beim Importieren in die Datenbank ist ein Fehler aufgetreten (Code: 1099). Der folgende Modernisierungstyp ist ungültig: " . $sugg['tag'];
	  }
	  $tag_id = $tagset_norm_type[$sugg['tag']];
	}
	// for all other tags, create a new tag entry first
	else {
	  $tqstr = $tistr . "('" . $this->dbconn->escapeSQL($sugg['tag']) . "', 0, '"
	    . $tagset_ids[$sugg['type']] . "')";
	  $tq = $this->query($tqstr);
	  if($qerr = $this->dbconn->last_error()) {
	    $this->dbconn->rollback();
	    return "Beim Importieren in die Datenbank ist ein Fehler aufgetreten (Code: 1100, Tagset-Typ: " . $sugg['type'] . ").\n" . $qerr;
	  }
	  $tag_id = $this->dbconn->last_insert_id();
	}
	// then append the proper values
	$qarr[] = "('" . $sugg['score'] . "', '" . $sugg['selected'] . "', '"
	  . $sugg['source'] . "', '{$tag_id}', '" . $mod['db_id'] . "')";
      }
    }
    if(!empty($qarr)) {
      $qstr .= implode(",", $qarr);
      $q = $this->query($qstr);
      if($qerr = $this->dbconn->last_error()) {
	$this->dbconn->rollback();
	return "Beim Importieren in die Datenbank ist ein Fehler aufgetreten (Code: 1101).\n" . $qerr;
      }
    }

    // Table 'shifttags'
    $qstr  = "INSERT INTO {$this->db}.shifttags ";
    $qstr .= "  (`tok_from`, `tok_to`, `tag_type`) VALUES ";
    $qarr  = array();
    $shifttags = $data->getShifttags();
    if(!empty($shifttags)) {
      foreach($shifttags as $shtag) {
	$qarr[] = "('" . $shtag['db_range'][0] . "', '" . $shtag['db_range'][1] . "', '"
	  . $shtag['type_letter'] . "')";
      }
      $qstr .= implode(",", $qarr);
      $q = $this->query($qstr);
      if($qerr = $this->dbconn->last_error()) {
	$this->dbconn->rollback();
	return "Beim Importieren in die Datenbank ist ein Fehler aufgetreten (Code: 1102).\n" . $qerr;
      }
    }

    // Table 'comment'
    $qstr  = "INSERT INTO {$this->db}.comment ";
    $qstr .= "  (`tok_id`, `value`, `comment_type`) VALUES ";
    $qarr  = array();
    $comments = $data->getComments();
    if(!empty($comments)) {
      foreach($comments as $comment) {
	$qarr[] = "('" . $comment['parent_db_id'] . "', '" 
	  . $this->dbconn->escapeSQL($comment['text']) . "', '"
	  . $this->dbconn->escapeSQL($comment['type']) . "')";
      }
      $qstr .= implode(",", $qarr);
      $q = $this->query($qstr);
      if($qerr = $this->dbconn->last_error()) {
	$this->dbconn->rollback();
	return "Beim Importieren in die Datenbank ist ein Fehler aufgetreten (Code: 1103).\n" . $qerr . "\n" . $qstr;
      }
    }

    $this->dbconn->commitTransaction();
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
    $qstr .= "FROM   {$this->db}.tag_suggestion ts ";
    $qstr .= "  LEFT JOIN {$this->db}.tag ON tag.id=ts.tag_id ";
    $qstr .= "  LEFT JOIN {$this->db}.tagset ON tagset.id=tag.tagset_id ";
    $qstr .= "WHERE  ts.mod_id='{$linenum}' AND tagset.class='lemma' AND ts.source='auto'";
    $query = $this->query($qstr);
    while($row = $this->dbconn->fetch_assoc($query)) {
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
	   . "     FROM   {$this->db}.tag "
	   . "       LEFT JOIN {$this->db}.tagset ON tag.tagset_id=tagset.id "
	   . "       LEFT JOIN {$this->db}.tag_suggestion ts ON ts.tag_id=tag.id "
	   . "       LEFT JOIN {$this->db}.modern ON modern.id=ts.mod_id "
	   . "       LEFT JOIN {$this->db}.token ON modern.tok_id=token.id "
	   . "       LEFT JOIN {$this->db}.text ON token.text_id=text.id "
	   . "       LEFT JOIN {$this->db}.mod2error ON mod2error.mod_id=modern.id "
	   . "     WHERE  mod2error.error_id='{$lemma_verified}' "
	   . "        AND UPPER(modern.ascii)=UPPER('{$line_ascii}') "
	   . "        AND text.project_id='{$line_project}' "
	   . "        AND tagset.class='lemma' "
	   . "        AND ts.selected=1 ";
	 $query = $this->query($qstr);
	 $processed_lemmas = array();
	 while($row = $this->dbconn->fetch_assoc($query)) {
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
	if($tagset['class']=="lemma" && $tagset['set_type']=="closed") {
	  $tsid = $tagset['id'];
	}
      }
      if($tsid && $tsid != 0) {
	$qs = "SELECT `id`, `value` FROM {$this->db}.tag "
	  . "  WHERE `tagset_id`='{$tsid}' AND `value` LIKE '{$q}%' "
	  . "  ORDER BY `value` LIMIT {$limit}";
	$query = $this->query($qs);
	while ( $row = $this->dbconn->fetch_assoc( $query ) ) {
	  $suggestions[] = array("id" => $row['id'], "v" => $row['value'], "t" => "q");
	}
      }
    }
    
    return $suggestions;
  }


}


?>