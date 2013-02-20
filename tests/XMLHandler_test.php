<?php
require_once"../lib/xmlHandler.php";

/**
 *
 * TODO
 *          import($xmlfile, $options)
 *          export($fileid, $format)
 *  mockups:
 *          DBInterface
 *          CoraDocument
 *
 *
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
    }
    public function getAllSuggestions($fileid, $lineid) {
    }
    public function openFile($fileid) {
    }
}

class Cora_Tests_XMLHandler_test extends PHPUnit_Framework_TestCase {
    protected $dbi;
    protected $xh;

    protected function setUp() {
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

        $this->assertEquals(array(
                    'ext_id' => 'Test101',
                    'name' => 'cora-importtest.xml'
                ),
                $this->dbi->options);

        $this->assertEquals(array(
                array("xml_id" => "t1",
                      "trans" => '$ol|tu',
                      "ordnr" => 1),
                array("xml_id" => "t2",
                      "trans" => 'ge#e$$en',
                      "ordnr" => 2),
                array("xml_id" => "t3",
                      "trans" => 'Anshelm/(.)',
                      "ordnr" => 3)
            ),
            $this->dbi->document->getTokens());

        $this->assertEquals(array(
                array(
                    'tags' => array(
                        array(
                            'source' => 'auto',
                            'selected' => 1,
                            'type' => 'pos',
                            'tag' => 'VVFIN.2.Sg.Pres.Ind',
                            'score' => '0.900'
                        ),
                        array(
                            'source' => 'auto',
                            'selected' => 0,
                            'type' => 'pos',
                            'tag' => 'ART.Def.Masc.Nom.Sg',
                            'score' => '0.047218'
                        ),
                        array(
                            'source' => 'auto',
                            'selected' => 0,
                            'type' => 'pos',
                            'tag' => 'ART.Indef.Neut.Akk.Sg',
                            'score' => '0.014275'
                        ),
                        array(
                            'source' => 'user',
                            'selected' => 1,
                            'type' => 'lemma',
                            'tag' => 'sollen',
                            'score' => null
                        ),
                        array(
                            'source' => 'user',
                            'selected' => 1,
                            'type' => 'norm',
                            'tag' => 'sollst',
                            'score' => null
                        )
                    ),
                    'xml_id' => 't1_m1',
                    'trans' => '$ol',
                    'ascii' => 'sol',
                    'utf' => 'ſol',
                    'parent_xml_id' => 't1'
                ),
                array(
                    'tags' => array(
                        array(
                            'source' => 'auto',
                            'selected' => 0,
                            'type' => 'pos',
                            'tag' => 'VVFIN.2.Sg.Pres.Ind',
                            'score' => '0.900'
                        ),
                        array(
                            'source' => 'auto',
                            'selected' => 0,
                            'type' => 'pos',
                            'tag' => 'ART.Def.Masc.Nom.Sg',
                            'score' => '0.047218'
                        ),
                        array(
                            'source' => 'auto',
                            'selected' => 0,
                            'type' => 'pos',
                            'tag' => 'ART.Indef.Neut.Akk.Sg',
                            'score' => '0.014275'
                        ),
                        array(
                            'source' => 'user',
                            'selected' => 1,
                            'type' => 'lemma',
                            'tag' => 'er/sie/es',
                            'score' => null
                        ),
                        array(
                            'source' => 'user',
                            'selected' => 1,
                            'type' => 'norm',
                            'tag' => 'du',
                            'score' => null
                        ),
                        array(
                            'source' => 'user',
                            'selected' => 1,
                            'type' => 'pos',
                            'tag' => 'PPER.2.Sg.*.Nom',
                            'score' => null
                        )
                    ),
                    'xml_id' => 't1_m2',
                    'trans' => 'tu',
                    'ascii' => 'tu',
                    'utf' => 'tu',
                    'parent_xml_id' => 't1'
                ),
                array(
                    'tags' => array(
                        array(
                            'source' => 'user',
                            'selected' => 1,
                            'type' => 'lemma',
                            'tag' => 'essen',
                            'score' => null
                        ),
                        array(
                            'source' => 'user',
                            'selected' => 1,
                            'type' => 'norm',
                            'tag' => 'gegessen',
                            'score' => null
                        ),
                        array(
                            'source' => 'user',
                            'selected' => 1,
                            'type' => 'pos',
                            'tag' => 'VVPP',
                            'score' => null
                        )
                    ),
                    'xml_id' => 't2_m1',
                    'trans' => 'ge#e$$en',
                    'ascii' => 'geessen',
                    'utf' => 'geeſſen',
                    'parent_xml_id' => 't2'
                ),
                array(
                    'tags' => array(
                        array(
                            'source' => 'user',
                            'selected' => 1,
                            'score' => null,
                            'type' => 'lemma',
                            'tag' => 'Anselm'
                        ),
                        array(
                            'source' => 'user',
                            'selected' => 1,
                            'type' => 'norm',
                            'tag' => 'Anselm',
                            'score' => null
                        ),
                        array(
                            'source' => 'user',
                            'selected' => 1,
                            'type' => 'pos',
                            'tag' => 'NE._._._',
                            'score' => null
                        )
                    ),
                    'xml_id' => 't3_m1',
                    'trans' => 'Anshelm',
                    'ascii' => 'Anshelm',
                    'utf' => 'Anshelm',
                    'parent_xml_id' => 't3'
                ),
                array(
                    'tags' => array(array(
                        'source' => 'user',
                        'selected' => 1,
                        'score' => null,
                        'type' => 'pos',
                        'tag' => '$.'
                    )),
                    'xml_id' => 't3_m2',
                    'trans' => '/',
                    'ascii' => '/',
                    'utf' => '/',
                    'parent_xml_id' => 't3'
                ),
                array(
                    'tags' => array(array(
                        'source' => 'user',
                        'selected' => 1,
                        'score' => null,
                        'type' => 'pos',
                        'tag' => '$.'
                    )),
                    'xml_id' => 't3_m3',
                    'trans' => '(.)',
                    'ascii' => '',
                    'utf' => '',
                    'parent_xml_id' => 't3'
                ),
            ),
            $this->dbi->document->getModerns());

        $this->assertEquals(array(
                array(
                    'xml_id' => 't1_d1',
                    'trans' => '$ol|tu',
                    'utf' => 'ſoltu',
                    'parent_tok_xml_id' => 't1',
                    'parent_line_xml_id' => 'l1'
                ),
                array(
                    'xml_id' => 't2_d1',
                    'trans' => 'ge#',
                    'utf' => 'ge',
                    'parent_tok_xml_id' => 't2',
                    'parent_line_xml_id' => 'l1'
                ),
                array(
                    'xml_id' => 't2_d2',
                    'trans' => 'e$$en',
                    'utf' => 'eſſen',
                    'parent_tok_xml_id' => 't2',
                    'parent_line_xml_id' => 'l1'
                ),
                array(
                    'xml_id' => 't3_d1',
                    'trans' => 'Anshelm/',
                    'utf' => 'Anshelm/',
                    'parent_tok_xml_id' => 't3',
                    'parent_line_xml_id' => 'l2'
                )
            ),
            $this->dbi->document->getDipls());

        $this->assertEquals(array(
                array(
                    'xml_id' => 'l1',
                    'name' => '01',
                    'num' => 1,
                    'range' => array('t1_d1', 't2_d2'),
                    'parent_xml_id' => 'c1'
                ),
                array(
                    'xml_id' => 'l2',
                    'name' => '02',
                    'num' => 2,
                    'range' => array('t3_d1', 't3_d1'),
                    'parent_xml_id' => 'c1'
                )
            ),
            $this->dbi->document->getLines());

        $this->assertEquals(array(array(
                'xml_id' => 'c1',
                'name' => '',
                'num' => 1,
                'range' => array('l1', 'l2'),
                'parent_xml_id' => 'p1'
            )),
            $this->dbi->document->getColumns());

        $this->assertEquals(array(array(
                'xml_id' => 'p1',
                'side' => 'v',
                'name' => '42',
                'num' => 1,
                'range' => array('c1', 'c1')
            )),
            $this->dbi->document->getPages());
        $this->assertEquals(array(
                array(
                    'type' => 'rub',
                    'type_letter' => 'R',
                    'range' => array('t1', 't2')
                ),
                array(
                    'type' => 'title',
                    'type_letter' => 'T',
                    'range' => array('t3', 't3')
                )
            ),
            $this->dbi->document->getShifttags());
        $this->assertEquals(array(
            array(
                'parent_db_id' => null,
                'parent_xml_id' => 't1',
                'text' => "Hier grosser Tintenfleck",
                'type' => 'K'
            ),
            array(
                'parent_db_id' => null,
                'parent_xml_id' => 't2',
                'text' => 'Beispielemendation',
                'type' => 'E'
            )),
            $this->dbi->document->getComments());

        // currently, the following assert fails since it imports the padding
        // whitespace
        $this->assertEquals("Testdatei. Freier Text hier. Alles moegliche an Kram steht da drin - alles zwischen +H und @H",
            $this->dbi->document->getHeader());
    }
    public function testExport() {
        $this->assertTrue(true);
    }
}
?>
