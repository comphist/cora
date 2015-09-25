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
 */ ?>
<?php

 /** @file ProjectAccessor.php
  * Functions related to managing projects.
  *
  * @author Marcel Bollmann
  * @date September 2014
  */

/** Manages access to projects, their settings, their associated
 *  documents, etc.
 */
class ProjectAccessor {
  protected $dbi; /**< DBInterface object to use for queries */
  protected $dbo; /**< PDO object to use for own queries */

  // SQL statements that are potentially used multiple times
  private $stmt_getUsers = null;
  private $stmt_getDefaults = null;
  private $stmt_setDefaults = null;
  private $stmt_deleteDefaults = null;
  private $stmt_getSettings = null;
  private $stmt_setSettings = null;
  private $stmt_getFiles = null;
  private $stmt_getFilesFull = null;
  private $stmt_getTagsetLinks = null;

  /** Construct a new ProjectAccessor.
   *
   * @param DBInterface $parent A DBInterface object to use for queries
   * @param PDO $dbo A PDO database object passed from DBInterface
   */
  function __construct($parent, $dbo) {
    $this->dbi = $parent;
    $this->dbo = $dbo;
    $this->prepareAllStatements();
  }

  /**********************************************
   ********* SQL Statement Preparations *********
   **********************************************/
  private function prepareAllStatements() {
      $query = "SELECT `id`, `name` FROM `users` "
          . "LEFT JOIN `user2project` ON user2project.user_id=users.id "
          . "    WHERE user2project.project_id=:pid";
      $this->stmt_getUsers = $this->dbo->prepare($query);
      $query = "SELECT `user_id` FROM `user2project` WHERE `project_id`=:pid";
      $this->stmt_getUsersID = $this->dbo->prepare($query);
      $query = "INSERT INTO `user2project` (`project_id`, `user_id`) "
          . "VALUES (:pid, :uid)";
      $this->stmt_setUsers = $this->dbo->prepare($query);
      $query = "DELETE FROM `user2project` WHERE `project_id`=:pid";
      $this->stmt_deleteUsers = $this->dbo->prepare($query);
      $query = "SELECT `tagset_id` FROM `text2tagset_defaults` "
          . "    WHERE text2tagset_defaults.project_id=:pid";
      $this->stmt_getDefaults = $this->dbo->prepare($query);
      $query = "INSERT INTO `text2tagset_defaults` "
          . "   (`project_id`, `tagset_id`) VALUES (:pid, :tid)";
      $this->stmt_setDefaults = $this->dbo->prepare($query);
      $query = "DELETE FROM `text2tagset_defaults` WHERE `project_id`=:pid";
      $this->stmt_deleteDefaults = $this->dbo->prepare($query);
      $query = "SELECT `cmd_edittoken`, `cmd_import` FROM `project` "
          . "    WHERE `id`=:pid";
      $this->stmt_getSettings = $this->dbo->prepare($query);
      $query = "UPDATE `project` SET `cmd_edittoken`=:cmdedit, "
          . "                        `cmd_import`=:cmdimport   "
          . "    WHERE `id`=:pid";
      $this->stmt_setSettings = $this->dbo->prepare($query);
      $query = "SELECT `id`, `sigle`, `fullname` FROM `text`"
          . "    WHERE `project_id`=:pid";
      $this->stmt_getFiles = $this->dbo->prepare($query);
      $query = "SELECT text.id, text.sigle, text.fullname, "
          . "   text.created, text.creator_id, creator.name AS creator_name,"
          . "   text.changed, text.changer_id, changer.name AS changer_name,"
          . "   text.currentmod_id, opener.name AS opened "
          . "        FROM `text` "
          . "   LEFT JOIN `locks`   ON text.id=locks.text_id "
          . "   LEFT JOIN `users`   opener  ON locks.user_id=opener.id "
          . "   LEFT JOIN `users`  creator  ON text.creator_id=creator.id "
          . "   LEFT JOIN `users`  changer  ON text.changer_id=changer.id "
          . "       WHERE text.project_id=:pid "
          . "   ORDER BY  text.sigle, text.fullname ASC";
      $this->stmt_getFilesFull = $this->dbo->prepare($query);
      $query = "SELECT `tagset_id` FROM `text2tagset`"
          . "    WHERE `text_id`=:tid";
      $this->stmt_getTagsetLinks = $this->dbo->prepare($query);
  }

  /**********************************************/

  /** Fetch a list of all available projects.
   *
   * @param string $userid Restrict list to projects associated with the
   *                       given user ID; defaults to null, which returns
   *                       all projects (e.g. for admin access)
   *
   * @return An array containing projects, as arrays with keys (id, name)
   */
  public function getAllProjects($userid=null) {
      $query = "SELECT `id`, `name` FROM `project`";
      $param = array();
      if(!is_null($userid)) {
          $query .= " LEFT JOIN `user2project` "
              . "            ON project.id=user2project.project_id"
              . "         WHERE user2project.user_id=:userid";
          $param = array(':userid' => $userid);
      }
      $query .= " ORDER BY `name` ASC";
      $stmt = $this->dbo->prepare($query);
      $stmt->execute($param);
      return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  /** Fetch a list of users associated with a given project.
   *
   * @param string $pid The project ID
   * @return An array containing users, as arrays with keys (id, name)
   */
  public function getAssociatedUsers($pid) {
      $this->stmt_getUsers->execute(array(':pid' => $pid));
      return $this->stmt_getUsers->fetchAll(PDO::FETCH_ASSOC);
  }

  /** Fetch a list of users associated with a given project.
   *
   * @param string $pid The project ID
   * @return An array of user IDs
   */
  public function getAssociatedUserIDs($pid) {
      $this->stmt_getUsersID->execute(array(':pid' => $pid));
      return $this->stmt_getUsersID->fetchAll(PDO::FETCH_COLUMN);
  }

  /** Set a new list of users associated with a given project.
   *
   * @param string $pid The project ID
   * @param array $idlist Array of user IDs
   */
  public function setAssociatedUsers($pid, $idlist) {
      try {
          $this->dbo->beginTransaction();
          $this->stmt_deleteUsers->execute(array(':pid' => $pid));
          $this->stmt_setUsers->bindValue(':pid', $pid, PDO::PARAM_INT);
          $this->stmt_setUsers->bindParam(':uid', $uid, PDO::PARAM_INT);
          foreach ($idlist as $uid) {
              $this->stmt_setUsers->execute();
          }
          $this->dbo->commit();
      }
      catch(PDOException $ex) {
          $this->dbo->rollBack();
          throw $ex;
      }
  }

  /** Fetch the default tagset links for a given project.
   *
   * @param string $pid The project ID
   * @return An array of tagset IDs
   */
  public function getAssociatedTagsetDefaults($pid) {
      $this->stmt_getDefaults->execute(array(':pid' => $pid));
      return $this->stmt_getDefaults->fetchAll(PDO::FETCH_COLUMN);
  }

  /** Save new default tagset links for a given project.
   *
   * @param string $pid The project ID
   * @param array $tslist Array of tagset IDs
   */
  public function setAssociatedTagsetDefaults($pid, $tslist) {
      try {
          $this->dbo->beginTransaction();
          $this->stmt_deleteDefaults->execute(array(':pid' => $pid));
          $this->stmt_setDefaults->bindValue(':pid', $pid, PDO::PARAM_INT);
          $this->stmt_setDefaults->bindParam(':tid', $tid, PDO::PARAM_INT);
          foreach ($tslist as $tid) {
              $this->stmt_setDefaults->execute();
          }
          $this->dbo->commit();
      }
      catch(PDOException $ex) {
          $this->dbo->rollBack();
          throw $ex;
      }
  }

  /** Fetch a list of files associated with a given project.
   *
   * @param string $pid The project ID
   * @param boolean $full If false (default), returns only ID, sigle, and
   *                      fullname for each file; if true, additionally
   *                      retrieves creator/changer information,
   *                      currentmod_id, and who has currently opened the
   *                      file (if applicable)
   * @return An array containing file info, as associative arrays
   */
  public function getAssociatedFiles($pid, $full=false) {
      $stmt = $full ? $this->stmt_getFilesFull : $this->stmt_getFiles;
      $stmt->execute(array(':pid' => $pid));
      $files = $stmt->fetchAll(PDO::FETCH_ASSOC);
      if($full) {
          $stmt = $this->stmt_getTagsetLinks;
          foreach($files as &$file) {
              $stmt->execute(array(':tid' => $file['id']));
              $file['tagset_links'] = $stmt->fetchAll(PDO::FETCH_COLUMN);
          }
      }
      return $files;
  }

  /** Fetch project-specific settings.
   *
   * @param string $pid The project ID
   * @return An associative array of settings
   */
  public function getSettings($pid) {
      $this->stmt_getSettings->execute(array(':pid' => $pid));
      return $this->stmt_getSettings->fetch(PDO::FETCH_ASSOC);
  }

  /** Set project-specific settings.
   *
   * @param string $pid The project ID
   * @param string $cmdedit Command when editing tokens
   * @param string $cmdimport Command when importing texts
   */
  public function setSettings($pid, $cmdedit, $cmdimport) {
      $data = array(':pid' => $pid,
                    ':cmdedit' => $cmdedit,
                    ':cmdimport' => $cmdimport);
      $this->stmt_setSettings->execute($data);
  }

  /** Set project-specific settings.
   *
   * @param string $pid The project ID
   * @param array $settings An associative array of settings
   */
  public function setSettingsArray($pid, $settings) {
      $this->setSettings($pid,
                         $settings['cmd_edittoken'],
                         $settings['cmd_import']);
  }
}
