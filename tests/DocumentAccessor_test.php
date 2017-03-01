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
require_once 'db_fixture/fixture.php';
require_once 'mocks/DocumentAccessor_mocks.php';
require_once "{$GLOBALS['CORA_WEB_DIR']}/lib/connect/DocumentAccessor.php";

class Cora_Tests_DocumentAccessor_test extends Cora_Tests_DbTestCase {
  private $obj; /**< The object under test */
  private $fileid = '2';
  protected $dbCleanInsertBeforeEveryTest = false;
  static protected $fixtureSet = false;

  public function setUp() {
      $this->getConnection();
      $this->obj = new DocumentAccessor(new Cora_Tests_DBInterface_Mock(), self::$pdo, $this->fileid);
      parent::setUp();
  }

  public function testIsValidModID() {
      $valid_ids = array(250, 251, 252, 253, 254, 255, 256, 257, 258, 259, 260);
      foreach ($valid_ids as $id) {
          $this->assertTrue($this->obj->isValidModID($id));
      }
      $this->assertFalse($this->obj->isValidModID(512),
                         "ID exists, but belongs to a different text");
      $this->assertFalse($this->obj->isValidModID(-1), "Illegal ID value");
      $this->assertFalse($this->obj->isValidModID(15000), "ID does not exist");
  }

  public function testGetSelectedAnnotations() {
      $actual = $this->obj->getSelectedAnnotations(250);
      $actual_ids = array();
      foreach ($actual as $result) {
          $actual_ids[] = $result['tag_id'];
      }
      $this->assertContains(3675, $actual_ids, "Tag 3675 is a selected annotation");
      $this->assertContains(1204, $actual_ids, "Tag 1204 is a selected annotation");
      //$this->assertNotContains(513, $actual_ids, "Tag 513 is not selected");
      $this->assertNotContains(1203, $actual_ids, "Tag 1203 is not linked to this mod");
  }

  public function testGetSelectedAnnotationsByClass() {
      $actual = $this->obj->getSelectedAnnotationsByClass(250);
      $this->assertArrayHasKey('pos', $actual);
      $this->assertArrayHasKey('lemma', $actual);
      $this->assertArrayHasKey('boundary', $actual);
      $this->assertArrayHasKey('norm', $actual);
      $this->assertArrayHasKey('lemmapos', $actual);
      $this->assertArrayNotHasKey('norm_broad', $actual);
      $this->assertArrayNotHasKey('comment', $actual);
      $this->assertArrayHasKey('tag_id', $actual['pos']);
      $this->assertArrayHasKey('tag_id', $actual['lemma']);
      $this->assertEquals(1204, $actual['pos']['tag_id']);
      $this->assertEquals(3675, $actual['lemma']['tag_id']);
  }

  public function testRetrieveTagsetInformation() {
      $this->assertEmpty($this->obj->getTagsets());
      $this->obj->retrieveTagsetInformation();
      $tagsets = $this->obj->getTagsets();
      $this->assertNotEmpty($tagsets);
      $this->assertArrayHasKey('pos', $tagsets);
      $this->assertArrayHasKey('lemma', $tagsets);
      $this->assertArrayHasKey('lemmapos', $tagsets);
      $this->assertArrayHasKey('norm', $tagsets);
      $this->assertArrayHasKey('lemmapos', $tagsets);
      $this->assertArrayNotHasKey('norm_broad', $tagsets);
      $this->assertArrayNotHasKey('comment', $tagsets);
  }
}

?>
