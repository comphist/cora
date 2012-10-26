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
  private $xml_header_options; /**< Valid attributes for the XML <header> tag. */

  function __construct($db) {
    $this->db = $db;
    $this->output_suggestions = true;
    $this->xml_header_options = array('sigle','name','tagset','progress');
  }

  /****** FUNCTIONS RELATED TO DATA IMPORT ******/

  /** Process header information. */
  private function processXMLHeader(&$reader, &$options) {
    $doc = new DOMDocument();
    // any data before the header is skipped!
    while ($reader->read() && $reader->name !== 'header');
    if ($reader->name !== 'header') { // EOF reached
      return "XML-Fehler: header-Tag nicht gefunden.";
    }
    $header = simplexml_import_dom($doc->importNode($reader->expand(), true));
    // get header attributes if they are not already set in $options
    foreach($this->xml_header_options as $key) {
      if (isset($header[$key]) && !empty($header[$key])
	  && (!isset($options[$key]) || empty($options[$key]))) {
	$options[$key] = (string) $header[$key];
      }
    }
    return False;
  }

  /** Process XML data. */
  private function processXMLData(&$reader) {
    $doc = new DOMDocument();
    $data = array();

    while ($reader->read()) {
      // only handle opening tags
      if ($reader->nodeType!==XMLReader::ELEMENT) { continue; }

      if ($reader->name == 'token') {
	$node = simplexml_import_dom($doc->importNode($reader->expand(), true));
	$token = array();
	// some of these can possibly be empty
	$token['id']      = $node['id'];
	$token['error']   = $node['error'];
	$token['form']    = $node->form['dipl'];
	$token['norm']    = $node->form['norm'];
	$token['lemma']   = $node->lemma['inst'];
	$token['pos']     = $node->pos['inst'];
	$token['morph']   = $node->infl['val'];
	$token['comment'] = $node->comment;
	$suggs = array();
	$posindex = 0;
	$morphindex = 0;
	foreach($node->suggestions->pos as $sugg){
	  $suggs[] = array('type'=>'pos',
			   'value'=>$sugg['inst'],
			   'score'=>$sugg['score'],
			   'index'=>$posindex++);
	}
	foreach($node->suggestions->infl as $sugg){
	  $suggs[] = array('type'=>'morph',
			   'value'=>$sugg['val'],
			   'score'=>$sugg['score'],
			   'index'=>$morphindex++);
	}
	$token['suggestions'] = $suggs;
	$data[] = $token;
      }
      // could add further 'if' statements to process <boundary>
      // elements, <page> groupings, etc.
    }

    return $data;
  }

  /** Check if data should be considered normalized, POS-tagged,
   *  and/or morph-tagged; and, if tags are present, whether they
   *  conform to the chosen tagset.
   */
  private function checkIntegrity(&$options, &$data) {
    $lines  = 0;
    $norm   = 0;
    $pos    = 0;
    $morph  = 0;
    $warnings = array();
    $posset   = array();
    $morphset = array();

    // check names
    if(isset($options['name']) && !empty($options['name'])) {
      if($this->db->queryForMetadata("file_name", $options['name'])){
	$warnings[] = "Ein Dokument mit dem Namen '".$options['name']."' existiert bereits.";
      }
    }

    if(isset($options['sigle']) && !empty($options['sigle'])) {
      if($this->db->queryForMetadata("sigle", $options['sigle'])){
	$warnings[] = "Ein Dokument mit der Sigle '".$options['sigle']."' existiert bereits.";
      }
    }

    // load tagset
    if(isset($options['tagset']) && !empty($options['tagset'])){
      // @hack hardcoded language:
      $tagset = $this->db->getTagset($options['tagset'], 'de');
      if(empty($tagset)){
	$warnings[] = "Tagset '".$options['tagset']
	  ."' existiert nicht oder ist leer.";
      } else {
	$attribs = array();
	foreach($tagset['attribs'] as $id=>$attrib){
	  $attribs[$attrib['shortname']] = $id;
	}
	ksort($attribs);
	// collect POS tags in a list
	foreach($tagset['tags'] as $id=>$tag){
	  $posset[] = (string) $tag['shortname'];
	  // build all valid morph tag combinations
	  $combinations = array();
	  foreach($attribs as $att_name=>$att_id){
	    if(in_array($att_id, $tag['link'])){
	      if(empty($combinations)){
		$combinations = $tagset['attribs'][$att_id]['val'];
	      } else {
		$newcomb = array();
		foreach($combinations as $prev){
		  foreach($tagset['attribs'][$att_id]['val'] as $next) {
		    $newcomb[] = $prev.".".$next;
		  }
		}
		$combinations = $newcomb;
	      }
	    }
	  }
	  if(empty($combinations)) { $combinations = array("--"); }
	  $morphset[$tag['shortname']] = $combinations;
	}
      }
    } else {
      $tagset = False;
    }

    // check data
    foreach($data as $line_id=>$token){
      $lines++;
      $poserror = False;
      if(!empty($token['norm'])) { $norm++; }
      if($tagset && !empty($token['pos'])) { 
	$pos++;
	$postag = (string) $token['pos'];
	if(!in_array($postag, $posset)){
	  $warning = "Token ".$line_id;
	  if(!empty($token['id'])) {
	    $warning = $warning . " (" . $token['id'] . ")";
	  }
	  $warning = $warning . ": '" . $postag ."' ist kein gültiger POS-Tag.";
	  $warnings[] = $warning;
	  $poserror = True;
	}
	if(!empty($token['morph'])) { 
	  $morph++;
	  $morphtag = (string) $token['morph'];
	  if(!$poserror && !empty($morphset[$postag]) &&
	     !in_array($morphtag,$morphset[$postag])) {
	    $warning = "Token ".$line_id;
	    if(!empty($token['id'])) {
	      $warning = $warning . " (" . $token['id'] . ")";
	    }
	    $warning = $warning.": '".$morphtag
	      ."' ist kein gültiger Morphologie-Tag für Wortart '"
	      .$postag."'.";
	    $warnings[] = $warning;
	  }
	}
      }
    }

    $threshold = 0.9 * $lines;
    $options['norm'] = ($norm>$threshold) ? 1 : 0;
    $options['POS_tagged'] = ($pos>$threshold) ? 1 : 0;
    $options['morph_tagged'] = ($morph>$threshold) ? 1 : 0;

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
    $xmlerror = $this->processXMLHeader($reader, $options);
    if($xmlerror){
      return array("success"=>False, 
		   "errors"=>array($xmlerror));
    }
    $data = $this->processXMLData($reader);
    $reader->close();

    // check for data integrity
    $warnings = $this->checkIntegrity($options, $data);
    if(!(isset($options['name']) && !empty($options['name'])) &&
       !(isset($options['sigle']) && !empty($options['sigle']))) {
      array_unshift($warnings, "Dokument hat weder Name noch Sigle; benutze Dateiname als Dokumentname.");
      $options['name'] = $xmlfile['name'];
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

  /** Export a document from the database in XML format.
   *
   * Retrieves all data for a given document from the database and
   * writes the corresponding XML data to the PHP output stream.
   *
   * @param string $fileid Internal ID of the document to export
   */
  public function export($fileid) {
    $this->outputXMLHeader($fileid.".xml");

    $writer = new XMLWriter();
    $writer->openURI('php://output');
    $writer->setIndent(true);
    $writer->setIndentString("  ");
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