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
require_once"{$GLOBALS['CORA_WEB_DIR']}/lib/localeHandler.php";

/** Mock LocaleHandler class so we can control which locales are supported
 */
class Cora_Tests_LocaleHandler_Mock extends LocaleHandler {
    function __construct() {
        $this->supported = array("en-US", "de-DE");
    }

    /* expose protected function */
    public function extractBestLocale($str) {
        return parent::extractBestLocale($str);
    }
}

/** Test LocaleHandler class
 *  05/2015 Marcel Bollmann
 */
class Cora_Tests_LocaleHandler_Test extends PHPUnit_Framework_TestCase {
    protected $lh;

    public function setUp() {
        $this->lh = new Cora_Tests_LocaleHandler_Mock();
    }

    public function testIsSupported() {
        $this->assertTrue($this->lh->isSupported("en-US"));
        $this->assertTrue($this->lh->isSupported("de-DE"));
        $this->assertFalse($this->lh->isSupported("de-AT"));
        $this->assertFalse($this->lh->isSupported("en"));
    }

    public function testBestLocale() {
        $accept = "de-DE,de;q=0.8,en-US;q=0.6,en;q=0.4";
        $this->assertEquals("de-DE",
                            $this->lh->extractBestLocale($accept));

        $accept = "de-AT,de;q=0.8,en-US;q=0.6,en;q=0.4";
        $this->assertEquals("de-DE",
                            $this->lh->extractBestLocale($accept));

        $accept = "zh-CN,zh;q=0.8,en-US;q=0.6,en;q=0.4";
        $this->assertEquals("en-US",
                            $this->lh->extractBestLocale($accept));

        $accept = "zh-CN,zh;q=0.8,de-DE;q=0.6,en-US;q=0.4";
        $this->assertEquals("de-DE",
                            $this->lh->extractBestLocale($accept));

        // list contains no supported locale - expect default locale
        $accept = "zh-CN,zh;q=0.8";
        $this->assertEquals($this->lh->defaultLocale(),
                            $this->lh->extractBestLocale($accept));
    }
}
?>
