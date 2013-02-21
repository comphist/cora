<?php
require_once"test_data.php";

require_once"../lib/xmlHandler.php";

/**
 *
 * TODO
 *          export($fileid, $format)
 */

/** A mock DBInterface to trick the XMLHandler with
 */
class Cora_Tests_DBInterface_Mock {
    public $document = null;
    public $options = null;

    public function insertNewDocument($options, $data) {
        $this->document = $data;
        $this->options = $options;
    }
    public function getAllLines($fileid) {
        // XXX needed for export
    }
    public function getAllSuggestions($fileid, $lineid) {
        // XXX needed for export
    }
    public function openFile($fileid) {
        // XXX needed for export
    }
}

class Cora_Tests_XMLHandler_test extends PHPUnit_Framework_TestCase {
    protected $dbi;
    protected $xh;
    protected $test_data;

    protected function setUp() {
        $this->test_data = get_XMLHandler_data();
        $this->dbi = new Cora_Tests_DBInterface_Mock();
        $this->xh = new XMLHandler($this->dbi);
    }

    public function testImport() {
        $options = array();
        $filename = array(
            "tmp_name" => "cora-importtest.xml",
            "name" => "cora-importtest.xml"
        );
        $this->xh->import($filename, $options);

        $this->assertEquals($this->test_data["options"],
                            $this->dbi->options);

        $this->assertEquals($this->test_data["tokens"],
                            $this->dbi->document->getTokens());

        $this->assertEquals($this->test_data["moderns"],
                            $this->dbi->document->getModerns());

        $this->assertEquals($this->test_data["dipls"],
            $this->dbi->document->getDipls());

        $this->assertEquals($this->test_data["lines"],
            $this->dbi->document->getLines());

        $this->assertEquals($this->test_data["columns"],
            $this->dbi->document->getColumns());

        $this->assertEquals($this->test_data["pages"],
            $this->dbi->document->getPages());
        $this->assertEquals($this->test_data["shifttags"],
            $this->dbi->document->getShifttags());
        $this->assertEquals($this->test_data["comments"],
            $this->dbi->document->getComments());

        // currently, the following assert fails since it imports the padding
        // whitespace
        $this->assertEquals($this->test_data["header"],
            $this->dbi->document->getHeader());
    }
    public function testExport() {
        $this->assertTrue(true);
    }
}
?>
