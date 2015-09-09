<?php
ini_set('memory_limit', '-1');
require_once "cloning_tools.php";
$cloning = new CloningTools();

$options = getopt("i:n:fuh");
if (array_key_exists("h", $options)
    || !array_key_exists("i", $options)) {
?>

Clone a project in the CorA database.

    Usage:
    <?php echo $argv[0]; ?> -i <pid> [-n <name>] [-f] [-u]

    <pid> is the ID of the project to clone.  By default, this will only create
    a new, empty project with the same settings as the cloned one.

    The name of the new project can be given with <name>; otherwise, it will
    be copied from the cloned project, with " (clone)" appended to it.

    When -f is given, all files within the project will also be cloned into
    the new project.

    When -u is given, all users with access to the old project will also be
    given access to the new project.

<?php
  exit;
}

$exitcode = 0;
$pid = $options["i"];
$name = (isset($options["n"]) ? $options["n"] : null);

try {
    $cloning->cloneProject($pid,
                           $name,
                           $with_users=isset($options['u']),
                           $with_files=isset($options['f'])
                           );
}
catch (Exception $ex) {
    print "\n*** ERROR: An exception occured:\n";
    print $ex->getMessage();
    print "\n";
    $exitcode = 1;
}

exit($exitcode);

?>
