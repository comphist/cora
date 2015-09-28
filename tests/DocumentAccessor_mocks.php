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
/** @file DocumentAccessor_mocks.php
 *
 * This file contains mocks that are commonly used by DocumentAccessor and/or
 * classes that derive from it, so the mocks will be required (and identical)
 * in several test files.
 */

/** DBInterface Mock for importDocument test
 *
 * 09/2015 Marcel Bollmann
 */
class Cora_Tests_DBInterface_Mock {
  public function getTagsets($class="pos", $orderby="name") {
    $tagsets = get_DBInterface_expected()['tagsets'];
    $result = array();
    foreach ($tagsets as $ts) {
      $ts['shortname'] = $ts['id'];
      $ts['longname'] = $ts['name'];
      unset($ts['name']);
      if (!$class || $ts['class'] == $class) {
        $result[] = $ts;
      }
    }
    return $result;
  }

  public function getTagsetsForFile($fileid) {
    if ($fileid == 3) {
      return array(
        array('id' => 1, 'name' => 'ImportTest', 'class' => 'pos', 'set_type' => 'closed'),
        array('id' => 2, 'name' => 'NormTest', 'class' => 'norm', 'set_type' => 'open'),
        array('id' => 3, 'name' => 'LemmaTest', 'class' => 'lemma', 'set_type' => 'open'),
        array('id' => 4, 'name' => 'Comment', 'class' => 'comment', 'set_type' => 'open')
      );
    } else {
      return array();
    }
  }

  public function getErrorTypes() {
    return array("general_error" => 1,
                 "inflection" => 2);
  }
}

/** CoraDocument Mock for importDocument test
 *
 * 03/2013 Florian Petran
 */
class Cora_Tests_CoraDocument_Mock {
    private $test_data;
    function __construct() {
        $this->test_data = get_CoraDocument_data();
    }
    public function getHeader() {
        return "Importtest header";
    }
    public function getPages() {
        return $this->test_data["pages"];
    }
    public function getColumns() {
        return $this->test_data["columns"];
    }
    public function getLines() {
        return $this->test_data["lines"];
    }
    public function getTokens() {
        return $this->test_data["tokens"];
    }
    public function getDipls() {
        return $this->test_data["dipls"];
    }
    public function getModerns() {
        return $this->test_data["mods"];
    }
    public function getShifttags() {
        return array();
    }
    public function getComments() {
        return array(array(
            "text" => "bla bla kommentar",
            "type" => "K",
            "parent_db_id" => "7"
        ));
    }

    public function fillPageIDs() {}
    public function fillColumnIDs() {}
    public function fillLineIDs() {}
    public function fillTokenIDs() {}
    public function fillDiplIDs() {}
    public function fillModernIDs() {}
}
