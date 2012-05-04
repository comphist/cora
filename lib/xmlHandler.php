<?php

/** @file xmlHandler.php
 * Import and export of files as XML data.
 *
 * @author Marcel Bollmann
 * @date May 2012
 */

class XMLHandler {

  private $db; /**< A DBInterface object. */
  private $output_suggestions; /**< Boolean indicating whether to output tagger suggestions. */

  function __construct($db) {
    $this->db = $db;
    $this->output_suggestions = true;
  }

  /** Output the HTTP header for an XML file. */
  private function outputXMLHeader($filename) {
    header("Cache-Control: public");
    header("Content-Type: text/xml");
    // header("Content-Transfer-Encoding: Binary");
    // header("Content-Length:".filesize($attachment_location));
    header("Content-Disposition: attachment; filename=".$filename);
  }

  /** Output metadata in XML format. */
  private function outputMetadataAsXML($writer, $fileid) {
    // this does nothing but fetch file metadata:
    $metadata = $this->db->openFile($fileid);
    if($metadata['success']) {
      $writer->startElement('header');
      $writer->writeAttribute('sigle', $metadata['data']['sigle']);
      $writer->writeAttribute('name', $metadata['data']['file_name']);
      $writer->writeAttribute('tagset', $metadata['data']['tagset']);
      $writer->writeAttribute('progress', $metadata['lastEditedRow']);
      $writer->endElement(); // 'header'
    }
    else {
      throw new Exception("File metadata could not be retrieved from the database.");
    }
  }

  /** Output lines in XML format. */
  private function outputLinesAsXML($writer, $fileid) {
    $count = 1;
    foreach($this->db->getAllLines($fileid) as $line){
      $writer->startElement('token');
      $writer->writeAttribute('id', "t_{$line['line_id']}");
      $writer->writeAttribute('count', $count++);
      // form
      $writer->startElement('form');
      $writer->writeAttribute('dipl', $line['token']);
      if($line['tag_norm']!==null && $line['tag_norm']!==''){
	$writer->writeAttribute('norm', $line['tag_norm']);
      }
      $writer->endElement();
      // lemma
      if($line['lemma']!==null && $line['lemma']!==''){
	$writer->startElement('lemma');
	$writer->writeAttribute('inst', $line['lemma']);
	$writer->endElement();
      }
      // pos
      if($line['tag_POS']!==null && $line['tag_POS']!==''){
	$writer->startElement('pos');
	$writer->writeAttribute('inst', $line['tag_POS']);
	$writer->endElement();
      }
      // morph
      if($line['tag_morph']!==null && $line['tag_morph']!==''){
	$writer->startElement('infl');
	$writer->writeAttribute('val', $line['tag_morph']);
	$writer->endElement();
      }
      // suggestions
      if($this->output_suggestions){
	$this->outputSuggestionsAsXML($writer, $fileid, $line['line_id']);
      }
      // comment
      if($line['comment']!==null && $line['comment']!=='') {
	$writer->writeElement('comment', $line['comment']);
      }
      // closing
      $writer->endElement(); // 'token'
    }
  }

  /** Output tagger suggestions in XML format. */
  private function outputSuggestionsAsXML($writer, $fileid, $lineid) {
    $writer->startElement('suggestions');
    foreach($this->db->getAllSuggestions($fileid,$lineid) as $sugg){
      if($sugg['tagtype']=='pos') {
	$writer->startElement('pos');
	$writer->writeAttribute('inst', $sugg['tag_name']);
	$writer->writeAttribute('score', $sugg['tag_probability']);
	$writer->endElement();
      }
      else if($sugg['tagtype']=='morph') {
	$writer->startElement('infl');
	$writer->writeAttribute('val', $sugg['tag_name']);
	$writer->writeAttribute('score', $sugg['tag_probability']);
	$writer->endElement();
      }
    }
    $writer->endElement(); // 'suggestions'
  }

  public function export($fileid) {
    $this->outputXMLHeader($fileid.".xml");

    $writer = new XMLWriter();
    $writer->openURI('php://output');
    $writer->startDocument('1.0', 'UTF-8'); 
    $writer->startElement('cora');

    $this->outputMetadataAsXML($writer, $fileid);
    $this->outputLinesAsXML($writer, $fileid);

    $writer->endElement(); // 'cora'
    $writer->endDocument();
    $writer->flush();
  }
}

?>