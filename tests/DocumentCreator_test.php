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
require_once 'DB_fixture.php';
require_once 'data/test_data.php';
require_once 'mocks/DocumentAccessor_mocks.php';
require_once 'mocks/LocaleHandler_mock.php';
require_once "{$GLOBALS['CORA_WEB_DIR']}/lib/connect.php";
require_once "{$GLOBALS['CORA_WEB_DIR']}/lib/connect/DocumentCreator.php";

/** Tests for DocumentCreator class
 */
class Cora_Tests_DocumentCreator_test extends Cora_Tests_DbTestCase {
  private $dbi;
  private $dbo;
  private $obj; /**< The object under test */

  public function setUp() {
    //$this->dbi = new Cora_Tests_DBInterface_Mock();
    $dbinfo = array(
      'HOST' => $GLOBALS["DB_HOST"],
      'USER' => $GLOBALS["DB_USER"],
      'PASSWORD' => $GLOBALS["DB_PASSWD"],
      'DBNAME' => $GLOBALS["DB_DBNAME"]
    );
    $this->dbi = new DBInterface($dbinfo, new MockLocaleHandler());
    $this->dbo = new PDO($GLOBALS["DB_DSN"],
                         $GLOBALS["DB_USER"],
                         $GLOBALS["DB_PASSWD"]);
    parent::setUp();
  }

  public function testInsertNewDocument() {
    $options = array("tagset" => "1",
                     "sigle" => "i1",
                     "name" => "importtest",
                     "project" => 1,
		     "tagsets" => array("1","2","3")
    );
    $data = new Cora_Tests_CoraDocument_Mock();
    $expected = $this->createXMLDataset("data/inserted_document.xml");

    $this->obj = new DocumentCreator($this->dbi, $this->dbo, $options);
    if (!$this->obj->importDocument($data, 3)) {
      $this->fail(implode("\n", $this->obj->getWarnings()));
    }
    $this->assertEmpty($this->obj->getWarnings());

    $this->assertTablesEqual($expected->getTable("inserted_text"),
      $this->getConnection()->createQueryTable("inserted_text",
        "SELECT id, sigle, fullname, project_id, currentmod_id, header FROM text "
        ."WHERE sigle='i1'"));
    $this->assertTablesEqual($expected->getTable("inserted_text2tagset"),
       $this->getConnection()->createQueryTable("inserted_text2tagset",
       "SELECT * FROM text2tagset WHERE text_id=6"));

    $this->assertTablesEqual($expected->getTable("inserted_page"),
      $this->getConnection()->createQueryTable("inserted_page",
        "SELECT * FROM page WHERE text_id=6"));

    $this->assertTablesEqual($expected->getTable("inserted_col"),
      $this->getConnection()->createQueryTable("inserted_col",
        "SELECT * FROM col WHERE page_id=3"));

    $this->assertTablesEqual($expected->getTable("inserted_line"),
      $this->getConnection()->createQueryTable("inserted_line",
        "SELECT * FROM line WHERE col_id=3"));

    $this->assertTablesEqual($expected->getTable("inserted_token"),
      $this->getConnection()->createQueryTable("inserted_token",
        "SELECT * FROM token WHERE text_id=6"));

    $this->assertTablesEqual($expected->getTable("inserted_dipl"),
      $this->getConnection()->createQueryTable("inserted_dipl",
        "SELECT * FROM dipl WHERE tok_id >= 7 AND tok_id <= 9"));

    $this->assertTablesEqual($expected->getTable("inserted_modern"),
      $this->getConnection()->createQueryTable("inserted_modern",
        "SELECT * FROM modern WHERE tok_id >= 7 AND tok_id <= 9"));

    $this->assertTablesEqual($expected->getTable("inserted_tag_suggestion"),
      $this->getConnection()->createQueryTable("inserted_tag_suggestion",
        "SELECT * FROM tag_suggestion WHERE mod_id >= 15"));

    $this->assertTablesEqual($expected->getTable("inserted_tag"),
      $this->getConnection()->createQueryTable("inserted_tag",
        "SELECT * FROM tag WHERE tagset_id=2 or tagset_id=3"));

    $this->assertTablesEqual($expected->getTable("inserted_comment"),
      $this->getConnection()->createQueryTable("inserted_comment",
        "SELECT * FROM comment WHERE tok_id=7"));

    $this->assertTablesEqual($expected->getTable("inserted_mod2error"),
      $this->getConnection()->createQueryTable("inserted_mod2error",
        "SELECT * FROM mod2error WHERE mod_id >= 15"));
    }
}

?>
