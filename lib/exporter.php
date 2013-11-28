<?php

/** @file exporter.php
 * Export of files.  Implements all file formats except XML,
 * which is implemented in @c xmlHandler.php.
 *
 * @author Marcel Bollmann
 * @date June 2013
 */

require_once( "documentModel.php" );
require_once( "xmlHandler.php" );

/** Defines constants that represent file formats available for
 * exporting. */
class ExportType {
  const CoraXML = 1; /**< CorA XML format */
  const Tagging = 2; /**< Tab-separated format containing
			simplification and POS tags, suitable for
			training a tagger model. */
  const Transcription = 3; // export in original transcription format
  const Normalization = 4; /**< Tab-separated format containing
			simplification, normalization, modernization. */

  public static function mapToContentType($format) {
    switch ($format) {
    case ExportType::CoraXML:
      return "text/xml";
      break;
    case ExportType::Tagging:
    case ExportType::Transcription:
    case ExportType::Normalization:
      return "text/plain";
      break;
    }
  }

  public static function mapToExtension($format) {
    switch ($format) {
    case ExportType::CoraXML:
      return ".xml";
      break;
    case ExportType::Tagging:
    case ExportType::Transcription:
    case ExportType::Normalization:
      return ".txt";
      break;
    }
  }
}

/** Encapsulates functions relevant for export. */
class Exporter {
  private $db; /**< A DBInterface object. */

  function __construct($db) {
    $this->db = $db;
  }

  /** Export a file.
   *
   * Requests the file to be exported from the database, then
   * delegates the call to specific exporters depending on the desired
   * target format.
   *
   * @param string $fileid ID of the file to be exported
   * @param string $format Desired format of the export file
   * @param resource $handle A resource representing an output stream
   *                         where the exported data will be sent
   *
   * @return ???
   */
  public function export($fileid, $format, $handle) {
    if($format == ExportType::Tagging) {
      return $this->exportForTagging($fileid, $handle);
    }
    if($format == ExportType::Normalization) {
      return $this->exportNormalization($fileid, $handle);
    }

    return; // can't do anything else yet

    // make a try..catch
    $doc = CoraDocument::fromDB($fileid, $this->db);
  }

  /** Export a file for tagging.
   *
   * Outputs a tab-separated text format with modern tokens (in their
   * simplified version) and their selected POS tags.
   *
   * @param string $fileid ID of the file to be exported
   * @param resource $handle A resource representing an output stream
   *                         where the exported data will be sent
   */
  protected function exportForTagging($fileid, $handle) {
    $tokens = $this->db->getAllTokens($fileid);
    $moderns = $tokens[2];
    foreach($moderns as $mod) {
      $tok = $mod['ascii'];
      $pos = '';
      foreach($mod['tags'] as $tag) {
	if($tag['type']=='POS' && $tag['selected']==1) {
	  $pos = $tag['tag'];
	}
      }
      fwrite($handle, $tok."\t".$pos."\n");
    }
  }

  /** Export a file with normalization annotations. */
  protected function exportNormalization($fileid, $handle) {
    $tokens = $this->db->getAllTokens($fileid);
    $moderns = $tokens[2];
    foreach($moderns as $mod) {
      $tok = $mod['ascii'];
      $norm = '--'; $normbroad = '--'; $normtype = '';
      foreach($mod['tags'] as $tag) {
	if($tag['selected']==1) {
	  if($tag['type']=='norm') {
	    $norm = $tag['tag'];
	  }
	  else if($tag['type']=='norm_broad') {
	    $normbroad = $tag['tag'];
	  }
	  else if($tag['type']=='norm_type') {
	    $normtype = $tag['tag'];
	  }
	}
      }
      fwrite($handle, $tok."\t".$norm);
      if($normbroad!='--') {
	fwrite($handle, "\t".$normbroad."\t".$normtype);
      }
      fwrite($handle, "\n");
    }
  }
 
}

?>