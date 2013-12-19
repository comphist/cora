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

class AutomaticAnnotator {
  protected $db; /**< A DBInterface object. */
  protected $taggerid;
  protected $projectid;

  protected $cmd_train;  /**< Shell command for training. */
  protected $cmd_tag;    /**< Shell command for tagging. */
  protected $tagset_ids; /**< Array of associated tagset IDs. */
  protected $tagset_cls; /**< Array of associated tagset classes. */
  protected $tagsets;    /**< Array of associated tagset metadata. */

  protected $paramdir = EXTERNAL_PARAM_DIR;

  /** Construct a new AutomaticAnnotator object.
   *
   * Annotator objects are always specific to a combination of
   * annotator ("tagger") and CorA project.
   */
  function __construct($db, $taggerid, $projectid) {
    $this->db = $db;
    if(!isset($taggerid) || empty($taggerid)) {
      throw new Exception("Tagger ID cannot be empty.");
    }
    $this->taggerid = $taggerid;
    $this->getTaggerInformation();
    if(!isset($projectid) || empty($projectid)) {
      throw new Exception("Project ID cannot be empty.");
    }
    $this->projectid = $projectid;
  }

  /** Fetch information about the tagger and its associated tagsets.
   */
  private function getTaggerInformation() {
    $tagger = $this->db->getTaggerList();
    if(!$tagger || empty($tagger) || !array_key_exists($this->taggerid, $tagger)) {
      throw new Exception ("Illegal tagger ID: {$this->taggerid}");
    }
    $this->cmd_train  = $tagger[$this->taggerid]['cmd_train'];
    $this->cmd_tag    = $tagger[$this->taggerid]['cmd_tag'];
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
    return $this->paramdir . "/" . $this->projectid . "-" . $this->taggerid;
  }

  protected function buildCommand($cmd, $tmpfname) {
    $cmd = str_replace('%prefix%', $this->getPrefix(), $cmd);
    $cmd = str_replace('%file%', $tmpfname, $cmd);
    // $cmd .= " 2>&1"; // DEBUG
    return $cmd;
  }

  /** Updates the database with new annotations.
   *
   * @param string $fileid ID of the file to be updated
   * @param array $lines Output from the external tagger, expected to
   *                     have one mod per line
   * @param array $moderns Array of all mods as they are currently
   *                       stored in the database
   */
  protected function updateAnnotation($fileid, $lines, $moderns) {
    $idx = array();
    // parse header
    $header = array_shift($lines);
    if($header==null || count($lines)!=count($moderns)) {
      throw new Exception("Ein interner Fehler ist aufgetreten:\nDatei hat "
			  . count($moderns) . " Tokens, der Tagger lieferte"
			  . " aber nur " . count($lines) . " zurück.");
    }
    $headings = explode("\t", $header);
    foreach($headings as $i => $heading) {
      if(in_array($heading, $this->tagset_cls)) {
	$idx[$heading] = $i;
      }
    }

    // parse lines
    $lines_to_save = array();
    $count = count($moderns);
    for($i=0; $i<$count; $i++) {
      if($moderns[$i]['verified']) { // don't change verified lines!
	continue;
      }
      $line = explode("\t", $lines[$i]);
      $save = array();
      foreach($idx as $cls => $j) {
	$save["anno_".$cls] = $line[$j];
      }
      if(!empty($save)) {
	$save["id"] = $moderns[$i]["db_id"];
	$lines_to_save[] = $save;
      }
    }
    
    // warnings are ignored here ...
    $this->db->performSaveLines($fileid, $lines_to_save);
  }

  public function annotate($fileid) {
    /* TODO: verify that file belongs to project && has the necessary
       tagset links?
    */
    // $filetagsets = $this->db->getTagsetsForFile($fileid);

    if(!$this->db->lockProjectForTagger($this->projectid, $this->taggerid)) {
      // TODO: this probably shouldn't be an exception
      throw new Exception("Für dieses Projekt wird derzeit bereits ein Tagger"
			  ." ausgeführt.  Bitte warten Sie einen Moment und"
			  ." führen dann den Vorgang erneut aus.");
    }

    try {
      // export for tagging
      $tmpin  = tempnam(sys_get_temp_dir(), 'cora_aa');
      $handle = fopen($tmpin, 'w');
      $exp = new Exporter($this->db);
      $moderns = $exp->exportForTagging($fileid, $handle, $this->tagset_cls, true);
      fclose($handle);

      // call tagger
      $output = array();
      $retval = 0;
      $cmd = $this->buildCommand($this->cmd_tag, $tmpin);
      exec($cmd, $output, $retval);
      unlink($tmpin);
      if($retval) {
	throw new Exception("Der Befehl gab den Status-Code {$retval} zurück.");
      }

      // import the annotations
      $this->updateAnnotation($fileid, $output, $moderns);
      $this->db->unlockProjectForTagger($this->projectid);
    }
    catch(Exception $e) {
      @unlink($tmpin);
      $this->db->unlockProjectForTagger($this->projectid);
      throw $e;
    }
  }


}

?>