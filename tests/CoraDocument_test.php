<?php
require_once "../lib/documentModel.php";

/**
 * TODO
 *      setLayoutInfo($pages="", $columns="", $lines="")
 *      mapRangesToIDs()
 *      fillModernIDs($first_id)
 *      fillDiplIDs($first_id)
 *      fillTokenIDs($first_id)
 *      fillLineIDs($first_id)
 *      fillColumnIDs($first_id)
 *      fillPageIDs($first_id)
 *      addComment($tok_id, $xml_id, $text, $type)
 */
class Cora_Tests_CoraDocument_test extends PHPUnit_Framework_TestCase {
    protected $cd;

    protected function setUp() {
        $options = array('sigle' => 't1', 'name' => 'testdocument');
        $this->cd = new CoraDocument($options);
        $this->cd->setTokens(
        array(
            array(
                "db_id" => "1",
                "xml_id" => "1",
                "All|so"),
            array(
                "db_id" => "2",
                "xml_id" => "2",
                "sprach#ete"),
            array(
                "db_id" => "4",
                "xml_id" => "4",
                "Anshelmus")
        ),
        array(
            array(
                "db_id" => "5",
                "xml_id" => "5",
                "parent_tok_xml_id" => "1",
                "Allso"),
            array(
                "db_id" => "6",
                "xml_id" => "6",
                "parent_tok_xml_id" => "2",
                "sprach"),
            array(
                "db_id" => "7",
                "xml_id" => "7",
                "parent_tok_xml_id" => "2",
                "ete"),
            array(
                "db_id" => "8",
                "xml_id" => "8",
                "parent_tok_xml_id" => "4",
                "Anshelmus")
        ),
        array(
            array(
                "db_id" => "9",
                "xml_id" => "9",
                "parent_tok_xml_id" => "1",
                "All"),
            array(
                "db_id" => "10",
                "xml_id" => "10",
                "parent_tok_xml_id" => "1",
                "so"),
            array(
                "db_id" => "11",
                "xml_id" => "11",
                "parent_tok_xml_id" => "2",
                "sprachete"),
            array(
                "db_id" => "12",
                "xml_id" => "12",
                "parent_tok_xml_id" => "4",
                "Anshelmus"))
        );
    }

    public function testFillIDs() {
        $this->cd->fillModernIDs("5");
        $this->cd->fillDiplIDs("10");
        $this->cd->fillTokenIDs("8");

        $this->assertTrue(true);

    }
}
?>
