<?php

/** Repairs database changes introduced by a bug that was fixed in r352.
 *
 * This script finds duplicate entries in the 'tag' table for tagsets
 * marked as 'closed' (where only unique tag values are supposed to
 * exist), repairs any references in 'tag_suggestion' to only point to
 * the first occurence of such an entry, then deletes the duplicates.
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

$stmt_fetchTagset = $dbo->prepare("SELECT `id`, `value` FROM tag "
                                  ."WHERE `tagset_id`=:tid ORDER BY `id` ASC");
$stmt_updateTS = $dbo->prepare("UPDATE `tag_suggestion` SET `tag_id`=:newid "
                               ."WHERE `tag_id`=:oldid");
$stmt_deleteTag = $dbo->prepare("DELETE FROM `tag` WHERE `id`=:tagid");

$repair = (sizeof($argv)>1 && $argv[1] == "repair");
$duplicate_count = 0;

$query = "SELECT * FROM tagset WHERE `set_type`='closed'";
$closedTagsets = $dbo->query($query)->fetchAll(PDO::FETCH_ASSOC);
foreach($closedTagsets as $tagset) {
    echo("Scanning tagset $tagset[name] ");
    echo("(id=$tagset[id], class=$tagset[class])... ");

    $unique_tags    = array(); // map unique tag values to lowest ID
    $duplicate_tags = array(); // map duplicate tag IDs to lowest ID
    $affected_rows = 0;

    $stmt_fetchTagset->execute(array(':tid' => $tagset['id']));
    while($tag = $stmt_fetchTagset->fetch(PDO::FETCH_ASSOC)) {
        if(!array_key_exists($tag['value'], $unique_tags)) {
            $unique_tags[$tag['value']] = $tag['id'];
        }
        else {  // duplicate!
            $duplicate_tags[$tag['id']] = $unique_tags[$tag['value']];
        }
    }

    echo("done.\n");
    if(empty($duplicate_tags))
        continue;
    echo("\t".sizeof($duplicate_tags)." duplicates found!\n");
    $duplicate_count += sizeof($duplicate_tags);

    // repair duplicates
    if($repair) {
        $dbo->beginTransaction();
        try {
            foreach($duplicate_tags as $old => $new) {
                $stmt_updateTS->execute(array(':oldid' => $old,
                                              ':newid' => $new));
                $affected_rows += $stmt_updateTS->rowCount();
                $stmt_deleteTag->execute(array(':tagid' => $old));
            }
        }
        catch(PDOException $ex) {
            echo("An exception occured:\n");
            echo($ex->getMessage());
            echo("\nChanges for this tagset are rolled back.\n");
            $dbo->rollBack();
            exit(1);
        }
        $dbo->commit();
        echo("\tRepaired $affected_rows entries in 'tag_suggestion'.\n");
    }
}

// final output
if($duplicate_count) {
    if(!$repair)
        echo("\nCall the script with argument 'repair' to perform reparation.\n");
}
else {
    echo("\nEverything okay.\n");
}

?>
