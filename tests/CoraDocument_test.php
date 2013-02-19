<?php
require_once "../lib/documentModel.php";

/**
 * TODO
 *      mapRangesToIDs()
 *      addComment($tok_id, $xml_id, $text, $type)
 *      setShiftTags($shifttags)
 */



class Cora_Tests_CoraDocument_test extends PHPUnit_Framework_TestCase {
    protected $test_data = array(
        "pages" => array( // page
                        array( "xml_id" => "p1",
                               "side" => 'v',
                               'range' => array('c1', 'c1'),
                               'no' => '42')
                    ),
        "columns" => array( // column
                        array('xml_id' => 'c1',
                              'parent_xml_id' => 'p1',
                              'range' => array('l1', 'l2'))
                    ),
        "lines" => array( // lines
                        array('xml_id' => 'l1',
                              'parent_xml_id' => 'c1',
                              'name' => '01',
                              'range' => array('t1_d1', 't2_d2')),
                        array('xml_id' => 'l2',
                              'parent_xml_id' => 'c1',
                              'name' => '02',
                              'range' => array('t3_d1', 't3_d1'))
                    ),
        "tokens" => array( // tokens
                        array("db_id" => "",
                              "xml_id" => "t1",
                              '$ol|tu'),
                        array("db_id" => "",
                              "xml_id" => "t2",
                              'ge#e$$en'),
                        array("db_id" => "",
                              "xml_id" => "t3",
                              "Anshelm/(.)")
                    ),
        "dipls" => array( // dipl
                        array("db_id" => "",
                              "xml_id" => "t1_d1",
                              "parent_tok_xml_id" => "t1",
                              "parent_line_xml_id" => "l1",
                              "\$ol|tu"),
                        array("db_id" => "",
                              "xml_id" => "t2_d1",
                              "parent_tok_xml_id" => "t2",
                              "parent_line_xml_id" => "l1",
                              "ge#"),
                        array("db_id" => "",
                              "xml_id" => "t2_d2",
                              "parent_tok_xml_id" => "t2",
                              "parent_line_xml_id" => "l1",
                              'e$$en'),
                        array("db_id" => "",
                              "xml_id" => "t3_d1",
                              "parent_tok_xml_id" => "t3",
                              "parent_line_xml_id" => "l2",
                              "Anshelm/")
                   ),
        "mods" => array( // mod
                        array("db_id" => "",
                              "xml_id" => "t1_m1",
                              "parent_xml_id" => "t1",
                              '$ol'),
                        array("db_id" => "",
                              "xml_id" => "t1_m2",
                              "parent_xml_id" => "t1",
                              'tu'),
                        array("db_id" => "",
                              "xml_id" => "t2_m1",
                              "parent_xml_id" => "t1",
                              'ge#e$$en'),
                        array("db_id" => "",
                              "xml_id" => "t3_m1",
                              "parent_xml_id" => "t1",
                              'Anshelm'),
                        array("db_id" => "",
                              "xml_id" => "t3_m2",
                              "parent_xml_id" => "t1",
                              '/'),
                        array("db_id" => "",
                              "xml_id" => "t3_m3",
                              "parent_xml_id" => "t1",
                              '(.)')
                )
    );
    protected $cd;

    protected function setUp() {
        $options = array('sigle' => 't1', 'name' => 'testdocument');
        $this->cd = new CoraDocument($options);
        $this->cd->setLayoutInfo($this->test_data["pages"],
                                 $this->test_data["columns"],
                                 $this->test_data["lines"]);
        $this->cd->setTokens($this->test_data["tokens"],
                             $this->test_data["dipls"],
                             $this->test_data["mods"]);
    }

    /////////////// custom asserts //////////////////////////////////////////

    /** assert id consistency
     *
     * check if the length of actual matches the expected length.
     * check if the IDs are consecutive numbers starting with expected
     * start_id.
     */
    private function assertIDsConsistent($actual, $start_id, $expected_length) {
        $this->assertEquals($expected_length, count($actual));
        for ($i = 0; $i < $expected_length; ++$i) {
            $this->assertEquals($start_id + $i, $actual[$i]["db_id"]);
        }
    }

    /** assert equality of ranges
     */
    private function assertRangesEqual($actual, $expected) {
        for ($i = 0; $i < count($actual); ++$i) {
            $this->assertEquals(
                $expected[$i][0],
                $actual[$i]["range"][0]
            );
            $this->assertEquals(
                $expected[$i][1],
                $actual[$i]["range"][1]
            );
        }
    }


    //////////////// tests ///////////////////////
    public function testLineIDs() {
        $this->cd->fillLineIDs("1");
        $this->assertIDsConsistent($this->cd->getLines(), 1, 2);
    }

    public function testColumnIDs() {
        $this->cd->fillColumnIDs("2");
        $this->assertIDsConsistent($this->cd->getColumns(), 2, 1);
    }

    public function testPageIDs() {
        $this->cd->fillPageIDs("3");
        $this->assertIDsConsistent($this->cd->getPages(), 3, 1);
    }

    public function testModernIDs() {
        $this->cd->fillModernIDs("5");
        $this->assertIDsConsistent($this->cd->getModerns(), 5, 6);
    }

    public function testDiplIDs() {
        $this->cd->fillDiplIDs("10");
        $this->assertIDsConsistent($this->cd->getDipls(), 10, 4);
    }

    public function testTokenIDs() {
        $this->cd->fillTokenIDs("8");
        $this->assertIDsConsistent($this->cd->getTokens(), 8, 3);
    }

    public function testRangeToID() {
        $this->cd->fillTokenIDs("8")
                 ->fillDiplIDs("10")
                 ->fillModernIDs("5")
                 ->fillPageIDs("3")
                 ->fillColumnIDs("2")
                 ->fillLineIDs("1")
                 ->addComment("8", "t1", "Hier grosser Tintenfleck", "K")
                 ->setShiftTags(array(
                     array('range' => array('t1', 't2'), 'type' => 'rub'),
                     array('range' => array('t3', 't3'), 'type' => 'title')
                 ));

        $this->cd->mapRangesToIDs();

        $this->assertRangesEqual($this->cd->getPages(), array(array(0, 1)));

        // things that have ranges:
        // - page
        // - column
        // - line
        // - shift tag
        $this->assertTrue(true);
    }
}
?>
