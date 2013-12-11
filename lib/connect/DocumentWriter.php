<?php

 /** @file DocumentWriter.php
  * Functions related to saving changes to documents.
  *
  * @author Marcel Bollmann
  * @date December 2013
  */

require_once('DocumentAccessor.php');

/** Handles saving changes to a document.
 */
class DocumentWriter extends DocumentAccessor {
  protected $flagtypes;

  /* Prepared SQL statements that are typically called multiple times
     during one save process. */
  private $stmt_deleteTag = null;
  private $stmt_insertTag = null;
  private $stmt_updateTag = null;
  private $stmt_insertTS = null;
  private $stmt_deleteTS = null;
  private $stmt_deselectTS = null;
  private $stmt_insertFlag = null;
  private $stmt_deleteFlag = null;
  private $stmt_insertComm = null;
  private $stmt_deleteComm = null;

  /** Construct a new DocumentWriter.
   *
   * Prepares to make changes to an existing document.  Retrieves
   * information (e.g., associated tagsets) about the file to be
   * modified during construction.  A different DocumentWriter object
   * should be created for each file to be modified.
   *
   * @param DBInterface $parent A DBInterface object to use for queries
   * @param PDO $dbo A PDO database object passed from DBInterface
   * @param string $fileid ID of the file to be modified
   */
  function __construct($parent, $dbo, $fileid) {
    parent::__construct($parent, $dbo, $fileid);

    $this->flagtypes = $this->dbi->getErrorTypes();
    $this->retrieveTagsetInformation();
    $this->prepareAllStatements();
  }

  /**********************************************
   ********* SQL Statement Preparations *********
   **********************************************/

  private function prepareAllStatements() {
    $stmt = "DELETE FROM tag WHERE `id`=:id";
    $this->stmt_deleteTag  = $this->dbo->prepare($stmt);
    $stmt = "DELETE FROM tag_suggestion WHERE `id`=:id";
    $this->stmt_deleteTS   = $this->dbo->prepare($stmt);
    $stmt = "UPDATE tag_suggestion SET `selected`=0 WHERE `id`=:id";
    $this->stmt_deselectTS = $this->dbo->prepare($stmt);
    $stmt = "INSERT INTO tag (`value`, `needs_revision`, `tagset_id`)"
      . "    VALUES (:value, :needrev, :tagset)";
    $this->stmt_insertTag  = $this->dbo->prepare($stmt);
    $stmt = "INSERT INTO tag_suggestion "
      . "      (`id`, `selected`, `source`, `tag_id`, `mod_id`) VALUES "
      . "      (:id,  :selected,  :source,  :tagid,   :modid) "
      . "    ON DUPLICATE KEY UPDATE `selected`=VALUES(selected), "
      . "                            `tag_id`=VALUES(tag_id)";
    $this->stmt_insertTS   = $this->dbo->prepare($stmt);
    $stmt = "UPDATE tag SET `value`=:value WHERE `id`=:id";
    $this->stmt_updateTag  = $this->dbo->prepare($stmt);
    $stmt = "INSERT IGNORE INTO mod2error (`mod_id`, `error_id`)"
      . "    VALUES (:modid, :flagid)";
    $this->stmt_insertFlag = $this->dbo->prepare($stmt);
    $stmt = "DELETE FROM mod2error WHERE `mod_id`=:modid AND `error_id`=:flagid";
    $this->stmt_deleteFlag = $this->dbo->prepare($stmt);
    $stmt = "INSERT INTO comment"
      . "           (`id`, `tok_id`, `value`, `comment_type`, `subtok_id`)"
      . "    VALUES (:id,  :tokid,   :value,  'C',            :subtokid)"
      . "    ON DUPLICATE KEY UPDATE `value`=VALUES(value)";
    $this->stmt_insertComm = $this->dbo->prepare($stmt);
    $stmt = "DELETE FROM comment WHERE `id`=:id";
    $this->stmt_deleteComm = $this->dbo->prepare($stmt);
  }

  /**********************************************/

  /** Preload a tagset.
   *
   * Retrieves all tags for a given tagset and stores them for future
   * reference.
   *
   * OVERRIDE: for writing, fetch tagset as key/value pairs
   */
  protected function preloadTagset($tagset) {
    $tags = $this->dbi->getTagsetByValue($tagset['id']);
    $this->tagsets[$tagset['class']]['tags'] = $tags;
  }

  /** Check whether all mod IDs belong to the associated file.
   */
  private function checkIDValidity($lines) {
    foreach($lines as $line) {
      if(!$this->isValidModID($line['id'])) {
	throw new DocumentAccessViolation("Invalid mod ID found: {$line['id']}");
      }
    }
  }

  /** Map old key names to new ones.
   *
   * TODO: Should be refactored in the client code so this is no
   * longer necessary. This is a fundamental change that affects many
   * functions, though, which is why this temporary function exists
   * for now.
   */
  private function temporary__renameLineKeys(&$line) {
    if(array_key_exists('anno_mod', $line)) {
      $line['anno_norm_broad'] = $line['anno_mod'];
      unset($line['anno_mod']);
    }
    if(array_key_exists('anno_modtype', $line)) {
      $line['anno_norm_type'] = $line['anno_modtype'];
      unset($line['anno_modtype']);
    }
    if(array_key_exists('anno_morph', $line)) {
      if(array_key_exists('anno_POS', $line)
	 && !empty($line['anno_morph'])
	 && $line['anno_morph'] != "--") {
	$line['anno_POS'] .= "." . $line['anno_morph'];
      }
      unset($line['anno_morph']);
    }
    if(array_key_exists('general_error', $line)) {
      $line['flag_general_error'] = $line['general_error'];
      unset($line['general_error']);
    }
    if(array_key_exists('lemma_verified', $line)) {
      $line['flag_lemma_verified'] = $line['lemma_verified'];
      unset($line['lemma_verified']);
    }
  }

  /** Remove a selected annotation for a given mod.
   */
  private function removeAnnotation($selected, $annoclass, $openset) {
    if($openset) {
      $this->stmt_deleteTS->execute(array(':id' => $selected['id']));
      $this->stmt_deleteTag->execute(array(':id' => $selected['tag_id']));
    }
    else {
      if($selected['source'] == "auto") {
	$this->stmt_deselectTS->execute(array(':id' => $selected['id']));
      } else {
	$this->stmt_deleteTS->execute(array(':id' => $selected['id']));
      }
    }
  }

  /** Update an annotation of an open class tagset.
  */
  private function updateOpenClassAnnotation($modid, $selected, $annoclass, $value) {
    if(empty($selected)) {
      $this->stmt_insertTag->execute(array(':value' => $value,
					   ':tagset' => $this->tagsets[$annoclass]['id'],
					   ':needrev' => 0));
      $newid = $this->dbo->lastInsertId();
      $this->stmt_insertTS->execute(array(':id' => NULL,
					  ':selected' => 1,
					  ':source' => 'user',
					  ':tagid' => $newid,
					  ':modid' => $modid));
    }
    else {
      $this->stmt_updateTag->execute(array(':id' => $selected['tag_id'],
					   ':value' => $value));
    }
  }

  /** Update an annotation of a closed class tagset.
  */
  private function updateClosedClassAnnotation($modid, $selected, $annoclass, $tagid) {
    if(!empty($selected)) {
      if($tagid == $selected['tag_id']) return; // nothing to change
      $this->removeAnnotation($selected, $annoclass, false);
    }
    $this->stmt_insertTS->execute(array(':id' => NULL,
					':selected' => 1,
					':source' => 'user',
					':tagid' => $tagid,
					':modid' => $modid));
  }

  /** Save an annotation for a given mod.
   *
   * @param string $modid A mod ID
   * @param array $selected Array with currently selected annotation
   * @param string $annoclass Tagset class for the annotation
   * @param string $value Value of the new annotation
   */
  protected function saveAnnotation($modid, $selected, $annoclass, $value) {
    if(!array_key_exists($annoclass, $this->tagsets)) {
      $this->warn("Skipping unknown annotation class '{$annoclass}' for mod {$modid}.");
      return;
    }

    $openset = ($this->tagsets[$annoclass]['set_type'] == "open");
    if(empty($value)) {
      if(!empty($selected)) {
	$this->removeAnnotation($selected, $annoclass, $openset);
      }
    }
    else if($openset) {
      $this->updateOpenClassAnnotation($modid, $selected, $annoclass, $value);
    }
    else {
      if(!array_key_exists($value, $this->tagsets[$annoclass]['tags'])) {
	$this->warn("Skipping illegal {$annoclass} tag '{$value}'.");
	return;
      }
      $tagid = $this->tagsets[$annoclass]['tags'][$value];
      $this->updateClosedClassAnnotation($modid, $selected, $annoclass, $tagid);
    }
  }

  /** Save flag for a given mod.
   *
   * @param string $modid A mod ID
   * @param string $flagtype Name of the flag
   * @param string $value Value of the flag
   */
  protected function saveFlag($modid, $flagtype, $value) {
    if(!array_key_exists($flagtype, $this->flagtypes)) {
      $this->warn("Skipping unknown flag type '{$flagtype}' for mod {$modid}.");
      return;
    }

    $param = array(':modid' => $modid,
		   ':flagid' => $this->flagtypes[$flagtype]);
    if(intval($value) == 1) {
      $this->stmt_insertFlag->execute($param);
    }
    else {
      $this->stmt_deleteFlag->execute($param);
    }
  }

  /** Save CorA-internal comment for a given mod.
   *
   * @param string $modid A mod ID
   * @param string $value Comment text to save
   */
  protected function saveComment($modid, $value) {
    $old_comment = $this->getCoraComment($modid);
    if(empty($value)) {
      if(!empty($old_comment['comment_id'])) {
	$this->stmt_deleteComm->execute(array(':id' => $old_comment['comment_id']));
      }
    }
    else {
      /* -- If there already is a comment, the 'ON DUPLICATE KEY
	 UPDATE' clause will ensure that it is overwritten.
	 -- If no comment was previously set, $old_comment will still
	 provide the 'token_id', while 'comment_id' being NULL will
	 cause a new database record to be inserted.
      */
      $param = array(':id' => $old_comment['comment_id'],
		     ':tokid' => $old_comment['token_id'],
		     ':value' => $value,
		     ':subtokid' => $modid);
      $this->stmt_insertComm->execute($param);
    }
  }

  /** Save modified lines to the database.
   *
   * Should probably never be called outside of @c saveLines.
   */
  private function saveLinesToDatabase($lines) {
    foreach($lines as $line) {
      $this->temporary__renameLineKeys($line);
      
      $id = $line['id'];
      $annotations = $this->getSelectedAnnotationsByClass($id);
      foreach($line as $property => $value) {
	// save annotations
	if(substr($property, 0, 5) === "anno_") {
	  $annoclass = substr($property, 5);
	  $selected  = array_key_exists($annoclass, $annotations) ?
	    $annotations[$annoclass] : null;
	  $this->saveAnnotation($id, $selected, $annoclass, $value);
	}
	// save flags
	else if(substr($property, 0, 5) === "flag_") {
	  $flagtype = str_replace("_", " ", substr($property, 5));
	  $this->saveFlag($id, $flagtype, $value);
	}	
	// save comment
	else if($property === "comment") {
	  $this->saveComment($id, $value);
	}
      }
    }
  }

  /** Save modified lines to the associated file.
   *
   * Checks the validity of the given mod IDs, then applies the
   * appropriate changes to the database.  Wraps @c
   * saveLinesToDatabase in a transaction that is rolled back if an
   * exception occurs.
   */
  public function saveLines($lines) {
    $this->checkIDValidity($lines);

    $this->dbo->beginTransaction();
    try {
      $this->saveLinesToDatabase($lines);
    }
    catch(Exception $ex) {
      $this->dbo->rollBack();
      throw $ex;
    }
    $this->dbo->commit();
    //$this->dbo->rollBack();
  }

}