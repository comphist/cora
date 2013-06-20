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
class ExportTypes {
  const CoraXML = 1; /**< CorA XML format */
  const Tagging = 2; /**< Tab-separated format containing
			simplification and POS tags, suitable for
			training a tagger model. */
  //const Transcription = 3; // export in original transcription format
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
   *
   * @return The modified string or array.
   */
  public function export($fileid, $format) {
      // make a try..catch
      $doc = CoraDocument::fromDB($fileid);

  }

}

?>