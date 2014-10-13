<?php
$CORA_DIR = dirname(__FILE__) . "/../";
require_once( $CORA_DIR . "lib/globals.php" );
require_once( $CORA_DIR . "lib/connect.php" );
require_once( $CORA_DIR . "lib/exporter.php" );
$dbi = new DBInterface(DB_SERVER, DB_USER, DB_PASSWORD, MAIN_DB);
$exp = new Exporter($dbi);

$options = getopt("f:pnxh");
if (array_key_exists("h", $options)
    || !array_key_exists("f", $options)) {
?>

Export a file from the CorA database.

    Usage:
    <?php echo $argv[0]; ?> -f <id> {-p|-n|-x}

    <id> is the ID of the file to export, while the other flags
    signal the export format:

      -p   Exports POS tagging format
      -n   Exports normalization format
      -x   Exports CorA XML

    You can specify the -f parameter multiple times to export
    several files at once.  (There is no indication where each
                             file begins and ends in this case.)

<?php
    exit;
}

// files to process
if(is_array($options["f"])) {
    $files = $options["f"];
} else {
    $files = array($options["f"]);
}

// output format
if(array_key_exists("p", $options)) {
    $format = ExportType::Tagging;
} else if (array_key_exists("n", $options)) {
    $format = ExportType::Normalization;
} else if (array_key_exists("x", $options)) {
    $format = ExportType::CoraXML;
} else {
?>
No export format given!
<?php
    exit;
}

// go
foreach($files as $k => $file) {
    $exp->export($file, $format, STDOUT);
}

?>