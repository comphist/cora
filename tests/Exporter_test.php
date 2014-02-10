<?php
require_once"data/test_data.php";

require_once"../lib/exporter.php";

/** A mock DBInterface
 */
class Cora_Tests_DBInterface_Mock {
    private $test_data;

    function __construct() {
        $this->test_data = get_Exporter_data();
    }

    public function getExpectedPOS() {
        return $this->test_data["expected_POS"];
    }
    public function getExpectedNorm() {
        return $this->test_data["expected_norm"];
    }
    public function getExpectedTagging_First()
    {
        return $this->test_data["expected_tagging_1"];
    }
    public function getExpectedTagging_Second()
    {
        return $this->test_data["expected_tagging_2"];
    }

    public function getAllTokens($fileid) {
        return $this->test_data["all_tokens"];
    }
}

class Cora_Tests_Exporter_test extends PHPUnit_Framework_TestCase {
    protected $dbi;
    protected $exp;

    protected function setUp() {
        $this->dbi = new Cora_Tests_DBInterface_Mock();
        $this->exp = new Exporter($this->dbi);
    }

    public function testExportPOS() {
        $stream = fopen("php://memory", 'r+');
        $result = $this->exp->export(42, ExportType::Tagging, $stream);
        rewind($stream);

        $this->assertEquals($this->dbi->getExpectedPOS(),
                            stream_get_contents($stream));
        fclose($stream);
    }

    public function testExportNorm() {
        $stream = fopen("php://memory", 'r+');
        $result = $this->exp->export(42, ExportType::Normalization, $stream);
        rewind($stream);

        $this->assertEquals($this->dbi->getExpectedNorm(),
                            stream_get_contents($stream));
        fclose($stream);
    }

    public function testExportForTagging_First() {
        $classes = array("POS", "norm");
        $stream  = fopen("php://memory", 'r+');
        $moderns = $this->exp->exportForTagging(42, $stream, $classes, true);
        rewind($stream);

        $this->assertEquals($this->dbi->getExpectedTagging_First(),
                            stream_get_contents($stream));
        $this->assertEquals($this->dbi->getAllTokens(42)[2],
                            $moderns);
        fclose($stream);
    }

    public function testExportForTagging_Second() {
        $classes = array("norm");
        $stream  = fopen("php://memory", 'r+');
        $moderns = $this->exp->exportForTagging(42, $stream, $classes, false);
        rewind($stream);

        $this->assertEquals($this->dbi->getExpectedTagging_Second(),
                            stream_get_contents($stream));
        $this->assertEquals($this->dbi->getAllTokens(42)[2],
                            $moderns);
        fclose($stream);
    }

}
?>
