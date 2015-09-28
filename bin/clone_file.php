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
 */ ?>
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
