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
require_once"{$GLOBALS['CORA_WEB_DIR']}/lib/contentModel.php";

/** Test Menu class
 *  02/2013 Florian Petran
 */
class Cora_Tests_Menu_Test extends PHPUnit_Framework_TestCase {
    protected $menu;
    protected $test_data;

    public function setUp() {
        $this->test_data = array(
            "item1" => array(
                        "id" => "1",
                        "file" => "testfile.php",
                        "js_file" => "testfile.js",
                        "caption" => "Test Caption",
                        "tooltip" => "Test Tooltip"
            ),
            "item2" => array(
                        "id" => "2",
                        "file" => "test2.php",
                        "js_file" => "test2.js",
                        "caption" => "Another Test Caption",
                        "tooltip" => "Another Test Tooltip"
            )
        );
        $this->menu = new Menu();
    }

    public function testAddItem() {
        $this->menu->addMenuItem(
            $this->test_data["item1"]["id"],
            $this->test_data["item1"]["file"],
            $this->test_data["item1"]["js_file"],
            $this->test_data["item1"]["caption"],
            $this->test_data["item1"]["tooltip"]
        );

        // assert that the menu item is the one we just added
        $this->assertEquals(array($this->test_data["item1"]["id"]),
            $this->menu->getItems());
        $this->assertEquals($this->test_data["item1"]["file"],
            $this->menu->getItemFile($this->test_data["item1"]["id"]));
        $this->assertEquals($this->test_data["item1"]["js_file"],
            $this->menu->getItemJSFile($this->test_data["item1"]["id"]));
        $this->assertEquals($this->test_data["item1"]["tooltip"],
            $this->menu->getItemTooltip($this->test_data["item1"]["id"]));
        $this->assertEquals($this->test_data["item1"]["caption"],
            $this->menu->getItemCaption($this->test_data["item1"]["id"]));
    }

    public function testDefaultItem() {
        foreach ($this->test_data as $item) {
            $this->menu->addMenuitem(
                $item["id"],
                $item["file"],
                $item["js_file"],
                $item["caption"],
                $item["tooltip"]
            );
        }

        $this->assertEquals($this->test_data["item1"]["id"],
                            $this->menu->getDefaultItem());

        $this->menu->setDefaultItem($this->test_data["item2"]["id"]);
        $this->assertEquals($this->test_data["item2"]["id"],
                            $this->menu->getDefaultItem());
    }
}
?>
