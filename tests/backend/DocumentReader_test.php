<?php 
/*
 * Copyright (C) 2016 Marcel Bollmann <bollmann@linguistics.rub.de>
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
require_once 'db_fixture/fixture.php';
require_once 'mocks/DocumentAccessor_mocks.php';
require_once "{$GLOBALS['CORA_WEB_DIR']}/lib/connect/DocumentReader.php";

class Cora_Tests_DocumentReader_test extends Cora_Tests_DbTestCase {
    private $dbo;
    private $obj; /**< The object under test */
    private $fileid = '2';
    protected $dbCleanInsertBeforeEveryTest = false;
    static protected $fixtureSet = false;

    public function setUp() {
        $this->dbo = new PDO($GLOBALS["DB_DSN"],
                             $GLOBALS["DB_USER"],
                             $GLOBALS["DB_PASSWD"]);
        $this->obj = new DocumentReader(new Cora_Tests_DBInterface_Mock(), $this->dbo, $this->fileid);
        parent::setUp();
    }

    public function testGetLinesByRange_normal() {
        $lines = $this->obj->getLinesByRange(5, 3);
        $this->assertCount(3, $lines,
                           "Requesting lines by range should return the requested number of lines");
        $expected = array(
            ["id" => 255, "trans" => "verleumdet"],
            ["id" => 256, "trans" => "haben"],
            ["id" => 257, "trans" => ","]
        );
        $this->assertArraySubset($expected, $lines, $strict=false,
                                 "Requesting lines by range should return the correct lines");
    }

    public function testGetLinesByRange_end() {
        $lines = $this->obj->getLinesByRange(247, 10);
        $this->assertCount(2, $lines,
                           "Requesting more lines than are available should return as much as possible");
        $expected = array(
            ["id" => 497, "trans" => "erfahren"],
            ["id" => 498, "trans" => "."]
        );
        $this->assertArraySubset($expected, $lines, $strict=false,
                                 "Requesting lines by range should return the correct lines");
    }

    public function testGetLinesByRange_invalid() {
        $lines = $this->obj->getLinesByRange(512, 5);
        $this->assertEmpty($lines, "Requesting an invalid range should return an empty array");
    }

    public function testGetLinesByID_normal() {
        $lines = $this->obj->getLinesByID([255, 256, 497]);
        $this->assertCount(3, $lines,
                           "Requesting lines by ID should return the requested number of lines");
        $expected = array(
            ["id" => 255, "trans" => "verleumdet"],
            ["id" => 256, "trans" => "haben"],
            ["id" => 497, "trans" => "erfahren"]
        );
        $this->assertArraySubset($expected, $lines, $strict=false,
                                 "Requesting lines by ID should return the correct lines");
    }

    public function testGetLinesBy_crosscheck() {
        $linesByID = $this->obj->getLinesByID([260, 261, 262, 263, 264]);
        $linesByRange = $this->obj->getLinesByRange(10, 5);
        $this->assertEquals($linesByID, $linesByRange,
                            "Requesting lines by ID and range should return identical data");
    }

    public function testGetTokTransWithLinebreaks() {
        $trans = $this->obj->getTokTransWithLinebreaks(209);
        $this->assertEquals("verleumdet", $trans,
                            "Transcription without line breaks should be returned verbatim");
        $trans = $this->obj->getTokTransWithLinebreaks(362);
        $this->assertEquals("dadurch|ge#wisser=\nmaßen", $trans,
                            "Line break in the transcription should be represented by a newline");
    }

    public function testGetDiplLayoutInfo() {
        $actual = $this->obj->getDiplLayoutInfo(243);
        $expected = array(
            "page_name" => "03", "page_side" => "v", "col_name" => "a", "line_name" => "01"
        );
        $this->assertEquals($expected, $actual, "Should return correct layout information");
        $actual = $this->obj->getDiplLayoutInfo(209);
        $expected = array(
            "page_name" => "02", "page_side" => "r", "col_name" => "", "line_name" => "01"
        );
        $this->assertEquals($expected, $actual, "Non-existant columns name should be an empty string");
        $actual = $this->obj->getDiplLayoutInfo(362);
        $expected = array(
            "page_name" => "06", "page_side" => "", "col_name" => "", "line_name" => "01"
        );
        $this->assertEquals($expected, $actual, "Should return layout information of first diplomatic token");
    }

    public function testGetAllAnnotations() {
        $actual = $this->obj->getAllAnnotations(255);
        $expected = array(
            ["class" => "pos", "value" => "VVPP", "selected" => 1, "score" => null, "source" => "user"],
            ["class" => "lemma", "value" => "verleumden", "selected" => 1, "score" => null, "source" => "user"],
            ["class" => "lemmapos", "value" => "VERB", "selected" => 1, "score" => null, "source" => "user"],
            ["class" => "norm", "value" => "verleumdet", "selected" => 1, "score" => null, "source" => "user"],
            ["class" => "norm_broad", "value" => "diffamiert", "selected" => 1, "score" => null, "source" => "user"],
            ["class" => "norm_type", "value" => "x", "selected" => 1, "score" => null, "source" => "user"],
            ["class" => "comment", "value" => "word not in lemma list", "selected" => 1, "score" => null, "source" => "user"],
            ["class" => "sec_comment", "value" => "foo bar", "selected" => 1, "score" => null, "source" => "user"]
        );
        foreach($expected as $exp) {
            $this->assertContains($exp, $actual, "Retrieving annotations should contain all annotations");
        }
        $this->assertCount(8, $actual, "Retrieving annotations should return correct number of annotations");

        $actual = $this->obj->getAllAnnotations(256);
        $expected = array(
            ["class" => "pos", "value" => "VAINF", "selected" => 1, "score" => null, "source" => "user"],
            ["class" => "lemma", "value" => "haben", "selected" => 1, "score" => null, "source" => "user"],
            ["class" => "lemmapos", "value" => "VERB", "selected" => 1, "score" => null, "source" => "user"],
            ["class" => "norm", "value" => "haben", "selected" => 1, "score" => null, "source" => "user"]
        );
        foreach($expected as $exp) {
            $this->assertContains($exp, $actual, "Retrieving annotations should contain all annotations");
        }
        $this->assertCount(4, $actual, "Retrieving annotations should return correct number of annotations");

        $actual = $this->obj->getAllAnnotations(300);
        $this->assertEmpty($actual, "Annotations should be empty when no annotations exist");
    }
}

?>
