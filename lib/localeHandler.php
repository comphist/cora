<?php

/** @file localeHandler.php
 * Functions specific to user locale.
 *
 * @author Marcel Bollmann
 * @date May 2015
 */

/** Contains locale-specific data and functions.
 */
class LocaleHandler {
  protected $locale = null;
  protected $supported = array();

  function __construct($localefile="/../locale/supported_locales.json") {
    $this->supported = json_decode(
      file_get_contents(__DIR__ . $localefile)
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
    return $locale;
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
}

?>
