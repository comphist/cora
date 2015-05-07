<?php

 /** @file DocumentCreator.php
  * Functions related to creating new documents.
  *
  * @author Marcel Bollmann
  * @date September 2014
  */

require_once('DocumentWriter.php');

/** Handles creation of new documents.
 */
class DocumentCreator extends DocumentWriter {
  protected $fullfile = null;
  protected $tagset_links = null;

  /* Prepared SQL statements that are typically called multiple times */
  private $stmt_createText = null;
  private $stmt_createTagsetLinks = null;
  private $stmt_newPage  = null;
  private $stmt_newCol   = null;
  private $stmt_newLine  = null;
  private $stmt_newToken = null;
  private $stmt_newDipl  = null;
  private $stmt_newMod   = null;
  private $stmt_newComm  = null;

  /** Construct a new DocumentCreator.
   *
   * @param DBInterface $parent A DBInterface object to use for queries
   * @param PDO $dbo A PDO database object passed from DBInterface
   * @param array $options Array containing metadata about the document
   *                       (sigle, name, tagset links, ...)
   */
  function __construct($parent, $dbo, &$options) {
      $this->dbi = $parent;
      $this->dbo = $dbo;

      // parse options first so that tagset links are available
      // for the parent constructors
      $this->parseOptions($options);
      $this->prepareCreatorStatements();

      // call parent ctors with fileid=null since we don't know it yet
      parent::__construct($parent, $dbo, null);
  }

  /**********************************************
   ********* SQL Statement Preparations *********
   **********************************************/

  private function prepareCreatorStatements() {
      $stmt = "INSERT INTO text "
          . "  (`sigle`, `fullname`, `project_id`, `created`,"
          . "   `creator_id`, `currentmod_id`, `header`, `fullfile`)"
          . "  VALUES (:sigle, :name, :project, CURRENT_TIMESTAMP,"
          . "          :uid, NULL, :header, :fullfile) ";
      $this->stmt_createText = $this->dbo->prepare($stmt);
      $stmt = "INSERT INTO text2tagset "
          . "  (`text_id`, `tagset_id`, `complete`) "
          . "  VALUES (:tid, :tagset, :complete)";
      $this->stmt_createTagsetLinks = $this->dbo->prepare($stmt);
      $stmt = "INSERT INTO page (`name`, `side`, `text_id`, `num`)"
          . "            VALUES (:name,  :side,  :tid,      :num)";
      $this->stmt_newPage = $this->dbo->prepare($stmt);
      $stmt = "INSERT INTO col (`name`, `num`, `page_id`)"
          . "           VALUES (:name,  :num,  :pageid)";
      $this->stmt_newCol = $this->dbo->prepare($stmt);
      $stmt = "INSERT INTO line (`name`, `num`, `col_id`)"
          . "            VALUES (:name,  :num,  :colid)";
      $this->stmt_newLine = $this->dbo->prepare($stmt);
      $stmt = "INSERT INTO token (`trans`, `ordnr`, `text_id`)"
          . "             VALUES (:trans,  :ordnr,  :tid)";
      $this->stmt_newToken = $this->dbo->prepare($stmt);
      $stmt = "INSERT INTO dipl (`trans`, `utf`, `tok_id`, `line_id`)"
          . "            VALUES (:trans,  :utf,  :tokid,   :lineid)";
      $this->stmt_newDipl = $this->dbo->prepare($stmt);
      $stmt = "INSERT INTO modern (`trans`, `utf`, `ascii`, `tok_id`)"
          . "              VALUES (:trans,  :utf,  :ascii,  :tokid)";
      $this->stmt_newMod = $this->dbo->prepare($stmt);
      $stmt = "INSERT INTO comment "
          . "        (`tok_id`, `value`, `comment_type`, `subtok_id`)"
          . " VALUES (:tokid,   :value,  :ctype,         :subtokid)";
      $this->stmt_newComm = $this->dbo->prepare($stmt);
  }

  /**********************************************/

  /** Parses an option array and sets appropriate class variables.
   */
  private function parseOptions(&$options) {
      if(array_key_exists("sigle", $options))
          $this->text_sigle = $options["sigle"];
      if(array_key_exists("name", $options))
          $this->text_fullname = $options["name"];
      if(array_key_exists("project", $options))
          $this->projectid = $options["project"];
      if(array_key_exists("trans_file", $options))
          $this->fullfile = $options["trans_file"];
      if(array_key_exists("tagsets", $options))
          $this->parseTagsetLinks($options["tagsets"]);
  }

  /** Parses tagset links given in an option array.
   */
  private function parseTagsetLinks($my_tagsets) {
      $this->tagset_links = array();
      $all_tagsets = $this->dbi->getTagsets(null);
      foreach($all_tagsets as $tagset) {
          if(in_array($tagset['id'], $my_tagsets)) {
              $tagset['name'] = $tagset['longname'];
              $this->tagset_links[] = $tagset;
          }
      }
  }

  /** Creates tagset links in the database.
   */
  private function createTagsetLinks() {
      foreach($this->tagset_links as $tagset) {
          $params = array(':tid' => $this->fileid,
                          ':tagset' => $tagset['id'],
                          ':complete' => 0);
          $this->stmt_createTagsetLinks->execute($params);
      }
  }

  /** Returns a list of tagsets linked to the associated file.
   */
  protected function getTagsetLinks() {
      return $this->tagset_links;
  }

  /** Create a new text in the DB and sets the fileid attribute.
   *
   * @param string $userid ID of the user to be stored as the text's creator
   */
  private function createNewText($userid) {
      $params = array(':sigle' => $this->text_sigle,
                      ':name'  => $this->text_fullname,
                      ':project' => $this->projectid,
                      ':uid'   => $userid,
                      ':header' => $this->text_header,
                      ':fullfile' => $this->fullfile);
      $this->stmt_createText->execute($params);
      $this->setFileID($this->dbo->lastInsertId());
  }

  /** Creates layout information in the DB (pages, columns, lines).
   *
   * @param CoraDocument $doc Document that contains the layout information
   */
  private function createLayoutInformation(&$doc) {
      // pages
      $first = null;
      foreach($doc->getPages() as $page) {
          $this->stmt_newPage->execute(array(':name' => $page['name'],
                                             ':side' => $page['side'],
                                             ':tid'  => $this->fileid,
                                             ':num'  => $page['num']));
          if(is_null($first))
              $first = $this->dbo->lastInsertId();
      }
      $doc->fillPageIDs($first);
      // columns
      $first = null;
      foreach($doc->getColumns() as $col) {
          $this->stmt_newCol->execute(array(':name' => $col['name'],
                                            ':num'  => $col['num'],
                                            ':pageid' => $col['parent_db_id']));
          if(is_null($first))
              $first = $this->dbo->lastInsertId();
      }
      $doc->fillColumnIDs($first);
      // lines
      $first = null;
      foreach($doc->getLines() as $line) {
          $this->stmt_newLine->execute(array(':name' => $line['name'],
                                             ':num'  => $line['num'],
                                             ':colid' => $line['parent_db_id']));
          if(is_null($first))
              $first = $this->dbo->lastInsertId();
      }
      $doc->fillLineIDs($first);
  }

  /** Creates token information in the DB (token, dipl, modern).
   *
   * @param CoraDocument $doc Document that contains the tokens
   */
  private function createTokens(&$doc) {
      // tokens
      $first = null;
      foreach($doc->getTokens() as $token) {
          $this->stmt_newToken->execute(array(':trans' => $token['trans'],
                                              ':ordnr' => $token['ordnr'],
                                              ':tid'   => $this->fileid));
          if(is_null($first))
              $first = $this->dbo->lastInsertId();
      }
      $doc->fillTokenIDs($first);
      // diplomatic tokens
      $first = null;
      foreach($doc->getDipls() as $dipl) {
          $this->stmt_newDipl->execute(array(':trans' => $dipl['trans'],
                                             ':utf'   => $dipl['utf'],
                                 ':tokid' => $dipl['parent_tok_db_id'],
                                ':lineid' => $dipl['parent_line_db_id']));
          if(is_null($first))
              $first = $this->dbo->lastInsertId();
      }
      $doc->fillDiplIDs($first);
      // modern tokens
      $first = null;
      foreach($doc->getModerns() as $mod) {
          $this->stmt_newMod->execute(array(':trans' => $mod['trans'],
                                            ':utf'   => $mod['utf'],
                                            ':ascii' => $mod['ascii'],
                                            ':tokid' => $mod['parent_db_id']));
          if(is_null($first))
              $first = $this->dbo->lastInsertId();
      }
      $doc->fillModernIDs($first);
  }

  /** Saves all annotations, including suggestions, in a document. */
  private function saveAllAnnotations(&$moderns) {
      $last_checked = null;
      $last_checked_found = false;
      foreach($moderns as $mod) {
          // currentmod_id -- done here because this might become a flag-type
          //                  annotation for moderns in the future
          if(!$last_checked_found) {
              if(isset($mod['chk']) && $mod['chk'])
                  $last_checked = $mod['db_id'];
              else
                  $last_checked_found = true;
          }
          // All tags
          foreach($mod['tags'] as $anno) {
              $this->saveAnnotation($mod['db_id'], null,
                                    $anno['type'], $anno['tag'],
                                    $anno['selected'],
                                    $anno['source'], $anno['score']);
          }
          // All flags
          if(array_key_exists('flags', $mod)) {
              foreach($mod['flags'] as $flag) {
                  $this->saveFlag($mod['db_id'], $flag, 1);
              }
          }
      }
      if($last_checked !== null)
          $this->markLastPosition($last_checked);
  }

  /** Saves all shifttags. */
  private function saveAllShifttags(&$shifttags) {
      foreach($shifttags as $shifttag) {
          $this->saveShifttag($shifttag['db_range'][0],
                              $shifttag['db_range'][1],
                              $shifttag['type_letter']);
      }
  }

  /** Saves all comments.
   *
   * NOTE: This doesn't re-use DocumentWriter::saveComment since that
   * function is very specific to 'C'-type comments only.
   * This could be refactored someday, as it would also allow us
   * to offer general comment editing to the user.
   */
  private function saveAllComments(&$comments) {
      foreach($comments as $comment) {
          if(!array_key_exists('subtok_db_id', $comment))
              $comment['subtok_db_id'] = null;
          $params = array(':tokid' => $comment['parent_db_id'],
                          ':value' => $comment['text'],
                          ':ctype' => $comment['type'],
                          ':subtokid' => $comment['subtok_db_id']);
          $this->stmt_newComm->execute($params);
      }
  }

  /**********************************************
   ************** Public functions **************
   **********************************************/

  /** Imports a new document into the database.
   *
   * @param CoraDocument $doc The document to be inserted
   *
   * @return True if import was successful, false otherwise
   */
  public function importDocument(&$doc) {
      $this->text_header = $doc->getHeader();
      $this->dbo->beginTransaction();
      try {
          $this->createNewText($_SESSION['user_id']);
          $this->createTagsetLinks();
          $this->createLayoutInformation($doc);
          $this->createTokens($doc);
          $this->saveAllAnnotations($doc->getModerns());
          $this->saveAllShifttags($doc->getShifttags());
          $this->saveAllComments($doc->getComments());
      }
      catch (Exception $ex) {
          $this->dbo->rollBack();
          $this->warn("ERROR: An exception occured!\n"
                      . $ex->getMessage());
          return false;
      }
      $this->dbo->commit();
      return true;
  }

}

?>
