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
require_once __DIR__ . "/../lib/cfg.php";
require_once __DIR__ . "/../lib/install.php";
require_once __DIR__ . "/../lib/localeHandler.php";

$migration_dir = __DIR__ . "/migration";
$default_settings = array("DBINFO" => array("HOST" => "127.0.0.1",
                                            "USER" => "cora",
                                            "PASSWORD" => "trustthetext",
                                            "DBNAME" => "cora"),
                          "DBROOT" => array("USER" => "root", "PASSWORD" => ""),
                          "MYSQL_BIN" => "mysql");

function get_settings_from_config() {
    global $default_settings;
    $settings = $default_settings;
    $dbinfo = Cfg::get('dbinfo');
    if (is_array($dbinfo)) {
        $settings["DBINFO"] = $dbinfo;
    }
    return $settings;
}

function get_settings_from_options($options) {
    $settings = get_settings_from_config();
    foreach ($options as $opt => $value) switch ($opt) {
        case 'o':
        case 'host':
            $settings["DBINFO"]["HOST"] = $value;
        break;
        case 'd':
        case 'database':
            $settings["DBINFO"]["DBNAME"] = $value;
        break;
        case 'u':
        case 'user':
            $settings["DBINFO"]["USER"] = $value;
        break;
        case 'p':
        case 'password':
            $settings["DBINFO"]["PASSWORD"] = $value;
        break;
        case 'U':
        case 'root-user':
            $settings["DBROOT"]["USER"] = $value;
        break;
        case 'P':
        case 'root-password':
            $settings["DBROOT"]["PASSWORD"] = $value;
        break;
        case 'b':
        case 'mysql-bin':
            $settings["MYSQL_BIN"] = $value;
        break;
    }
    $settings["DBROOT"]["HOST"] = $settings["DBINFO"]["HOST"];
    return $settings;
}

function get_settings_from_post($post) {
    $settings = get_settings_from_config();
    foreach ($post as $opt => $value) switch ($opt) {
        case 'db_host':
            $settings["DBINFO"]["HOST"] = $value;
        break;
        case 'db_user':
            $settings["DBINFO"]["USER"] = $value;
        break;
        case 'db_password':
            $settings["DBINFO"]["PASSWORD"] = $value;
        break;
        case 'db_dbname':
            $settings["DBINFO"]["DBNAME"] = $value;
        break;
        case 'db_rootuser':
            $settings["DBROOT"]["USER"] = $value;
        break;
        case 'db_rootpass':
            $settings["DBROOT"]["PASSWORD"] = $value;
        break;
        case 'mysql_bin':
            $settings["MYSQL_BIN"] = $value;
        break;
    }
    $settings["DBROOT"]["HOST"] = $settings["DBINFO"]["HOST"];
    return $settings;
}

function make_installer($settings) {
    $installer = new InstallHelper($settings["DBINFO"], new LocaleHandler());
    $installer->mysql_bin = $settings["MYSQL_BIN"];
    return $installer;
}

function get_database_status($installer) {
    $status = array("can_connect" => $installer->canConnect(),
                    "can_execute_mysql" => $installer->canExecuteMySQL(),
                    "need_migration" => false,
                    "migration_path" => false);
    if ($status['can_connect']) {
        $status['version_current'] = $installer->getDBVersion();
        $status['version_required'] = Cfg::get("db_version");
        $status['need_migration'] = ($status['version_current'] != $status['version_required']);
        if ($status['need_migration']) {
            global $migration_dir;
            $status['migration_path'] = $installer->findMigrationPath($status['version_current'],
                                                                      $status['version_required'],
                                                                      $migration_dir);
        }
    } else {
        $status['pdo_exception'] = $installer->pdo_exception;
    }
    return $status;
}

/**
 * Interactively prompts for input without echoing to the terminal.
 * Requires a bash shell or Windows and won't work with
 * safe_mode settings (Uses `shell_exec`)
 *
 * Source: https://www.sitepoint.com/interactive-cli-password-prompt-in-php/
 */
function prompt_silent($prompt = "Enter Password:") {
  if (preg_match('/^win/i', PHP_OS)) {
    $vbscript = sys_get_temp_dir() . 'prompt_password.vbs';
    file_put_contents(
      $vbscript, 'wscript.echo(InputBox("'
      . addslashes($prompt)
      . '", "", "password here"))');
    $command = "cscript //nologo " . escapeshellarg($vbscript);
    $password = rtrim(shell_exec($command));
    unlink($vbscript);
    return $password;
  } else {
    $command = "/usr/bin/env bash -c 'echo OK'";
    if (rtrim(shell_exec($command)) !== 'OK') {
      throw new InstallException("Can't invoke bash");
    }
    $command = '/usr/bin/env bash -c \'read -s -p "'
      . addslashes($prompt)
      . '" mypassword && echo $mypassword\'';
    $password = rtrim(shell_exec($command));
    echo "\n";
    return $password;
  }
}
?>
