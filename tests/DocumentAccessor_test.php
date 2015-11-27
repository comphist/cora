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
require_once 'DocumentAccessor_mocks.php';
require_once "{$GLOBALS['CORA_WEB_DIR']}/lib/connect/DocumentAccessor.php";

class Cora_Tests_DocumentAccessor_test extends Cora_Tests_DbTestCase {
  private $dbo;
  private $obj; /**< The object under test */
  private $fileid = '3';

  public function setUp() {
    $this->dbo = new PDO($GLOBALS["DB_DSN"],
                         $GLOBALS["DB_USER"],
                         $GLOBALS["DB_PASSWD"]);
    $this->obj = new DocumentAccessor(new Cora_Tests_DBInterface_Mock(), $this->dbo, $this->fileid);
    parent::setUp();
  }

  public function testIsValidModID() {
    $valid_ids = array(1, 2, 3, 4, 5, 6, 7, 8, 9);
    foreach ($valid_ids as $id) {
      $this->assertTrue($this->obj->isValidModID($id));
    }
    $this->assertFalse($this->obj->isValidModID(13),
                       "ID exists, but belongs to a different text");
    $this->assertFalse($this->obj->isValidModID(-1), "Illegal ID value");
    $this->assertFalse($this->obj->isValidModID(256), "ID does not exist");
  }

  public function testGetSelectedAnnotations() {
    $actual = $this->obj->getSelectedAnnotations(5);
    $actual_ids = array();
    foreach ($actual as $result) {
      $actual_ids[] = $result['tag_id'];
    }
    $this->assertContains(219, $actual_ids, "Tag 219 is a selected annotation");
    $this->assertContains(511, $actual_ids, "Tag 511 is a selected annotation");
    $this->assertNotContains(513, $actual_ids, "Tag 513 is not selected");
    $this->assertNotContains(478, $actual_ids, "Tag 478 is not linked to this mod");
  }

  public function testGetSelectedAnnotationsByClass() {
    $actual = $this->obj->getSelectedAnnotationsByClass(5);
    $this->assertArrayHasKey('pos', $actual);
    $this->assertArrayHasKey('lemma', $actual);
    $this->assertArrayNotHasKey('norm', $actual);
    $this->assertArrayHasKey('tag_id', $actual['pos']);
    $this->assertArrayHasKey('tag_id', $actual['lemma']);
    $this->assertEquals(219, $actual['pos']['tag_id']);
    $this->assertEquals(511, $actual['lemma']['tag_id']);
  }

  public function testRetrieveTagsetInformation() {
    $this->assertEmpty($this->obj->getTagsets());
    $this->obj->retrieveTagsetInformation();
    $tagsets = $this->obj->getTagsets();
    $this->assertNotEmpty($tagsets);
    $this->assertArrayHasKey('pos', $tagsets);
    $this->assertArrayHasKey('lemma', $tagsets);
  }
}

?>
