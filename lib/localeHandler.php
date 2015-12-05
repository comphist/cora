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

/** @file localeHandler.php
 * Functions specific to user locale.
 *
 * @author Marcel Bollmann
 * @date May 2015
 */

require_once "cfg.php";

/** Contains locale-specific data and functions.
 */
class LocaleHandler {
  protected $locale = null;
  protected $localedir = null;
  protected $supported = array();
  private $data = array();

  function __construct($localedir=null) {
    if ($localedir == null) {
      $localedir = __DIR__ . "/../locale/";
    }
    $this->localedir = $localedir;
    $this->supported = json_decode(
      file_get_contents($localedir . "supported_locales.json")
    );
  }

  /** Set a locale. */
  public function set($locale) {
    if($locale == null || !$this->isSupported($locale)) {
      if (isset($_SERVER["HTTP_ACCEPT_LANGUAGE"])) {
        $locale = $this->extractBestLocale($_SERVER["HTTP_ACCEPT_LANGUAGE"]);
      } else {
        $locale = $this->defaultLocale();
      }
    }
    $this->locale = $locale;
    $this->loadLocaleFile();
    return $locale;
  }

  /** Retrieve a localized string.
   *
   * @param string $category The name of the field to retrieve, with dots (.)
   *                         being interpreted as field delimiters
   * @param array $args An optional array of values to be substituted for
   *                    placeholders in the localized string.
   *
   * @return The localized string.
   */
  public function localize($category, $args=array()) {
    if ($this->locale == null) return "";
    $str = $this->retrieveElement($category);
    return self::performSubstitutions($str, $args);
  }

  /** An alias for LocaleHandler::localize(). */
  public function _($category, $args=array()) {
    return $this->localize($category, $args);
  }

  /** An alias for LocaleHandler::localize(). */
  public function __invoke($category, $args=array()) {
    return $this->localize($category, $args);
  }

  /** Check if a given locale is supported.
   */
  public function isSupported($locale) {
    return in_array($locale, $this->supported);
  }

  /** Return the default locale.
   */
  public function defaultLocale() {
    if (count($this->supported) == 0)
      return null;
    $default = Cfg::get("default_language");
    if (in_array($default, $this->supported))
      return $default;
    return $this->supported[0];
  }

  /** Return the locale that best matches the given Accept-Language string.
   *
   * Parses a string of accepted languages in the format of an HTTP
   * Accept-Language header, and returns the best matching locale from the list
   * of supported locales.
   */
  protected function extractBestLocale($str) {
    // from <http://www.thefutureoftheweb.com/blog/use-accept-language-header>
    preg_match_all('/([a-z]{1,8}(-[a-z]{1,8})?)\s*(;\s*q\s*=\s*(1|0\.[0-9]+))?/i',
                   $str,
                   $lang_parse);

    if (count($lang_parse[1])) {
      // create a list like "en" => 0.8
      $langs = array_combine($lang_parse[1], $lang_parse[4]);
      // set default to 1 for any without q factor
      foreach ($langs as $lang => $val) {
        if ($val === '') $langs[$lang] = 1;
      }
      // sort list based on value
      arsort($langs, SORT_NUMERIC);
    }

    // look through sorted list and use first one that matches our languages
    foreach ($langs as $lang => $val) {
      if ($this->isSupported($lang)) {
        return $lang;
      }
      $lang = strtolower($lang);
      foreach ($this->supported as $s) {
        if ($lang == strtolower($s))
          return $s;
        if (strpos(strtolower($s), $lang) === 0)
          return $s;
      }
    }

    return $this->defaultLocale();
  }

  /** Loads the JSON file containing the translation strings. */
  private function loadLocaleFile() {
    if ($this->locale == null)
      return;
    $filename = $this->localedir . "Locale." . $this->locale . ".json";
    $json = json_decode(file_get_contents($filename), true);
    assert((isset($json["name"]) && $json["name"] === $this->locale));
    assert(isset($json["sets"]));
    $this->data = $json["sets"];
  }

  /** Looks up a string in the localization table. */
  private function retrieveElement($category) {
    $keys = explode(".", $category);
    $elem = $this->data;
    foreach($keys as $k) {
      if (!isset($elem[$k])) return "";
      $elem = $elem[$k];
    }
    return $elem;
  }

  /** Substitutes variables in a format string. */
  private static function performSubstitutions($s, $args) {
    if (empty($args)) return $s;
    $keys = array();
    $values = array();
    foreach($args as $k => $v) {
      $keys[] = '{'.$k.'}';
      $values[] = $v;
    }
    return str_replace($keys, $values, $s);
  }
}

?>
