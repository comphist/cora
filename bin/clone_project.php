#!/usr/bin/env php
<?php 
/*
 * Copyright (C) 2015-2017 Marcel Bollmann <bollmann@linguistics.rub.de>
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
 */ ?>
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
