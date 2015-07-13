<?php

 /** @file DocumentReader.php
  * Functions related to retrieving (parts of) documents for the client
  * interface.
  *
  * @author Marcel Bollmann
  * @date December 2014
  */

require_once('DocumentAccessor.php');

/** Handles retrieving information from a document.
 */
class DocumentReader extends DocumentAccessor {
  private $toktrans_cache = array(null, null);

  // SQL statements
  private $stmt_getLinesByRange = null;
  private $stmt_getLinesByID = null;
  private $stmt_getDiplTrans = null;
  private $stmt_getDiplLayoutInfo = null;
  private $stmt_getAllAnnotations = null;

  /** Construct a new DocumentReader.
   *
   * @param DBInterface $parent A DBInterface object to use for queries
   * @param PDO $dbo A PDO database object passed from DBInterface
   * @param string $fileid ID of the file to be accessed
   */
  function __construct($parent, $dbo, $fileid) {
    parent::__construct($parent, $dbo, $fileid);

    $this->prepareReaderStatements();
  }

  /**********************************************
   ********* SQL Statement Preparations *********
   **********************************************/

  private function prepareReaderStatements() {
    $stmt = "SELECT modern.id, modern.trans, modern.utf, "
          . "       modern.tok_id, c1.value AS comment "
          . "FROM   modern "
          . "  LEFT JOIN token   ON modern.tok_id=token.id "
          . "  LEFT JOIN comment c1 ON  c1.tok_id=token.id "
          . "        AND c1.subtok_id=modern.id AND c1.comment_type='C' "
          . "WHERE  token.text_id=:tid "
          . "ORDER BY token.ordnr ASC, modern.id ASC "
          . "LIMIT  :offset, :count";
    $this->stmt_getLinesByRange = $this->dbo->prepare($stmt);
    $stmt = "SELECT modern.id, modern.trans, modern.utf, "
          . "       modern.tok_id, c1.value AS comment "
          . "FROM   modern "
          . "  LEFT JOIN token   ON modern.tok_id=token.id "
          . "  LEFT JOIN comment c1 ON  c1.tok_id=token.id "
          . "        AND c1.subtok_id=modern.id AND c1.comment_type='C' "
          . "WHERE  token.text_id=:tid AND modern.id=:mid";
    $this->stmt_getLinesByID = $this->dbo->prepare($stmt);
    $stmt = "SELECT d.trans, d.line_id FROM dipl d "
          . " WHERE d.tok_id=:tokid ORDER BY d.id ASC";
    $this->stmt_getDiplTrans = $this->dbo->prepare($stmt);
    $stmt = "SELECT IFNULL(l.name, l.num) AS line_name, "
          . "       IFNULL(c.name, '')    AS col_name, "
          . "       IFNULL(p.name, p.num) AS page_name, "
          . "       IFNULL(p.side, '')    AS page_side "
          . "FROM   dipl d "
          . "  LEFT JOIN line l ON l.id=d.line_id "
          . "  LEFT JOIN col  c ON c.id=l.col_id "
          . "  LEFT JOIN page p ON p.id=c.page_id "
          . "WHERE  d.tok_id=:tokid "
          . " ORDER BY d.id ASC LIMIT 1";
    $this->stmt_getDiplLayoutInfo = $this->dbo->prepare($stmt);
    $stmt = "SELECT tag.value, ts.score, ts.selected, ts.source, "
          . "       LOWER(tt.class) AS `class` "
          . "FROM   modern"
          . "  LEFT JOIN (tag_suggestion ts, tag) "
          . "         ON (ts.tag_id=tag.id AND ts.mod_id=modern.id) "
          . "  LEFT JOIN tagset tt ON tag.tagset_id=tt.id "
          . "WHERE  modern.id=:modid ";
    $this->stmt_getAllAnnotations = $this->dbo->prepare($stmt);
  }

  /**********************************************/

  /** Retrieve lines in a given range of line numbers.
   *
   * @param int $offset Number of first line to be retrieved
   * @param int $count Number of total lines to be retrieved
   *
   * @return an @em array of mods, with ID and trans/utf fields
   */
  public function getLinesByRange($offset, $count) {
    $this->stmt_getLinesByRange->bindValue(':tid', $this->fileid, PDO::PARAM_INT);
    $this->stmt_getLinesByRange->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
    $this->stmt_getLinesByRange->bindValue(':count', (int)$count, PDO::PARAM_INT);
    $this->stmt_getLinesByRange->execute();
    return $this->stmt_getLinesByRange->fetchAll(PDO::FETCH_ASSOC);
  }

  /** Retrieve lines with given modern IDs.
   *
   * @param array $idlist List of modern IDs
   *
   * @return an @em array of mods, with ID and trans/utf fields
   */
  public function getLinesByID($idlist) {
    $mid = 0;
    $results = array();
    $this->stmt_getLinesByID->bindValue(':tid', $this->fileid, PDO::PARAM_INT);
    $this->stmt_getLinesByID->bindParam(':mid', $mid, PDO::PARAM_INT);
    foreach($idlist as $mid) {
      $this->stmt_getLinesByID->execute();
      if($res = $this->stmt_getLinesByID->fetch(PDO::FETCH_ASSOC))
        $results[] = $res;
    }
    return $results;
  }

  /** Return the transcription of a full token including line breaks.
   *
   * @param int $tokid ID of the token
   * @return a @em string containing the transcription with line breaks
   *    whereever the line_id of the corresponding dipl element changes
   */
  public function getTokTransWithLinebreaks($tokid) {
    $trans = "";
    $last = null;

    // In practice, this function is called for a sequential list of moderns, so
    // it might be called several times in a row with the same $tokid.
    // Therefore, we cache the last -- and only the last -- result.
    if ($this->toktrans_cache[0] === $tokid)
      return $this->toktrans_cache[1];

    // Loop over all dipls belonging to this token
    $this->stmt_getDiplTrans->execute(array(':tokid' => $tokid));
    while ($row = $this->stmt_getDiplTrans->fetch(PDO::FETCH_ASSOC)) {
      if ($last !== null && $last !== $row['line_id']) {
        $trans .= "\n";
      }
      $trans .= $row['trans'];
      $last = $row['line_id'];
    }

    $this->toktrans_cache = array($tokid, $trans);
    return $trans;
  }

  /** Retrieve layout information for a given token.
   *
   * @param int $tokid ID of the token
   * @return an @em array with layout information for the first dipl
   *         of the given token
   */
  public function getDiplLayoutInfo($tokid) {
    $this->stmt_getDiplLayoutInfo->execute(array(':tokid' => $tokid));
    return $this->stmt_getDiplLayoutInfo->fetch(PDO::FETCH_ASSOC);
  }

  /** Retrieve layout information for a given token.
   *
   * @param int $tokid ID of the token
   * @return an @em array with layout information for the first dipl
   *         of the given token
   */
  public function getAllAnnotations($modid) {
    $this->stmt_getAllAnnotations->execute(array(':modid' => $modid));
    return $this->stmt_getAllAnnotations->fetchAll(PDO::FETCH_ASSOC);
  }

}
