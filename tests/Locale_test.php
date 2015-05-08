<?php
require_once"../lib/localeHandler.php";

/** Mock LocaleHandler class so we can control which locales are supported
 */
class Cora_Tests_LocaleHandler_Mock extends LocaleHandler {
    function __construct() {
        $this->supported = array("en-US", "de-DE");
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

    public function testDefaultLocale() {
        $this->assertEquals("en-US", $this->lh->defaultLocale());
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

        $accept = "zh-CN,zh;q=0.8";
        $this->assertEquals("en-US",
                            $this->lh->extractBestLocale($accept));
    }
}
?>
