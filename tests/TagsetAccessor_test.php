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
require_once "{$GLOBALS['CORA_WEB_DIR']}/lib/connect/TagsetAccessor.php";

class Cora_Tests_TagsetAccessor_test extends Cora_Tests_DbTestCase {
  private $dbo;
  private $tagset;
  protected $dbCleanInsertBeforeEveryTest = false;

  public function setUp() {
      $this->dbo = new PDO($GLOBALS["DB_DSN"],
                           $GLOBALS["DB_USER"],
                           $GLOBALS["DB_PASSWD"]);
      $this->tagset = new TagsetAccessor($this->dbo, 6);
      parent::setUp();
  }

  public function testWithoutID() {
    $tagset = new TagsetAccessor($this->dbo, null);
    $this->assertNull($tagset->getID(),
                      "Accessor without ID should have no ID");
    $this->assertNull($tagset->getName(),
                      "Accessor without ID should have no name");
    $this->assertNull($tagset->getSetType(),
                      "Accessor without ID should have no set type");
    $this->assertNull($tagset->getClass(),
                      "Accessor without ID should have no class");
    $this->assertEquals(0, $tagset->count(),
                        "Accessor without ID should report no entries");
    $this->assertEmpty($tagset->entries(),
                       "Accessor without ID should have no entries");
    $this->assertFalse($tagset->hasErrors(),
                       "Accessor without ID should report no errors");
  }

  public function testBasicGetters() {
    $this->assertFalse($this->tagset->hasErrors(),
                       "Constructing with legal tagset ID should report no error");
    $this->assertEquals(6, $this->tagset->getID(),
                        "Accessor should report the correct tagset ID");
    $this->assertEquals("STTS", $this->tagset->getName(),
                        "Accessor should report the correct tagset name");
    $this->assertEquals("closed", $this->tagset->getSetType(),
                        "Accessor should report the correct set type");
    $this->assertEquals("pos", $this->tagset->getClass(),
                        "Accessor should report the correct tagset class");
    $this->assertEquals(2795, $this->tagset->count(),
                        "Accessor should report the correct number of tags");
    $this->assertTrue($this->tagset->contains("$."),
                      "Accessor's tag list should include '$.'");
    $tag = $this->tagset->getTag("$.");
    $this->assertEquals(1, $tag['id'],
                        "Accessor's tag data for '$.' should be correct");
    $this->assertEquals(0, $tag['needs_revision'],
                        "Accessor's tag data for '$.' should be correct");
    $this->assertEquals("$.", $tag['value'],
                        "Accessor's tag data for '$.' should be correct");
    $this->assertTrue($this->tagset->contains("PRF.2.Pl.Dat"),
                      "Accessor's tag list should include 'PRF.2.Pl.Dat'");
    $this->assertFalse($this->tagset->contains("PRF"),
                       "Accessor's tag list should NOT include 'PRF'");
    $this->assertFalse($this->tagset->needsCommit(),
                       "Calling only getters should not require a commit");
  }

  public function testCheckTag() {
    $this->assertTrue($this->tagset->checkTag("XY.YZ.ZA.AB"),
                      "'XY.YZ.ZA.AB' should be a valid tag");
    $input = str_repeat("x", 255);
    $this->assertTrue($this->tagset->checkTag($input),
                      "String with 255 characters should be a valid tag");
    $input = str_repeat("x", 256);
    $this->assertFalse($this->tagset->checkTag($input),
                       "String with 256 characters should NOT be a valid tag");
    $this->assertCount(1, $this->tagset->getErrors(),
                       "Accessor should report exactly one error");
  }

  public function testAddTag_Invalids() {
    $this->assertFalse($this->tagset->addTag(""),
                       "Empty tags should not be added");
    $this->assertFalse($this->tagset->addTag("    "),
                       "Whitespace-only tags should not be added");
    $input = str_repeat("x", 256);
    $this->assertFalse($this->tagset->addTag($input),
                       "Invalid tags should not be added");
    $this->assertFalse($this->tagset->needsCommit(),
                       "Failing operations should not require a commit");
  }

  public function testAddTag_New() {
    $oldcount = $this->tagset->count();
    $this->assertTrue($this->tagset->addTag("FOO"),
                      "Accessor should accept a new tag 'FOO'");
    $this->assertTrue($this->tagset->needsCommit(),
                      "Accessor should require a commit after adding a new tag");
    $this->assertEquals($oldcount + 1, $this->tagset->count(),
                        "Tag list count should increase after adding a new tag");
    $tag = $this->tagset->getTag("FOO");
    $this->assertEquals("FOO", $tag['value'],
                        "New tag should have value 'FOO'");
    $this->assertEquals(0, $tag['needs_revision'],
                        "New tag 'FOO' should NOT be marked as needing revision");
    $this->assertEquals('new', $tag['status'],
                        "New tag 'FOO' should be marked as new");
    $this->assertTrue($this->tagset->addTag("BAR", true),
                      "Accessor should accept a new tag 'BAR'");
    $tag = $this->tagset->getTag("BAR");
    $this->assertEquals(1, $tag['needs_revision'],
                        "New tag 'BAR' should be marked as needing revision");
  }

  public function testAddTag_Existing() {
    $this->assertTrue($this->tagset->addTag("$."),
                      "Adding an already existing tag should be accepted");
    $tag = $this->tagset->getTag("$.");
    $this->assertEquals(1, $tag['id'],
                        "Adding an already existing tag should have no side-effects");
    $this->assertEquals(0, $tag['needs_revision'],
                        "Adding an already existing tag should have no side-effects");
    $this->assertEquals("$.", $tag['value'],
                        "Adding an already existing tag should have no side-effects");
    $this->assertTrue($this->tagset->addTag("$.", true),
                      "Adding an already existing tag should be accepted");
    $tag = $this->tagset->getTag("$.");
    $this->assertEquals(1, $tag['id'],
                        "Adding an already existing tag should have no side-effects");
    $this->assertEquals(1, $tag['needs_revision'],
                        "Adding an already existing tag can change 'needs_revision' flag");
    $this->assertFalse($this->tagset->hasErrors(),
                       "Adding already existing tags should produce no errors");
  }

  public function testAddTag_Twice() {
    $this->assertTrue($this->tagset->addTag("FOO"),
                      "Accessor should accept a new tag 'FOO'");
    $this->assertTrue($this->tagset->addTag("FOO", true),
                      "Accessor should accept an already added tag 'FOO'");
    $tag = $this->tagset->getTag("FOO");
    $this->assertEquals(1, $tag['needs_revision'],
                        "New tag 'FOO' should be marked as needing revision");
    $this->assertEquals('new', $tag['status'],
                        "New tag 'FOO' should still be marked as new");
  }

  public function testSetRevisionFlag() {
    $this->assertTrue($this->tagset->setRevisionFlagForTag("$.", true),
                      "Setting a revision flag with boolean should succeed");
    $tag = $this->tagset->getTag("$.");
    $this->assertEquals(1, $tag['needs_revision'],
                        "Setting a revision flag should change the tag data");
    $this->assertEquals('update', $tag['status'],
                        "Setting a revision flag should mark the tag as updated");
    $this->assertFalse($this->tagset->hasErrors(),
                       "Setting a revision flag should produce no errors");
    $this->assertTrue($this->tagset->setRevisionFlagForTag("ADV", "1"),
                      "Setting a revision flag with string should succeed");
    $tag = $this->tagset->getTag("ADV");
    $this->assertEquals(1, $tag['needs_revision'],
                        "Setting a revision flag should change the tag data");
    $this->assertTrue($this->tagset->needsCommit(),
                      "Changing a revision flag should require a commit");
    $this->assertFalse($this->tagset->hasErrors(),
                       "Setting a revision flag should produce no errors");
    $this->assertFalse($this->tagset->setRevisionFlagForTag("FOO", true),
                       "Setting a revision flag for non-existing tag should fail");
    $this->assertTrue($this->tagset->hasErrors(),
                      "Setting a revision flag for non-existing tag "
                      . "should report an error");
  }

  public function testChangeTag_Invalids() {
    $this->assertFalse($this->tagset->changeTag("ADV", ""),
                       "Changing a tag value to be empty should fail");
    $this->assertFalse($this->tagset->changeTag("ADV", "    "),
                       "Changing a tag value to whitespace should fail");
    $input = str_repeat("x", 256);
    $this->assertFalse($this->tagset->changeTag("ADV", $input),
                       "Changing a tag value to something invalid should fail");
    $this->assertFalse($this->tagset->changeTag("ADV", "ADV"),
                       "Changing a tag value to itself should fail");
    $this->assertFalse($this->tagset->changeTag("ADV", "ADJD.Sup"),
                       "Changing a tag value to an already-existing value should fail");
    $this->assertFalse($this->tagset->changeTag("FOO", "ADV"),
                       "Changing a non-existant tag should fail");
    $this->assertTrue($this->tagset->hasErrors(),
                      "Failing to change tag values should produce errors");
    $this->assertFalse($this->tagset->needsCommit(),
                       "Failing to change tag values should NOT require a commit");
  }

  public function testChangeTag_Normal() {
    $this->assertTrue($this->tagset->changeTag("ADV", "FOO"),
                      "Changing a tag value to 'FOO' should succeed");
    $this->assertFalse($this->tagset->contains("ADV"),
                       "Tagset should no longer contain the old value after a change");
    $this->assertTrue($this->tagset->contains("FOO"),
                      "Tagset should contain the new value after a change");
    $tag = $this->tagset->getTag("FOO");
    $this->assertEquals(1927, $tag['id'],
                        "Changing a tag value should NOT change the tag ID");
    $this->assertEquals(0, $tag['needs_revision'],
                        "Changing a tag value should NOT change the revision flag");
    $this->assertEquals('FOO', $tag['value'],
                        "Changing a tag value should change the tag value (d'oh!)");
    $this->assertEquals('update', $tag['status'],
                        "Changing a tag value should mark the tag as updated");
    $this->assertFalse($this->tagset->hasErrors(),
                       "Legally changing a tag value should NOT produce errors");
    $this->assertTrue($this->tagset->needsCommit(),
                      "Changing a tag value should require a commit");
  }

  public function testDeleteOrMarkTag_Invalids() {
    $this->assertFalse($this->tagset->deleteOrMarkTag("FOO"),
                       "Deleting a non-existing tag should fail");
    $this->assertTrue($this->tagset->hasErrors(),
                      "Deleting a non-existing tag should produce an error");
    $this->assertFalse($this->tagset->needsCommit(),
                       "Deleting a non-existing tag should NOT require a commit");
  }

  public function testDeleteOrMarkTag_WithLink() {
    $this->assertTrue($this->tagset->deleteOrMarkTag("NE.Masc.Akk.Sg"),
                      "Deleting an existing tag should succeed");
    $tag = $this->tagset->getTag("NE.Masc.Akk.Sg");
    $this->assertEquals(1, $tag['needs_revision'],
                        "Deleting a tag that is referenced by a tag_suggestion"
                        . " should mark it as needing revision");
    $this->assertEquals('update', $tag['status'],
                        "Deleting a tag that is referenced by a tag_suggestion"
                        . " should mark it as updated");
    $this->assertFalse($this->tagset->hasErrors(),
                       "Deleting a tag should NOT produce errors");
    $this->assertTrue($this->tagset->needsCommit(),
                      "Deleting a tag should require a commit");
  }

  public function testDeleteOrMarkTag_WithoutLink() {
    $this->assertTrue($this->tagset->deleteOrMarkTag("PPOSAT.Fem.Dat.Sg"),
                      "Deleting an existing tag should succeed");
    $tag = $this->tagset->getTag("PPOSAT.Fem.Dat.Sg");
    $this->assertEquals(0, $tag['needs_revision'],
                        "Deleting a tag that is NOT referenced by a tag_suggestion"
                        . " should NOT change its revision status");
    $this->assertEquals('delete', $tag['status'],
                        "Deleting a tag that is NOT referenced by a tag_suggestion"
                        . " should mark it as deleted");
    $this->assertFalse($this->tagset->hasErrors(),
                       "Deleting a tag should NOT produce errors");
    $this->assertTrue($this->tagset->needsCommit(),
                      "Deleting a tag should require a commit");
  }

  public function testCombinations_AddUpdate() {
    $this->assertTrue($this->tagset->addTag("FOO"),
                      "Accessor should accept a new tag 'FOO'");
    $this->assertTrue($this->tagset->setRevisionFlagForTag("FOO", true),
                      "Setting revision flag for newly-added tag should succeed");
    $tag = $this->tagset->getTag("FOO");
    $this->assertEquals('new', $tag['status'],
                        "Newly-added tag should still be marked as new after a change");
    $this->assertTrue($this->tagset->changeTag("FOO", "BAR"),
                      "Changing the value of a newly-added tag should succeed");
    $tag = $this->tagset->getTag("BAR");
    $this->assertEquals('new', $tag['status'],
                        "Newly-added tag should still be marked as new after a change");
  }

  public function testCombinations_AddDelete() {
    $this->assertTrue($this->tagset->addTag("FOO"),
                      "Accessor should accept a new tag 'FOO'");
    $this->assertTrue($this->tagset->deleteOrMarkTag("FOO"),
                      "Deleting a newly-added tag should succeed");
    $this->assertFalse($this->tagset->contains("FOO"),
                       "Deleting a newly-added tag should cause it to be hard-deleted");
  }

  public function testCombinations_UpdateDelete() {
    $this->assertTrue($this->tagset->setRevisionFlagForTag("$.", true),
                      "Setting a revision flag with boolean should succeed");
    $this->assertTrue($this->tagset->deleteOrMarkTag("$."),
                      "Deleting an already-changed tag should succeed");
    $tag = $this->tagset->getTag("$.");
    $this->assertEquals('delete', $tag['status'],
                        "Deleting an already-changed tag should mark it as deleted");
    $this->assertTrue($this->tagset->changeTag("ADV", "FOO"),
                      "Changing a tag value to 'FOO' should succeed");
    $this->assertTrue($this->tagset->deleteOrMarkTag("FOO"),
                      "Deleting an already-changed tag should succeed");
    $tag = $this->tagset->getTag("FOO");
    $this->assertEquals('delete', $tag['status'],
                        "Deleting an already-changed tag should mark it as deleted");
  }

  public function testCombinations_DeleteUpdate() {
    $this->assertTrue($this->tagset->deleteOrMarkTag("ADV"),
                      "Deleting an existing tag should succeed");
    $this->assertFalse($this->tagset->setRevisionFlagForTag("ADV", true),
                       "Setting a revision flag for an already-deleted tag should fail");
    $this->assertFalse($this->tagset->changeTag("ADV", "FOO"),
                       "Changing an already-deleted tag should fail");
  }

  public function testCombinations_DeleteAdd() {
    $this->assertTrue($this->tagset->deleteOrMarkTag("PPOSAT.Fem.Dat.Sg"),
                      "Deleting an existing tag should succeed");
    $this->assertTrue($this->tagset->addTag("PPOSAT.Fem.Dat.Sg"),
                      "Re-adding an already-deleted tag should succeed");
    $tag = $this->tagset->getTag("PPOSAT.Fem.Dat.Sg");
    $this->assertEquals('update', $tag['status'],
                        "Re-adding an already-deleted tag should mark it as updated");
  }

  public function testCommitChanges_addTag() {
    $this->assertTrue($this->tagset->addTag("FOO"),
                      "Accessor should accept a new tag 'FOO'");
    $this->assertTrue($this->tagset->commitChanges(),
                      "Committing changes to the database should succeed");
    $this->assertFalse($this->tagset->hasErrors(),
                       "Committing changes to the database should produce no errors");
    $this->assertEquals(1,
                        $this->getConnection()->createQueryTable("testtags",
                        "SELECT * FROM tag WHERE value='FOO' and tagset_id=6")->getRowCount(),
                        "Database should contain a new entry for 'FOO'");
  }

  public function testCommitChanges_changeTag() {
    $this->assertTrue($this->tagset->changeTag("ADV", "BAR"),
                      "Changing a tag value to 'BAR' should succeed");
    $this->assertTrue($this->tagset->commitChanges(),
                      "Committing changes to the database should succeed");
    $this->assertFalse($this->tagset->hasErrors(),
                       "Committing changes to the database should produce no errors");
    $this->assertEquals(1,
                        $this->getConnection()->createQueryTable("testtags",
                        "SELECT * FROM tag WHERE value='BAR' and id=1927")->getRowCount(),
                        "Database should contain an entry for 'BAR' under the old ID");
  }

  public function testCommitChanges_markTag() {
    $this->assertTrue($this->tagset->deleteOrMarkTag("NE.Masc.Akk.Sg"),
                      "Deleting an existing tag should succeed");
    $this->assertTrue($this->tagset->commitChanges(),
                      "Committing changes to the database should succeed");
    $this->assertFalse($this->tagset->hasErrors(),
                       "Committing changes to the database should produce no errors");
    $this->assertEquals(1,
                        $this->getConnection()->createQueryTable("testtags",
                        "SELECT needs_revision FROM tag WHERE value='NE.Masc.Akk.Sg'")->getValue(0, "needs_revision"),
                        "Tag should be marked as needing revision in the database");
  }

  public function testCommitChanges_deleteTag() {
    $this->assertTrue($this->tagset->deleteOrMarkTag("PPOSAT.Fem.Dat.Sg"),
                      "Deleting an existing tag should succeed");
    $this->assertTrue($this->tagset->commitChanges(),
                      "Committing changes to the database should succeed");
    $this->assertFalse($this->tagset->hasErrors(),
                       "Committing changes to the database should produce no errors");
    $this->assertEquals(0,
                        $this->getConnection()->createQueryTable("testtags",
                        "SELECT * FROM tag WHERE value='PPOSAT.Fem.Dat.Sg'")->getRowCount(),
                        "Database should NOT contain a hard-deleted tag");
  }

  public function testCommitChanges_POSConsistency() {
    $this->assertTrue($this->tagset->addTag("PPOSAT.Fem.Pl"),
                      "Accessor should accept a new tag 'PPOSAT.Fem.Pl'");
    $this->assertFalse($this->tagset->commitChanges(),
                       "Committing 'PPOSAT.Fem.Pl' to the database should fail");
    $this->assertTrue($this->tagset->hasErrors(),
                      "Committing 'PPOSAT.Fem.Pl' to the database should produce an error");
    $this->assertStringStartsWith("POS tag has inconsistent attribute count",
                                  $this->tagset->getErrors()[0],
                                  "Committing 'PPOSAT.Fem.Pl' should fail due to POS consistency check");
  }

  public function testCommitChanges_POSEmptyAttributes() {
    $this->assertTrue($this->tagset->addTag("PPOSAT...Pl"),
                      "Accessor should accept a new tag 'PPOSAT...Pl'");
    $this->assertFalse($this->tagset->commitChanges(),
                       "Committing 'PPOSAT...Pl' to the database should fail");
    $this->assertTrue($this->tagset->hasErrors(),
                      "Committing 'PPOSAT...Pl' to the database should produce an error");
    $this->assertStringStartsWith("POS tag has empty attributes",
                                  $this->tagset->getErrors()[0],
                                  "Committing 'PPOSAT...Pl' should fail due to empty attributes");
  }
}

?>
