<?php

/** Expected data for DBInterface_test
 *
 *  02/2013 Florian Petran
 *
 *  TODO would be better to read it from a separate file instead
 *  of defining it in a huge function, but this works for now
 */
function get_DBInterface_expected() {
   return array(
        "users" => array(
            "system"    => array("id" => "1",
                                 "name" => "system",
                                 "admin" => "1",
                                 "lastactive" => "2013-01-16 14:22:57" ),
            "test"      => array("id" => "5",
                                 "name" => "test",
                                 "admin" => "0",
                                 "lastactive" => "2013-01-22 15:38:32"),
            "bollmann"  => array("id" => "3",
                                 "name" => "bollmann",
                                 "admin" => "1",
                                 "lastactive" => "2013-02-04 11:29:04")
        ),
        "settings" => array(
            "test" => array("lines_per_page" => "30",
                            "lines_context" => "5",
                            "columns_order" => null,
                            "columns_hidden" => null,
                            "show_error" => "1")
        ),
	"tagsets" => array(
			   "ts1" => array("id" => '1',
					  "name" => "ImportTest",
					  "class" => "POS",
					  "set_type" => "closed"),
			   "ts2" => array("id" => '2',
					  "name" => "NormTest",
					  "class" => "norm",
					  "set_type" => "open"),
			   "ts3" => array("id" => '3',
					  "name" => "LemmaTest",
					  "class" => "lemma",
					  "set_type" => "open"),
			   ),
        "texts" => array(
            "t1" => array("id" => "3",
                          "sigle" => "t1",
                          "fullname" => "test-dummy",
                          "project_id" => "1",
                          "created" => "2013-01-22 14:30:30",
                          "creator_id" => "1",
                          "changed" => "0000-00-00 00:00:00",
                          "changer_id" => "3",
                          "currentmod_id" => null
            ),
            "t1_reduced" => array("id" => "3",
                          "sigle" => "t1",
                          "fullname" => "test-dummy",
                          "project_id" => "1",
                          "currentmod_id" => null,
                          "header" => null,
            ),
            "t2" => array("id" => "4",
                          "sigle" => "t2",
                          "fullname" => "yet another dummy",
                          "project_id" => "1",
                          "created" => "2013-01-31 13:13:20",
                          "creator_id" => "1",
                          "changed" => "0000-00-00 00:00:00",
                          "changer_id" => "1",
                          "currentmod_id" => "14",
            ),
            "t2_reduced" => array("id" => "4",
                          "sigle" => "t2",
                          "fullname" => "yet another dummy",
                          "project_id" => "1",
                          "currentmod_id" => "14",
                          "header" => null,
            ),
            "t3" => array("id" => "5",
                          "sigle" => "t3",
                          "fullname" => "dummy without tokens",
                          "project_id" => "1",
                          "created" => "2013-01-31 13:13:20",
                          "creator_id" => "1",
                          "changed" => "0000-00-00 00:00:00",
                          "changer_id" => "1",
                          "currentmod_id" => null,
	    ),
            "t3_reduced" => array("id" => "5",
                          "sigle" => "t3",
                          "fullname" => "dummy without tokens",
                          "project_id" => "1",
                          "currentmod_id" => null,
                          "header" => null,
	    ),
	    "header_fullfile" => array("header" => null,
			  "fullfile" => null
	    )
				       

        ),
        "texts_extended" => array(
            "t1" => array('project_name' => 'Default-Gruppe',
                          'opened' => 'bollmann',
                          'creator_name' => 'system',
                          'changer_name' => 'bollmann'),
            "t2" => array('project_name' => 'Default-Gruppe',
                          'opened' => null,
                          'creator_name' => 'system',
                          'changer_name' => 'system'),
            "t3" => array('project_name' => 'Default-Gruppe',
                          'opened' => null,
                          'creator_name' => 'system',
                          'changer_name' => 'system')
        ),
        "lines" => array(
                       array('id' => '1',
                            'trans' => '*{A*4}n$helm%9',
                            'utf' => 'Anshelm\'',
                            'tok_id' => '1',
                            'full_trans' => '*{A*4}n$helm%9',
                            'num' => '0',
                            'suggestions' => array (
                                array ( 'POS' => 'VVFIN',
                                        'morph' => '3.Pl.Past.Konj',
                                        'score' => '0.97')
                            ),
                            'anno_POS' => 'VVFIN',
                            'anno_morph' => '3.Pl.Past.Konj',
			     'comment' => null,
			     'page_name' => '1',
			     'page_side' => 'r',
			     'col_name' => '',
			     'line_name' => '1'
                        ),
                        array('id'          => '2',
                            'trans'       => 'pi$t||',
                            'utf'         => 'pist',
                            'tok_id'      => '2',
                            'full_trans'  => 'pi$t||u||s',
                            'num'         => '1',
                            'suggestions' => array(
                                array( 'POS' => 'PPOSAT',
                                       'morph' => 'Fem.Nom.Sg',
                                       'score' => null)
                            ),
                            'anno_POS'    => "PPOSAT",
                            'anno_morph'  => "Fem.Nom.Sg",
			      'comment' => null,
			     'page_name' => '1',
			     'page_side' => 'r',
			     'col_name' => '',
			     'line_name' => '2'
                        ),
                        array('id'          => '3',
                            'trans'       => 'u||',
                            'utf'         => 'u',
                            'tok_id'      => '2',
                            'full_trans'  => 'pi$t||u||s',
                            'num'         => '2',
                            'general_error' => 1,
                            'suggestions' => array(array('POS' => 'VMINF',
                                                   'morph' => '--',
                                                   'score' => null)),
                            'anno_POS' => 'VMINF',
                            'anno_morph' => '--',
			      'comment' => null,
			     'page_name' => '1',
			     'page_side' => 'r',
			     'col_name' => '',
			     'line_name' => '2'
                        ),
                        array('id'          => '4',
                            'trans'       => 's',
                            'utf'         => 's',
                            'tok_id'      => '2',
                            'full_trans'  => 'pi$t||u||s',
                            'num'         => '3',
                            'suggestions' => array(
                                array('POS' => 'VVFIN',
                                      'morph' => '3.Pl.Pres.Konj',
                                      'score' => null)
                            ),
                            'anno_POS'    => 'VVFIN',
                            'anno_morph'  => '3.Pl.Pres.Konj',
			      'comment' => null,
			     'page_name' => '1',
			     'page_side' => 'r',
			     'col_name' => '',
			     'line_name' => '2'
                        ),
                        array('id'          => '5',
                            'trans'       => 'aller#lieb$tev',
                            'utf'         => 'allerliebstev',
                            'tok_id'      => '3',
                            'full_trans'  => 'aller#lieb$tev',
                            'num'         => '4',
                            'suggestions' => array(),
                            'anno_POS'    => 'PDS',
                            'anno_morph'  => '*.Gen.Pl',
                            'anno_lemma'  => 'lemma',
			      'comment' => 'cora comment',
			     'page_name' => '1',
			     'page_side' => 'r',
			     'col_name' => '',
			     'line_name' => '3'
                        ),
                        array('id'          => '6',
                            'trans'       => 'vunf=tusent#vnd#vierhundert#vn-(=)sechzig',
                            'utf'         => 'vunftusentvndvierhundertvnsechzig',
                            'tok_id'      => '4',
                            'full_trans'  => 'vunf= tusent#vnd#vierhundert#vn-(=) sechzig',
                            'num'         => '5',
                            'suggestions' => array(),
                            'anno_norm'   => 'norm',
			      'comment' => null,
			     'page_name' => '1',
			     'page_side' => 'r',
			     'col_name' => '',
			     'line_name' => '3'
                        ),
                        array('id' => '7',
                            'trans' => 'kunnen',
                            'utf' => 'kunnen',
                            'tok_id' => '5',
                            'full_trans' => 'kunnen.(.)',
                            'num' => '6',
                            'general_error' => 1,
                            'suggestions' => Array (),
                            'anno_lemma' => 'deletedlemma',
			      'comment' => null,
			     'page_name' => '1',
			     'page_side' => 'r',
			     'col_name' => '',
			     'line_name' => '5'
                        ),
                        array('id' => '8',
                            'trans' => '.',
                            'utf' => '.',
                            'tok_id' => '5',
                            'full_trans' => 'kunnen.(.)',
                            'num' => '7',
                            'suggestions' => Array (),
			      'comment' => null,
			     'page_name' => '1',
			     'page_side' => 'r',
			     'col_name' => '',
			     'line_name' => '5',
                             'anno_POS' => 'PDS',
                             'anno_morph' => 'Fem.Nom.Sg'
                        ),
                        array('id' => '9',
                            'trans' => '(.)',
                            'utf' => '.',
                            'tok_id' => '5',
                            'full_trans' => 'kunnen.(.)',
                            'num' => '8',
                            'suggestions' => Array (),
                            'anno_norm' => 'deletednorm',
			      'comment' => null,
			     'page_name' => '1',
			     'page_side' => 'r',
			     'col_name' => '',
			     'line_name' => '5'
                        )
        )
    );
}

/** Test data for XMLHandler
 * this is both the initial data for testExport() and
 * the expected data for testImport().
 */
function get_XMLHandler_expected() {
    return array(
        "moderns" => array(
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
        "tokens" => array(
                array("xml_id" => "t0",
                      "trans" => "",
                      "ordnr" => 1),
                array("xml_id" => "t1",
                      "trans" => '$ol|tu',
                      "ordnr" => 2),
                array("xml_id" => "t2",
                      "trans" => 'ge#e$$en',
                      "ordnr" => 3),
                array("xml_id" => "t3",
                      "trans" => 'Anshelm/(.)',
                      "ordnr" => 4)
        ),
        "dipls" => array(
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
        "lines" => array(
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
        "columns" => array(
                array(
                    'xml_id' => 'c1',
                    'name' => '',
                    'num' => 1,
                    'range' => array('l1', 'l2'),
                    'parent_xml_id' => 'p1'
                )
        ),
        "pages" => array(
            array(
                'xml_id' => 'p1',
                'side' => 'v',
                'name' => '42',
                'num' => 1,
                'range' => array('c1', 'c1')
            )
        ),
        "shifttags" => array(
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
        "comments" => array(
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
            )
        ),
        "header" => "Testdatei. Freier Text hier. Alles moegliche an Kram steht da drin - alles zwischen +H und @H",
        "options" => array(
                    'ext_id' => 'Test101',
                    'name' => 'cora-importtest.xml'
        )
    );
}

function get_XMLHandler_initial() {
    return array(
        "lines" => array(
                       array('id' => '1',
                            'trans' => '*{A*4}n$helm%9',
                            'utf' => 'Anshelm\'',
                            'tok_id' => '1',
                            'ext_id' => null,
                            'full_trans' => '*{A*4}n$helm%9',
                            'num' => '0',
                            'suggestions' => array (
                                array ( 'POS' => 'VVFIN',
                                        'morph' => '3.Pl.Past.Konj',
                                        'score' => '0.97')
                            ),
                            'anno_POS' => 'VVFIN',
                            'anno_morph' => '3.Pl.Past.Konj',
                            'comment' => null
                        ),
                    ),
    );
}


/** Test data to initialize CoraDocument with
 *
 * 02/2013-03/2013 Florian Petran
 *
 * it's also used for CoraDocument Mock objects,
 * e.g. in DBInterface tests.
 *
 */
function get_CoraDocument_data() {
    return array(
        "pages" => array( // page
                        array( "xml_id" => "p1",
                               "side" => 'v',
                               'range' => array('c1', 'c1'),
                               'name' => '42',
                               'num' => '1'
                           )
                    ),
        "columns" => array( // column
                        array('xml_id' => 'c1',
                              'range' => array('l1', 'l2'),
                              'name' => 'a',
                              'num' => '1',
                              'parent_db_id' => '3'
                        )
                    ),
        "lines" => array( // lines
                        array('xml_id' => 'l1', // 8
                              'name' => '01',
                              'num' => '1',
                              'parent_db_id' => '4',
                              'range' => array('t1_d1', 't2_d2')),
                        array('xml_id' => 'l2', // 9
                              'name' => '02',
                              'num' => '2',
                              'parent_db_id' => '4',
                              'range' => array('t3_d1', 't3_d1'))
                    ),
        "tokens" => array( // tokens
                        array("db_id" => "",
                              "xml_id" => "t1",
                              'ordnr' => 1,
                              'trans' => '$ol|tu'),
                        array("db_id" => "",
                              "xml_id" => "t2",
                              'ordnr' => 2,
                              'trans' => 'ge#e$$en'),
                        array("db_id" => "",
                              "xml_id" => "t3",
                              'ordnr' => 3,
                              'trans' => "Anshelm/(.)")
                    ),
        "dipls" => array( // dipl
                        array("db_id" => "",
                              "xml_id" => "t1_d1",
                              "parent_tok_xml_id" => "t1",
                              'parent_tok_db_id' => '7',
                              'parent_line_db_id' => '8',
                              'utf' => '',
                              'trans' => "\$ol|tu"),
                        array("db_id" => "",
                              "xml_id" => "t2_d1",
                              "parent_tok_xml_id" => "t2",
                              'parent_tok_db_id' => '8',
                              'parent_line_db_id' => '8',
                              'utf' => '',
                              'trans' => "ge#"),
                        array("db_id" => "",
                              "xml_id" => "t2_d2",
                              "parent_tok_xml_id" => "t2",
                              'parent_tok_db_id' => '8',
                              'parent_line_db_id' => '9',
                              'utf' => '',
                              'trans' => 'e$$en'),
                        array("db_id" => "",
                              "xml_id" => "t3_d1",
                              "parent_tok_xml_id" => "t3",
                              'parent_tok_db_id' => '9',
                              'parent_line_db_id' => '9',
                              'utf' => '',
                              'trans' => "Anshelm/")
                   ),
        "mods" => array( // mod
                        array("db_id" => "15",
                              "xml_id" => "t1_m1",
                              "parent_xml_id" => "t1",
                              'tags' => array(
                                  array(
                                      'type' => 'pos',
                                      'tag' => 'ADJA.Comp.Fem.Dat.Sg',
                                      'score' => '0.91',
                                      'source' => 'user',
                                      'selected' => '0'
                                  ),
                                  array(
                                      'type' => 'norm',
                                      'tag' => 'soll',
                                      'score' => '1.0',
                                      'source' => 'user',
                                      'selected' => '1',
                                  ),
                                  array(
                                      'type' => 'lemma',
                                      'tag' => 'sollen',
                                      'source' => 'user',
                                      'score' => null,
                                      'selected' => '0'
                                  )
                              ),
                              'parent_db_id' => '7',
                              'ascii' => '', // XXX
                              'utf' => '', // XXX
                              'trans' => '$ol'),
                        array("db_id" => "16",
                              "xml_id" => "t1_m2",
                              "parent_xml_id" => "t1",
                              'tags' => array(
                                  array(
                                      'type' => 'pos',
                                      'tag' => 'ADJA.Comp.Fem.Dat.Sg',
                                      'score' => '0.91',
                                      'source' => 'user',
                                      'selected' => '0'
                                  ),
                                  array(
                                      'type' => 'norm',
                                      'tag' => 'soll',
                                      'score' => '1.0',
                                      'source' => 'user',
                                      'selected' => '1',
                                  ),
                                  array(
                                      'type' => 'lemma',
                                      'tag' => 'sollen',
                                      'score' => null,
                                      'source' => 'user',
                                      'selected' => '0'
                                  )
                              ),
                              'parent_db_id' => '7',
                              'ascii' => '', // XXX
                              'utf' => '', // XXX
                              'trans' => 'tu'),
                        array("db_id" => "17",
                              "xml_id" => "t2_m1",
                              "parent_xml_id" => "t2",
                              'tags' => array(
                                  array(
                                      'type' => 'pos',
                                      'tag' => 'ADJA.Comp.Fem.Dat.Sg',
                                      'score' => '0.91',
                                      'source' => 'user',
                                      'selected' => '0'
                                  ),
                                  array(
                                      'type' => 'norm',
                                      'tag' => 'soll',
                                      'score' => '1.0',
                                      'source' => 'user',
                                      'selected' => '1',
                                  ),
                                  array(
                                      'type' => 'lemma',
                                      'tag' => 'sollen',
                                      'score' => null,
                                      'source' => 'user',
                                      'selected' => '0'
                                  )
                              ),
                              'parent_db_id' => '8',
                              'ascii' => '', // XXX
                              'utf' => '', // XXX
                              'trans' => 'ge#e$$en'),
                        array("db_id" => "18",
                              "xml_id" => "t3_m1",
                              "parent_xml_id" => "t3",
                              'tags' => array(
                                  array(
                                      'type' => 'pos',
                                      'tag' => 'ADJA.Comp.Fem.Dat.Sg',
                                      'score' => '0.91',
                                      'source' => 'user',
                                      'selected' => '0'
                                  ),
                                  array(
                                      'type' => 'norm',
                                      'tag' => 'soll',
                                      'score' => '1.0',
                                      'source' => 'user',
                                      'selected' => '1',
                                  ),
                                  array(
                                      'type' => 'lemma',
                                      'tag' => 'sollen',
                                      'score' => null,
                                      'source' => 'user',
                                      'selected' => '0'
                                  )
                              ),
                              'parent_db_id' => '9',
                              'ascii' => '', // XXX
                              'utf' => '', // XXX
                              'trans' => 'Anshelm'),
                        array("db_id" => "19",
                              "xml_id" => "t3_m2",
                              "parent_xml_id" => "t3",
                              'tags' => array(
                                  array(
                                      'type' => 'pos',
                                      'tag' => 'ADJA.Comp.Fem.Dat.Sg',
                                      'score' => '0.91',
                                      'source' => 'user',
                                      'selected' => '0'
                                  ),
                                  array(
                                      'type' => 'norm',
                                      'tag' => 'soll',
                                      'score' => '1.0',
                                      'source' => 'user',
                                      'selected' => '1',
                                  ),
                                  array(
                                      'type' => 'lemma',
                                      'tag' => 'sollen',
                                      'score' => null,
                                      'source' => 'user',
                                      'selected' => '0'
                                  )
                              ),
                              'parent_db_id' => '9',
                              'ascii' => '', // XXX
                              'utf' => '', // XXX
                              'trans' => '/'),
                        array("db_id" => "20",
                              "xml_id" => "t3_m3",
                              "parent_xml_id" => "t3",
                              'tags' => array(
                                  array(
                                      'type' => 'pos',
                                      'tag' => 'ADJA.Comp.Fem.Dat.Sg',
                                      'score' => '0.91',
                                      'source' => 'user',
                                      'selected' => '0'
                                  ),
                                  array(
                                      'type' => 'norm',
                                      'tag' => 'soll',
                                      'score' => '1.0',
                                      'source' => 'user',
                                      'selected' => '1',
                                  ),
                                  array(
                                      'type' => 'lemma',
                                      'tag' => 'sollen',
                                      'score' => null,
                                      'source' => 'user',
                                      'selected' => '0'
                                  )
                              ),
                              'parent_db_id' => '9',
                              'ascii' => '', // XXX
                              'utf' => '', // XXX
                              'trans' => '(.)')
                )
    );
}

/** Test data for automatic annotation
 *
 * 02/2014 Marcel Bollmann
 */
function get_AutomaticAnnotator_data() {
    $outfile = dirname(__FILE__) . "/tagger_output.txt";
    return array("all_tokens" => array(array(), // tokens
                                       array(), // dipls
                                       array(   // moderns
                                             array("parent_tok_db_id" => 701,
                                                   "db_id" => 801,
                                                   "trans" => "vnd",
                                                   "ascii" => "vnd",
                                                   "utf"   => "vnd",
                                                   "comment" => "",
                                                   "verified" => 0,
                                                   "tags" => array(
                                                                   array("tag" => "DARTU",
                                                                         "score" => 0.1,
                                                                         "selected" => 1,
                                                                         "source" => "auto",
                                                                         "type" => "POS"),
                                                                   array("tag" => "und",
                                                                         "score" => 0.1,
                                                                         "selected" => 1,
                                                                         "source" => "auto",
                                                                         "type" => "norm")
                                                                   ),
                                                   "errors" => array()
                                                   ),
                                             array("parent_tok_db_id" => 702,
                                                   "db_id" => 802,
                                                   "trans" => "er",
                                                   "ascii" => "er",
                                                   "utf"   => "er",
                                                   "comment" => "",
                                                   "verified" => 1,
                                                   "tags" => array(
                                                                   array("tag" => "PPER",
                                                                         "score" => 0.8,
                                                                         "selected" => 1,
                                                                         "source" => "auto",
                                                                         "type" => "POS"),
                                                                   array("tag" => "er",
                                                                         "score" => 0.8,
                                                                         "selected" => 1,
                                                                         "source" => "auto",
                                                                         "type" => "norm")
                                                                   ),
                                                   "errors" => array()
                                                   ),
                                             array("parent_tok_db_id" => 703,
                                                   "db_id" => 803,
                                                   "trans" => "gi\ebt",
                                                   "ascii" => "giebt",
                                                   "utf"   => "giēbt",
                                                   "comment" => "",
                                                   "verified" => 0,
                                                   "tags" => array(
                                                                   array("tag" => "NE",
                                                                         "score" => 0.5,
                                                                         "selected" => 1,
                                                                         "source" => "auto",
                                                                         "type" => "POS"),
                                                                   array("tag" => "giebel",
                                                                         "score" => 0.8,
                                                                         "selected" => 1,
                                                                         "source" => "auto",
                                                                         "type" => "norm")
                                                                   ),
                                                   "errors" => array()
                                                   )
                                                )
                                       ),
                 "taggerlist" => array('1' => array('name' => "Mock-Tagger",
                                                    'trainable' => true,
                                                    'cmd_train' => "sleep 1",
                                                    'cmd_tag' => "cat {$outfile}",
                                                    'tagsets' => array('1', '2'))
                                       ),
                 "tagsetlist" => array("ts1" => array("id" => '1',
                                                      "name" => "ImportTest",
                                                      "class" => "POS",
                                                      "set_type" => "closed"),
                                       "ts2" => array("id" => '2',
                                                      "name" => "NormTest",
                                                      "class" => "norm",
                                                      "set_type" => "open")
                                       ),
                 "expected" => array(array("id" => 801,
                                           "anno_norm" => "und",
                                           "anno_POS" => "KOKOM"),
                                     array("id" => 803,
                                           "anno_norm" => "ging",
                                           "anno_POS" => "VVFIN")
                                     )
                 );
}

function get_Exporter_data() {
    return array("all_tokens" => array(array(), // tokens
                                       array(), // dipls
                                       array(   // moderns
                                             array("parent_tok_db_id" => 701,
                                                   "db_id" => 801,
                                                   "trans" => "vnd",
                                                   "ascii" => "vnd",
                                                   "utf"   => "vnd",
                                                   "comment" => "",
                                                   "verified" => 0,
                                                   "tags" => array(
                                                                   array("tag" => "DARTU",
                                                                         "score" => 0.1,
                                                                         "selected" => 1,
                                                                         "source" => "auto",
                                                                         "type" => "POS"),
                                                                   array("tag" => "und",
                                                                         "score" => 0.1,
                                                                         "selected" => 1,
                                                                         "source" => "auto",
                                                                         "type" => "norm")
                                                                   ),
                                                   "errors" => array()
                                                   ),
                                             array("parent_tok_db_id" => 702,
                                                   "db_id" => 802,
                                                   "trans" => "jn",
                                                   "ascii" => "jn",
                                                   "utf"   => "jn",
                                                   "comment" => "",
                                                   "verified" => 1,
                                                   "tags" => array(
                                                                   array("tag" => "PPER",
                                                                         "score" => 0.8,
                                                                         "selected" => 1,
                                                                         "source" => "auto",
                                                                         "type" => "POS"),
                                                                   array("tag" => "ihn",
                                                                         "score" => 0.8,
                                                                         "selected" => 1,
                                                                         "source" => "user",
                                                                         "type" => "norm"),
                                                                   array("tag" => "ihnen",
                                                                         "score" => 0.8,
                                                                         "selected" => 1,
                                                                         "source" => "user",
                                                                         "type" => "norm_broad"),
                                                                   array("tag" => "f",
                                                                         "score" => 0.8,
                                                                         "selected" => 1,
                                                                         "source" => "user",
                                                                         "type" => "norm_type")
                                                                   ),
                                                   "errors" => array()
                                                   ),
                                             array("parent_tok_db_id" => 703,
                                                   "db_id" => 803,
                                                   "trans" => "gi\ebt",
                                                   "ascii" => "giebt",
                                                   "utf"   => "giēbt",
                                                   "comment" => "",
                                                   "verified" => 0,
                                                   "tags" => array(
                                                                   array("tag" => "NE",
                                                                         "score" => 0.5,
                                                                         "selected" => 1,
                                                                         "source" => "auto",
                                                                         "type" => "POS"),
                                                                   array("tag" => "VVIMP",
                                                                         "score" => 0.75,
                                                                         "selected" => 0,
                                                                         "source" => "auto",
                                                                         "type" => "POS"),
                                                                   array("tag" => "giebel",
                                                                         "score" => 0.8,
                                                                         "selected" => 1,
                                                                         "source" => "auto",
                                                                         "type" => "norm")
                                                                   ),
                                                   "errors" => array()
                                                   )
                                                )
                                       ),
                 "expected_POS" => "vnd\tDARTU\njn\tPPER\ngiebt\tNE\n",
                 "expected_norm" => "vnd\tund\njn\tihn\tihnen\tf\ngiebt\tgiebel\n",
                 "expected_tagging_1" => "ascii\tPOS\tnorm\nvnd\tDARTU\tund\n"
                                         ."jn\tPPER\tihn\ngiebt\tNE\tgiebel\n",
                 "expected_tagging_2" => "vnd\tund\njn\tihn\ngiebt\tgiebel\n"
                 );
}


?>
