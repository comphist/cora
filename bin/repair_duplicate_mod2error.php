<?php

/** Repairs duplicate entries in mod2error (that shouldn't happen due
 * to UNIQUE KEY constraint, but hey...)
 *
 * @author Marcel Bollmann
 * @date September 2014
 */

require_once '../lib/cfg.php';

$dbinfo = Cfg::get('dbinfo');
$dbo = new PDO('mysql:host='.$dbinfo['HOST']
              .';dbname='.$dbinfo['DBNAME']
              .';charset=utf8',
               $dbinfo['USER'], $dbinfo['PASSWORD']);
$dbo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$stmt_insert = $dbo->prepare("INSERT INTO `mod2error` (`mod_id`, `error_id`) "
                             . "VALUES (:modid, :errid)");

$entries = $dbo->query("SELECT * FROM `mod2error`");
$unique_entries = array();
$entry_count_all  = 0;
$entry_count_uniq = 0;
while($entry=$entries->fetch(PDO::FETCH_ASSOC)) {
    $entry_count_all++;
    if(isset($unique_entries[$entry['mod_id']])) {
        if(!in_array($entry['error_id'], $unique_entries[$entry['mod_id']])) {
            $unique_entries[$entry['mod_id']][] = $entry['error_id'];
            $entry_count_uniq++;
        }
    }
    else {
        $unique_entries[$entry['mod_id']] = array($entry['error_id']);
        $entry_count_uniq++;
    }
}

echo("Total  row count in mod2error: ".$entry_count_all."\n");
echo("Unique row count in mod2error: ".$entry_count_uniq."\n");

if($entry_count_all == $entry_count_uniq)
    exit(0);

$dbo->beginTransaction();
try {
    $dbo->query("DELETE FROM mod2error");
    foreach($unique_entries as $modid => $errorlist) {
        foreach($errorlist as $errid) {
            $stmt_insert->execute(array(':modid' => $modid,
                                        ':errid' => $errid));
        }
    }
}
catch(Exception $ex) {
    echo("An exception occured:\n".$ex->getMessage()."\n");
    $dbo->rollBack();
    exit(1);
}
$dbo->commit();

?>
