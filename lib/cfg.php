<?php
/** @file cfg.php
 * Class to access configuration options
 */

define('CORA_CONFIG_FILE', dirname(__FILE__) . "/../config.php");

/** Accessor for configuration options.
 *
 * Currently, this acts as a simple wrapper around a configuration
 * array defined elsewhere.  By making this a class (instead of accessing
 * globals directly), we have the option to easily change the
 * implementation/storage of these config variables, though.
 */
class Cfg {
  private static $options;

  private static function load_opts() {
    $options = include CORA_CONFIG_FILE;
    $version = include dirname(__FILE__) . "/../version.php";
    self::$options = array_merge($options, $version);
  }

  /** Accesses a configuration variable.
   */
  public static function get($var) {
    if (!self::$options) { self::load_opts(); }
    return self::$options[$var];
  }
}

?>
