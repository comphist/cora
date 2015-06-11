<?php

 /** @file SearchQuery.php
  * Functions related to searching within documents.
  *
  * @author Marcel Bollmann
  * @date December 2014
  */

/** Builds and executes a query to search within a document.
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
    // select all moderns in correct order, joining other info if required
    $sqlstr = "SELECT m.id FROM modern m"
              . "  LEFT JOIN token ON token.id=m.tok_id ";
    if($this->join_comment) {
      $sqlstr .= "LEFT JOIN comment ON comment.subtok_id=m.id "
                 . "                 AND comment.comment_type='C' ";
    }
    $sqlstr .= "      WHERE token.text_id={$this->fileid} AND ("
               . implode($this->operator, $this->condition_strings)
               . "    ) ORDER BY token.ordnr ASC, m.id ASC";
    return $sqlstr;
  }

  /** Add a new condition to the search query.
   *
   * @param string $field The field to search, e.g. "pos" or "token_all"
   * @param string $match The condition to use, e.g. "eq" or "regex"
   * @param string $value The value to search for
   */
  public function addCondition($field, $match, $value) {
    // empty value? -> existential query
    if(strlen($value) === 0 && $match !== "nset" && $match !== "set") {
      $match = ($match === "eq") ? "nset" : "set";
    }
    // delegate
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
    list($operand, $value) = $this->getOperandValue($match, $value);
    if($field === "token_trans") {
      $this->condition_strings[] = "m.trans{$operand}?";
      $this->condition_values[] = $value;
    } else if ($field === "token_all") {
      // substr(...) matcht neq, nin, nset
      $joiner = (substr($match, 0, 1) === "n") ? " AND " : " OR ";
      $layers = array("(m.trans{$operand}?",
                      "m.utf{$operand}?",
                      "m.ascii{$operand}?)");
      $this->condition_strings[] = implode($joiner, $layers);
      array_push($this->condition_values, $value, $value, $value);
    }
  }

  protected function addFlagCondition($field, $match) {
    $flagtype = str_replace("_", " ", substr($field, 5));
    if(array_key_exists($flagtype, $this->flags)) {
      $errorid = $this->flags[$flagtype];
      $flagvalue = ($match === "set") ? 'EXISTS' : 'NOT EXISTS';
      $sqlstr = "{$flagvalue} (SELECT * FROM mod2error f WHERE"
              . "              f.mod_id=m.id AND f.error_id={$errorid})";
      $this->condition_strings[] = $sqlstr;
    }
  }

  protected function addCommentCondition($field, $match, $value) {
    $this->join_comment = true;
    if($match === "set") {
      $this->condition_strings[] = "(comment.value IS NOT NULL AND comment.value!='')";
    } else if($match === "nset") {
      $this->condition_strings[] = "(comment.value IS NULL OR comment.value='')";
    } else {
      list($operand, $value) = $this->getOperandValue($match, $value);
      $this->condition_strings[] = "comment.value{$operand}?";
      $this->condition_values[] = $value;
    }
  }

  protected function addTagsetCondition($field, $match, $value) {
    if(array_key_exists($field, $this->tagsets)) {
      $tagsetid = $this->tagsets[$field];
      list($operand, $value, $ex) = $this->getOperandValueExists($match, $value);
      $sqlstr = "{$ex} (SELECT * FROM tag_suggestion ts "
              . "       LEFT JOIN tag ON tag.id=ts.tag_id "
              . "       WHERE ts.mod_id=m.id AND ts.selected=1 "
              . "         AND tag.tagset_id={$tagsetid} "
              . "         AND tag.value{$operand}?)";
      $this->condition_strings[] = $sqlstr;
      $this->condition_values[] = $value;
    }
  }

  /** Translate match criterion + value into an SQL operand + value.
   */
  protected function getOperandValue($match, $value) {
    switch ($match) {
      case "eq":
        return array("=", $value);
      case "neq":
        return array("!=", $value);
      case "in":
        return array(" LIKE ", "%".$this->escapeForLIKE($value)."%");
      case "nin":
        return array(" NOT LIKE ", "%".$this->escapeForLIKE($value)."%");
      case "bgn":
        return array(" LIKE ", $this->escapeForLIKE($value)."%");
      case "end":
        return array(" LIKE ", "%".$this->escapeForLIKE($value));
      case "regex":
        return array(" REGEXP ", $value);
      case "nset":
        return array("=", "");
      case "set":
      default:
        return array("!=", "");
    }
  }

  /** Translate match criterion + value into an SQL operand + value for
   *  constructs using EXISTS + subquery.
   */
  protected function getOperandValueExists($match, $value) {
    list($operand, $value) = $this->getOperandValue($match, $value);
    switch ($match) {
      case "neq":
        return array("=", $value, "NOT EXISTS");
      case "nset":
        return array("!=", $value, "NOT EXISTS");
      case "nin":
        return array(" LIKE ", $value, "NOT EXISTS");
      default:
        return array($operand, $value, "EXISTS");
    }
  }

  private function escapeForLIKE($value) {
    return str_replace("_", "\_", str_replace("%", "\%", $value));
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
