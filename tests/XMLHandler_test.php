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
 */ ?>
<?php
require_once"data/test_data.php";
require_once"mocks/LocaleHandler_mock.php";
require_once"{$GLOBALS['CORA_WEB_DIR']}/lib/xmlHandler.php";

/**
 *
 * TODO
 *          export($fileid, $format)
 */

/** A mock DBInterface to trick the XMLHandler with
 */
class Cora_Tests_DBInterface_Mock {
    /// the imported document and options, we'll run asserts on these
    public $document;
    public $options;

    // expected and initial data
    private $expected;
    private $initial;

    function __construct() {
        $this->expected = get_XMLHandler_expected();
        $this->initial = get_XMLHandler_initial();
    }

    public function insertNewDocument($options, $data) {
        $this->document = $data;
        $this->options = $options;
        return array('success' => true, 'warnings' => array());
    }
    public function getAllLines($fileid) {
        return $this->initial["lines"];
    }
    public function getAllSuggestions($fileid, $lineid) {
        // XXX needed for export
        return array(
        );
    }
    public function openFile($fileid) {
        if ($fileid == "512") {
            return array("success" => false);
        }

        return array(
            "lastEditedRow" => "",
            "data" => array(
                "id" => "",
                "sigle" => "",
                "fullname" => "",
                "project_id" => "",
                "created" => "",
                "creator_id" => "",
                "changed" => "",
                "changer_id" => "",
                "currentmod_id" => "",
                "ext_id" => "",
                "file_name" => "bla",
                "tagset" => "",
                "header" => $this->expected["header"]
            ),
            "success" => true
        );
    }
}

class Cora_Tests_XMLHandler_test extends PHPUnit_Framework_TestCase {
    protected $dbi;
    protected $xh;
    protected $test_data;

    protected function setUp() {
        $this->test_data = get_XMLHandler_expected();
        $this->dbi = new Cora_Tests_DBInterface_Mock();
        $this->xh = new XMLHandler($this->dbi, new MockLocaleHandler());
    }

    public function testImport() {
        $options = array('ext_id' => '');
        $filename = array(
            "tmp_name" => "data/cora-importtest.xml",
            "name" => "cora-importtest.xml"
        );
        $this->xh->import($filename, $options, 1);

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

    public function testExportNonexistent() {
        try{
            // exporting a nonexistent file must throw
	    // MB: not even implemented yet, leaving lines in for
	    // future implementation
	    // $this->xh->export("512", "cora");
        } catch (Exception $e) {
            return;
        }
	return;
        // $this->fail("Exporting nonexistent file didn't throw exception!");
    }

    public function testExport() {
        // not yet implemented
        return;
        $result_filename = "";
        $this->xh->export("1", "cora");
        $this->assertFileEquals("data/cora-importtest.xml",
                                $result_filename);
    }
}
?>
