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

require_once"{$GLOBALS['CORA_WEB_DIR']}/lib/automaticAnnotation.php";

use PHPUnit\Framework\TestCase;

/** A mock DBInterface
 */
class Cora_Tests_DBInterface_AA_Mock {
    private $test_data;

    public $saved_fileid;
    public $saved_lines;

    function __construct() {
        $this->test_data = get_AutomaticAnnotator_data();
    }

    public function getExpected() {
        return $this->test_data["expected"];
    }

    public function getTaggerList() {
        return $this->test_data["taggerlist"];
    }

    public function getTagsetMetadata($idlist) {
        if(in_array('1', $idlist) && in_array('2', $idlist)) {
            return $this->test_data["tagsetlist"];
        }
        return array();
    }

    public function getTaggerOptions($taggerid) {
        return $this->test_data["tagger_options"];
    }

    public function getAllModerns_simple($fileid, $do_anno) {
        return $this->test_data["all_moderns_simple"];
    }

    public function performSaveLines($fileid, $lines_to_save) {
        // store contents so we can test whatever the calling function
        // gives us here
        $this->saved_fileid = $fileid;
        $this->saved_lines = $lines_to_save;
    }

    public function lockProjectForTagger($pid, $tid) {
        return true;
    }

    public function unlockProjectForTagger($pid) { }
}

class Cora_Tests_AutomaticAnnotator_test extends TestCase {
    protected $dbi;
    protected $exp;
    protected $aa;

    protected function setUp() {
        $this->dbi = new Cora_Tests_DBInterface_AA_Mock();
        $this->exp = new Exporter($this->dbi);
        $this->aa = new AutomaticAnnotationWrapper($this->dbi, $this->exp, 1, 7);
    }

    public function testAnnotate() {
        $this->aa->annotate(11);

        $this->assertEquals(11, $this->dbi->saved_fileid);
        $this->assertEquals($this->dbi->getExpected(),
                            $this->dbi->saved_lines);
    }

}
?>
