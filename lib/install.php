<?php
/** @file install.php
 * Functionality only required for installing and updating CorA
 */

require_once "cfg.php";
require_once "connect.php";

/** Functions needed when installing or updating the database
 */
class InstallHelper {
  private $dbinfo;
  private $dbo;
  private $dbi;
  private $can_connect = true;

  public function __construct($dbinfo) {
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

  /** Gets the current database version string.
   */
  public function getDBVersion() {
    if (!$this->can_connect) return null;
    return $this->dbo->query("SELECT `version` FROM versioning ORDER BY `id` DESC LIMIT 1",
                             PDO::FETCH_COLUMN, 0);
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

  /** Generate SQL string to create data and grants for first-time installation.
   *
   * Adds the system user and creates grants for the CorA user on the database.
   */
  public function generateSQLforFirstTimeInit() {
    $dbname = $this->dbinfo['DBNAME'];
    $dbuser = $this->dbinfo['USER'];
    $dbhost = $this->dbinfo['HOST'];
    $dbpass = $this->dbinfo['PASSWORD'];
    $sql = <<<SQL
LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` ( `id`, `name`, `password`, `admin` ) VALUES ( 1, "system", "", 1 );
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

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
