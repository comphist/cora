<?php
/*
 * Copyright (C) 2015 Marcel Bollmann <bollmann@linguistics.rub.de>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy of
 * this software and associated documentation files (the "Software"), to deal in
 * the Software without restriction, including without limitation the rights to
 * use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of
 * the Software, and to permit persons to whom the Software is furnished to do so,
 * subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS
 * FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR
 * COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER
 * IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN
 * CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
*/
?>
<?php
/** @file xmlHandler.php
 * Import and export of files as XML data.
 *
 * @author Marcel Bollmann
 * @date May 2012, updated February 2013
 */
require_once ("documentModel.php");

/** Provides functions specific to handling CorA-XML format. */
class XMLHandler {
    private $db; /**< A DBInterface object. */
    private $output_suggestions; /**< Boolean indicating whether to output tagger suggestions. */
    private $xml_header_options; /**< Valid attributes for the XML <header> tag. */

    function __construct($db, $lh) {
        $this->db = $db;
        $this->lh = $lh;
        $this->output_suggestions = true;
        $this->xml_header_options = array('sigle', 'name', 'tagset', 'progress');
    }

    /****** FUNCTIONS RELATED TO DATA IMPORT ******/

    private function setOptionsFromHeader(&$header, &$options) {
        // get header attributes if they are not already set in $options
        foreach ($this->xml_header_options as $key) {
            if (isset($header[$key]) && !empty($header[$key])
                && (!isset($options[$key]) || empty($options[$key]))) {
                $options[$key] = (string)$header[$key];
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
                    $options['ext_id'] = (string)$header['id'];
                }
                return False;
            }
        }
        return $this->lh->_("XMLError.noTextTag");
    }

    /** Parses a range string ("t1..t4" or just "t1") into an array
     containing beginning and end of the range.
     */
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
        foreach ($node->page as $pagenode) {
            $page = array();
            $page['xml_id'] = (string)$pagenode['id'];
            $page['side'] = (string)$pagenode['side'];
            $page['name'] = (string)$pagenode['no'];
            $page['num'] = ++$pagecount;
            $page['range'] = $this->parseRange((string)$pagenode['range']);
            $pages[] = $page;
        }
        // columns
        foreach ($node->column as $colnode) {
            $column = array();
            $column['xml_id'] = (string)$colnode['id'];
            $column['name'] = (string)$colnode['name'];
            $column['num'] = ++$colcount;
            $column['range'] = $this->parseRange((string)$colnode['range']);
            $columns[] = $column;
        }
        // lines
        foreach ($node->line as $linenode) {
            $line = array();
            $line['xml_id'] = (string)$linenode['id'];
            $line['name'] = (string)$linenode['name'];
            $line['num'] = ++$linecount;
            $line['range'] = $this->parseRange((string)$linenode['range']);
            $lines[] = $line;
        }
        $document->setLayoutInfo($pages, $columns, $lines);
    }

    /** Process shift tag information. */
    private function processShiftTags(&$node, &$document) {
        $shifttags = array();
        $type_to_letter = array("rub" => "R", "title" => "T", "lat" => "L", "marg" => "M", "fm" => "F");
        foreach ($node->children() as $tagnode) {
            $shifttag = array();
            $shifttag['type'] = (string)$tagnode->getName();
            $shifttag['type_letter'] = $type_to_letter[$shifttag['type']];
            $shifttag['range'] = $this->parseRange((string)$tagnode['range']);
            $shifttags[] = $shifttag;
        }
        $document->setShiftTags($shifttags);
    }

    private function processToken(&$document, &$node, &$tokcount, &$t, &$d, &$m) {
        $token = array();
        $thistokid = (string)$node['id'];
        $token['xml_id'] = (string)$node['id'];
        $token['trans'] = (string)$node['trans'];
        $token['ordnr'] = $tokcount;
        $t[] = $token;
        // diplomatic tokens
        foreach ($node->dipl as $diplnode) {
            $dipl = array();
            $dipl['xml_id'] = (string)$diplnode['id'];
            $dipl['trans'] = (string)$diplnode['trans'];
            $dipl['utf'] = (string)$diplnode['utf'];
            $dipl['parent_tok_xml_id'] = $thistokid;
            $d[] = $dipl;
        }
        // modern tokens
        foreach ($node->mod as $modnode) {
            $modern = array('tags' => array(), 'flags' => array());
            $modern['xml_id'] = (string)$modnode['id'];
            $modern['trans'] = (string)$modnode['trans'];
            $modern['ascii'] = (string)$modnode['ascii'];
            $modern['utf'] = (string)$modnode['utf'];
            $modern['chk'] = (((string)$modnode['checked']) === "y");
            $modern['parent_xml_id'] = $thistokid;
            // first, parse all automatic suggestions
            if ($modnode->suggestions) {
                foreach ($modnode->suggestions->children() as $suggnode) {
                    $sugg = array('source' => 'auto', 'selected' => 0);
                    $sugg['type'] = $suggnode->getName();
                    $sugg['tag'] = (string)$suggnode['tag'];
                    $sugg['score'] = (string)$suggnode['score'];
                    $modern['tags'][] = $sugg;
                }
            }
            // then, parse all selected annotations
            foreach ($modnode->children() as $annonode) {
                $annotype = strtolower($annonode->getName());
                // CorA-internal comment -- should now be represented in XML as
                // <comment tag=""/>, but this is retained for compatibility reasons
                if ($annotype == 'cora-comment') {
                    $modern['tags'][] = array('source' => 'user',
                                              'selected' => 1,
                                              'score' => null,
                                              'type' => 'comment',
                                              'tag' => (string)$annonode);
                }
                // CorA-internal flag
                else if ($annotype == 'cora-flag') {
                    $modern['flags'][] = (string)$annonode['name'];
                }
                // annotation
                else if ($annotype !== 'suggestions') {
                    $annotag = (string)$annonode['tag'];
                    $found = false;
                    // loop over all suggestions to check whether the annotation
                    // is included there, and if so, select it
                    foreach ($modern['tags'] as & $sugg) {
                        if ($sugg['type'] == $annotype && $sugg['tag'] == $annotag) {
                            $sugg['selected'] = 1;
                            $found = true;
                            break;
                        }
                    }
                    unset($sugg);
                    // if it is not, create a new entry for it
                    if (!$found) {
                        $sugg = array('source' => 'user', 'selected' => 1, 'score' => null);
                        $sugg['type'] = $annotype;
                        $sugg['tag'] = $annotag;
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
        $document = new CoraDocument($options, $this->lh);
        $tokens = array();
        $dipls = array();
        $moderns = array();
        $token['xml_id'] = "t0";
        $token['trans'] = "";
        $token['ordnr'] = 1;
        $tokens[] = $token;
        $tokcount = 1;
        $lasttokid = "t0";
        while ($reader->read()) {
            // only handle opening tags
            if ($reader->nodeType !== XMLReader::ELEMENT) {
                continue;
            }
            $node = simplexml_import_dom($doc->importNode($reader->expand(), true));
            if ($reader->name == 'cora-header') {
                $this->setOptionsFromHeader($node, $options);
            } else if ($reader->name == 'header') {
                $document->setHeader(trim((string)$node));
            } else if ($reader->name == 'layoutinfo') {
                $this->processLayoutInformation($node, $document);
            } else if ($reader->name == 'shifttags') {
                $this->processShiftTags($node, $document);
            } else if ($reader->name == 'comment') {
                $document->addComment(null, $lasttokid, (string)$node, (string)$node['type']);
            } else if ($reader->name == 'token') {
                ++$tokcount;
                $lasttokid = $this->processToken($document, $node, $tokcount, $tokens, $dipls, $moderns);
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
     * @param string $uid User ID of the document's creator
     */
    public function import($xmlfile, $options, $uid) {
        // check for validity
        libxml_use_internal_errors(true);
        $doc = new DOMDocument('1.0', 'utf-8');
        $doc->loadXML(file_get_contents($xmlfile['tmp_name']));
        $errors = libxml_get_errors();
        if (!empty($errors) && $errors[0]->level > 0) {
            $message = $this->lh->_("XMLError.invalid");
            $message.= "\n" . $errors[0]->message . ' at line ' . $errors[0]->line . '.';
            return array("success" => False,
                         "errors" => array($this->lh->_("XMLError.generic") . " " . $message));
        }
        // process XML
        $reader = new XMLReader();
        if (!$reader->open($xmlfile['tmp_name'])) {
            return array("success" => False,
                         "errors" => $this->lh->_("ServerError.internal", array("code" => "xml1")));
        }
        $format = '';
        $xmlerror = $this->processXMLHeader($reader, $options, $format);
        if ($xmlerror) {
            $reader->close();
            return array("success" => False, "errors" => array($xmlerror));
        }
        try {
            $data = $this->processXMLData($reader, $options, $format);
        }
        catch(DocumentValueException $e) {
            $reader->close();
            return array("success" => False, "errors" => array($e->getMessage()));
        }
        $reader->close();
        // check for data integrity
        $warnings = $this->checkIntegrity($options, $data);
        if (!(isset($options['name']) && !empty($options['name']))
            && !(isset($options['sigle']) && !empty($options['sigle']))) {
            array_unshift($warnings, $this->lh->_("XMLError.missingNameAndID"));
            $options['name'] = $xmlfile['name'];
        }
        if (!(isset($options['ext_id']) && !empty($options['ext_id']))) {
            $options['ext_id'] = '';
        }
        // insert data into database
        $status = $this->db->insertNewDocument($options, $data, $uid);
        if (!$status['success']) {
            return array("success" => false, "errors" => $status['warnings']);
        }
        return array("success" => True, "warnings" => array_merge($warnings, $status['warnings']));
    }

    /****** FUNCTIONS RELATED TO DATA EXPORT ******/

    public function serializeDocument($document) {
        // prepare document
        $document->mapIDsToRanges();
        $tokens = $document->getTokens();
        $dipls = $document->getDiplsByTokenID();
        $moderns = $document->getModernsByTokenID();
        $comments = $document->getCommentsByTokenID();
        $pages = $document->getPages();
        $columns = $document->getColumns();
        $lines = $document->getLines();
        $id_map = $this->generateXMLIDs($tokens, $dipls, $moderns, $pages, $columns, $lines);
        // create DOM object
        $doc = new DOMDocument();
        $root = $doc->createElement('text');
        $root->setAttribute('id', $document->getSigle());
        $doc->appendChild($root);
        $this->serializeHeader($document, $doc, $root);
        // layoutinfo
        $this->serializeLayout($pages, $columns, $lines, $doc, $root);
        // shifttags
        $this->serializeShiftTags($document->getShiftTags(), $id_map["tokens"], $doc, $root);
        // first token is virtual---only used to attach comments
        $currenttok = array_shift($tokens);
        if (array_key_exists($currenttok['db_id'], $comments)) {
            $this->serializeComments($comments[$currenttok['db_id']], $doc, $root);
        }
        // <token>s
        foreach ($tokens as & $currenttok) {
            $tok_id = $currenttok['db_id'];
            $xmltoken = $doc->createElement('token');
            $xmltoken->setAttribute('id', $currenttok['xml_id']);
            $xmltoken->setAttribute('trans', $currenttok['trans']);
            if (array_key_exists($tok_id, $dipls)) {
                $this->serializeDipls($dipls[$tok_id], $doc, $xmltoken);
            }
            if (array_key_exists($tok_id, $moderns)) {
                $this->serializeModerns($moderns[$tok_id], $doc, $xmltoken);
            }
            $root->appendChild($xmltoken);
            // comments go between tokens, so check for them here
            if (array_key_exists($tok_id, $comments)) {
                $this->serializeComments($comments[$tok_id], $doc, $root);
            }
        }
        unset($currenttok);
        return $doc;
    }

    private function serializeHeader(&$document, &$doc, &$root) {
        $header = $doc->createElement('cora-header');
        $header->setAttribute('sigle', $document->getSigle());
        $header->setAttribute('name', $document->getName());
        $root->appendChild($header);
        $header = $doc->createElement('header');
        $hdrtxt = $doc->createTextNode($document->getHeader());
        $header->appendChild($hdrtxt);
        $root->appendChild($header);
    }

    private function serializeLayout(&$pages, &$columns, &$lines, &$doc, &$root) {
        $container = $doc->createElement('layoutinfo');
        foreach ($pages as & $currentpage) {
            $elem = $doc->createElement('page');
            $elem->setAttribute('id', $currentpage['xml_id']);
            $elem->setAttribute('side', $currentpage['side']);
            $elem->setAttribute('no', $currentpage['name']);
            $elem->setAttribute('range', $currentpage['xml_range']);
            $container->appendChild($elem);
        }
        foreach ($columns as & $currentcol) {
            $elem = $doc->createElement('column');
            $elem->setAttribute('id', $currentcol['xml_id']);
            if (!empty($currentcol['name'])) {
                $elem->setAttribute('name', $currentcol['name']);
            }
            $elem->setAttribute('range', $currentcol['xml_range']);
            $container->appendChild($elem);
        }
        foreach ($lines as & $currentline) {
            $elem = $doc->createElement('line');
            $elem->setAttribute('id', $currentline['xml_id']);
            if (!empty($currentline['name'])) {
                $elem->setAttribute('name', $currentline['name']);
            }
            $elem->setAttribute('range', $currentline['xml_range']);
            $container->appendChild($elem);
        }
        $root->appendChild($container);
        unset($currentpage);
        unset($currentcol);
        unset($currentline);
    }

    private function serializeShiftTags($shifttags, &$id_map, &$doc, &$root) {
        $letter_to_tag = array("R" => "rub", "T" => "title", "L" => "lat", "M" => "marg", "F" => "fm");
        $container = $doc->createElement('shifttags');
        foreach ($shifttags as & $shifttag) {
            if (array_key_exists($shifttag['type_letter'], $letter_to_tag)) {
                $tagname = $letter_to_tag[$shifttag['type_letter']];
            } else {
                $tagname = $shifttag['type_letter'];
            }
            $elem = $doc->createElement($tagname);
            list($from_id, $to_id) = $shifttag['db_range'];
            $range = $id_map[$from_id] . ".." . $id_map[$to_id];
            $elem->setAttribute('range', $range);
            $container->appendChild($elem);
        }
        unset($shifttag);
        $root->appendChild($container);
    }

    private function serializeDipls(&$dipls, &$doc, &$root) {
        foreach ($dipls as & $dipl) {
            $elem = $doc->createElement('dipl');
            $elem->setAttribute('id', $dipl['xml_id']);
            $elem->setAttribute('trans', $dipl['trans']);
            $elem->setAttribute('utf', $dipl['utf']);
            $root->appendChild($elem);
        }
        unset($dipl);
    }

    private function serializeModerns(&$moderns, &$doc, &$root) {
        foreach ($moderns as & $mod) {
            $elem = $doc->createElement('mod');
            $elem->setAttribute('id', $mod['xml_id']);
            $elem->setAttribute('trans', $mod['trans']);
            $elem->setAttribute('utf', $mod['utf']);
            $elem->setAttribute('ascii', $mod['ascii']);
            $elem->setAttribute('checked', ($mod['verified'] ? 'y' : 'n'));
            $suggestions = $doc->createElement('suggestions');
            foreach ($mod['tags'] as & $currenttag) {
                if (empty($currenttag['tag'])) {
                    continue;
                }
                // tagset type is lowercased ... maybe define a mapping instead?
                $anno = $doc->createElement(strtolower($currenttag['type']));
                $anno->setAttribute('tag', $currenttag['tag']);
                if ($currenttag['selected'] == 1) {
                    $elem->appendChild($anno);
                } else {
                    if (!is_null($currenttag['score'])) {
                        $anno->setAttribute('score', $currenttag['score']);
                    }
                    if (!is_null($currenttag['source'])) {
                        $anno->setAttribute('source', $currenttag['source']);
                    }
                    $suggestions->appendChild($anno);
                }
            }
            unset($currenttag);
            if ($suggestions->hasChildNodes()) {
                $elem->appendChild($suggestions);
            }
            // CorA-internal flags
            foreach ($mod['flags'] as & $currentflag) {
                $flag = $doc->createElement('cora-flag');
                $flag->setAttribute('name', $currentflag);
                $elem->appendChild($flag);
            }
            unset($currentflag);
            $root->appendChild($elem);
        }
        unset($moderns);
    }

    private function serializeComments(&$comments, &$doc, &$root) {
        foreach ($comments as & $comment) {
            // CorA-comments; should no longer exist, but let's guard against
            // them anyway:
            if ($comment['type'] == "C") continue;
            // True "in-between tokens" comments:
            $elem = $doc->createElement('comment');
            $elem->setAttribute('type', $comment['type']);
            $txt = $doc->createTextNode($comment['text']);
            $elem->appendChild($txt);
            $root->appendChild($elem);
        }
        unset($comment);
    }

    /** Generates XML IDs for tokens, dipls, and moderns, and returns
     arrays mapping DB IDs to XML IDs.
     */
    private function generateXMLIDs(&$tokens, &$dipls, &$moderns, &$pages, &$columns, &$lines) {
        $tok_db_to_xml = array();
        $dipl_db_to_xml = array();
        $tid = 0; // so empty virtual token gets 0
        foreach ($tokens as & $currenttok) {
            $dbid = $currenttok['db_id'];
            $xmlid = 't' . $tid++;
            $tok_db_to_xml[$dbid] = $xmlid;
            $currenttok['xml_id'] = $xmlid;
            $dipl_id = 1;
            $mod_id = 1;
            if (isset($dipls[$dbid])) {
                foreach ($dipls[$dbid] as & $currentdipl) {
                    $dipl_xmlid = $xmlid . "_d" . $dipl_id++;
                    $dipl_db_to_xml[$currentdipl['db_id']] = $dipl_xmlid;
                    $currentdipl['xml_id'] = $dipl_xmlid;
                }
            }
            unset($currentdipl);
            if (isset($moderns[$dbid])) {
                foreach ($moderns[$dbid] as & $currentmod) {
                    $mod_xmlid = $xmlid . "_m" . $mod_id++;
                    $currentmod['xml_id'] = $mod_xmlid;
                }
            }
            unset($currentmod);
        }
        unset($currenttok);
        $line_db_to_xml = array();
        $col_db_to_xml = array();
        $lid = 1;
        foreach ($lines as & $currentline) {
            $dbid = $currentline['db_id'];
            $xmlid = 'l' . $lid++;
            $line_db_to_xml[$dbid] = $xmlid;
            $currentline['xml_id'] = $xmlid;
            if (array_key_exists('range', $currentline)) {
                list($from, $to) = $currentline['range'];
                if ($from == $to) {
                    $currentline['xml_range'] = $dipl_db_to_xml[$from];
                } else {
                    $currentline['xml_range'] = $dipl_db_to_xml[$from] . ".." . $dipl_db_to_xml[$to];
                }
            }
        }
        $lid = 1;
        foreach ($columns as & $currentcol) {
            $dbid = $currentcol['db_id'];
            $xmlid = 'c' . $lid++;
            $col_db_to_xml[$dbid] = $xmlid;
            $currentcol['xml_id'] = $xmlid;
            if (array_key_exists('range', $currentcol)) {
                list($from, $to) = $currentcol['range'];
                if ($from == $to) {
                    $currentcol['xml_range'] = $line_db_to_xml[$from];
                } else {
                    $currentcol['xml_range'] = $line_db_to_xml[$from] . ".." . $line_db_to_xml[$to];
                }
            }
        }
        $lid = 1;
        foreach ($pages as & $currentpage) {
            $dbid = $currentpage['db_id'];
            $xmlid = 'p' . $lid++;
            $currentpage['xml_id'] = $xmlid;
            if (array_key_exists('range', $currentpage)) {
                list($from, $to) = $currentpage['range'];
                if ($from == $to) {
                    $currentpage['xml_range'] = $col_db_to_xml[$from];
                } else {
                    $currentpage['xml_range'] = $col_db_to_xml[$from] . ".." . $col_db_to_xml[$to];
                }
            }
        }
        return array("tokens" => $tok_db_to_xml, "dipls" => $dipl_db_to_xml);
    }
}
?>
