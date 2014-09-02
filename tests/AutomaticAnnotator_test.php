<?php
require_once"data/test_data.php";

require_once"../lib/automaticAnnotation.php";

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

class Cora_Tests_AutomaticAnnotator_test extends PHPUnit_Framework_TestCase {
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
