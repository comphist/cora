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

/** @file TagsetAccessor.php
 *
 * @author Marcel Bollmann
 * @date July 2015
 */

/** Handles extended tagset access such as import or export.
 */
class TagsetAccessor {
  protected $dbo; /**< PDO object to use for own queries */
  protected $id;  /**< ID of the associated tagset */

  protected $name; /**< Name of the associated tagset */
  protected $tsclass; /**< Class of the associated tagset */
  protected $settype; /**< Set type (open,closed) of the associated tagset */
  protected $tags_by_value = array(); /**< List of tags */

  public $check_pos = true; /**< Whether to apply integrity checks to POS tags */

  protected $has_changed = false; /**< Whether changes have been made */
  protected $errors = array(); /**< Messages of errors that occured */

  // SQL statements
  private $stmt_insertTag = null;
  private $stmt_deleteTag = null;
  private $stmt_updateTag = null;
  private $stmt_checkTagLinks = null;

  /** Construct a new TagsetAccessor.
   *
   * @param PDO $dbo A PDO database object to use for queries
   * @param string $tagset_id ID of the tagset to be accessed
   */
  function __construct($dbo, $tagset_id) {
    $this->dbo = $dbo;
    $this->id = $tagset_id;
    $this->prepareStatements();
    if ($this->id !== null)
      $this->loadTagset();
  }

  /**********************************************
   ********* SQL Statement Preparations *********
   **********************************************/

  private function prepareStatements() {
    $stmt = "SELECT COUNT(*) FROM tag_suggestion WHERE `tag_id`=:id";
    $this->stmt_checkTagLinks = $this->dbo->prepare($stmt);
  }

  // statements that are only needed when changes are commited
  private function prepareCommitStatements() {
    $stmt = "INSERT INTO tag (`value`, `needs_revision`, `tagset_id`) "
          . "VALUES (:value, :needsrev, :tagset) ";
    $this->stmt_insertTag = $this->dbo->prepare($stmt);
    $stmt = "DELETE FROM tag WHERE `id`=:id";
    $this->stmt_deleteTag = $this->dbo->prepare($stmt);
    $stmt = "UPDATE tag SET `value`=:value, `needs_revision`=:needsrev "
          . " WHERE `id`=:id";
    $this->stmt_updateTag = $this->dbo->prepare($stmt);
  }

  /**********************************************
   ********* Private/protected functions ********
   **********************************************/

  private function convertNeedsRev($value) {
    if ($value === true || $value === '1' || $value === 1)
      return 1;
    return 0;
  }

  private function splitPOS($value) {
    if (substr($value, -1) === '.' && substr_count($value, '.') === 1) {
      return array($value);
    }
    return explode('.', $value);
  }

  protected function error($message) {
    $this->errors[] = $message;
    return false;
  }

  /** Retrieves tagset information from the database. */
  protected function loadTagset() {
    // fetch metadata
    $stmt = $this->dbo->prepare("SELECT `name`, `set_type`, `class` FROM tagset "
                                . "WHERE `id`=?");
    $stmt->execute(array($this->id));
    $metadata = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$metadata) {  // illegal ID
      return $this->error("Couldn't find tagset with ID {$this->id}");
    }
    $this->name = $metadata['name'];
    $this->tsclass = strtolower($metadata['class']);
    $this->settype = strtolower($metadata['set_type']);
    // fetch tag list
    $stmt = $this->dbo->prepare("SELECT `id`, `value`, `needs_revision` "
                                . "FROM tag WHERE `tagset_id`=?");
    $stmt->execute(array($this->id));
    while ($tag = $stmt->fetch(PDO::FETCH_ASSOC)) {
      $this->tags_by_value[$tag['value']] = $tag;
    }
  }

  protected function executeCommitChanges() {
    $this->prepareCommitStatements();
    foreach ($this->tags_by_value as $value => $tag) {
      if (!isset($tag['status']))
        continue;
      $status = $tag['status'];
      if ($status === 'new') {
        $this->stmt_insertTag->execute(array(':value' => $tag['value'],
                                             ':needsrev' => $tag['needs_revision'],
                                             ':tagset' => $this->id));
      }
      else if ($status === 'delete') {
        $this->stmt_deleteTag->execute(array(':id' => $tag['id']));
      }
      else if ($status === 'update') {
        $this->stmt_updateTag->execute(array(':id' => $tag['id'],
                                             ':value' => $tag['value'],
                                             ':needsrev' => $tag['needs_revision']));
      }
    }
  }

  protected function checkPOSConsistency() {
    if (!$this->check_pos || $this->tsclass != 'pos')
      return true;
    $feature_count = array();
    $consistent = true;
    foreach ($this->tags_by_value as $value => $tag) {
      if (isset($tag['status']) && $tag['status'] === 'delete')
        continue;  // do not look at deleted tags
      if (isset($tag['needs_revision']) && $tag['needs_revision'] == 1)
        continue;  // do not look at "illegal" tags either
      $parts = $this->splitPOS($value);
      foreach ($parts as $part) {
        if (strlen($part) < 1) {
          $this->error("POS tag has empty attributes: {$value}");  //$LOCALE
          $consistent = false;
          break;
        }
      }
      if (isset($feature_count[$parts[0]])) {
        if ($feature_count[$parts[0]] !== count($parts)) {
          $old = $feature_count[$parts[0]];
          $new = count($parts);
          $this->error("POS tag has inconsistent attribute count "
                      . "(now {$new}, expected {$old}): {$value}"); //$LOCALE
          $consistent = false;
        }
      }
      else {
        $feature_count[$parts[0]] = count($parts);
      }
    }
    return $consistent;
  }

  /**********************************************
   ********* Public functions *******************
   **********************************************/

  public function getErrors() {
    return $this->errors;
  }

  public function hasErrors() {
    return count($this->errors) > 0;
  }

  public function count() {
    return count($this->tags_by_value);
  }

  public function contains($value) {
    return isset($this->tags_by_value[$value]);
  }

  public function getID() { return $this->id; }
  public function getName() { return $this->name; }
  public function getSetType() { return $this->settype; }
  public function getClass() { return $this->tsclass; }

  public function getTag($value) {
    if ($this->contains($value))
      return $this->tags_by_value[$value];
    return null;
  }

  public function needsCommit() {
    return $this->has_changed;
  }

  /** Return a list of all tags.
   *
   * @return An associative array containing all tags of this tagset.
   *         The array maps tag values to an array with more tag info,
   *         containing at least the keys 'id', 'value', and
   *         'needs_revision'.
   */
  public function entries() {
    return $this->tags_by_value;
  }

  /** Checks a tag value for validity.
   *
   * @return A boolean value indicating if the tag is valid or not.
   *         If it's not, the reason for the failed check will be stored
   *         as the last element of TagsetAccessor::getErrors().
   */
  public function checkTag($value) {
    if (strlen($value) > 255) {
      return $this->error("Tag is longer than 255 characters: {$value}");  //$LOCALE
    }
    return true;
  }

  /** Add a new tag to the tagset.
   *
   * @param string $value The new tag value
   * @param $needs_rev Whether the tag should be flagged as needing revision;
   *        if not given, defaults to false.
   *
   * @return True if tag was successfully added, false otherwise.
   */
  public function addTag($value, $needs_rev=false) {
    $value = trim($value);
    if (empty($value) || !$this->checkTag($value)) return false;
    if ($this->contains($value)) {
      if (isset($this->tags_by_value[$value]['status'])
          && $this->tags_by_value[$value]['status'] === 'delete') {
        $this->tags_by_value[$value]['status'] = 'update';
      }
      return $this->setRevisionFlagForTag($value, $needs_rev);
    }
    $needs_rev = $this->convertNeedsRev($needs_rev);
    $tag = array('value' => $value,
                 'needs_revision' => $needs_rev,
                 'status' => 'new');
    $this->tags_by_value[$value] = $tag;
    $this->has_changed = true;
    return true;
  }

  /** Set the 'needs_revision' flag for a tag.
   */
  public function setRevisionFlagForTag($value, $needs_rev) {
    if (!isset($this->tags_by_value[$value])) {
      return $this->error("Tried to change non-existing tag: {$value}");  //$LOCALE
    }
    $tag = &$this->tags_by_value[$value];
    if (isset($tag['status'])) {
      if ($tag['status'] === 'delete') {
        return $this->error("Tried to change already-deleted tag: {$value}");  //$LOCALE
      }
    }
    else {
      $tag['status'] = 'update';
    }
    $needs_rev = $this->convertNeedsRev($needs_rev);
    $tag['needs_revision'] = $needs_rev;
    $this->has_changed = true;
    return true;
  }

  /** Modifies a tag value in-place.
   */
  public function changeTag($value, $nvalue) {
    $nvalue = trim($nvalue);
    if (!isset($this->tags_by_value[$value])) {
      return $this->error("Tried to change non-existing tag: {$value}");  //$LOCALE
    }
    else if (isset($this->tags_by_value[$nvalue])) {
      return $this->error("Tried to change to already-existing tag: {$nvalue}");  //$LOCALE
    }
    else if ($value === $nvalue || empty($value)
             || empty($nvalue) || !$this->checkTag($nvalue)) {
      return false;
    }
    $tag = $this->tags_by_value[$value];
    if (isset($tag['status'])) {
      if ($tag['status'] === 'delete') {
        return $this->error("Tried to change already-deleted tag: {$value}");  //$LOCALE
      }
    }
    else {
      $tag['status'] = 'update';
    }
    $tag['value'] = $nvalue;
    $this->tags_by_value[$nvalue] = $tag;
    unset($this->tags_by_value[$value]);
    $this->has_changed = true;
    return true;
  }

  /** Delete a tag, or mark it as needing revision if it is currently in use.
   */
  public function deleteOrMarkTag($value) {
    if (!isset($this->tags_by_value[$value])) {
      $this->error("Tried to delete non-existing tag: {$value}");  //$LOCALEs
      return false;
    }
    $tag = &$this->tags_by_value[$value];
    if (isset($tag['status']) && $tag['status'] === 'new') {
      unset($this->tags_by_value[$value]);
      return true;
    }
    if ($this->settype === 'closed') {
      // closed-class tagsets can link to tag entries
      $this->stmt_checkTagLinks->execute(array(':id' => $tag['id']));
      if ($this->stmt_checkTagLinks->fetchColumn() > 0) {
        $this->setRevisionFlagForTag($value, true);
        return true;
      }
    }
    $tag['status'] = 'delete';
    $this->has_changed = true;
    return true;
  }

  /** Commits all changes to the database.
   */
  public function commitChanges() {
    if (!$this->needsCommit()) return true;
    if (!$this->checkPOSConsistency()) return false;
    try {
      $this->dbo->beginTransaction();
      $this->executeCommitChanges();
      $this->dbo->commit();
    } catch (DBOException $ex) {
      $this->dbo->rollBack();
      $this->error($ex->getMessage());
      return false;
    }
    return true;
  }
}

?>
