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
    if (!$this->dbobj) {return false;} else {return true;}
  }

  /** Set the default database. The name of the default database
   * should be referenced in every SQL query string.
   *
   * @param string $name Name of the database to be set as default.
   */
  public function setDefaultDatabase( $name ) {
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
  protected function escapeSQL( $obj ) {
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
    $this->db = $this->dbconn->getDefaultDatabase();
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
    return @mysql_fetch_assoc( $query );
  }

  /** Get user info by id.
   */
  public function getUserById($uid) {
    $qs = "SELECT `id`, name, admin, lastactive FROM {$this->db}.users "
      . "WHERE `id`={$uid}";
    $query = $this->query( $qs );
    return @mysql_fetch_assoc( $query );
  }
  
  /** Get user info by name.
   */
  public function getUserByName($uname) {
    $qs = "SELECT `id`, name, admin, lastactive FROM {$this->db}.users "
      . "WHERE name='{$uname}'";
    $query = $this->query( $qs );
    return @mysql_fetch_assoc( $query );
  }

  /** Get user ID by name.  Often used because formerly, users were
   *  always identified by name (and therefore the app still uses
   *  usernames in most places), while now, users must be identified
   *  by ID in all tables.
   */
  public function getUserIDFromName($uname) {
    $qs = "SELECT `id` FROM {$this->db}.users WHERE name='{$uname}'";
    $query = $this->query( $qs );
    $row = @mysql_fetch_assoc( $query );
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
	return @mysql_fetch_assoc( $this->query( $qs ) );
  }

  /** Get a list of all users.
   *
   * @return An @em array containing all usernames in the database and
   * information about their admin status.
   */
  public function getUserList() {
    $qs = "SELECT `id`, name, admin, lastactive FROM {$this->db}.users WHERE `id`!=1";
    $query = $this->query( $qs );
    $users = array();
    while ( @$row = mysql_fetch_assoc($query) ) {
      $users[] = $row;
    }
    return $users;
  }

  /** Get a list of all tagsets.
   *
   * @param string $lang A language code (@c de or @c en)
   *
   * @return A list of associative arrays, containing the names
   * and IDs of the tagset.
   */
  public function getTagsets() {
    $result = array();
    $qs = "SELECT * FROM {$this->db}.tagset WHERE `class`='POS' ORDER BY `name`";
    $query = $this->query($qs);
    while ( @$row = mysql_fetch_assoc( $query, $this->dbobj ) ) {
      $data = array();
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
   *
   * @return An associative @em array containing the tagset information.
   */
  public function getTagset( $tagset ) {
    $tags = array();

    $qs  = "SELECT `id`, `value`, `needs_revision` FROM {$this->db}.tag ";
    $qs .= "WHERE `tagset_id`='{$tagset}' ORDER BY `value`";
    $query = $this->query($qs);
    while ( @$row = mysql_fetch_assoc( $query, $this->dbobj ) ) {
      $tags[] = array('id' => $row['id'],
		      'value' => $row['value'],
		      'needs_revision' => $row['needs_revision']);
    }
    
    return $tags;
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
	$message = mysql_error();
	$this->dbconn->rollback();
      }
    }
    else {
      $message = mysql_error();
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

  /** Toggle visibility of normalization column for a user.
   *
   * @param string $username The username for which the status should
   * be changed
   *
   * @return The result of the corresponding @c mysql_query() command.
   */
  public function toggleNormStatus($username) {
    return $this->toggleUserStatus($username, 'normvisible');
  }

  /** Helper function for @c toggleAdminStatus() and @c toggleNormStatus(). */
  public function toggleUserStatus($username, $statusname) {
    $qs = "SELECT {$statusname} FROM {$this->db}.users WHERE name='{$username}'";
    $query = $this->query($qs);
    @$row = mysql_fetch_array($query, $this->dbobj);
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
    @$row = mysql_fetch_array($query, $this->dbobj);
    return $row;
  }

  /** Insert a new document.
   *
   * @param array $options Metadata for the document
   * @param array $data Document data
   *
   * @return An error message if insertion failed, False otherwise
   */
  public function insertNewDocument(&$options, &$data){
    $this->dbconn->startTransaction();

    // Insert metadata
    $metadata  = "INSERT INTO {$this->db}.files_metadata ";
    $metadata .= "(file_name, sigle, byUser, created, tagset, ext_id, ";
    $metadata .= "POS_tagged, morph_tagged, norm, project_id) VALUES ('";
    $metadata .= mysql_real_escape_string($options['name']) . "', '";
    $metadata .= mysql_real_escape_string($options['sigle']) . "', '";
    $metadata .= $_SESSION['user'] . "', CURRENT_TIMESTAMP, '";
    $metadata .= mysql_real_escape_string($options['tagset']) . "', '";
    $metadata .= mysql_real_escape_string($options['ext_id']) . "', ";
    $metadata .= $options['POS_tagged'] . ", ";
    $metadata .= $options['morph_tagged'] . ", ";
    $metadata .= $options['norm'] . ", ";
    $metadata .= $options['project'] . ")";

    if(!$this->query($metadata)){
      $errstr = "Fehler beim Schreiben in 'files_metadata':\n" .
	mysql_errno() . ": " . mysql_error() . "\n";
      $this->dbconn->rollback();
      return $errstr;
    }

    $file_id = mysql_insert_id(); 
    $this->lockFile($file_id, "@@@system@@@");

    if(!empty($options['progress'])) {
      $this->markLastPosition($file_id,$options['progress'],'@@global@@');
    }

    // Insert data
    $data_head  = "INSERT INTO {$this->db}.files_data (file_id, line_id, ext_id,";
    $data_head .= "token, tag_POS, tag_morph, tag_norm, lemma, comment) VALUES";
    $data_table = new LongSQLQuery($this, $data_head, "");
    $sugg_head  = "INSERT INTO {$this->db}.files_tags_suggestion ";
    $sugg_head .= "(file_id, line_id, tag_suggestion_id, tagtype, ";
    $sugg_head .= "tag_name, tag_probability, lemma) VALUES"; 
    $sugg_table = new LongSQLQuery($this, $sugg_head, "");
    $err_head   = "INSERT INTO {$this->db}.files_errors ";
    $err_head  .= "(file_id, line_id, user, value) VALUES";
    $err_table  = new LongSQLQuery($this, $err_head, "");

    try {
      foreach($data as $index=>$token){
	$token = $this->dbconn->escapeSQL($token);
	$qs = "('{$file_id}', {$index}, '".
	  mysql_real_escape_string($token['id'])."', '".
	  mysql_real_escape_string($token['form'])."', '".
	  mysql_real_escape_string($token['pos'])."', '".
	  mysql_real_escape_string($token['morph'])."', '".
	  mysql_real_escape_string($token['norm'])."', '".
	  mysql_real_escape_string($token['lemma'])."', '".
	  mysql_real_escape_string($token['comment'])."')";
	$status = $data_table->append($qs);
	if($status){ $this->dbconn->rollback(); return $status; }
	foreach($token['suggestions'] as $sugg){
	  $qs = "('{$file_id}', {$index}, ".$sugg['index'].
	    ", '".$sugg['type']."', '".
	    mysql_real_escape_string($sugg['value']).
	    "', ".$sugg['score'].", '".
	    mysql_real_escape_string($token['lemma'])."')";
	  $status = $sugg_table->append($qs);
	  if($status){ $this->dbconn->rollback(); return $status; }
	}
	if(!empty($token['error'])){
	  $qs = "('{$file_id}', {$index}, '@@global@@', '".
	    $token['error']."')";
	  $status = $err_table->append($qs);
	  if($status){ $this->dbconn->rollback(); return $status; }
	}
      }
      $status = $data_table->flush();
      if($status){ $this->dbconn->rollback(); return $status; }
      $status = $sugg_table->flush();
      if($status){ $this->dbconn->rollback(); return $status; }
      $status = $err_table->flush();
      if($status){ $this->dbconn->rollback(); return $status; }

      $this->unlockFile($file_id, "@@@system@@@");
      $this->dbconn->commitTransaction();
    } catch (SQLQueryException $e) {
      $this->dbconn->rollback();
      return "Fehler beim Importieren der Daten:\n" . $e . "\n";
    }
    
    return False;
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
    $locksCount = mysql_affected_rows();
    // then, check if file is still/already locked
    $qs = "SELECT * FROM {$this->db}.locks WHERE text_id={$fileid}";
    $result = $this->query($qs);
    if(mysql_num_rows($result)>0) {
      // if file is locked, return info about user currently locking the file
      $qs  = "SELECT a.lockdate as 'locked_since', b.name as 'locked_by' ";
      $qs .= "FROM {$this->db}.locks a, {$this->db}.users b ";
      $qs .= "WHERE a.text_id={$fileid} AND a.user_id=b.id";
      $query = $this->query($qs);
      @$row = mysql_fetch_row( $query, $this->dbobj );
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
    $qs .= "WHERE a.user_id='{$userid}' AND a.text_id=b.text_id LIMIT 1";
    return @mysql_fetch_assoc($this->query($qs));
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
    $qs  = "SELECT ts.id, ts.class ";
    $qs .= "FROM   {$this->db}.text2tagset ttt ";
    $qs .= "  LEFT JOIN {$this->db}.tagset ts  ON ts.id=ttt.tagset_id ";
    $qs .= "WHERE  ttt.text_id='{$fileid}'";
    $q = $this->query($qs);
    $qerr = mysql_error();
    if($qerr) { return $qerr; }

    $tslist = array();
    while($row = @mysql_fetch_assoc($q)) {
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
    
    $qs  = "SELECT text.*, tagset.id AS 'tagset_id' ";
    $qs .= "FROM   ({$this->db}.text, {$this->db}.tagset) ";
    $qs .= "  LEFT JOIN {$this->db}.text2tagset ttt ";
    $qs .= "         ON (ttt.tagset_id=tagset.id AND ttt.text_id=text.id) ";
    $qs .= "WHERE  text.id='{$fileid}' AND tagset.class='POS'";
    if($query = $this->query($qs)){
      $metadata = @mysql_fetch_assoc($query);
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
	  $row = @mysql_fetch_assoc($q,$this->dbobj);
	  $lock['lastEditedRow'] = intval($row['position']) - 1;
	}
      }
      $lock['data'] = $metadata;
      $lock['success'] = true;
    } else {
      $lock['success'] = false;
    }
    
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
    if(@mysql_fetch_row($q,$this->dbobj)) {
      return true;
    } else {
      return false;
    }
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
    $this->dbconn->startTransaction();
    $this->lockFile($fileid, "system");
    
    $qs = "	DELETE FROM {$this->db}.files_metadata WHERE file_id='{$fileid}'";
    if(!$this->query($qs)) { $this->dbconn->rollback(); return "Query for metadata failed."; }
    
    $qs = "	DELETE FROM {$this->db}.files_tags_suggestion WHERE file_id='{$fileid}'";							 
    if(!$this->query($qs)) { $this->dbconn->rollback(); return "Query for tags_suggestion failed."; }
    
    $qs = "	DELETE FROM {$this->db}.files_data WHERE file_id='{$fileid}'";
    if(!$this->query($qs)) { $this->dbconn->rollback(); return "Query for data failed."; }
    
    $qs = "DELETE FROM {$this->db}.files_errors WHERE file_id='{$fileid}'";
    if(!$this->query($qs)) { $this->dbconn->rollback(); return "Query for errors failed."; }
    
    $qs = "DELETE FROM {$this->db}.files_progress WHERE file_id='{$fileid}'";
    if(!$this->query($qs)) { $this->dbconn->rollback(); return "Query for progress failed."; }
    
    $qs = "DELETE FROM {$this->db}.files_locked WHERE file_id='{$fileid}'";
    $this->query($qs);
    
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
    while ( @$row = mysql_fetch_array( $query, $this->dbobj ) ) {
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
    while ( @$row = mysql_fetch_array( $query, $this->dbobj ) ) {
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
    while ( @$row = mysql_fetch_array( $query, $this->dbobj ) ) {
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
    while ( @$row = mysql_fetch_array( $query, $this->dbobj ) ) {
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
    $qs = "SELECT a.* FROM ({$this->db}.project a, {$this->db}.user2project b) WHERE (a.id=b.project_id AND b.user_id='{$uid}') ORDER BY project_id";
    $query = $this->query($qs); 
    $projects = array();
    while ( @$row = mysql_fetch_array( $query, $this->dbobj ) ) {
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
    return mysql_insert_id();
  }
  
  /** Deletes a project.  Will fail unless no document is
   * assigned to the project.
   *
   * @param string $pid the project id
   * @return a boolean value indicating success
   */
  public function deleteProject($pid){
    $qs = "DELETE FROM {$this->db}.project WHERE `id`={$pid}";
    if($this->query($qs)) {
      return True;
    }
    return False;
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
    $qs .= "WHERE token.text_id='{$fileid}'";
    if($query = $this->query($qs)){
      $row = mysql_fetch_row($query);
      return $row[0];
    }		
    return 0;
  }
  
  /** Retrieve all lines from a file, including error data,
   *  but not tagger suggestions.
   */
  public function getAllLines($fileid){
    $qs = "SELECT a.*, b.value AS 'errorChk' FROM {$this->db}.files_data a LEFT JOIN {$this->db}.files_errors b ON (a.file_id=b.file_id AND a.line_id=b.line_id) WHERE a.file_id='{$fileid}' ORDER BY line_id";
    $query = $this->query($qs);
    $data = array();
    while($row = @mysql_fetch_assoc($query,$this->dbobj)){
      $data[] = $row;
    }
    return $data;
  }
  
  /** Retrieve all tagger suggestions for a line in a file. */
  public function getAllSuggestions($fileid, $lineid){
    $qs = "SELECT * FROM {$this->db}.files_tags_suggestion WHERE file_id='{$fileid}' AND line_id='{$lineid}' ORDER BY tagtype DESC";
    $q = $this->query($qs);
    
    $tag_suggestions = array();
    while($row = @mysql_fetch_assoc($q)){
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

    $qs  = "SELECT q.*, @rownum := @rownum + 1 AS num FROM ";
    $qs .= "  (SELECT modern.id, modern.trans, modern.utf, ";
    $qs .= "          modern.tok_id, token.trans AS full_trans ";
    $qs .= "   FROM   {$this->db}.token ";
    $qs .= "     LEFT JOIN {$this->db}.modern ON modern.tok_id=token.id ";
    // Layout-Info mit JOINen?
    $qs .= "   WHERE  token.text_id='{$fileid}' ";
    $qs .= "   ORDER BY token.ordnr ASC, modern.id ASC) q ";
    $qs .= "JOIN (SELECT @rownum := -1) r ";
    $qs .= "LIMIT {$start},{$lim}";
    $query = $this->query($qs); 		

    /* The following loop separately performs all queries on a
       line-by-line basis that can potentially return more than one
       result row.  Integrating this in the SELECT above might yield
       better performance, but will be more complicated to process (as
       the 1:1 relation between rows and modern tokens is no longer
       guaranteed).  Change this only if performance becomes an issue.
     */
    while($line = @mysql_fetch_assoc($query)){
      $mid = $line['id'];

      // Error annotations
      $qs  = "SELECT error_types.name ";
      $qs .= "FROM   {$this->db}.modern ";
      $qs .= "  LEFT JOIN {$this->db}.mod2error ON modern.id=mod2error.mod_id ";
      $qs .= "  LEFT JOIN {$this->db}.error_types ON mod2error.error_id=error_types.id ";
      $qs .= "WHERE  modern.id='{$mid}'";
      $q = $this->query($qs);
      while($row = @mysql_fetch_assoc($q)) {
	if($row['name']=='general error') {
	  $line['general_error'] = 1;
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
      while($row = @mysql_fetch_assoc($q)){
	if($row['class']=='norm' && $row['selected']=='1') {
	  $line['anno_norm'] = $row['value'];
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
    while($row = @mysql_fetch_assoc($q)) {
      $errortypes[$row['name']] = $row['id'];
    }
    return $errortypes;
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
   *
   * @return @bool the result of the mysql query
   */ 		
  public function saveLines($fileid,$lasteditedrow,$lines) {
    $locked = $this->lockFile($fileid, $_SESSION["user"]);
    if(!$locked['success']) {
      return "lock failed";
    }

    $idlist = array();
    $pos_tags = array();    // maps tags to tag IDs
    $tagset_ids = array();  // maps tagset classes to tagset IDs

    /* Check if all IDs belong to the currently opened document
       (--this is done because IDs are managed on the client side and
       therefore could potentially be manipulated)
    */
    foreach($lines as $line) {
      $idlist[] = $line['id'];
    }
    $modchk  = "SELECT modern.id FROM {$this->db}.modern ";
    $modchk .= "LEFT JOIN {$this->db}.token ON modern.tok_id=token.id ";
    $modchk .= "LEFT JOIN {$this->db}.text  ON token.text_id=text.id ";
    $modchk .= "WHERE text.id='{$fileid}' AND modern.id IN (";
    $modchk .= implode(',', $idlist);
    $modchk .= ")";
    $modchq  = $this->query($modchk);
    $modchn  = mysql_num_rows($modchq);
    if($modchn!=count($idlist)) {
      $diff = count($idlist) - $modchn;
      return "Ein interner Fehler ist aufgetreten (Code: 1074).  Die Anfrage enthielt {$diff} ungültige Token-ID(s) für das derzeit geöffnete Dokument.";
    }

    /* Get tagset information for currently opened document */
    $errortypes = $this->getErrorTypes();
    if(array_key_exists('general error', $errortypes)) {
      $error_general = $errortypes['general error'];
    } else {
      $error_general = false;
    }
    $tslist = $this->getTagsetsForFile($fileid);
    if(!is_array($tslist)) {
      return "Ein interner Fehler ist aufgetreten (Code: 1075).  Die Datenbank meldete:\n{$tslist}";
    }
    foreach($tslist as $tagset) {
      $tagset_ids[$tagset['class']] = $tagset['id'];
    }
    $hasnorm = array_key_exists('norm', $tagset_ids);
    $haslemma = array_key_exists('lemma', $tagset_ids);
    $haspos = array_key_exists('POS', $tagset_ids);
    // POS
    if($haspos) {
      $qstr  = "SELECT `id`, `value` FROM {$this->db}.tag ";
      $qstr .= "WHERE `tagset_id`='" . $tagset_ids['POS'] . "'";
      $q = $this->query($qstr);
      $qerr = mysql_error();
      if($qerr) {
	return "Ein interner Fehler ist aufgetreten (Code: 1076).  Die Datenbank meldete:\n{$qerr}";
      }
      while($row = @mysql_fetch_assoc($q)) {
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

    foreach($lines as $line) {
      /* Get currently selected annotations */
      $selected = array();
      $qstr  = "SELECT ts.id, ts.tag_id, ts.source, tag.value, tagset.class ";
      $qstr .= "FROM   {$this->db}.tag_suggestion ts ";
      $qstr .= "  LEFT JOIN {$this->db}.tag ON tag.id=ts.tag_id ";
      $qstr .= "  LEFT JOIN {$this->db}.tagset ON tagset.id=tag.tagset_id ";
      $qstr .= "WHERE  ts.selected=1 AND ts.mod_id='" . $line['id'] . "'";
      $q = $this->query($qstr);
      $qerr = mysql_error();
      if($qerr) {
	$this->dbconn->rollback();
	return "Ein interner Fehler ist aufgetreten (Code: 1077).  Die Datenbank meldete:\n{$qerr}";
      }
      while($row = @mysql_fetch_assoc($q)) {
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
	if(array_key_exists('anno_morph', $line) && !empty($line['anno_morph'])) {
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

    }

    /* Only now, perform all INSERTs/DELETEs/UPDATEs */

    $this->dbconn->startTransaction();

    try {
      if(!empty($updatetag)) {
	$qstr  = "INSERT INTO {$this->db}.tag (`id`, `value`) VALUES ";
	$qstr .= implode(",", $updatetag);
	$qstr .= " ON DUPLICATE KEY UPDATE `value`=VALUES(value)";
	$q = $this->query($qstr);
	$qerr = mysql_error();
	if($qerr) { throw new SQLQueryException($qerr."\n".$qstr); }
      }
      if(!empty($inserttag)) {
	foreach($inserttag as $insertdata) {
	  $qstr = "INSERT INTO {$this->db}.tag (`value`, `needs_revision`, `tagset_id`)";
	  $qstr .= "VALUES " . $insertdata['query'];
	  $q = $this->query($qstr);
	  $qerr = mysql_error();
	  if($qerr) { throw new SQLQueryException($qerr."\n".$qstr); }
	  $q = $this->query("SELECT LAST_INSERT_ID()");
	  $row = mysql_fetch_row($q);
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
	$qerr = mysql_error();
	if($qerr) { throw new SQLQueryException($qerr."\n".$qstr); }
	$qstr  = "DELETE FROM {$this->db}.tag WHERE `id` IN ('";
	$qstr .= implode("','", $deletetag);
	$qstr .= "')";
	$q = $this->query($qstr);
	$qerr = mysql_error();
	if($qerr) { throw new SQLQueryException($qerr."\n".$qstr); }
      }
      if(!empty($insertts)) {
	$qstr  = "INSERT INTO {$this->db}.tag_suggestion ";
	$qstr .= " (`id`, `selected`, `source`, `tag_id`, `mod_id`) VALUES ";
	$qstr .= implode(",", $insertts);
	$qstr .= " ON DUPLICATE KEY UPDATE `selected`=VALUES(selected), ";
	$qstr .= "                        `tag_id`=VALUES(tag_id)";
	$q = $this->query($qstr);
	$qerr = mysql_error();
	if($qerr) { throw new SQLQueryException($qerr."\n".$qstr); }
      }
      if(!empty($deletets)) {
	$qstr  = "DELETE FROM {$this->db}.tag_suggestion WHERE `id` IN ('";
	$qstr .= implode("','", $deletets);
	$qstr .= "')";
	$q = $this->query($qstr);
	$qerr = mysql_error();
	if($qerr) { throw new SQLQueryException($qerr."\n".$qstr); }
      }
      if(!empty($deleteerr)) {
	$qstr  = "DELETE FROM {$this->db}.mod2error WHERE ";
	$qstr .= implode(" OR ", $deleteerr);
	$q = $this->query($qstr);
	$qerr = mysql_error();
	if($qerr) { throw new SQLQueryException($qerr."\n".$qstr); }
      }
      if(!empty($inserterr)) {
	$qstr  = "INSERT IGNORE INTO {$this->db}.mod2error ";
	$qstr .= "  (`mod_id`, `error_id`) VALUES ";
	$qstr .= implode(", ", $inserterr);
	$q = $this->query($qstr);
	$qerr = mysql_error();
	if($qerr) { throw new SQLQueryException($qerr."\n".$qstr); }
      }
    }
    catch(SQLQueryException $e) {
      $this->dbconn->rollback();
      return "Ein interner Fehler ist aufgetreten (Code: 1080).  Die Datenbank meldete:\n" . $e->getMessage();
    }

    // ERROR MARKERS & MARK LAST POSITION still missing!


    $this->dbconn->commitTransaction();
    
    // finally, one last query...
    $result = $this->markLastPosition($fileid,$lasteditedrow);
    
    return False;
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
    return $this->query( $qs );
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
	return array("success"=>false, "errors"=>array(mysql_error()));
      }
      $tagsetid = mysql_insert_id();
      $qhead  = "INSERT INTO {$this->db}.tag (`value`, `needs_revision`, ";
      $qhead .= "`tagset_id`) VALUES";
      $query  = new LongSQLQuery($this, $qhead, '');
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
  
}


?>

