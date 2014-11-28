<?php
require_once 'cli_status_bar.php';

$CORA_DIR = dirname(__FILE__) . "/../";
require_once( $CORA_DIR . "lib/globals.php" );
$dbo = new PDO('mysql:host='.DB_SERVER.';dbname='.MAIN_DB.';charset=utf8',
	       DB_USER, DB_PASSWORD);
$dbo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
// require_once( $CORA_DIR . "lib/connect.php" );
// $dbi = new DBInterface(DB_SERVER, DB_USER, DB_PASSWORD, MAIN_DB);

$options = getopt("f:t:x");
if (array_key_exists("h", $options)
    || !array_key_exists("f", $options)
    || !array_key_exists("t", $options)) {
?>

Delete tags from a tagset.

    Usage:
    <?php echo $argv[0]; ?> -f <list> -t <id> [-x]

    Removes all tags in the file <list> from the tagset with
    the ID <id>.

    Tags that are still in use in any documents are marked as
    "needs_revision = 1", while tags that are not referenced
    anywhere are hard-deleted.  Tags given in <list> which
    do not occur in the tagset trigger a warning.

    Use option -x to actually perform these changes;
    otherwise, the process is only simulated.

<?php
    exit;
}

// set from options
$file_contents = file_get_contents($options["f"]);
if(!$file_contents) {
  exit("ERROR: Couldn't open file for reading.\n");
}
$taglist = array_map('trim', explode("\n", $file_contents));
$tid = $options["t"];
$execute = array_key_exists("x", $options);

// is the tagset valid?
$stmt = $dbo->prepare("SELECT * FROM `tagset` WHERE `id`=:id");
$stmt->bindValue(':id', $tid, PDO::PARAM_INT);
$stmt->execute();
$tagset = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$tagset) {
  exit("ERROR: No tagset with ID {$tid}.\n");
} else if ($tagset['set_type'] != "closed") {
  exit("ERROR: tagset {$tid}: {$tagset['name']} ({$tagset['class']}, {$tagset['set_type']}) is not a closed tagset.\n");
}
echo("Working on tagset {$tid}: {$tagset['name']} ({$tagset['class']}, {$tagset['set_type']})\n");
echo("\nAnalyzing tags...\n");

if($execute)
  $dbo->beginTransaction();

// prepare statements
$stmt_tid = $dbo->prepare("SELECT `id` FROM `tag` WHERE value=:value AND tagset_id=:id");
$stmt_cnt = $dbo->prepare("SELECT COUNT(*) FROM `tag_suggestion` WHERE `tag_id`=:id");
$stmt_rev = $dbo->prepare("UPDATE `tag` SET `needs_revision`=1 WHERE `id`=:id");
$stmt_del = $dbo->prepare("DELETE FROM `tag` WHERE `id`=:id");
$notfound = 0;
$revised = 0;
$deleted = 0;
$done = 0;
$total = count($taglist);

// loop
try {
  foreach($taglist as $tag) {
    $done++;
    show_status($done, $total);
    if(strlen($tag) < 1)
      continue;
    // find tag
    $stmt_tid->execute(array(':id' => $tid, ':value' => $tag));
    $tagid = $stmt_tid->fetchColumn();
    if(!$tagid) {
      $notfound++;
      continue;
    }
    // count references
    $stmt_cnt->execute(array(':id' => $tagid));
    $refs = $stmt_cnt->fetchColumn();
    // perform action
    if($refs > 0) {
      $revised++;
      if($execute)
        $stmt_rev->execute(array(':id' => $tagid));
    } else {
      $deleted++;
      if($execute)
        $stmt_del->execute(array(':id' => $tagid));
    }
  }
}
catch (PDOException $ex) {
  if($execute)
    $dbo->rollBack();
  echo("\nERROR: A database error occured:\n".$ex->getMessage()."\n");
  exit(1);
}

if($execute)
  $dbo->commit();

show_status($total, $total);

// statistics
echo("\n\n        Not found: {$notfound}\n");
echo(" Still referenced: {$revised}\n");
echo("          Deleted: {$deleted}\n\n");
if(!$execute) {
  echo("Call this script again with option -x to perform these changes.\n\n");
}

?>
