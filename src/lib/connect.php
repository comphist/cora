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
*/
?>
<?php
/** @file connect.php
 * Provide objects for database access.
 *
 * @author Marcel Bollmann
 * @date January 2012
 */

/* The database settings. */
require_once 'cfg.php';
require_once 'documentModel.php';
require_once 'commandHandler.php';
require_once 'connect/DocumentAccessor.php';
require_once 'connect/DocumentReader.php';
require_once 'connect/DocumentWriter.php';
require_once 'connect/DocumentCreator.php';
require_once 'connect/ProjectAccessor.php';
require_once 'connect/SearchQuery.php';
require_once 'connect/TagsetAccessor.php';
require_once 'connect/TagsetCreator.php';

/** Common interface for all database requests.
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
    private $lh;

    /** Create a new DBInterface.
     *
     * @param array $dbinfo An associative array expected to contain at least
     *                      HOST, USER, PASSWORD, and DBNAME.
     */
    function __construct($dbinfo, $lh) {
        $this->dbo = new PDO('mysql:host=' . $dbinfo['HOST']
                             . ';dbname=' . $dbinfo['DBNAME']
                             . ';charset=utf8',
                             $dbinfo['USER'], $dbinfo['PASSWORD']);
        $this->dbo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->db = $dbinfo['DBNAME'];
        $this->lh = $lh;
    }

    /** Return the hash of a given password string. */
    public static function hashPassword($pw) {
        return password_hash($pw, PASSWORD_DEFAULT, ['cost' => Cfg::get('password_cost') ]);
    }

    /** Verify password.
     *
     * Supports both the new password_verify() and the old legacy md5+sha1 method.
     */
    public function verifyPassword($pw, $hash) {
        return password_verify($pw, $hash) || (md5(sha1($pw)) == $hash);
    }

    /** Look up username and password.
     *
     * @param string $user Username to be looked up.
     * @param string $pw Password corresponding to the username.
     *
     * @return An @em array containing the database entry for the given
     * user. If the username is not valid, or if the password is not
     * correct, the query will fail, and false is returned.
     */
    public function getUserData($user, $pw) {
        $qs = "SELECT `id`, name, password, admin, lastactive FROM users"
            . "  WHERE  name=:name AND `id`!=1 LIMIT 1";
        $stmt = $this->dbo->prepare($qs);
        $stmt->execute(array(':name' => $user));
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($data && $this->verifyPassword($pw, $data['password'])) {
            if (password_needs_rehash($data['password'],
                                      PASSWORD_DEFAULT,
                                      ['cost' => Cfg::get('password_cost')])) {
                $this->changePassword($data['id'], $pw);
            }
            return $data;
        } else {
            return false;
        }
    }

    /** Get user info by id.
     */
    public function getUserById($uid) {
        $qs = "SELECT `id`, name, admin, lastactive FROM users" . "  WHERE  `id`=:id";
        $stmt = $this->dbo->prepare($qs);
        $stmt->bindValue(':id', $uid, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /** Get user info by name.
     */
    public function getUserByName($uname) {
        $qs = "SELECT `id`, name, admin, lastactive FROM users" . "  WHERE name=:name";
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

    /** Return settings for a given user.
     *
     * @param string $user Username
     *
     * @return An array with the database entries from the table 'user_settings' for the given user.
     */

    public function getUserSettings($user) {
        $qs = "SELECT lines_per_page, lines_context, text_preview, "
            . "         columns_order, columns_hidden, show_error, locale "
            . "    FROM users WHERE name=:name";
        $stmt = $this->dbo->prepare($qs);
        $stmt->bindValue(':name', $user, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /** Save settings for a given user.
     *
     * @param string $uid User ID
     * @param array $data Array containing the user settings
     */
    public function saveUserSettings($uid, $data) {
        $qs = "UPDATE `users` SET `email`=:email, `comment`=:comment "
            . " WHERE `id`=:uid";
        $stmt = $this->dbo->prepare($qs);
        $stmt->execute(array(':uid' => $uid,
                             ':email' => $data['email'],
                             ':comment' => $data['comment']));
        return $stmt->rowCount();
    }

    /** Get a list of all users.
     *
     * @return An @em array containing all usernames in the database and
     * information about their admin status.
     */
    public function getUserList($to) {
        $qs = "SELECT users.id, users.name, users.admin, users.lastactive,"
            . "          users.email, users.comment,"
            . "          CASE WHEN users.lastactive "
            . "                    BETWEEN DATE_SUB(NOW(), INTERVAL {$to} MINUTE)"
            . "                        AND NOW()"
            . "               THEN 1 ELSE 0 END AS active, "
            . "          text.id AS opened_text "
            . "     FROM users "
            . "LEFT JOIN locks ON locks.user_id=users.id "
            . "LEFT JOIN text  ON text.id=locks.text_id "
            . "    WHERE users.id!=1"
            . "    ORDER BY name";
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
    public function getTagsets($class = "pos", $orderby = "name") {
        $qs = "SELECT `id`, `id` AS `shortname`, `name` AS `longname`, `set_type`,"
            . "       LOWER(class) AS `class`, `settings`"
            . "    FROM tagset";
        if ($class) {
            $qs.= " WHERE `class`=:class";
        }
        $qs.= " ORDER BY {$orderby}";
        $stmt = $this->dbo->prepare($qs);
        if ($class) {
            $stmt->bindValue(':class', strtolower($class), PDO::PARAM_STR);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Build and return an array containing a full tagset.
     *
     * This function retrieves all valid tags of a given tagset.
     *
     * @param string $tagset The id of the tagset to be retrieved
     *
     * @return An associative @em array containing the tagset information.
     */
    public function getTagset($tagset) {
        $tagset = new TagsetAccessor($this->dbo, $tagset);
        return array_values($tagset->entries());
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
        $tagset = new TagsetAccessor($this->dbo, $tagset);
        return array_map(function ($tag) {
            return $tag['id'];
        }, $tagset->entries());
    }

    /** Perform a search within a document.
     *
     * @param string $id ID of the file to search
     * @param array $query Search criteria, in the form of
     *                     {'operator': 'any'|'all',
     *                      'conditions':
     *                        [{'field': ..., 'match': ..., 'value': ...}, ...]
     *                     }
     */
    public function searchDocument($id, $query) {
        $sq = new SearchQuery($this, $id);
        $sq->setOperator($query['operator']);
        foreach ($query['conditions'] as $c) {
            $sq->addCondition($c['field'], $c['match'], $c['value']);
        }
        if ($stmt = $sq->execute($this->dbo)) {
            $idlist = $stmt->fetchAll(PDO::FETCH_COLUMN);
            $dr = new DocumentReader($this, $this->dbo, $id);
            $data = $dr->getLinesByID($idlist);
            $this->prepareLinesForClient($data, $dr);
            return array('results' => $data, 'success' => true);
        }
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
        catch(PDOException $ex) {
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
        if (!empty($user)) { // username already exists
            return false;
        }
        $hashpw = self::hashPassword($password);
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
     * @param string $uid ID of the user
     * @param string $password The new password
     *
     * @return 1 if successful, 0 otherwise
     */
    public function changePassword($uid, $password) {
        $hashpw = self::hashPassword($password);
        $qs = "UPDATE users SET password=:pw WHERE `id`=:uid";
        $stmt = $this->dbo->prepare($qs);
        $stmt->bindValue(':uid', $uid, PDO::PARAM_INT);
        $stmt->bindValue(':pw', $hashpw, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->rowCount();
    }

    /** Change project<->user associations.
     *
     * @param string $pid the project ID of the project to change
     * @param array $userlist an array of user IDs
     *
     * @return A status array
     */
    public function changeProjectUsers($pid, $userlist) {
        $pa = new ProjectAccessor($this, $this->dbo);
        $pa->setAssociatedUsers($pid, $userlist);
    }

    /** Drop a user record from the database.
     *
     * @param string $uid ID of the user to be deleted
     *
     * @return 1 if successful, 0 otherwise
     */
    public function deleteUser($uid) {
        if ($uid == 1 || $uid == "1") return 0;
        $qs = "DELETE FROM users WHERE `id`=:uid";
        $stmt = $this->dbo->prepare($qs);
        $stmt->bindValue(':uid', $uid, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->rowCount();
    }

    /** Toggle administrator status of a user.
     *
     * @param string $uid ID of the user for which the administrator
     * status should be changed
     *
     * @return 1 if successful, 0 otherwise
     */
    public function toggleAdminStatus($uid) {
        return $this->toggleUserStatus($uid, 'admin');
    }

    /** Helper function for @c toggleAdminStatus(). */
    public function toggleUserStatus($uid, $statusname) {
        $qs = "SELECT {$statusname} FROM users WHERE `id`=:uid";
        $stmt = $this->dbo->prepare($qs);
        $stmt->execute(array(':uid' => $uid));
        $oldstat = $stmt->fetch(PDO::FETCH_COLUMN);
        $newstat = ($oldstat == 1) ? 0 : 1;
        $qs = "UPDATE users SET {$statusname}={$newstat} WHERE `id`=:uid";
        $stmt = $this->dbo->prepare($qs);
        $stmt->execute(array(':uid' => $uid));
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
    public function lockFile($fileid, $uname) {
        // first, check if file exists
        $stmt = $this->dbo->prepare("SELECT COUNT(*) FROM text WHERE `id`=:textid");
        $stmt->execute(array(':textid' => $fileid));
        if ($stmt->fetch(PDO::FETCH_COLUMN) == 0) {
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
        if ($stmt->rowCount() > 0) {
            // if file is locked, return info about user currently locking the file
            $qs = "SELECT a.lockdate as 'locked_since', b.name as 'locked_by' "
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
        return array("success" => true, "lockCounts" => (string)$locksCount);
    }

    /** Get locked file for given user.
     *
     * Retrieves the lock entry for a given user from the file lock table.
     *
     * @param string $user username
     *
     * @return An @em array with file id and file name of the locked file
     */
    public function getLockedFiles($uname) {
        $uid = $this->getUserIDFromName($uname);
        $qs = "SELECT a.text_id as 'file_id', b.fullname as 'file_name' "
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
     * @param string $fileid file id
     * @param string $user username (defaults to current session user)
     *
     * @return @em array result of the mysql query
     */
    public function unlockFile($fileid, $uname = "", $force = false) {
        $qs = "DELETE FROM locks WHERE text_id=:tid";
        if (!$force) {
            if ($uname == "") return 0;
            $userid = $this->getUserIDFromName($uname);
            $qs.= " AND user_id={$userid}";
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
    public function lockProjectForTagger($pid, $tid) {
        $qs = "SELECT COUNT(*) FROM tagger_locks WHERE `project_id`=:pid";
        $stmt = $this->dbo->prepare($qs);
        $stmt->execute(array(':pid' => $pid));
        if ($stmt->fetch(PDO::FETCH_COLUMN) != 0) {
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
        $place_holders = implode(',', array_fill(0, count($idlist), '?'));
        $qs = "SELECT ts.id, ts.name, LOWER(ts.class) AS `class`, ts.set_type "
            . "     FROM tagset ts "
            . "    WHERE ts.id IN ($place_holders)";
        $stmt = $this->dbo->prepare($qs);
        $stmt->execute($idlist);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Check whether annotations exist for a given file and tagset.
     */
    public function doAnnotationsExist($fileid, $tagsetid) {
        $qs = "SELECT COUNT(*) FROM tag_suggestion"
            . "   LEFT JOIN modern ON modern.id=tag_suggestion.mod_id"
            . "   LEFT JOIN token  ON token.id=modern.tok_id"
            . "   LEFT JOIN tag    ON tag.id=tag_suggestion.tag_id"
            . "       WHERE token.text_id=:textid"
            . "         AND tag.tagset_id=:tagsetid";
        $stmt = $this->dbo->prepare($qs);
        $stmt->execute(array(':textid' => $fileid, ':tagsetid' => $tagsetid));
        return $stmt->fetchColumn();
    }

    /** Get tagsets associated with a file.
     *
     * Retrieves a list of tagsets with their respective class for the
     * given file.
     */
    public function getTagsetsForFile($fileid) {
        $qs = "SELECT ts.id, ts.name, LOWER(ts.class) AS `class`, "
            . "       ts.set_type, ts.settings "
            . "     FROM text2tagset ttt "
            . "     LEFT JOIN tagset ts  ON ts.id=ttt.tagset_id "
            . "    WHERE ttt.text_id=:tid";
        $stmt = $this->dbo->prepare($qs);
        $stmt->execute(array(':tid' => $fileid));
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Add new tagset associations to a file.
     */
    public function addTagsetsForFile($fileid, $tagset_ids) {
        $qs = "INSERT INTO `text2tagset` (`text_id`, `tagset_id`)"
            . "     VALUES (:textid, :tagsetid)";
        $stmt = $this->dbo->prepare($qs);
        foreach ($tagset_ids as $tsid) {
            $stmt->execute(array(':textid' => $fileid, ':tagsetid' => $tsid));
        }
        return $stmt->rowCount();
    }

    /** Delete tagset associations for a file.
     */
    public function deleteTagsetsForFile($fileid, $tagset_ids) {
        $this->dbo->beginTransaction();
        try {
            $qs = "DELETE FROM `text2tagset`"
                . "      WHERE `text_id`=:textid AND `tagset_id`=:tagsetid";
            $stmt = $this->dbo->prepare($qs);
            foreach ($tagset_ids as $tsid) {
                $stmt->execute(array(':textid' => $fileid, ':tagsetid' => $tsid));
            }
        }
        catch(PDOException $ex) {
            $this->dbo->rollBack();
            return false;
        }
        $this->dbo->commit();
        return true;
    }

    /** Delete a tagger with a given ID. */
    public function deleteTagger($tid) {
        $qs = "DELETE FROM `tagger` WHERE `id`=:tid";
        // ON DELETE CASCADE should take care of everything else
        $stmt = $this->dbo->prepare($qs);
        $stmt->execute(array(':tid' => $tid));
        return $stmt->rowCount();
    }

    /** Create a new tagger. */
    public function addTagger($name, $tclass) {
        try {
            $qs = "INSERT INTO `tagger` (`class_name`, `display_name`)"
                . "               VALUES (:cname, :dname)";
            $stmt = $this->dbo->prepare($qs);
            $stmt->execute(array(':cname' => $tclass, ':dname' => $name));
            return array("success" => true, "id" => $this->dbo->lastInsertId());
        }
        catch(PDOException $ex) {
            return array("success" => false, "errors" => array($ex->getMessage()));
        }
    }

    /** Get a list of all taggers with their associated tagset links.
     */
    public function getTaggerList() {
        $tlist = array();
        $qs = "SELECT t.id, t.class_name, t.display_name, "
            . "         t.trainable, ts.tagset_id "
            . "    FROM tagger t "
            . "    LEFT JOIN tagger2tagset ts ON t.id=ts.tagger_id";
        $stmt = $this->dbo->prepare($qs);
        $stmt->execute();
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if (array_key_exists($row['id'], $tlist)) {
                $tlist[$row['id']]['tagsets'][] = $row['tagset_id'];
            } else {
                $tlist[$row['id']] = array('name' => $row['display_name'],
                                           'class_name' => $row['class_name'],
                                           'trainable' => $row['trainable'] == 1 ? true : false,
                                           'tagsets' => array($row['tagset_id']));
            }
        }
        return $tlist;
    }

    /** Get data about a specific tagger in order to instantiate it. */
    public function getTaggerOptions($taggerid) {
        $qs = "SELECT opt_key, opt_value "
            . "  FROM tagger_options "
            . " WHERE `tagger_id`=?";
        $stmt = $this->dbo->prepare($qs);
        $stmt->execute(array($taggerid));
        return $stmt->fetchAll(PDO::FETCH_COLUMN | PDO::FETCH_GROUP | PDO::FETCH_UNIQUE);
    }

    /** Sets information about a tagger. */
    public function setTaggerSettings($data) {
        try {
            $this->dbo->beginTransaction();
            $tid = $data['id'];
            $qs = "UPDATE `tagger` SET `class_name`=:cname, "
                . "                    `display_name`=:dname, "
                . "                    `trainable`=:trainable "
                . "              WHERE `id`=:tid";
            $stmt = $this->dbo->prepare($qs);
            $stmt->execute(array(':cname' => $data['class_name'],
                                 ':dname' => $data['name'],
                                 ':trainable' => $data['trainable'],
                                 ':tid' => $tid));
            /* options */
            $qs = "DELETE FROM `tagger_options` WHERE `tagger_id`=:tid";
            $stmt = $this->dbo->prepare($qs);
            $stmt->execute(array(':tid' => $tid));
            $qs = "INSERT INTO `tagger_options` (`tagger_id`, `opt_key`, `opt_value`)"
                . "                      VALUES (:tid, :k, :v)";
            $stmt_insert = $this->dbo->prepare($qs);
            foreach ($data['options'] as $k => $v) {
                $stmt_insert->execute(array(':tid' => $tid, ':k' => $k, ':v' => $v));
            }
            /* tagset links */
            $qs = "DELETE FROM `tagger2tagset` WHERE `tagger_id`=:tid";
            $stmt = $this->dbo->prepare($qs);
            $stmt->execute(array(':tid' => $tid));
            $qs = "INSERT INTO `tagger2tagset` (`tagger_id`, `tagset_id`)"
                . "                     VALUES (:tid, :tsid)";
            $stmt_insert = $this->dbo->prepare($qs);
            foreach ($data['tagsets'] as $tsid) {
                $stmt_insert->execute(array(':tid' => $tid, ':tsid' => $tsid));
            }
        }
        catch(PDOException $ex) {
            $this->dbo->rollBack();
            throw $ex;
        }
        $this->dbo->commit();
    }

    /** Get a list of all taggers, including tagset links and options. */
    public function getTaggerListAndOptions() {
        $tlist = array();
        foreach ($this->getTaggerList() as $tid => $tagger) {
            $tagger['id'] = $tid;
            $tagger['options'] = $this->getTaggerOptions($tagger['id']);
            $tlist[] = $tagger;
        }
        return $tlist;
    }

    /** Get options (e.g. associated check script) for a given project. */
    public function getProjectOptions($projectid) {
        $pa = new ProjectAccessor($this, $this->dbo);
        return $pa->getSettings($projectid);
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
        foreach ($tagsets as $ts) {
            $tslist[] = $ts['id'];
        }
        foreach ($taggers as $id => $tagger) {
            $is_applicable = true;
            foreach ($tagger['tagsets'] as $ts) {
                if (!in_array($ts, $tslist)) {
                    $is_applicable = false;
                }
            }
            if ($is_applicable) {
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
     * @param string $fileid file id
     *
     * @return an @em array with at least the file meta data. If
     * exists, the user's last edited row is also transmitted.
     */
    public function openFile($fileid, $user = "system") {
        $locked = $this->lockFile($fileid, $user);
        if (!$locked['success']) {
            return array('success' => false, 'errors' => array("lock failed"));
        }
        $qs = "SELECT text.id, text.sigle, text.fullname, text.project_id, ";
        $qs.= "       text.currentmod_id, text.header ";
        $qs.= "  FROM text ";
        $qs.= " WHERE text.id=:tid";
        $stmt = $this->dbo->prepare($qs);
        $stmt->execute(array(':tid' => $fileid));
        $metadata = $stmt->fetch(PDO::FETCH_ASSOC);
        $cmid = $metadata['currentmod_id'];
        $lock['lastEditedRow'] = - 1;
        if (!empty($cmid)) {
            // calculate position of currentmod_id
            $qs = "SELECT position FROM ";
            $qs.= " (SELECT x.id, @rownum := @rownum + 1 AS position FROM ";
            $qs.= "   (SELECT a.id FROM (modern a, token b) ";
            $qs.= "    WHERE a.tok_id=b.id AND b.text_id=:tid ";
            $qs.= "    ORDER BY b.ordnr ASC, a.id ASC) x ";
            $qs.= "  JOIN (SELECT @rownum := 0) r) y ";
            $qs.= "WHERE y.id = {$cmid}";
            $stmt = $this->dbo->prepare($qs);
            $stmt->execute(array(':tid' => $fileid));
            $position = $stmt->fetch(PDO::FETCH_COLUMN);
            $lock['lastEditedRow'] = intval($position) - 1;
        }
        $metadata['tagsets'] = $this->getTagsetsForFile($fileid);
        $metadata['taggers'] = $this->getTaggersForFile($fileid);
        $metadata['idlist'] = $this->getAllModernIDs($fileid);
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
    public function isAllowedToOpenFile($fileid, $uname) {
        $uid = $this->getUserIDFromName($uname);
        $qs = "SELECT a.fullname FROM (text a, user2project b) ";
        $qs.= "WHERE a.id=:tid AND b.user_id=:uid AND a.project_id=b.project_id";
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
    public function isAllowedToDeleteFile($fileid, $user) {
        $uid = $this->getUserIDFromName($user);
        $qs = "SELECT a.fullname FROM text a ";
        $qs.= "WHERE a.id=:tid AND a.creator_id=:uid";
        $stmt = $this->dbo->prepare($qs);
        $stmt->execute(array(':tid' => $fileid, ':uid' => $uid));
        return ($stmt->fetch()) ? true : false;
    }

    /** Delete a file.
     *
     * Deletes ALL database entries linked with the given file id.
     *
     * @param string $fileid file id
     *
     * @return bool @c true
     */
    public function deleteFile($fileid) {
        // first, get all open class tags associated with this file as
        // they have to be deleted separately
        $qs = "SELECT tag.id AS tag_id FROM tag "
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
            $internalError = $this->lh->_("ServerError.internal", array("code" => "1040"));
            return $internalError . "\n" . $ex->getMessage();
        }
        $this->dbo->beginTransaction();
        // delete associated open class tags
        if (!empty($deletetag)) {
            $qs = "DELETE FROM tag_suggestion WHERE `tag_id` IN (" . implode(",", $deletetag) . ")";
            try {
                $stmt = $this->dbo->prepare($qs);
                $stmt->execute();
            }
            catch(PDOException $ex) {
                $this->dbo->rollBack();
                $internalError = $this->lh->_("ServerError.internal", array("code" => "1041"));
                return $internalError . "\n" . $ex->getMessage();
            }
            $qs = "DELETE FROM tag WHERE `id` IN (" . implode(",", $deletetag) . ")";
            try {
                $stmt = $this->dbo->prepare($qs);
                $stmt->execute();
            }
            catch(PDOException $ex) {
                $this->dbo->rollBack();
                $internalError = $this->lh->_("ServerError.internal", array("code" => "1043"));
                return $internalError . "\n" . $ex->getMessage();
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
            $internalError = $this->lh->_("ServerError.internal", array("code" => "1042"));
            return $internalError . "\n" . $ex->getMessage();
        }
        $this->dbo->commit();
        return false;
    }

    /** Change sigle, name, and/or header for a given file.
     */
    public function changeMetadata($options) {
        try {
            $qs = "UPDATE text SET `sigle`=:sigle, `fullname`=:name, "
                . "                `header`=:header "
                . "          WHERE `id`=:id";
            $stmt = $this->dbo->prepare($qs);
            $stmt->execute(array(':id' => $options['id'],
                                 ':sigle' => $options['sigle'],
                                 ':name' => $options['name'],
                                 ':header' => $options['header']));
        }
        catch(PDOException $ex) {
            $internalError = $this->lh->_("ServerError.internal", array("code" => "1061"));
            return $internalError . "\n" . $ex->getMessage();
        }
    }

    /** Get a list of all projects and their associated files and
     *  settings.
     */
    public function getProjectsAndFiles($userid = null) {
        $pa = new ProjectAccessor($this, $this->dbo);
        $projects = $pa->getAllProjects($userid);
        foreach ($projects as & $project) {
            $pid = $project['id'];
            $project['tagsets'] = $pa->getAssociatedTagsetDefaults($pid);
            $project['files'] = $pa->getAssociatedFiles($pid, true);
            if (is_null($userid)) {
                $project['settings'] = $pa->getSettings($pid);
                $project['users'] = $pa->getAssociatedUsers($pid);
            } else {
                $project['settings'] = array_map('boolval', $pa->getSettings($pid));
            }
        }
        return $projects;
    }

    /** Save project settings, such as default tagsets, linked users,
     *  and external commands.
     */
    public function saveProjectSettings($pid, $data) {
        $pa = new ProjectAccessor($this, $this->dbo);
        try {
            if (array_key_exists('users', $data))
                $this->changeProjectUsers($pid, $data['users']);
            if (array_key_exists('tagsets', $data))
                $pa->setAssociatedTagsetDefaults($pid, $data['tagsets']);
            $pa->setSettings($pid, $data['cmd_edittoken'], $data['cmd_import']);
        }
        catch(Exception $ex) {
            return array('success' => false,
                         'errors' => array($this->lh->_("ServerError.genericException"),
                                           $ex->getMessage()));
        }
        return array('success' => true);
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
    public function getFiles() {
        $qs = "SELECT a.id, a.sigle, a.fullname, a.created, "
            . "         a.creator_id, a.changer_id, "
            . "         a.changed, a.currentmod_id, c.name as opened, "
            . "         d.id as project_id, d.name as project_name, "
            . "         e.name as creator_name, f.name as changer_name "
            . "     FROM text a " . "LEFT JOIN locks b ON a.id=b.text_id "
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
    public function getFilesForUser($uname) {
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

    /** Get filename from ID
     *
     * @param string $fid File ID
     */
    public function getFilenameFromID($fid) {
        $qs = "SELECT a.id, a.sigle, a.fullname "
            . "FROM   text a WHERE a.id=:fid";
        $stmt = $this->dbo->prepare($qs);
        $stmt->execute(array(':fid' => $fid));
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /** Get a list of all files in a given project.
     *
     * @param string $pid Project ID
     * @return an two-dimensional @em array with the meta data
     */
    public function getFilesForProject($pid) {
        $qs = "SELECT a.id, a.sigle, a.fullname "
            . "FROM  text a WHERE a.project_id={$pid}";
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
    public function getProjects() {
        $pa = new ProjectAccessor($this, $this->dbo);
        return $pa->getAllProjects();
    }

    /** Get a list of all projects accessible by a given user.
     *
     * @param string $user username
     * @return a two-dimensional @em array with the project id and name
     */
    public function getProjectsForUser($uname) {
        $pa = new ProjectAccessor($this, $this->dbo);
        $uid = $this->getUserIDFromName($uname);
        return $pa->getAllProjects($uid);
    }

    /** Get a list of all project user groups.
     *
     * Should only be called for administrators.
     *
     * @return a two-dimensional @em array with the project id and name
     */
    public function getProjectUsers() {
        $qs = "SELECT user2project.project_id, users.name AS username "
            . "    FROM user2project "
            . "    LEFT JOIN users ON user2project.user_id=users.id";
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
    public function createProject($name) {
        try {
            $qs = "INSERT INTO project (`name`) VALUES (:name)";
            $stmt = $this->dbo->prepare($qs);
            $stmt->bindValue(':name', $name, PDO::PARAM_STR);
            $stmt->execute();
            return array("success" => true, "pid" => $this->dbo->lastInsertId());
        }
        catch(PDOException $ex) {
            return array("success" => false, "errors" => array($ex->getMessage()));
        }
    }

    /** Deletes a project.  Will fail unless no document is
     * assigned to the project.
     *
     * @param string $pid the project id
     * @return a boolean value indicating success
     */
    public function deleteProject($pid) {
        try {
            $stmt = $this->dbo->prepare("DELETE FROM project WHERE `id`=:pid");
            $stmt->bindValue(':pid', $pid, PDO::PARAM_INT);
            $stmt->execute();
            $result = ($stmt->rowCount() > 0) ? true : false;
        }
        catch(PDOException $ex) {
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
    public function setUserSettings($user, $lpp, $cl) {
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
    public function setUserSetting($user, $name, $value) {
        $validnames = array("lines_context", "lines_per_page",
                            "show_error", "columns_order",
                            "columns_hidden", "text_preview", "locale");
        if (in_array($name, $validnames)) {
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
    public function getMaxLinesNo($fileid) {
        $qs = "SELECT COUNT(modern.id) FROM token ";
        $qs.= "LEFT JOIN modern ON modern.tok_id=token.id ";
        $qs.= "WHERE token.text_id=:tid";
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
    protected function getFlagsForModern($mid) {
        $qs = "SELECT error_types.name ";
        $qs.= "FROM   modern ";
        $qs.= "  LEFT JOIN mod2error ON modern.id=mod2error.mod_id ";
        $qs.= "  LEFT JOIN error_types ON mod2error.error_id=error_types.id ";
        $qs.= "WHERE  modern.id=:mid";
        $stmt = $this->dbo->prepare($qs);
        $stmt->execute(array(':mid' => $mid));
        $flags = $stmt->fetchAll(PDO::FETCH_COLUMN);
        if (count($flags) == 1 && is_null($flags[0])) return array();
        return $flags;
    }

    /** Retrieves all layout information for a given document. */
    public function getLayoutInfo($fileid) {
        $pages = array();
        $columns = array();
        $lines = array();
        $currentpage = null;
        $currentcolumn = null;
        $qs = "SELECT p.id AS page_id, p.name AS page_name, "
            . "          p.side AS page_side, p.num AS page_num, "
            . "          c.id AS col_id,  c.name AS col_name,  c.num AS col_num, "
            . "          l.id AS line_id, l.name AS line_name, l.num AS line_num "
            . "     FROM page p " . "LEFT JOIN col  c ON c.page_id=p.id "
            . "LEFT JOIN line l ON l.col_id=c.id "
            . "    WHERE p.text_id=:tid "
            . " ORDER BY p.num ASC, c.num ASC, l.num ASC";
        $stmt = $this->dbo->prepare($qs);
        $stmt->execute(array(':tid' => $fileid));
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($result as $row) {
            if ($row['page_id'] != $currentpage) {
                $currentpage = $row['page_id'];
                $pages[] = array('db_id' => $row['page_id'],
                                 'name' => $row['page_name'],
                                 'side' => $row['page_side'],
                                 'num' => $row['page_num']);
            }
            if ($row['col_id'] != $currentcolumn) {
                $currentcolumn = $row['col_id'];
                $columns[] = array('db_id' => $row['col_id'],
                                   'name' => $row['col_name'],
                                   'num' => $row['col_num'],
                                   'parent_db_id' => $row['page_id']);
            }
            $lines[] = array('db_id' => $row['line_id'],
                             'name' => $row['line_name'],
                             'num' => $row['line_num'],
                             'parent_db_id' => $row['col_id']);
        }
        return array($pages, $columns, $lines);
    }

    /** Retrieves all shift tags for a given document. */
    public function getShiftTags($fileid) {
        $shifttags = array();
        $qs = "SELECT shifttags.tok_from, shifttags.tok_to, shifttags.tag_type "
            . "     FROM shifttags "
            . "     JOIN token ON token.id=shifttags.tok_from "
            . "    WHERE token.text_id=:tid";
        $stmt = $this->dbo->prepare($qs);
        $stmt->execute(array(':tid' => $fileid));
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($result as $row) {
            $tag = array("type_letter" => $row['tag_type'],
                         "db_range" => array($row['tok_from'], $row['tok_to']));
            $shifttags[] = $tag;
        }
        return $shifttags;
    }

    /** Retrieves all (non-CorA) comments for a given document. */
    public function getComments($fileid) {
        $qs = "SELECT comment.tok_id AS parent_db_id, comment.value AS text,"
            . "          comment.comment_type AS type "
            . "     FROM comment "
            . "     JOIN token ON token.id=comment.tok_id "
            . "    WHERE token.text_id=:tid";
        $stmt = $this->dbo->prepare($qs);
        $stmt->execute(array(':tid' => $fileid));
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Retrieve all modern IDs from a file, in their logical order.
     *
     * @param string $fileid ID of the file
     */
    public function getAllModernIDs($fileid) {
        $qs = "SELECT modern.id FROM modern "
            . "  LEFT JOIN token ON token.id=modern.tok_id "
            . " WHERE token.text_id=:tid "
            . " ORDER BY token.ordnr ASC, modern.id ASC";
        $stmt = $this->dbo->prepare($qs);
        $stmt->execute(array(':tid' => $fileid));
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    /** Retrieves all moderns from a file, including annotations.
     *
     * @param string $fileid ID of the file
     */
    public function getAllModerns($fileid) {
        /* TODO: this is temporary until "verified" status is marked
         directly with the modern */
        $qs = "SELECT `currentmod_id` FROM text WHERE `id`=:tid";
        $stmt = $this->dbo->prepare($qs);
        $stmt->execute(array(':tid' => $fileid));
        $currentmod_id = $stmt->fetch(PDO::FETCH_COLUMN);
        $verified = ($currentmod_id && $currentmod_id != null && !empty($currentmod_id));
        $qs = "SELECT token.id AS parent_tok_db_id, modern.id AS db_id, "
            . "          modern.trans, modern.utf, modern.ascii "
            . "     FROM token "
            . "    INNER JOIN modern ON modern.tok_id=token.id "
            . "    WHERE token.text_id=:tid "
            . "    ORDER BY token.ordnr ASC, modern.id ASC";
        $stmt = $this->dbo->prepare($qs);
        $stmt->execute(array(':tid' => $fileid));
        $moderns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $qs = "SELECT tag.value AS tag, ts.score, ts.selected, ts.source,"
            . "           LOWER(tt.class) AS type "
            . "      FROM   modern"
            . "      LEFT JOIN (tag_suggestion ts, tag) "
            . "             ON (ts.tag_id=tag.id AND ts.mod_id=modern.id) "
            . "      LEFT JOIN tagset tt ON tag.tagset_id=tt.id "
            . "     WHERE modern.id=:mid";
        $stmt = $this->dbo->prepare($qs);
        foreach ($moderns as & $row) {
            // Annotations
            $stmt->execute(array(':mid' => $row['db_id']));
            $row['tags'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $row['flags'] = $this->getFlagsForModern($row['db_id']);
            /* TODO: this is temporary until "verified" status is marked
             directly with the modern */
            $row['verified'] = $verified;
            if ($row['db_id'] == $currentmod_id) {
                $verified = false;
            }
        }
        unset($row);
        return $moderns;
    }

    /** Retrieves all moderns from a file.
     *
     * Compared to getAllModerns(), this function returns a reduced set
     * of information about the moderns, i.e., their ASCII value and
     * _selected_ annotations (if the flag is set).  Additionally,
     * moderns are returned indexed by ID.
     *
     * @param string $fileid ID of the file
     * @param boolean $do_anno Whether annotation should be included
     */
    public function getAllModerns_simple($fileid, $do_anno = true) {
        /* TODO: this is temporary until "verified" status is marked
         directly with the modern */
        $qs = "SELECT `currentmod_id` FROM text WHERE `id`=:tid";
        $stmt = $this->dbo->prepare($qs);
        $stmt->execute(array(':tid' => $fileid));
        $currentmod_id = $stmt->fetch(PDO::FETCH_COLUMN);
        $verified = ($currentmod_id && $currentmod_id != null && !empty($currentmod_id));
        $qs = "SELECT modern.id, modern.ascii, modern.utf, modern.trans "
            . "     FROM token "
            . "    INNER JOIN modern ON modern.tok_id=token.id "
            . "    WHERE token.text_id=:tid "
            . "    ORDER BY token.ordnr ASC, modern.id ASC";
        $stmt = $this->dbo->prepare($qs);
        $stmt->execute(array(':tid' => $fileid));
        $moderns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if ($do_anno) {
            $qs = "SELECT modern.id, tag.value AS tag, LOWER(tt.class) AS type "
                . "      FROM   modern"
                . "      LEFT JOIN (tag_suggestion ts, tag) "
                . "             ON (ts.tag_id=tag.id AND ts.mod_id=modern.id) "
                . "      LEFT JOIN tagset tt ON tag.tagset_id=tt.id "
                . "      LEFT JOIN token ON token.id=modern.tok_id "
                . "     WHERE token.text_id=:tid AND ts.selected=1";
            $stmt = $this->dbo->prepare($qs);
            $stmt->execute(array(':tid' => $fileid));
            $annotations = $stmt->fetchAll(PDO::FETCH_ASSOC | PDO::FETCH_GROUP);
            foreach ($moderns as & $row) {
                $row['tags'] = array();
                if (isset($annotations[$row['id']])) {
                    foreach ($annotations[$row['id']] as $anno) {
                        $row['tags'][$anno['type']] = $anno['tag'];
                    }
                }
                /* TODO: this is temporary until "verified" status is marked
                 directly with the modern */
                $row['verified'] = $verified;
                if ($row['id'] == $currentmod_id) {
                    $verified = false;
                }
            }
        }
        unset($row);
        return $moderns;
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
        $tokens = array();
        $dipls = array();
        $moderns = array();
        // tokens
        $qs = "SELECT token.id AS db_id, token.trans"
            . "     FROM token "
            . "    WHERE token.text_id=:tid"
            . "    ORDER BY token.ordnr ASC";
        $stmt = $this->dbo->prepare($qs);
        $stmt->execute(array(':tid' => $fileid));
        $tokens = $stmt->fetchAll(PDO::FETCH_ASSOC);
        // dipls
        $qs = "SELECT token.id AS parent_tok_db_id, dipl.id AS db_id, "
            . "          dipl.line_id AS parent_line_db_id, dipl.utf, dipl.trans "
            . "     FROM token "
            . "    INNER JOIN dipl ON dipl.tok_id=token.id "
            . "    WHERE token.text_id=:tid "
            . "    ORDER BY token.ordnr ASC, dipl.id ASC ";
        $stmt = $this->dbo->prepare($qs);
        $stmt->execute(array(':tid' => $fileid));
        $dipls = $stmt->fetchAll(PDO::FETCH_ASSOC);
        // moderns
        $moderns = $this->getAllModerns($fileid);
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
    public function getLines($fileid, $start, $lim) {
        if (!$start || $start === null) $start = 0;
        if (!$lim || $lim === null) $lim = PHP_INT_MAX;
        $dr = new DocumentReader($this, $this->dbo, $fileid);
        $data = $dr->getLinesByRange($start, $lim);
        $this->prepareLinesForClient($data, $dr);
        return $data;
    }

    private function prepareLinesForClient(&$data, $dr) {
        foreach ($data as $linenum => & $line) {
            // Token transcription & layout information
            $line['full_trans'] = $dr->getTokTransWithLinebreaks($line['tok_id']);
            $line = array_merge($line, $dr->getDiplLayoutInfo($line['tok_id']));
            // Flags (formerly "error annotations")
            foreach ($this->getFlagsForModern($line['id']) as $flag) {
                $flagname = 'flag_' . str_replace(' ', '_', $flag);
                $line[$flagname] = 1;
            }
            // Annotations
            $line['suggestions'] = array();
            $annotations = $dr->getAllAnnotations($line['id']);
            foreach ($annotations as $row) {
                if ($row['selected'] == '1') {
                    $line['anno_' . $row['class']] = $row['value'];
                }
                if ($row['class'] == 'pos' && $row['source'] == 'auto') {
                    // all "auto" annotations are transmitted as "suggestions"
                    $line['suggestions'][] = array('pos' => $row['value'], 'score' => $row['score']);
                }
            }
        }
        unset($line);
    }

    /** Retrieves error types and indexes them by name. */
    public function getErrorTypes() {
        $stmt = $this->dbo->prepare("SELECT `name`, `id` FROM error_types");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    /** Delete locks if the locking user has been inactive for too
     * long; currently, this is set to be >30 minutes.
     */
    public function releaseOldLocks($to) {
        $qs = "DELETE locks FROM locks";
        $qs.= "  LEFT JOIN users ON users.id=locks.user_id";
        $qs.= "  WHERE users.lastactive < (NOW() - INTERVAL {$to} MINUTE)";
        $this->dbo->exec($qs);
    }

    /** Saves changed data.
     *
     * Wraps @c saveLines() with different argument structure.
     */
    public function saveData($fileid, $data, $uname) {
        $ler = (isset($data['ler']) ? $data['ler'] : null);
        return $this->saveLines($fileid, $ler, $data['lines'], $uname);
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
    public function saveLines($fileid, $lasteditedrow, $lines, $uname) {
        $locked = $this->lockFile($fileid, $uname);
        if (!$locked['success']) {
            return "lock failed";
        }
        $warnings = $this->performSaveLines($fileid, $lines, $lasteditedrow);
        $userid = $this->getUserIDFromName($uname);
        $this->updateChangedTimestamp($fileid, $userid);
        if (!empty($warnings)) {
            return $this->lh->_("ServerError.savedWithWarnings") . "\n" . implode("\n", $warnings);
        }
        return False;
    }

    public function performSaveLines($fileid, $lines, $lasteditedrow) {
        $dw = new DocumentWriter($this, $this->dbo, $fileid);
        if (!empty($lines)) {
            $dw->saveLines($lines);
        }
        if ($lasteditedrow !== null) {
            if ($lasteditedrow == - 1) {
                $lasteditedrow = null;
            }
            $dw->markLastPosition($lasteditedrow);
        }
        return $dw->getWarnings();
    }

    /** Updates "last edited" information for a file.
     */
    public function updateChangedTimestamp($fileid, $userid) {
        $qs = "UPDATE text SET `changer_id`=:uid, "
            . "                  `changed`=CURRENT_TIMESTAMP WHERE `id`=:tid";
        $stmt = $this->dbo->prepare($qs);
        $stmt->execute(array(':uid' => $userid, ':tid' => $fileid));
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
        $qs = "SELECT a.id FROM token a ";
        $qs.= "WHERE  a.ordnr > (SELECT b.ordnr FROM token b ";
        $qs.= "                  WHERE  b.id=:tokid) ";
        $qs.= "       AND a.text_id=:tid ";
        $qs.= "ORDER BY a.ordnr ASC LIMIT 1 ";
        try {
            $stmt = $this->dbo->prepare($qs);
            $stmt->execute(array(':tokid' => $tokenid, ':tid' => $textid));
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
        }
        catch(PDOException $ex) {
            $errors[] = $this->lh->_("ServerError.internal", array("code" => "1220"));
            $errors[] = $ex->getMessage() . "\n" . $qs;
            return array("success" => false, "errors" => $errors);
        }
        if ($row && array_key_exists('id', $row)) {
            $nexttokenid = $row['id'];
        }
        $qs = "SELECT a.id FROM token a ";
        $qs.= "WHERE  a.ordnr < (SELECT b.ordnr FROM token b ";
        $qs.= "                  WHERE  b.id=:tokid) ";
        $qs.= "       AND a.text_id=:tid ";
        $qs.= "ORDER BY a.ordnr DESC LIMIT 1 ";
        try {
            $stmt = $this->dbo->prepare($qs);
            $stmt->execute(array(':tokid' => $tokenid, ':tid' => $textid));
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
        }
        catch(PDOException $ex) {
            $errors[] = $this->lh->_("ServerError.internal", array("code" => "1221"));
            $errors[] = $ex->getMessage() . "\n" . $qs;
            return array("success" => false, "errors" => $errors);
        }
        if ($row && array_key_exists('id', $row)) {
            $prevtokenid = $row['id'];
        }
        // find shift tags attached to this token
        $stinsert = array();
        $qs = "SELECT `id`, `tok_from`, `tok_to` FROM shifttags ";
        $qs.= "WHERE  `tok_from`=:tokfrom OR `tok_to`=:tokto";
        try {
            $stmt = $this->dbo->prepare($qs);
            $stmt->execute(array(':tokfrom' => $tokenid, ':tokto' => $tokenid));
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        catch(PDOException $ex) {
            $errors[] = $this->lh->_("ServerError.internal", array("code" => "1222"));
            $errors[] = $ex->getMessage() . "\n" . $qs;
            return array("success" => false, "errors" => $errors);
        }
        // if necessary, move these shift tags around to prevent them from getting deleted
        foreach ($result as $row) {
            // if both refer to the current token, do nothing
            if ($row['tok_from'] != $row['tok_to']) {
                if ($row['tok_from'] == $tokenid) {
                    $stinsert[] = array(':id' => $row['id'],
                                        ':tokfrom' => $nexttokenid,
                                        ':tokto' => $row['tok_to']);
                } else {
                    $stinsert[] = array(':id' => $row['id'],
                                        ':tokfrom' => $row['tok_from'],
                                        ':tokto' => $prevtokenid);
                }
            }
        }
        // perform modifications
        $this->dbo->beginTransaction();
        if (!empty($stinsert)) {
            $qs = "INSERT INTO shifttags (`id`, `tok_from`, `tok_to`) ";
            $qs.= "               VALUES (:id,  :tokfrom,   :tokto) ";
            $qs.= " ON DUPLICATE KEY UPDATE `tok_from`=VALUES(tok_from), `tok_to`=VALUES(tok_to)";
            try {
                $stmt = $this->dbo->prepare($qs);
                foreach ($stinsert as $param) {
                    $stmt->execute($param);
                }
            }
            catch(PDOException $ex) {
                $errors[] = $this->lh->_("ServerError.internal", array("code" => "1223"));
                $errors[] = $ex->getMessage() . "\n" . $qs;
                $this->dbo->rollBack();
                return array("success" => false, "errors" => $errors);
            }
        }
        // move any comments attached to this token
        if ($prevtokenid !== null || $nexttokenid !== null) {
            $commtokenid = ($prevtokenid === null ? $nexttokenid : $prevtokenid);
            $qs = "UPDATE comment SET `tok_id`=:ctokid WHERE `tok_id`=:tokid";
            try {
                $stmt = $this->dbo->prepare($qs);
                $stmt->execute(array(':ctokid' => $commtokenid, ':tokid' => $tokenid));
            }
            catch(PDOException $ex) {
                $errors[] = $this->lh->_("ServerError.internal", array("code" => "1224"));
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
        catch(PDOException $ex) {
            $errors[] = $this->lh->_("ServerError.internal", array("code" => "1225"));
            $errors[] = $ex->getMessage() . "\n" . $qs;
            $this->dbo->rollBack();
            return array("success" => false, "errors" => $errors);
        }
        $this->dbo->commit();
        $this->updateChangedTimestamp($textid, $userid);
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
        $ordnr = null;
        $lineid = null;
        // fetch ordnr for token
        $qs = "SELECT `ordnr` FROM token WHERE `id`=:id";
        $stmt = $this->dbo->prepare($qs);
        $stmt->execute(array(':id' => $oldtokenid));
        $ordnr = $stmt->fetch(PDO::FETCH_COLUMN);
        // fetch line for first dipl
        $qs = "SELECT `line_id` FROM dipl WHERE `tok_id`=:id ";
        $qs.= "ORDER BY `id` ASC LIMIT 1";
        $stmt = $this->dbo->prepare($qs);
        $stmt->execute(array(':id' => $oldtokenid));
        $lineid = $stmt->fetch(PDO::FETCH_COLUMN);
        $this->dbo->beginTransaction();
        // add token
        $qs = "INSERT INTO token (`text_id`, `trans`, `ordnr`) ";
        $qs.= "           VALUES (:tid,      :trans,  :ordnr)";
        try {
            $stmt = $this->dbo->prepare($qs);
            $stmt->execute(array(':tid' => $textid, ':trans' => $toktrans, ':ordnr' => $ordnr));
        }
        catch(PDOException $ex) {
            $errors[] = "Ein interner Fehler ist aufgetreten (Code: 1230).";
            $errors[] = $ex->getMessage() . "\n" . $qs;
            $this->dbo->rollBack();
            return array("success" => false, "errors" => $errors);
        }
        $tokenid = $this->dbo->lastInsertId();
        // re-order tokens
        $qs = "UPDATE token SET `ordnr`=`ordnr`+1 ";
        $qs.= "WHERE `text_id`=:tid AND (`id`=:id OR `ordnr`>:ordnr)";
        try {
            $stmt = $this->dbo->prepare($qs);
            $stmt->execute(array(':tid' => $textid, ':id' => $oldtokenid, ':ordnr' => $ordnr));
        }
        catch(PDOException $ex) {
            $errors[] = $this->lh->_("ServerError.internal", array("code" => "1231"));
            $errors[] = $ex->getMessage() . "\n" . $qs;
            $this->dbo->rollBack();
            return array("success" => false, "errors" => $errors);
        }
        // insert dipl
        try {
            $qs = "INSERT INTO dipl (`tok_id`, `line_id`, `utf`, `trans`) ";
            $qs.= "          VALUES (:tokid,   :lineid,   :utf,  :trans) ";
            $stmt = $this->dbo->prepare($qs);
            $diplcount = count($converted['dipl_trans']);
            for ($i = 0;$i < $diplcount;$i++) { // loop by index because two arrays are involved
                $stmt->execute(array(':tokid' => $tokenid,
                                     ':lineid' => $lineid,
                                     ':utf' => $converted['dipl_utf'][$i],
                                     ':trans' => $converted['dipl_trans'][$i]));
            }
        }
        catch(PDOException $ex) {
            $errors[] = $this->lh->_("ServerError.internal", array("code" => "1232"));
            $errors[] = $ex->getMessage() . "\n" . $qs;
            $this->dbo->rollBack();
            return array("success" => false, "errors" => $errors);
        }
        // insert mod
        try {
            $qs = "INSERT INTO modern (`tok_id`, `ascii`, `utf`, `trans`) ";
            $qs.= "            VALUES (:tokid,   :ascii,  :utf,  :trans) ";
            $stmt = $this->dbo->prepare($qs);
            $modcount = count($converted['mod_trans']);
            for ($j = 0;$j < $modcount;$j++) {
                $stmt->execute(array(':tokid' => $tokenid,
                                     ':ascii' => $converted['mod_ascii'][$j],
                                     ':utf' => $converted['mod_utf'][$j],
                                     ':trans' => $converted['mod_trans'][$j]));
            }
        }
        catch(PDOException $ex) {
            $errors[] = $this->lh->_("ServerError.internal", array("code" => "1233"));
            $errors[] = $ex->getMessage() . "\n" . $qs;
            $this->dbo->rollBack();
            return array("success" => false, "errors" => $errors);
        }
        // done!
        $this->dbo->commit();
        $this->updateChangedTimestamp($textid, $userid);
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
        $oldmod = array();
        $lineids = array(); // all possible line IDs for this token
        $dipl_break_values = array_count_values($converted['dipl_breaks']);
        $newlinecount = (isset($dipl_break_values[1]) ? 1 + $dipl_break_values[1] : 1);
        // delete newlines in the token transcription
        $toktrans = str_replace("\n", "", $toktrans);
        // get current dipls
        $qs = "SELECT * FROM dipl WHERE `tok_id`=:tokid ORDER BY `id` ASC";
        $stmt = $this->dbo->prepare($qs);
        $stmt->execute(array(':tokid' => $tokenid));
        $lastline = "";
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $olddipl[] = $row;
            if ($row['line_id'] !== $lastline) {
                $lineids[] = $row['line_id'];
                $lastline = $row['line_id'];
            }
        }
        $oldlinecount = count($lineids);
        // does the token span more lines (in the diplomatic transcription) than before?
        // --- this takes up an awful lot of space ...
        if ($newlinecount > $oldlinecount) {
            if (($newlinecount - $oldlinecount) > 1) {
                $errors[] = $this->lh->_("TranscriptionError.lineCountMismatch");
                $errors[] = $this->lh->_("TranscriptionError.lineCountMismatchDetails",
                                      array("new" => $newlinecount,
                                            "old" => $oldlinecount));
                return array("success" => false, "errors" => $errors);
            }
            // fetch the first dipl of the next token and check if it is on
            // a different line than the last dipl of the current token -->
            // if so, this works, if not, then it's an error
            $qs = "SELECT d.line_id FROM dipl d ";
            $qs.= "  WHERE d.tok_id IN (SELECT t.id AS tok_id FROM token t ";
            $qs.= "                      WHERE t.text_id=:tid ";
            $qs.= "                        AND t.ordnr > (SELECT u.ordnr FROM token u ";
            $qs.= "                                        WHERE u.id=:tokid) ";
            $qs.= "                      ORDER BY t.ordnr ASC) ";
            $qs.= " ORDER BY d.id ASC LIMIT 1";
            try {
                $stmt = $this->dbo->prepare($qs);
                $stmt->execute(array(':tid' => $textid, ':tokid' => $tokenid));
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
            }
            catch(PDOException $ex) {
                $errors[] = "Ein interner Fehler ist aufgetreten (Code: 1210).";
                $errors[] = $ex->getMessage() . "\n" . $qs;
                return array("success" => false, "errors" => $errors);
            }
            if (empty($row) || !isset($row['line_id'])) {
                $errors[] = $this->lh->_("TranscriptionError.lineBreakDangling");
                return array("success" => false, "errors" => $errors);
            }
            if ($row['line_id'] == $lastline) {
                $errors[] = $this->lh->_("TranscriptionError.lineBreakMisplaced");
                return array("success" => false, "errors" => $errors);
            }
            $lineids[] = $row['line_id'];
        }
        // prepare dipl queries
        $diplinsert = array();
        $dipldelete = array();
        $currentline = 0;
        $diplcount = count($converted['dipl_trans']);
        for ($i = 0;$i < $diplcount;$i++) { // loop by index because three arrays are involved
            $diplid = (isset($olddipl[$i]) ? $olddipl[$i]['id'] : PDO::PARAM_NULL);
            $dipltrans = $converted['dipl_trans'][$i];
            $diplinsert[] = array(':diplid' => $diplid, ':tokid' => $tokenid, ':lineid' => $lineids[$currentline], ':utf' => $converted['dipl_utf'][$i], ':trans' => $dipltrans);
            if ($converted['dipl_breaks'][$i] == 1) {
                $currentline++;
            }
        }
        // are there dipls that need to be deleted?
        while (isset($olddipl[$i])) {
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
        $modcount = count($converted['mod_trans']);
        for ($j = 0;$j < $modcount;$j++) {
            $modid = (isset($oldmod[$j]) ? $oldmod[$j]['id'] : PDO::PARAM_NULL);
            $modinsert[] = array(':modid' => $modid,
                                 ':tokid' => $tokenid,
                                 ':trans' => $converted['mod_trans'][$j],
                                 ':ascii' => $converted['mod_ascii'][$j],
                                 ':utf' => $converted['mod_utf'][$j]);
        }
        // are there mods that need to be deleted?
        while (isset($oldmod[$j])) {
            $moddelete[] = $oldmod[$j]['id'];
            $j++;
        }
        // perform actual queries
        $this->dbo->beginTransaction();
        // dipl
        if (!empty($diplinsert)) { // e.g., standalone edition numberings have no dipl
            $qs = "INSERT INTO dipl (`id`, `tok_id`, `line_id`, `utf`, `trans`) ";
            $qs.= "          VALUES (:diplid, :tokid, :lineid,  :utf,  :trans) ";
            $qs.= " ON DUPLICATE KEY UPDATE `line_id`=VALUES(line_id), ";
            $qs.= "                    `utf`=VALUES(utf), `trans`=VALUES(trans)";
            try {
                $stmt = $this->dbo->prepare($qs);
                foreach ($diplinsert as $param) {
                    $stmt->execute($param);
                }
            }
            catch(PDOException $ex) {
                $errors[] = $this->lh->_("ServerError.internal", array("code" => "1211"));
                $errors[] = $ex->getMessage() . "\n" . $qs;
                $this->dbo->rollBack();
                return array("success" => false, "errors" => $errors);
            }
        }
        if (!empty($dipldelete)) {
            $qs = "DELETE FROM dipl WHERE `id` IN (" . implode(", ", $dipldelete) . ")";
            try {
                $this->dbo->exec($qs);
            }
            catch(PDOException $ex) {
                $errors[] = $this->lh->_("ServerError.internal", array("code" => "1212"));
                $errors[] = $ex->getMessage() . "\n" . $qs;
                $this->dbo->rollBack();
                return array("success" => false, "errors" => $errors);
            }
        }
        // modern
        if (!empty($modinsert)) { // this can happen for struck words, e.g. *[vnd*]
            $qs = "INSERT INTO modern (`id`, `tok_id`, `trans`, `ascii`, `utf`) ";
            $qs.= "            VALUES (:modid, :tokid, :trans,  :ascii,  :utf) ";
            $qs.= " ON DUPLICATE KEY UPDATE `trans`=VALUES(trans), ";
            $qs.= "                    `ascii`=VALUES(ascii), `utf`=VALUES(utf)";
            try {
                $stmt = $this->dbo->prepare($qs);
                foreach ($modinsert as $param) {
                    $stmt->execute($param);
                }
            }
            catch(PDOException $ex) {
                $errors[] = $this->lh->_("ServerError.internal", array("code" => "1213"));
                $errors[] = $ex->getMessage() . "\n" . $qs;
                $this->dbo->rollBack();
                return array("success" => false, "errors" => $errors);
            }
        }
        if (!empty($moddelete)) {
            $qs = "DELETE FROM modern WHERE `id` IN (" . implode(", ", $moddelete) . ")";
            try {
                $this->dbo->exec($qs);
            }
            catch(PDOException $ex) {
                $errors[] = $this->lh->_("ServerError.internal", array("code" => "1214"));
                $errors[] = $ex->getMessage() . "\n" . $qs;
                $this->dbo->rollBack();
                return array("success" => false, "errors" => $errors);
            }
            // if 'currentmod_id' is set to one of the deleted tokens, set it to something feasible
            $qs = "SELECT currentmod_id FROM text WHERE `id`=:tid ";
            $qs.= "  AND `currentmod_id` IN (" . implode(", ", $moddelete) . ")";
            $stmt = $this->dbo->prepare($qs);
            $stmt->execute(array(':tid' => $textid));
            if ($stmt->rowCount() > 0) {
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
            $stmt->execute(array(':tokid' => $tokenid, ':trans' => $toktrans));
        }
        catch(PDOException $ex) {
            $errors[] = $this->lh->_("ServerError.internal", array("code" => "1215"));
            $errors[] = $ex->getMessage() . "\n" . $qs;
            $this->dbo->rollBack();
            return array("success" => false, "errors" => $errors);
        }
        $this->dbo->commit();
        $this->updateChangedTimestamp($textid, $userid);
        return array("success" => true, "oldmodcount" => count($oldmod), "newmodcount" => $modcount);
    }
    /** Add a list of tags as a new tagset.
     */
    public function importTagList($taglist, $cls, $settype, $name) {
        $creator = new TagsetCreator($this->dbo, $cls, $settype, $name);
        $creator->addTaglist($taglist);
        if ($creator->hasErrors()) {
            return array('success' => false, 'errors' => $creator->getErrors());
        }
        if (!$creator->commitChanges()) {
            return array('success' => false, 'errors' => $creator->getErrors());
        }
        return array('success' => true);
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
     * @param string $uid User ID of the document's creator
     *
     * @return An associative array containing:
     *          -- 'success', which is true if the import was successful
     *          -- 'warnings', an array possibly containing warning messages
     */
    public function insertNewDocument(&$options, &$data, $uid) {
        $dc = new DocumentCreator($this, $this->dbo, $options);
        $success = $dc->importDocument($data, $uid);
        return array('success' => $success, 'warnings' => $dc->getWarnings());
    }

    public function getLemmaSuggestionFromLineNumber($linenum) {
        $suggestions = array();
        $qstr = "SELECT ts.id, ts.tag_id, ts.source, tag.value, "
              . "       LOWER(tagset.class) AS `class` "
              . "     FROM tag_suggestion ts "
              . "LEFT JOIN tag ON tag.id=ts.tag_id "
              . "LEFT JOIN tagset ON tagset.id=tag.tagset_id "
              . "    WHERE ts.mod_id=:modid AND `class`='lemma' AND ts.source='auto'";
        $stmt = $this->dbo->prepare($qstr);
        $stmt->execute(array(':modid' => $linenum));
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $suggestions[] = array("id" => $row['tag_id'], "v" => $row['value'], "t" => "s");
        }
        return $suggestions;
    }

    public function getLemmaSuggestionFromIdenticalAscii($linenum) {
        $suggestions = array();
        $errortypes = $this->getErrorTypes();
        if (array_key_exists('lemma verified', $errortypes)) {
            $lemma_verified = $errortypes['lemma verified'];
            $paa = $this->getProjectAndAscii($linenum);
            if ($paa && !empty($paa)) {
                $line_ascii = $paa['ascii'];
                $line_project = $paa['project_id'];
                $qstr = "SELECT tag.id, tag.value, ts.id AS ts_id "
                      . "     FROM   tag "
                      . "       LEFT JOIN tagset ON tag.tagset_id=tagset.id "
                      . "       LEFT JOIN tag_suggestion ts ON ts.tag_id=tag.id "
                      . "       LEFT JOIN modern ON modern.id=ts.mod_id "
                      . "       LEFT JOIN token ON modern.tok_id=token.id "
                      . "       LEFT JOIN text ON token.text_id=text.id "
                      . "       LEFT JOIN mod2error ON mod2error.mod_id=modern.id "
                      . "     WHERE  mod2error.error_id=:errid "
                      . "        AND modern.ascii=:ascii "
                      . "        AND text.project_id=:projectid "
                      . "        AND tagset.class='lemma' "
                      . "        AND ts.selected=1 ";
                $stmt = $this->dbo->prepare($qstr);
                $stmt->execute(array(':errid' => $lemma_verified,
                                     ':ascii' => $line_ascii,
                                     ':projectid' => $line_project));
                $processed_lemmas = array();
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    if (!in_array($row['value'], $processed_lemmas)) {
                        $suggestions[] = array("id" => $row['id'], "v" => $row['value'], "t" => "c");
                        $processed_lemmas[] = $row['value'];
                    }
                }
            }
        }
        return $suggestions;
    }

    public function getLemmaSuggestionFromQueryString($q, $fileid, $limit) {
        $suggestions = array();
        if (strlen($q) > 0) {
            // strip ID for the search, if applicable
            $q = preg_replace('/ \[.*\]$/', '', $q);
            $tslist = $this->getTagsetsForFile($fileid);
            $tsid = 0;
            foreach ($tslist as $tagset) {
                if ($tagset['class'] == "lemma_sugg") {
                    $tsid = $tagset['id'];
                }
            }
            if ($tsid && $tsid != 0) {
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
        // get automatic suggestions stored with the line number
        $sugg1 = $this->getLemmaSuggestionFromLineNumber($linenum);
        // get confirmed selected lemmas from tokens with identical simplification
        $sugg2 = $this->getLemmaSuggestionFromIdenticalAscii($linenum);
        // get lemma matches for query string
        $sugg3 = $this->getLemmaSuggestionFromQueryString($q, $fileid, $limit);
        return array_merge($sugg1, $sugg2, $sugg3);
    }

    /** Check for current notices.
     *
     * Returns an array of all notices which have not yet expired, and
     * deletes those that have.
     *
     * @return An array of notices
     */
    public function checkForNotices() {
        $stmt = $this->dbo->prepare("SELECT `id`, `text`, `type` FROM notices "
                                    . "WHERE `expires` >= NOW()");
        $stmt->execute();
        $notices = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $this->dbo->exec("DELETE FROM notices WHERE `expires` < NOW()");
        return $notices;
    }

    /** Get all notices.
     *
     * Returns an array of all notices including expiry date.
     *
     * @return An array of notices
     */
    public function getAllNotices() {
        $stmt = $this->dbo->prepare("SELECT `id`, `text`, `type`, `expires` FROM notices");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Add a notice.
     *
     * @param string $type Type of the notice
     * @param string $text Notice text
     * @param string $expires Expiry date of the notice
     *
     * @return 1 if successful, 0 otherwise
     */
    public function addNotice($type, $text, $expires) {
        $stmt = $this->dbo->prepare("INSERT INTO `notices` "
                                    . "(`type`, `text`, `expires`) "
                                    . "VALUES (:type, :text, :expires)");
        $stmt->execute(array(':type' => $type, ':text' => $text, ':expires' => $expires));
        return $stmt->rowCount();
    }

    /** Delete a notice.
     */
    public function deleteNotice($id) {
        $stmt = $this->dbo->prepare("DELETE FROM notices WHERE `id`=?");
        $stmt->execute(array($id));
        return $stmt->rowCount();
    }
}
?>
