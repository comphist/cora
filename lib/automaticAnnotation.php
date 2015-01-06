<?php

/** @file automaticAnnotation.php
 * Features for automatic, dynamic training and annotation of texts
 * within CorA.
 *
 * @author Marcel Bollmann
 * @date June 2013
 */

//require_once( "documentModel.php" );
require_once( "globals.php" );
require_once( "exporter.php" );
require_once( "annotation/AutomaticAnnotator.php" );
require_once( "annotation/RFTaggerAnnotator.php" );
require_once( "annotation/DualRFTaggerAnnotator.php" );
require_once( "annotation/Lemmatizer.php" );

class AutomaticAnnotationWrapper {
  protected $db; /**< A DBInterface object. */
  protected $exp; /**< An Exporter object. */
  protected $taggerid;
  protected $projectid;

  protected $tagger;
  protected $tagset_ids; /**< Array of associated tagset IDs. */
  protected $tagset_cls; /**< Array of associated tagset classes. */
  protected $tagsets;    /**< Array of associated tagset metadata. */
  protected $trainable;

  protected $paramdir = EXTERNAL_PARAM_DIR;

  private $tagger_objects = array("RFTagger"     => "RFTaggerAnnotator",
                                  "DualRFTagger" => "DualRFTaggerAnnotator",
                                  "Lemmatizer"   => "Lemmatizer");

  /** Construct a new AutomaticAnnotator object.
   *
   * Annotator objects are always specific to a combination of
   * annotator ("tagger") and CorA project.
   */
  function __construct($db, $exp, $taggerid, $projectid) {
    $this->db = $db;
    $this->exp = $exp;
    if(!isset($taggerid) || empty($taggerid)) {
      throw new Exception("Tagger ID cannot be empty.");
    }
    $this->taggerid = $taggerid;
    if(!isset($projectid) || empty($projectid)) {
      throw new Exception("Project ID cannot be empty.");
    }
    $this->projectid = $projectid;

    $this->instantiateTagger();
  }

  /** Fetch information about the tagger and its associated tagsets,
   *  and instantiate the respective tagger class.
   */
  private function instantiateTagger() {
    $tagger = $this->db->getTaggerList();
    if(!$tagger || empty($tagger) || !array_key_exists($this->taggerid, $tagger)) {
      throw new Exception ("Illegal tagger ID: {$this->taggerid}");
    }
    // instantiate class object
    $this->trainable = $tagger[$this->taggerid]['trainable'];
    $class_name = $tagger[$this->taggerid]['class_name'];
    if(!array_key_exists($class_name, $this->tagger_objects)) {
      throw new Exception ("Unknown tagger class: {$class_name}");
    }
    $options = $this->db->getTaggerOptions($this->taggerid);
    $this->tagger = new $this->tagger_objects[$class_name]($this->getPrefix(),
                                                           $options);
    // get info about associated tagsets
    $this->tagset_ids = $tagger[$this->taggerid]['tagsets'];
    $this->tagsets    = $this->db->getTagsetMetadata($this->tagset_ids);
    $this->tagset_cls = array();
    foreach($this->tagsets as $tagset) {
      $this->tagset_cls[] = $tagset['class'];
    }
  }

  /** Get the filename prefix for parameter files.
   */
  protected function getPrefix() {
    return $this->paramdir . "/" . $this->projectid . "-" . $this->taggerid . "-";
  }

  protected function containsOnlyValidAnnotations($anno) {
      foreach($anno as $k => $v) {
          if((substr($k, 0, 5) == "anno_")
             && (!in_array(substr($k, 5), $this->tagset_cls))) {
              return false;
          }
      }
      return true;
  }

  /** Updates the database with new annotations.
   *
   * @param string $fileid ID of the file to be updated
   * @param array $lines Output from the external tagger, expected to
   *                     have one mod per line
   * @param array $moderns Array of all mods as they are currently
   *                       stored in the database
   */
  protected function updateAnnotation($fileid, $tokens, $annotated) {
      $is_not_verified = function ($tok) { return !$tok['verified']; };
      $extract_id      = function ($tok) { return $tok['id']; };
      $valid_id_list   = array();
      foreach(array_filter($tokens, $is_not_verified) as $ftok) {
          $valid_id_list[$ftok['id']] = true;
      }

      $is_valid_annotation = function ($elem) use (&$valid_id_list) {
          return !empty($elem)
              && isset($valid_id_list[$elem['id']])
              && $this->containsOnlyValidAnnotations($elem);
      };
      $lines_to_save = array_filter($annotated, $is_valid_annotation);

      // warnings are ignored here ...
      $this->db->performSaveLines($fileid, $lines_to_save);
  }

  public function annotate($fileid) {
    /* TODO: verify that file belongs to project && has the necessary
       tagset links?
    */
      $tokens = $this->db->getAllModerns_simple($fileid, true);
      // ^-- seeing existing annotations is required for some Annotators,
      //     so we can't really ever set the second parameter to false...
      $annotated = $this->tagger->annotate($tokens);
      $this->updateAnnotation($fileid, $tokens, $annotated);
  }

  public function train() {
      if(!$this->trainable) return;
      $all_files = $this->db->getFilesForProject($this->projectid);
      $tokens = array();
      foreach($all_files as $f) {
          $tokens = array_merge($tokens,
                                $this->db->getAllModerns_simple($f['id'], true));
      }
      $this->tagger->train($tokens);
  }

}

?>
