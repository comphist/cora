<?php
$CORA_DIR = dirname(__FILE__) . "/../";
$DEVNULL = fopen("/dev/null", "a");
require_once( $CORA_DIR . "lib/cfg.php" );
require_once( $CORA_DIR . "lib/connect.php" );
require_once( $CORA_DIR . "lib/exporter.php" );
$dbi = new DBInterface(Cfg::get('dbinfo'));
$exp = new Exporter($dbi);

memprof_enable();
//$data = $dbi->openFile(149);
//$exp->export(149, ExportType::CoraXML, array(), $DEVNULL);
$data = $dbi->getAllModerns(149);
memprof_dump_pprof(STDOUT);

fwrite(STDERR, "Memory usage:      ".memory_get_usage()."\n");
fwrite(STDERR, "Memory peak usage: ".memory_get_peak_usage()."\n");

?>
