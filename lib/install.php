<?php
/** @file install.php
 * Functionality only required for installing and updating CorA
 */

require_once "cfg.php";
require_once "connect.php";

class InstallException extends Exception {}

/** Functions needed when installing or updating the database
 */
class InstallHelper {
  private $dbinfo;
  private $dbo;
  private $dbi;
  private $can_connect;
  public $mysql_bin = "mysql";

  public function __construct($dbinfo) {
    $this->setDBInfo($dbinfo);
  }

  public function setDBInfo($dbinfo) {
    $this->dbinfo = $dbinfo;
    try {
      $this->dbo = new PDO('mysql:host='.$dbinfo['HOST']
                          .';dbname='.$dbinfo['DBNAME']
                          .';charset=utf8',
			   $dbinfo['USER'],
                           $dbinfo['PASSWORD']);
      $this->dbi = new DBInterface($dbinfo);
      $this->can_connect = true;
    }
    catch (PDOException $ex) {
      $this->dbo = null;
      $this->can_connect = false;
    }
  }

  /** Find out if a connection is possible. */
  public function canConnect() {
    return $this->can_connect;
  }

  /** Gets the current database version string.  */
  public function getDBVersion() {
    if (!$this->can_connect) return null;
    $q = $this->dbo->query("SELECT `version` FROM versioning ORDER BY `id` DESC LIMIT 1");
    if (!$q) return null;
    return $q->fetchColumn();
  }

  /** Check if a name is a valid user name.
   *
   * If a connection to the database can be made, we check if the account
   * already exists.  Otherwise, we just check if it's non-empty and not equal
   * to the reserved name "system".
   */
  public function isValidAccountName($name) {
    if (!$name || $name == "system") return false;
    if ($this->can_connect) {
      return !$this->dbi->getUserByName($name);
    }
    return true;
  }

  /** Execute a system command, capturing both STDOUT and STDERR as well as the
   * return code.
   */
  protected static function exec($cmd, $input='') {
    $cwd = getcwd();
    $proc = proc_open($cmd, array(array('pipe', 'r'),
                                  array('pipe', 'w'),
                                  array('pipe', 'w')), $pipes, $cwd);
    fwrite($pipes[0], $input);
    fclose($pipes[0]);
    $stdout = stream_get_contents($pipes[1]);
    fclose($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[2]);
    $return_code = (int)proc_close($proc);
    return array($return_code, $stdout, $stderr);
  }

  /** Find out if the MySQL binary can be executed.
   *
   * Set the binary to look for by changing $this->mysql_bin.
   */
  public function canExecuteMySQL() {
    try {
      $cmd = "\"{$this->mysql_bin}\" --version";
      list($rc, $stdout, $stderr) = self::exec($cmd);
      if (($rc > 0) || !$stdout)
        return false;
      return (substr($stdout, 0, 5) == "mysql");
    }
    catch (Exception $ex) {
      return false;
    }
  }

  /** Execute a MySQL script.
   *
   * @param string $script Script to execute
   * @param array $cred Database credentials
   */
  protected function callMySQL($script, $cred) {
    if (!$this->canExecuteMySQL())
      throw new InstallException('mysql_not_found');
    $cmd = "\"{$this->mysql_bin}\""
         . " --user={$cred['USER']}"
         . " --host='{$cred['HOST']}'";
    if ($cred['PASSWORD'])
      $cmd .= " --password={$cred['PASSWORD']}";
    list($rc, $stdout, $stderr) = self::exec($cmd, $script);
    if ($rc > 0)
      throw new InstallException("MySQL command returned code {$rc}:\n{$stderr}");
  }

  /** Check if system user exists and has correct settings. */
  public function correctSystemUser() {
    if (!$this->can_connect) return false;
    $qs = "SELECT `name`, `password`, `admin` FROM users WHERE `id`=1";
    $stmt = $this->dbo->prepare($qs);
    $stmt->execute();
    if ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
      return ($data['name'] == "system"
              && !$data['password']
              && $data['admin'] == 1);
    }
    return false;
  }

  /** Repair the system user. */
  public function fixSystemUser() {
    if (!$this->can_connect) return false;
    $this->dbo->exec("DELETE FROM users WHERE `id`=1 OR `name`='system'");
    $this->dbo->exec("INSERT INTO users (`id`, `name`, `admin`, `password`) "
                    ."VALUES (1, 'system', 1, '')");
    return true;
  }

  /** Search for SQL files that specify how to migrate from one schema version
   *  to another.
   *
   * @return A list of filenames containing the SQL files for the migration,
   *    or 'false' if unsuccessful.
   */
  public function findMigrationPath($from, $to, $filepath) {
    $path = array();
    while ($from != $to) {
      $prefix = "{$filepath}/{$from}-to-";
      $migration = glob("{$prefix}*");
      if (!$migration) return false;
      $migration = $migration[0];
      if (substr($migration, -4) != ".sql") return false;
      $from = substr($migration, strlen($prefix), -4);
      $path[] = $migration;
      if (count($path) > 1000) { // endless loop protection
        return false;
      }
    }
    return $path;
  }

  /** Apply a migration path to the database.
   *
   * Calls the mysql executable on an automatically generated script.
   * Requires MySQL root permissions.
   *
   * This function communicates error state via InstallException.
   *
   * @param array $migrpath A list of SQL scripts to be executed in order,
   *     as returned by InstallHelper::findMigrationPath.
   * @param array $root Database credentials for MySQL
   */
  public function applyMigrationPath($migrpath, $root) {
    $output = array("USE `".$this->dbinfo['DBNAME']."`;\n");
    foreach($migrpath as $filename) {
      $output[] = file_get_contents($filename);
    }
    $output[] = $this->generateSQLforVersionString();
    $this->callMySQL(implode("\n", $output), $root);
  }

  /** Perform a fresh database install.
   *
   * THIS OPERATION WILL DELETE AND REBUILD THE CORA DATABASE.
   *
   * @param string $path Path to the DB creation files
   * @param array $root Database credentials for MySQL
   */
  public function installDB($path, $root) {
    if (!file_exists("{$path}/coradb.sql"))
      throw new InstallException("coradb_missing");
    if (!file_exists("{$path}/coradb-data.sql"))
      throw new InstallException("coradb_data_missing");
    $first_account = array('user' => 'admin',
                           'password' => 'admin');
    $output = array(
      $this->generateSQLforDatabaseCreation(),
      file_get_contents("{$path}/coradb.sql"),
      file_get_contents("{$path}/coradb-data.sql"),
      $this->generateSQLforAccount($first_account),
      $this->generateSQLforGrants(),
      $this->generateSQLforVersionString()
    );
    $this->callMySQL(implode("\n", $output), $root);
  }

  /** Generate SQL string to create the database. */
  public function generateSQLforDatabaseCreation() {
    $sql = "CREATE DATABASE IF NOT EXISTS `".$this->dbinfo['DBNAME']."`;\n"
         . "USE `".$this->dbinfo['DBNAME']."`;\n\n";
    return $sql;
  }

  /** Generate SQL string to create a user account. */
  public function generateSQLforAccount($account) {
    $user = $account['user'];
    $pass = DBInterface::hashPassword($account['password']);
    $sql = <<<SQL
INSERT INTO `users` ( `name`, `password`, `admin` ) VALUES ( "{$user}", "{$pass}", 1 );

SQL;
    return $sql;
  }

  /** Generate SQL string to create necessary user grants.
   */
  public function generateSQLforGrants() {
    $dbname = $this->dbinfo['DBNAME'];
    $dbuser = $this->dbinfo['USER'];
    $dbhost = $this->dbinfo['HOST'];
    $dbpass = $this->dbinfo['PASSWORD'];
    $sql = <<<SQL
GRANT SELECT,DELETE,UPDATE,INSERT ON {$dbname}.* TO '{$dbuser}'@'{$dbhost}' IDENTIFIED BY '{$dbpass}';
GRANT CREATE,DROP ON {$dbname}_test.* TO '{$dbuser}_test'@'{$dbhost}' IDENTIFIED BY '{$dbpass}_test';
GRANT ALL PRIVILEGES ON {$dbname}_test.* TO '{$dbuser}'@'{$dbhost}' IDENTIFIED BY '{$dbpass}';

SQL;
    return $sql;
  }

  /** Generate SQL string to insert versioning information.
   *
   * If no version string is given, the one from the current configuration
   * will be used.
   */
  public function generateSQLforVersionString($version=false) {
    if (!$version) {
      $version = Cfg::get("db_version");
    }
    $sql = <<<SQL
INSERT INTO `versioning` (`version`, `time`) VALUES ("$version", NOW());

SQL;
    return $sql;
  }

}

?>
