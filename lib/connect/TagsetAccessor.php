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
  protected $feature_count = array(); /**< Number of morphological features per
                                           POS tag; only used when $check_pos
                                           is true. */

  protected $has_changed = false; /**< Whether changes have been made */
  protected $errors = array(); /**< Messages of errors that occured */

  // SQL statements
  private $stmt_insertTag = null;

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

  protected function error($message) {
    $this->errors[] = $message;
  }

  public function getErrors() {
    return $this->errors;
  }

  public function hasErrors() {
    return count($this->errors) > 0;
  }

  /**********************************************
   ********* SQL Statement Preparations *********
   **********************************************/

  private function prepareStatements() {
    $stmt = "INSERT INTO tag (`value`, `needs_revision`, `tagset_id`) "
          . "VALUES (:value, :needsrev, :tagset) ";
    $this->stmt_insertTag = $this->dbo->prepare($stmt);
  }

  /**********************************************/

  /** Retrieves tagset information from the database. */
  protected function loadTagset() {
    // fetch metadata
    $stmt = $this->dbo->prepare("SELECT name, set_type, class FROM tagset "
                                . "WHERE id=?");
    $stmt->execute(array($this->id));
    $metadata = $stmt->fetch(PDO::FETCH_ASSOC);
    $this->name = $metadata['name'];
    $this->tsclass = strtolower($metadata['class']);
    $this->settype = strtolower($metadata['set_type']);
    // fetch tag list
    $stmt = $this->dbo->prepare("SELECT id, value, needs_revision "
                                . "FROM tag WHERE tagset_id=?");
    $stmt->execute(array($this->id));
    while ($tag = $stmt->fetch(PDO::FETCH_ASSOC)) {
      $this->tags_by_value[$tag['value']] = $tag;
    }
    // gather POS integrity data, if necessary
    if ($this->tsclass === 'pos' && $this->check_pos) {
      foreach($this->tags_by_value as $value => $tag) {
        $parts = $this->splitPOS($value);
        $this->feature_count[$parts[0]] = count($parts);
      }
    }
  }

  private function splitPOS($value) {
    if (substr($value, -1) === '.' && substr_count($value, '.') === 1) {
      return array($value);
    }
    return explode('.', $value);
  }

  /** Checks a tag value for validity.
   *
   * @return A boolean value indicating if the tag is valid or not.
   *         If it's not, the reason for the failed check will be stored
   *         as the last element of TagsetAccessor::getErrors().
   */
  public function checkTag($value) {
    if (strlen($value) > 255) {
      $this->error("Tag is longer than 255 characters: {$value}");
      return false;
    }
    if (isset($this->tags_by_value[$value])) {
      $this->error("Tag already exists: {$value}");
      return false;
    }
    // only for POS:
    if ($this->tsclass === 'pos' && $this->check_pos) {
      $parts = $this->splitPOS($value);
      foreach ($parts as $part) {
        if (strlen($part) < 1) {
          $this->error("POS tag has empty attributes: {$value}");
          return false;
        }
      }
      if (isset($this->feature_count[$parts[0]])
          && $this->feature_count[$parts[0]] !== count($parts)) {
        $old = $this->feature_count[$parts[0]];
        $new = count($parts);
        $this->error("POS tag has inconsistent attribute count "
                    . "(now {$now}, expected {$old}): {$value}");
        return false;
      }
    }
    // passed
    return true;
  }

  /** Add a new tag to the tagset.
   *
   * @param string $value The new tag value
   * @param $needs_rev Whether the tag should be flagged as needing revision;
   *        if not given, defaults to false.
   */
  public function addTag($value, $needs_rev) {
    $value = trim($value);
    if (empty($value) || !$this->checkTag($value)) return;
    $needs_rev = ($needs_rev === true || $needs_rev === '1') ? '1' : '0';
    $tag = array('value' => $value,
                 'needs_revision' => $needs_rev,
                 'status' => 'new');
    $this->tags_by_value[$value] = $tag;
    $this->has_changed = true;
  }

  /** Commits all changes to the database.
   */
  public function commitChanges() {
    if (!$this->has_changed) return true;
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

  protected function executeCommitChanges() {
    foreach ($this->tags_by_value as $value => $tag) {
      if (!isset($tag['status']))
        continue;
      $status = $tag['status'];
      if ($status === 'new') {
        $data = array(':value' => $tag['value'],
                      ':needsrev' => $tag['needs_revision'],
                      ':tagset' => $this->id);
        $this->stmt_insertTag->execute($data);
      }
    }
  }

}

?>
