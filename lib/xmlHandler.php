<?php

/** @file xmlHandler.php
 * Import and export of files as XML data.
 *
 * @author Marcel Bollmann
 * @date May 2012, updated February 2013
 */

require_once( "documentModel.php" );


class XMLHandler {

  private $db; /**< A DBInterface object. */
  private $output_suggestions; /**< Boolean indicating whether to output tagger suggestions. */
  private $xml_header_options; /**< Valid attributes for the XML <header> tag. */

  function __construct($db) {
    $this->db = $db;
    $this->output_suggestions = true;
    $this->xml_header_options = array('sigle','name','tagset','progress');
  }

  /****** FUNCTIONS RELATED TO DATA IMPORT ******/

  private function setOptionsFromHeader(&$header, &$options) {
    // get header attributes if they are not already set in $options
    foreach($this->xml_header_options as $key) {
      if (isset($header[$key]) && !empty($header[$key])
	  && (!isset($options[$key]) || empty($options[$key]))) {
	$options[$key] = (string) $header[$key];
      }
    }
  }

  /** Process header information. */
  private function processXMLHeader(&$reader, &$options) {
    $doc = new DOMDocument();
    while ($reader->read()) {
      if ($reader->name == 'text') {
	$header = simplexml_import_dom($doc->importNode($reader->expand(), true));
	if (isset($header['id']) && !empty($header['id'])
	    && (!isset($options['ext_id']) || empty($options['ext_id']))) {
	  $options['ext_id'] = (string) $header['id'];
	}
	return False;
      }
    }
    return "XML-Format nicht erkannt: <text>-Tag nicht gefunden.";
  }

  /** Parses a range string ("t1..t4" or just "t1") into an array
      containing beginning and end of the range. */
  private function parseRange($range) {
    $x = explode("..", $range);
    $start = $x[0];
    $end = (isset($x[1]) ? $x[1] : $x[0]);
    return array($start, $end);
  }

  /** Process layout information. */
  private function processLayoutInformation(&$node, &$document) {
    $pages = array();
    $columns = array();
    $lines = array();
    $pagecount = 0;
    $colcount = 0;
    $linecount = 0;
    // pages
    foreach($node->page as $pagenode) {
      $page = array();
      $page['xml_id'] = (string) $pagenode['id'];
      $page['side']   = (string) $pagenode['side'];
      $page['name']   = (string) $pagenode['no'];
      $page['num']    = ++$pagecount;
      $page['range']  = $this->parseRange((string) $pagenode['range']);
      $pages[] = $page;
    }
    // columns
    foreach($node->column as $colnode) {
      $column = array();
      $column['xml_id'] = (string) $colnode['id'];
      $column['name']   = (string) $colnode['name'];
      $column['num']    = ++$colcount;
      $column['range']  = $this->parseRange((string) $colnode['range']);
      $columns[] = $column;
    }
    // lines
    foreach($node->line as $linenode) {
      $line = array();
      $line['xml_id'] = (string) $linenode['id'];
      $line['name']   = (string) $linenode['name'];
      $line['num']    = ++$linecount;
      $line['range']  = $this->parseRange((string) $linenode['range']);
      $lines[] = $line;
    }

    $document->setLayoutInfo($pages, $columns, $lines);
  }

  /** Process shift tag information. */
  private function processShiftTags(&$node, &$document) {
    $shifttags = array();
    $type_to_letter = array("rub" => "R",
			    "title" => "T",
			    "lat" => "L",
			    "marg" => "M",
			    "fm" => "F");
    foreach($node->children() as $tagnode) {
      $shifttag = array();
      $shifttag['type'] = $tagnode->getName();
      $shifttag['type_letter'] = $type_to_letter[$shifttag['type']];
      $shifttag['range'] = $this->parseRange((string) $tagnode['range']);
      $shifttags[] = $shifttag;
    }
    $document->setShiftTags($shifttags);
  }

  private function processToken(&$node, &$tokcount, &$t, &$d, &$m) {
    $token = array();
    $thistokid       = (string) $node['id'];
    $token['xml_id'] = (string) $node['id'];
    $token['trans']  = (string) $node['trans'];
    $token['ordnr']  = $tokcount;
    $t[] = $token;
    // diplomatic tokens
    foreach($node->dipl as $diplnode) {
      $dipl = array();
      $dipl['xml_id'] = (string) $diplnode['id'];
      $dipl['trans']  = (string) $diplnode['trans'];
      $dipl['utf']    = (string) $diplnode['utf'];
      $dipl['parent_tok_xml_id'] = $thistokid;
      $d[] = $dipl;
    }
    // modern tokens
    foreach($node->mod as $modnode) {
      $modern = array('tags' => array());
      $modern['xml_id'] = (string) $modnode['id'];
      $modern['trans']  = (string) $modnode['trans'];
      $modern['ascii']  = (string) $modnode['ascii'];
      $modern['utf']    = (string) $modnode['utf'];
      $modern['parent_xml_id'] = $thistokid;
      // first, parse all automatic suggestions
      foreach($modnode->suggestions->children() as $suggnode) {
	$sugg = array('source' => 'auto', 'selected' => 0);
	$sugg['type']   = $suggnode->getName();
	$sugg['tag']    = (string) $suggnode['tag'];
	$sugg['score']  = (string) $suggnode['score'];
	$modern['tags'][] = $sugg;
      }
      // then, parse all selected annotations
      foreach($modnode->children() as $annonode) {
	$annotype = $annonode->getName();
	if($annotype!=='suggestions') {
	  $annotag  = (string) $annonode['tag'];
	  $found = false;
	  // loop over all suggestions to check whether the annotation
	  // is included there, and if so, select it
	  foreach($modern['tags'] as &$sugg) {
	    if($sugg['type']==$annotype && $sugg['tag']==$annotag) {
	      $sugg['selected'] = 1;
	      $found = true;
	      break;
	    }
	  }
	  unset($sugg);
	  // if it is not, create a new entry for it
	  if(!$found) {
	    $sugg = array('source' => 'user', 'selected' => 1, 'score' => null);
	    $sugg['type'] = $annotype;
	    $sugg['tag']  = $annotag;
	    $modern['tags'][] = $sugg;
	  }
	}
      }
      $m[] = $modern;
    }
    return $thistokid;
  }

  /** Process XML data. */
  private function processXMLData(&$reader, &$options) {
    $doc = new DOMDocument();
    $document = new CoraDocument($options);

    $tokens  = array();
    $dipls   = array();
    $moderns = array();
    $tokcount = 0;
    $lasttokid = null;

    while ($reader->read()) {
      // only handle opening tags
      if ($reader->nodeType!==XMLReader::ELEMENT) { continue; }

      $node = simplexml_import_dom($doc->importNode($reader->expand(), true));
      if ($reader->name == 'cora-header') {
	$this->setOptionsFromHeader($node, $options);
      }
      else if ($reader->name == 'header') {
	$document->setHeader((string)$node);
      }
      else if ($reader->name == 'layoutinfo') {
	$this->processLayoutInformation($node, $document);
      }
      else if ($reader->name == 'shifttags') {
	$this->processShiftTags($node, $document);
      }
      else if ($reader->name == 'comment') {
	$document->addComment(null, $lasttokid, (string) $node, (string) $node['type']);
      }
      else if ($reader->name == 'token') {
        ++$tokcount;
	$lasttokid = $this->processToken($node, $tokcount, $tokens, $dipls, $moderns);
      }
    }

    $document->setTokens($tokens, $dipls, $moderns);
    $document->mapRangesToIDs();
    return $document;
  }

  /** Check if data should be considered normalized, POS-tagged,
   *  and/or morph-tagged; and, if tags are present, whether they
   *  conform to the chosen tagset.
   */
  private function checkIntegrity(&$options, &$data) {
    $warnings = array();

    return $warnings;
  }

  /** Import XML data into the database as a new document.
   *
   * Parses XML data and sends database queries to import the data.
   * Data will be imported as a new document; adding information to an
   * already existing document is not (yet) supported.
   *
   * @param string $xmlfile Name of a file containing XML data to
   * import; typically a temporary file generated from user-uploaded
   * data
   * @param array $options Array containing metadata (e.g. sigle,
   * name, tagset) for the document; if there is a conflict with
   * the same type of data being supplied in the XML file,
   * the @c $options array takes precedence
   */
  public function import($xmlfile, $options) {
    // check for validity
    libxml_use_internal_errors(true);
    $doc = new DOMDocument('1.0', 'utf-8');
    $doc->loadXML(file_get_contents($xmlfile['tmp_name']));
    $errors = libxml_get_errors();
    if (!empty($errors) && $errors[0]->level > 0) {
      $message  = "Datei enthält kein wohlgeformtes XML. Parser meldete:\n";
      $message .= $errors[0]->message.' at line '.$errors[0]->line.'.';
      return array("success"=>False, "errors"=>array("XML-Fehler: ".$message));
    }

    // process XML
    $reader = new XMLReader();
    if(!$reader->open($xmlfile['tmp_name'])) {
      return array("success"=>False,
		   "errors"=>array("Interner Fehler: Konnte temporäre Datei '".$xmlfile."' nicht öffnen."));
    }
    $format = '';
    $xmlerror = $this->processXMLHeader($reader, $options, $format);
    if($xmlerror){
      $reader->close();
      return array("success"=>False, 
		   "errors"=>array($xmlerror));
    }
    try {
      $data = $this->processXMLData($reader, $options, $format);
    }
    catch (DocumentValueException $e) {
      $reader->close();
      return array("success"=>False,
		   "errors"=>array($e->getMessage()));
    }
    $reader->close();

    // check for data integrity
    $warnings = $this->checkIntegrity($options, $data);
    if(!(isset($options['name']) && !empty($options['name'])) &&
       !(isset($options['sigle']) && !empty($options['sigle']))) {
      array_unshift($warnings, "Dokument hat weder Name noch Sigle; benutze Dateiname als Dokumentname.");
      $options['name'] = $xmlfile['name'];
    }
    if(!(isset($options['ext_id']) && !empty($options['ext_id']))) {
      $options['ext_id'] = '';
    }

    // insert data into database
    $sqlerror = $this->db->insertNewDocument($options, $data);
    if($sqlerror){
      return array("success"=>False, 
		   "errors"=>array("SQLError: ".$sqlerror));
    }

    return array("success"=>True, "warnings"=>$warnings);
  }

  /****** FUNCTIONS RELATED TO DATA EXPORT ******/

  /** Output the HTTP header for an XML file. */
  private function outputXMLHeader($filename) {
    header("Cache-Control: public");
    header("Content-Type: text/xml");
    // header("Content-Transfer-Encoding: Binary");
    // header("Content-Length:".filesize($attachment_location));
    header("Content-Disposition: attachment; filename=".$filename);
  }

  /** Output metadata in XML format. */
  private function outputMetadataAsXML($writer, $metadata, $format) {
    if($format == 'hist') {
      $header = 'cora-header';
    } else {
      $header = 'header';
    }
    $writer->startElement($header);
    $writer->writeAttribute('sigle', $metadata['data']['sigle']);
    $writer->writeAttribute('name', $metadata['data']['file_name']);
    $writer->writeAttribute('tagset', $metadata['data']['tagset']);
    $writer->writeAttribute('progress', $metadata['lastEditedRow']);
    $writer->endElement(); // 'header'
  }

  /** Output lines in XML format. */
  private function outputLinesAsXML($writer, $fileid, $format) {
    if($format == 'cora') {
      $this->outputLinesAsCoraXML($writer, $fileid);
    } else if($format == 'hist') {
      $this->outputLinesAsHistXML($writer, $fileid);
    }
  }

  private function outputLinesAsCoraXML($writer, $fileid) {
    $count = 1;
    foreach($this->db->getAllLines($fileid) as $line){
      $writer->startElement('token');
      if($line['ext_id']!==null && $line['ext_id']!=='') {
	$writer->writeAttribute('id', $line['ext_id']);
      }
      if(!empty($line['errorChk'])) {
	$writer->writeAttribute('error', $line['errorChk']);
      }
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
      if($line['tag_morph']!==null && $line['tag_morph']!=='' && $line['tag_morph']!=='--'){
	$writer->startElement('infl');
	$writer->writeAttribute('val', $line['tag_morph']);
	$writer->endElement();
      }
      // suggestions
      if($this->output_suggestions){
	$this->outputSuggestionsAsXML($writer, $fileid, $line['line_id'], 'cora');
      }
      // comment
      if($line['comment']!==null && $line['comment']!=='') {
	$writer->writeElement('comment', $line['comment']);
      }
      // closing
      $writer->endElement(); // 'token'
    }
  }

  private function outputLinesAsHistXML($writer, $fileid) {
    $count = 1;
    foreach($this->db->getAllLines($fileid) as $line){
      $writer->startElement('mod');
      $writer->writeAttribute('utf', $line['token']);
      if($line['ext_id']!==null && $line['ext_id']!=='') {
	$writer->writeAttribute('id', $line['ext_id']);
      }
      if(!empty($line['errorChk'])) {
	$writer->writeAttribute('cora-error', $line['errorChk']);
      }
      // norm
      if($line['tag_norm']!==null && $line['tag_norm']!==''){
	$writer->startElement('norm');
	$writer->writeAttribute('tag', $line['tag_norm']);
	$writer->endElement();
      }
      // lemma
      if($line['lemma']!==null && $line['lemma']!==''){
	$writer->startElement('lemma');
	$writer->writeAttribute('tag', $line['lemma']);
	$writer->endElement();
      }
      // pos
      if($line['tag_POS']!==null && $line['tag_POS']!==''){
	$writer->startElement('pos');
	$writer->writeAttribute('tag', $line['tag_POS']);
	$writer->endElement();
      }
      // morph
      if($line['tag_morph']!==null && $line['tag_morph']!==''){
	$writer->startElement('morph');
	$writer->writeAttribute('tag', $line['tag_morph']);
	$writer->endElement();
      }
      // suggestions
      if($this->output_suggestions){
	$this->outputSuggestionsAsXML($writer, $fileid, $line['line_id'], 'hist');
      }
      // comment
      if($line['comment']!==null && $line['comment']!=='') {
	$writer->writeElement('cora-comment', $line['comment']);
      }
      // closing
      $writer->endElement(); // 'mod'
    }
  }

  /** Output tagger suggestions in XML format. */
  private function outputSuggestionsAsXML($writer, $fileid, $lineid, $format) {
    if($format=='cora') {
      $posattr   = 'inst';
      $morph     = 'infl';
      $morphattr = 'val';
    } else {
      $posattr   = 'tag';
      $morph     = 'morph';
      $morphattr = 'tag';
    }
    $writer->startElement('suggestions');
    foreach($this->db->getAllSuggestions($fileid,$lineid) as $sugg){
      if($sugg['tagtype']=='pos') {
	$writer->startElement('pos');
	$writer->writeAttribute($posattr, $sugg['tag_name']);
	$writer->writeAttribute('score', $sugg['tag_probability']);
	$writer->endElement();
      }
      else if($sugg['tagtype']=='morph') {
	$writer->startElement($morph);
	$writer->writeAttribute($morphattr, $sugg['tag_name']);
	$writer->writeAttribute('score', $sugg['tag_probability']);
	$writer->endElement();
      }
    }
    $writer->endElement(); // 'suggestions'
  }

  /** Export a document from the database in XML format.
   *
   * Retrieves all data for a given document from the database and
   * writes the corresponding XML data to the PHP output stream.
   *
   * @param string $fileid Internal ID of the document to export
   * @param string $format XML format to export to (currently
   * accepted: 'cora','hist')
   */
  public function export($fileid,$format) {
    $this->outputXMLHeader($fileid.".xml");

    $writer = new XMLWriter();
    $writer->openURI('php://output');
    $writer->setIndent(true);
    $writer->setIndentString("  ");
    $writer->startDocument('1.0', 'UTF-8'); 

    // fetch metadata
    // openFile() does nothing but fetch file metadata
    $metadata = $this->db->openFile($fileid);
    if(!$metadata['success']) {
      throw new Exception("File metadata could not be retrieved from the database.");
    }

    if($format=='cora') {
      $writer->startElement('cora');
    }
    else if($format=='hist') {
      $writer->startElement('text');
      $writer->writeAttribute('id', $metadata['data']['ext_id']);
    }

    $this->outputMetadataAsXML($writer, $metadata, $format);
    $this->outputLinesAsXML($writer, $fileid, $format);
    $writer->endElement();
    $writer->endDocument();
    $writer->flush();
  }


}

?>
