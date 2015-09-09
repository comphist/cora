<?php
ini_set('memory_limit', '-1');
require_once "cloning_tools.php";
$cloning = new CloningTools();

$options = getopt("i:p:sh");
if (array_key_exists("h", $options)
    || !array_key_exists("i", $options)) {
?>

Clone a file in the CorA database.

    Usage:
    <?php echo $argv[0]; ?> -i <fid> [-p <pid>]

    <fid> is the ID of the file to clone.  By default, the clone is
    created in the same project and with the same metadata, except
    that it has " (clone)" appended to its name.

    When '-p <pid>' is given, the clone is created in the project with
    this ID instead, and the name is not changed.

    You can specify the -i parameter multiple times to clone
    several files at once.

<?php
  exit;
}

$exitcode = 0;
$files = (is_array($options["i"]) ? $options["i"] : array($options["i"]));
$pid = (isset($options["p"]) ? $options["p"] : null);

foreach ($files as $k => $file) {
    try {
        $cloning->cloneFile($file, $pid);
    }
    catch (Exception $ex) {
        print "\n*** ERROR: An exception occured:\n";
        print $ex->getMessage();
        print "\n";
        $exitcode = 1;
    }
}

exit($exitcode);

?>
