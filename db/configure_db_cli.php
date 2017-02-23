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
require_once "common.php";

$notwin = (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN');
$status_code = 0;
define("STATUS_UNKNOWN_ERROR", 1);
define("STATUS_CANNOT_CONNECT", 4);
define("STATUS_MIGRATION_NEEDED", 5);
define("STATUS_ACTION_NOT_POSSIBLE", 6);
define("STATUS_FORCE_REQUIRED", 7);
$options = getopt("o:d:u:p:U:P:b:a:h",
                  array("host:", "database:", "user:", "password:",
                        "root-user:", "root-password:", "mysql-bin:",
                        "action:", "force"));
if (array_key_exists("h", $options)) {
?>

Checks/installs/migrates the CorA database.

    Usage:
      <?php echo $argv[0]; ?> [options] [-a <action>]

    Possible options:
      -o, --host           DB hostname (default: 127.0.0.1)
      -d, --database       DB name (default: cora)
      -u, --user           DB user (default: cora)
      -p, --password       DB password (default: trustthetext)
      -U, --root-user      DB root user (default: root)
      -P, --root-password  DB root password (by default, will try
                           an empty password and then prompt for
                           password entry)
      -b, --mysql-bin      Path to MySQL client (default: mysql)
      -a, --action         Choose an action (see below)

    Possible actions:
      status               Show the database status (default)
      install              Perform a fresh database install
      upgrade              Upgrade the database to a newer version

<?php
    exit;
}
if (array_key_exists("action", $options))
    $action = $options["action"];
elseif (array_key_exists("a", $options))
    $action = $options["a"];
else
    $action = "status";

function echo_db_credentials() {
    global $settings;
    printf("*** Database credentials:\n");
    printf("      Host: %s\n", $settings["DBINFO"]["HOST"]);
    printf("      User: %s\n", $settings["DBINFO"]["USER"]);
    printf("      Password: %s\n", $settings["DBINFO"]["PASSWORD"]);
    printf("      Database: %s\n", $settings["DBINFO"]["DBNAME"]);
}

function echo_db_root() {
    global $settings;
    printf("*** Database root credentials:\n");
    printf("       User: %s\n", $settings["DBROOT"]["USER"]);
    printf("       Password: %s\n",
           (empty($settings["DBROOT"]["PASSWORD"]) ? "(not given)" : "(given)"));
}

function echo_status() {
    global $status;
    global $status_code;
    if ($status['can_connect']) {
        echo_db_credentials();
        printf("*** Database connection established.\n");
        printf("      Current db version:  %s\n", $status['version_current']);
        printf("      Required db version: %s\n", $status['version_required']);
        if (!$status['need_migration']) {
            printf("*** Database okay, no further action needed.\n");
            $status_code = 0;
        } else {
            printf("*** Database version mismatch!  You should probably upgrade the database.\n");
            $status_code = STATUS_MIGRATION_NEEDED;
        }
    } else {
        echo_db_credentials();
        printf("*** Could NOT connect to database.\n\n");
        if ($status['pdo_exception']) {
            printf($status['pdo_exception']);
            printf("\n");
        }
        $status_code = STATUS_CANNOT_CONNECT;
    }
}

function ensure_mysql() {
    global $status;
    if (!$status['can_execute_mysql']) {
        printf("*** ERROR: Cannot execute MySQL command\n");
        exit(STATUS_ACTION_NOT_POSSIBLE);
    }
}

function recheck_and_save() {
    global $installer;
    global $settings;
    global $status;
    global $status_code;
    printf(" success.\n\n*** Rechecking database status...\n");
    $installer->setDBInfo($settings['DBINFO']);
    $status = get_database_status($installer);
    echo_status();
    if ($status_code == 0) {
        try {
            Cfg::save_user_opts();
            printf("*** Saved database settings.\n");
        }
        catch(Exception $ex) {
            printf("*** CAUTION: Database settings could not be saved:\n%s\n", $ex->getMessage());
            printf("*** If you changed the defaults, you might need to edit your config.php manually.\n");
        }
    }
}

function try_or_prompt(&$settings, &$status, &$installer, $function) {
    try {
        $function($settings, $status, $installer);
    }
    catch(InstallException $ex) {
        $msg = $ex->getMessage();
        if (empty($settings["DBROOT"]["PASSWORD"])
            && strpos($msg, "MySQL command returned code") !== false
            && strpos($msg, "Access denied") !== false) {
            printf("\n*** Password required for connecting as MySQL user '%s'\n\n", $settings["DBROOT"]["USER"]);
            $settings['DBROOT']['PASSWORD'] = prompt_silent(
                'Enter password for MySQL user "' . $settings["DBROOT"]["USER"] . '": '
            );
            $function($settings, $status, $installer);
        } else {
            throw $ex;
        }
    }
}

$settings = get_settings_from_options($options);
$installer = make_installer($settings);
$status = get_database_status($installer);
echo_status();

if ($action == "install") {
    if ($status['can_connect'] && !array_key_exists('force', $options)) {
        printf("!!!\n!!! ATTENTION!\n");
        printf("!!! You selected 'install', but a CorA database already exists.\n");
        printf("!!! To perform a fresh install anyway, please use the '--force' option.\n");
        printf("!!! Doing so will DELETE ALL EXISTING DATA in the CorA database.\n");
        printf("!!!\n");
        exit(STATUS_FORCE_REQUIRED);
    }
    echo_db_root();
    printf("*** Trying to perform a fresh install...");
    try {
        try_or_prompt(
            $settings, $status, $installer,
            function(&$settings, &$status, &$installer) {
                $installer->installDB(__DIR__, $settings['DBROOT']);
            }
        );
    }
    catch(Exception $ex) {
        printf("\n*** ERROR: An error occured:\n%s\n", $ex->getMessage());
        exit(STATUS_UNKNOWN_ERROR);
    }
    recheck_and_save();
}

if ($action == "upgrade" && $status['need_migration']) {
    if (empty($status['migration_path'])) {
        printf("*** ERROR: Version mismatch, but no migration path found!\n");
        exit(STATUS_ACTION_NOT_POSSIBLE);
    }
    printf("*** Automatic migration path found:\n");
    foreach ($status['migration_path'] as $path_entry) {
        printf("      %s\n", $path_entry);
    }
    echo_db_root();
    printf("*** Trying to perform database migration...");
    try {
        try_or_prompt(
            $settings, $status, $installer,
            function(&$settings, &$status, &$installer) {
                $installer->applyMigrationPath($status['migration_path'], $settings['DBROOT']);
            }
        );
    }
    catch(Exception $ex) {
        printf("\n*** ERROR: An error occured:\n%s\n", $ex->getMessage());
        exit(STATUS_UNKNOWN_ERROR);
    }
    recheck_and_save();
}

exit($status_code);
?>
