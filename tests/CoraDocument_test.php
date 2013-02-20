<?php
require_once "../lib/documentModel.php";

/** Tests for CoraDocument
 *
 * 02/2013 Florian Petran
 */
class Cora_Tests_CoraDocument_test extends PHPUnit_Framework_TestCase {
    protected $test_data;
    // initial test data. $this->test_data is reset to the initial data
    // before each test.
    protected $test_data_initial = array(
        "pages" => array( // page
                        array( "xml_id" => "p1",
                               "side" => 'v',
                               'range' => array('c1', 'c1'),
                               'no' => '42')
                    ),
        "columns" => array( // column
                        array('xml_id' => 'c1',
                              'range' => array('l1', 'l2'))
                    ),
        "lines" => array( // lines
                        array('xml_id' => 'l1',
                              'name' => '01',
                              'range' => array('t1_d1', 't2_d2')),
                        array('xml_id' => 'l2',
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
                              "\$ol|tu"),
                        array("db_id" => "",
                              "xml_id" => "t2_d1",
                              "parent_tok_xml_id" => "t2",
                              "ge#"),
                        array("db_id" => "",
                              "xml_id" => "t2_d2",
                              "parent_tok_xml_id" => "t2",
                              'e$$en'),
                        array("db_id" => "",
                              "xml_id" => "t3_d1",
                              "parent_tok_xml_id" => "t3",
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
                              "parent_xml_id" => "t2",
                              'ge#e$$en'),
                        array("db_id" => "",
                              "xml_id" => "t3_m1",
                              "parent_xml_id" => "t3",
                              'Anshelm'),
                        array("db_id" => "",
                              "xml_id" => "t3_m2",
                              "parent_xml_id" => "t3",
                              '/'),
                        array("db_id" => "",
                              "xml_id" => "t3_m3",
                              "parent_xml_id" => "t3",
                              '(.)')
                )
    );
    protected $cd;

    protected function setUp() {
        $options = array('sigle' => 't1', 'name' => 'testdocument');
        $this->test_data = $this->test_data_initial;
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
        return $this;
    }

    /** assert if mapRangesToIDs throws a DocumentValueException
     */
    private function assertThrowsDVException() {
        $this->cd->setLayoutInfo(
            $this->test_data["pages"],
            $this->test_data["columns"],
            $this->test_data["lines"]
        );
        try {
            $this->cd->mapRangesToIDs();
        } catch (DocumentValueException $e) {
            return $this;
        }
        $this->fail("DocumentValueException was not thrown!");
    }

    //////////////// tests ///////////////////////
    public function testHeader() {
        $testheader = "blablabla. test test 1234.";
        $this->cd->setHeader($testheader);
        $this->assertEquals($testheader, $this->cd->getHeader());
    }
    public function testLineIDs() {
        $this->cd->mapRangesToIDs();
        $this->cd->fillLineIDs("1");
        $this->assertIDsConsistent($this->cd->getLines(), 1, 2);
    }

    public function testColumnIDs() {
        $this->cd->mapRangesToIDs();
        $this->cd->fillColumnIDs("2");
        $this->assertIDsConsistent($this->cd->getColumns(), 2, 1);
    }

    public function testPageIDs() {
        $this->cd->mapRangesToIDs();
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
        $this->cd->mapRangesToIDs();

        $this->cd
                 ->setShiftTags(array(
                     array('range' => array('t1', 't2'), 'type' => 'rub'),
                     array('range' => array('t3', 't3'), 'type' => 'title')
                 ))
                 ->fillTokenIDs("8")
                 ->addComment("", "t1_d1", "Hier grosser Tintenfleck", "K")
                 ->fillDiplIDs("10")
                 ->fillModernIDs("5")
                 ->fillPageIDs("3")
                 ->fillColumnIDs("2")
                 ->fillLineIDs("1")
                 ;

        $actual = $this->cd->getDipls();
        $this->assertEquals("8", $actual[0]["parent_tok_db_id"]);
        $this->assertEquals("9", $actual[1]["parent_tok_db_id"]);
        $this->assertEquals("9", $actual[2]["parent_tok_db_id"]);
        $this->assertEquals("10", $actual[3]["parent_tok_db_id"]);

        $this->assertEquals("1", $actual[0]["parent_line_db_id"]);
        $this->assertEquals("1", $actual[1]["parent_line_db_id"]);
        $this->assertEquals("1", $actual[2]["parent_line_db_id"]);
        $this->assertEquals("2", $actual[3]["parent_line_db_id"]);

        $actual = $this->cd->getModerns();
        $this->assertEquals("8", $actual[0]["parent_db_id"]);
        $this->assertEquals("8", $actual[1]["parent_db_id"]);
        $this->assertEquals("9", $actual[2]["parent_db_id"]);
        $this->assertEquals("10", $actual[3]["parent_db_id"]);
        $this->assertEquals("10", $actual[4]["parent_db_id"]);
        $this->assertEquals("10", $actual[5]["parent_db_id"]);

        $actual = $this->cd->getLines();
        $this->assertEquals("2", $actual[0]["parent_db_id"]);
        $this->assertEquals("2", $actual[1]["parent_db_id"]);

        $actual = $this->cd->getColumns();
        $this->assertEquals("3", $actual[0]["parent_db_id"]);

        $actual = $this->cd->getShiftTags();
        $this->assertEquals(array(8, 9), $actual[0]["db_range"]);
        $this->assertEquals(array(10, 10), $actual[1]["db_range"]);

        $actual = $this->cd->getComments();
        $this->assertEquals("10", $actual[0]['parent_db_id']);
    }

    public function testPageStartsWithCorrectColumn() {
        array_unshift($this->test_data["columns"], array(
            "xml_id" => "c0",
        ));
        $this->assertThrowsDVException();
    }
    public function testColumnStartsWithCorrectLine() {
        array_unshift($this->test_data["lines"], array(
            "xml_id" => "l0"
        ));
        $this->assertThrowsDVException();
    }
    public function testLineStartsWithCorrectDipl() {
        array_unshift($this->test_data["dipls"], array(
            "xml_id" => "t1_d0"
        ));
        $this->cd->setTokens($this->test_data["tokens"],
                             $this->test_data["dipls"],
                             $this->test_data["mods"]);
        $this->assertThrowsDVException();
    }

    public function testEnoughColumnsForPage() {
        $this->test_data["pages"] = array(array(
            "xml_id" => "p1",
            "range" => array("c1", "c2")
        ));
        $this->assertThrowsDVException();
    }
    public function testEnoughLinesForColumn() {
        $this->test_data["columns"] = array(array(
            "xml_id" => "c1",
            "range" => array("l1", "l3")
        ));
        $this->assertThrowsDVException();
    }
    public function testEnoughDiplForLine() {
        $this->test_data["lines"][1]["range"] = array("t3_d1", "t3_d2");
        $this->assertThrowsDVException();
    }

    public function testNoColumnsWithoutPage() {
        array_push($this->test_data["columns"], array(
            "xml_id" => "c2"
        ));
        $this->assertThrowsDVException();
    }
    public function testNoLinesWithoutColumn() {
        array_push($this->test_data["lines"], array(
            "xml_id" => "l3"
        ));
        $this->assertThrowsDVException();
    }
    public function testNoDiplWithoutLine() {
        array_push($this->test_data["dipls"], array(
            "xml_id" => "t4_d1"
        ));
        $this->cd->setTokens($this->test_data["tokens"],
                             $this->test_data["dipls"],
                             $this->test_data["mods"]);
        $this->assertThrowsDVException();
    }

}
?>
