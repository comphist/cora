<?php

 /** @file SearchQuery.php
  * Functions related to searching within documents.
  *
  * @author Marcel Bollmann
  * @date December 2014
  */

class SearchQuery {
  protected $dbi; /**< DBInterface object to use for queries */
  protected $fileid;

  private $tagsets;
  private $flags;
  private $join_comment = false;
  private $condition_strings = array();
  public $condition_values = array();
  private $operator = " AND ";

  function __construct($parent, $fileid) {
    $this->dbi = $parent;
    $this->fileid = $fileid;
    $tslist = $parent->getTagsetsForFile($fileid); // or load "on-demand"?
    foreach($tslist as $ts) {
      $this->tagsets[$ts['class']] = $ts['id'];
    }
    $this->flags = $parent->getErrorTypes(); // or load "on-demand"?
  }

  /** Prepare and execute the search query.
   *
   * @param PDO $dbo The PDO object to use for the query
   * @return A PDOStatement object for the query
   */
  public function execute($dbo) {
    $stmt = $dbo->prepare($this->buildQueryString());
    $stmt->execute($this->condition_values);
    return $stmt;
  }

  public function buildQueryString() {
    $sqlstr = "SELECT modern.id FROM modern "
            . "  LEFT JOIN token ON token.id=modern.tok_id ";
    if($this->join_comment) {
      $sqlstr .= "LEFT JOIN comment ON comment.subtok_id=modern.id "
               . "                 AND comment.comment_type='C' ";
    }
    $sqlstr .= "      WHERE token.text_id={$this->fileid} AND ("
             . implode($this->operator, $this->condition_strings) . ")";
    return $sqlstr;
  }

  /** Add a new condition to the search query.
   *
   * @param string $field The field to search, e.g. "pos" or "token_all"
   * @param string $match The condition to use, e.g. "eq" or "regex"
   * @param string $value The value to search for
   */
  public function addCondition($field, $match, $value) {
    if(substr($field, 0, 6) === "token_") {
      $this->addTokenCondition($field, $match, $value);
    } else if(substr($field, 0, 5) === "flag_") {
      $this->addFlagCondition($field, $match);
    } else if($field === "comment") {
      $this->addCommentCondition($field, $match, $value);
    } else {
      $this->addTagsetCondition($field, $match, $value);
    }
  }

  protected function addTokenCondition($field, $match, $value) {
    list($operand, $newvalue) = $this->getOperandAndValue($match, $value);
    if($field === "token_trans") {
      $this->condition_strings[] = "modern.trans{$operand}?";
      $this->condition_values[] = $newvalue;
    } else if ($field === "token_all") {
      $this->condition_strings[] = "(modern.trans{$operand}? "
                                 . " OR modern.utf{$operand}? "
                                 . " OR modern.ascii{$operand}?)";
      array_push($this->condition_values, $newvalue, $newvalue, $newvalue);
    }
  }

  protected function addFlagCondition($field, $match) {
    $flagtype = str_replace("_", " ", substr($field, 5));
    if(array_key_exists($flagtype, $this->flags)) {
      $errorid = $this->flags[$flagtype];
      $flagvalue = ($match === "set") ? 'EXISTS' : 'NOT EXISTS';
      $sqlstr = "{$flagvalue} (SELECT * FROM mod2error f WHERE"
              . "              f.mod_id=modern.id AND f.error_id={$errorid})";
      $this->condition_strings[] = $sqlstr;
    }
  }

  protected function addCommentCondition($field, $match, $value) {
    $this->join_comment = true;
    list($operand, $newvalue) = $this->getOperandAndValue($match, $value);
    $this->condition_strings[] = "comment.value{$operand}?";
    $this->condition_values[] = $newvalue;
  }

  protected function addTagsetCondition($field, $match, $value) {
    if(array_key_exists($field, $this->tagsets)) {
      $tagsetid = $this->tagsets[$field];
      list($operand, $newvalue) = $this->getOperandAndValue($match, $value);
      $sqlstr = "EXISTS (SELECT * FROM tag_suggestion ts "
              . "        LEFT JOIN tag ON tag.id=ts.tag_id "
              . "        WHERE ts.mod_id=modern.id AND ts.selected=1 "
              . "          AND tag.tagset_id={$tagsetid} "
              . "          AND tag.value{$operand}?)";
      $this->condition_strings[] = $sqlstr;
      $this->condition_values[] = $newvalue;
    }
  }

  /** Translate match criterion + value into an SQL operand + value.
   */
  protected function getOperandAndValue($match, $value) {
    if($match === "eq") {
      return array("=", $value);
    } else if($match === "bgn") {
      return array(" LIKE ", $value."%");
    } else if($match === "end") {
      return array(" LIKE ", "%".$value);
    } else if($match === "in") {
      return array(" LIKE ", "%".$value."%");
    } else if($match === "regex") {
      return array(" REGEX ", $value);
    }
  }

  public function setOperator($op) {
    if($op === "all") {
      $this->operator = " AND ";
    } else if($op === "any") {
      $this->operator = " OR ";
    }
  }
}

?>
