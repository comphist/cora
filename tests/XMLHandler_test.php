<?php
require_once"../lib/xmlHandler.php";
require_once"DB_fixture.php";

/**
 *
 * TODO
 *          setOptionsFromHeader($header, $options)
 *          processXMLHeader($reader, $options)
 *          parseRange($range)
 *          processLayoutInformation($node, $document)
 *          processShiftTags($node, $document)
 *          processToken($node, $tokcount $t
 *          tbc, these are all private now
 *
 *          import($xmlfile, $options)
 *          export($fileid, $format)
 *  mockups:
 *          DBInterface
 *          CoraDocument
 *
 *
 */

abstract class Cora_Tests_DBInterface_Mock extends Cora_Tests_DbTestCase {
    public function insertNewDocument($options, $data) {
    }
    public function getAllLines($fileid) {
    }
    public function getAllSuggestions($fileid, $lineid) {
    }
    public function openFile($fileid) {
    }
}

// possibly this isn't testable, since the ctor call is in a private method
// of XMLHandler XXX
class Cora_Tests_CoraDocument_Mock {
    public function setHeader($value) {
    }
    public function setLayoutInfo($pages="", $columns="", $lines="") {
    }
    public function setShiftTags($shifttags) {
    }
}

class Cora_Tests_XMLHandler_test extends Cora_Tests_DBInterface_Mock {
    protected $xh;

    protected function setUp() {
        $this->xh = new XMLHandler($this);
    }

    public function testImport() {
        $options = array();
        $this->xh->import(array("tmp_name" => "cora-importtest.xml"), $options);
        $this->assertTrue(true);
    }
    public function testExport() {
        $this->assertTrue(true);
    }
}
?>
