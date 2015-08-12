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
  private static $user_options;
  private static $default_options;

  private static function load_opts() {
    if (file_exists(CORA_CONFIG_FILE)) {
      self::$user_options = include CORA_CONFIG_FILE;
    } else {
      self::$user_options = array();
    }
    $options = include dirname(__FILE__) . "/../config.defaults.php";
    $version = include dirname(__FILE__) . "/../version.php";
    self::$default_options = array_merge($options, $version);
  }

  /** Accesses a configuration variable.
   */
  public static function get($var) {
    if (!self::$default_options) { self::load_opts(); }
    if (array_key_exists($var, self::$user_options)) {
      return self::$user_options[$var];
    }
    return self::$default_options[$var];
  }
}

?>
