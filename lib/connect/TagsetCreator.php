<?php

/** @file TagsetCreator.php
 *
 * @author Marcel Bollmann
 * @date July 2015
 */

require_once('TagsetAccessor.php');

/** Handles creation of new tagsets.
 */
class TagsetCreator extends TagsetAccessor {

  /** Construct a new TagsetCreator.
   *
   * @param PDO $dbo A PDO database object to use for queries
   * @param string $cls Class of the new tagset
   * @param string $settype Set type of the new tagset (open,closed)
   * @param string $name Name of the new tagset
   */
  function __construct($dbo, $cls, $settype, $name) {
    parent::__construct($dbo, null);
    $this->tsclass = $cls;
    $this->settype = $settype;
    $this->name = $name;
  }

  /** Add a list of tags.
   *
   * Leading circumflex (^) will be interpreted as marking the tag in question
   * as 'needing revision'.  If this is not desired, add the tags individually
   * using TagsetAccessor::addTag().
   */
  public function addTaglist($taglist) {
    foreach ($taglist as $tag) {
      $tag = trim($tag);
      if (empty($tag)) continue;
      if (substr($tag, 0, 1) === '^') {
        $value = substr($tag, 1);
        $needs_rev = true;
      }
      else {
        $value = $tag;
        $needs_rev = false;
      }
      $this->addTag($value, $needs_rev);
    }
  }

  protected function executeCommitChanges() {
    $stmt = "INSERT INTO tagset (`name`, `set_type`, `class`) "
          . "VALUES (:name, :settype, :class)";
    $data = array(':name' => $this->name,
                  ':settype' => $this->settype,
                  ':class' => $this->tsclass);
    $stmt_createTagset = $this->dbo->prepare($stmt);
    $stmt_createTagset->execute($data);
    $this->id = $this->dbo->lastInsertId();
    parent::executeCommitChanges();
  }

}

?>
