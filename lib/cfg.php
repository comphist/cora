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
*/
?>
<?php
/** @file cfg.php
 * Class to access configuration options
 */
define('CORA_CONFIG_FILE', true);

/** Accessor for configuration options
 *
 * Currently, this acts as a simple wrapper around a configuration
 * array defined elsewhere.  By making this a class (instead of accessing
 * globals directly), we have the option to easily change the
 * implementation/storage of these config variables, though.
 */
class Cfg {
    private static $user_config_file;
    private static $user_options;
    private static $default_options;
    private static function load_opts() {
        $options = include dirname(__FILE__) . "/../config.defaults.php";
        $version = include dirname(__FILE__) . "/../version.php";
        self::$default_options = array_merge($options, $version);
        self::$user_config_file = dirname(__FILE__) . "/../config.php";
        if (!self::$user_options) {
            self::load_user_opts();
        }
    }

    /** Load user-defined configuration values
     *
     * @param string $filename Name of configuration file to read from;
     *                         defaults to self::$user_config_file
     */
    public static function load_user_opts($filename = null) {
        if ($filename === null) {
            $filename = self::$user_config_file;
        }
        if (file_exists($filename)) {
            self::$user_options = include $filename;
        } else {
            self::$user_options = array();
        }
    }

    /** Save user-defined configuration values
     *
     * @param string $filename Name of configuration file to save to;
     *                         defaults to self::$user_config_file
     */
    public static function save_user_opts($filename = null) {
        if ($filename === null) {
            $filename = self::$user_config_file;
        }
        $status = file_put_contents($filename, '<?php return ' . var_export(self::$user_options, true) . ';');
        if (!$status) {
            throw new RuntimeException("Couldn't write user configuration to disk.");
        }
    }

    /** Access a configuration variable
     *
     * If the variable is set in the user configuration (config.php), it will be
     * returned from there; otherwise, it is returned from the default
     * configuration file (config.defaults.php).
     *
     * @param string $var Name of the variable
     */

     public static function get($var) {
        if (!self::$default_options) {
            self::load_opts();
        }
        if (array_key_exists($var, self::$user_options)) {
            return self::$user_options[$var];
        }
        if (array_key_exists($var, self::$default_options)) {
            return self::$default_options[$var];
        }
        return null;
    }

    /** Set a configuration variable
     *
     * Sets a configuration variable to a given value.  This value will always
     * be stored in the user configuration.
     *
     * @param string $var Name of the variable
     * @param string $val New value
     */
    public static function set($var, $val) {
        if (!self::$default_options) {
            self::load_opts();
        }
        self::$user_options[$var] = $val;
    }
}
?>
