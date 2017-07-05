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
$CORA_DIR = include 'cora_config_webdir.php';
require_once "{$CORA_DIR}/lib/cfg.php";
require_once "{$CORA_DIR}/lib/connect.php";
$dbinfo = Cfg::get('dbinfo');
$dbi = new DBInterface($dbinfo);
$dbo = new PDO('mysql:host='.$dbinfo['HOST']
               .';dbname='.$dbinfo['DBNAME']
               .';charset=utf8',
               $dbinfo['USER'],
               $dbinfo['PASSWORD']);

$options = getopt("m:rh");
if (array_key_exists("h", $options)
    || (!array_key_exists("m", $options) && !array_key_exists("r", $options))) {
?>

Tests the speed of retrieving lemma suggestions.

    Usage:
    <?php echo $argv[0]; ?> {-m <id> | -r}

    <id> is the ID of the mod for which suggestions should be retrieved.

    If -r is set, modern IDs and query strings are selected at random.

<?php
    exit;
}

// mods to process
if(array_key_exists("m", $options)) {
    if(is_array($options["m"])) {
        $moderns = $options["m"];
    } else {
        $moderns = array($options["m"]);
    }
}

// randomize?
if(array_key_exists("r", $options)) {
    $stmt_modIDs = $dbo->query("SELECT `id` FROM modern ORDER BY RAND() LIMIT 10");
    $moderns = $stmt_modIDs->fetchAll(PDO::FETCH_COLUMN);
}

$qlist = array("und", "u", "endracht", "en", "negen", "ab", "i", "do");
$qs = "SELECT token.text_id, modern.ascii FROM token"
    . "  LEFT JOIN modern ON modern.tok_id=token.id"
    . "  WHERE modern.id=:modid";
$stmt = $dbo->prepare($qs);

$times_linenum = array();
$times_ascii = array();
$times_query = array();

foreach ($moderns as $modid) {
    $stmt->execute(array(':modid' => $modid));
    $mod = $stmt->fetch(PDO::FETCH_ASSOC);
    print "[{$mod['text_id']}: {$modid}] {$mod['ascii']}\n";

    $time_start = microtime(true);
    $sugg = $dbi->getLemmaSuggestionFromLineNumber($modid);
    $time_end = microtime(true);
    $time_delta = $time_end - $time_start;
    $format = '  FromLineNumber:     %d suggestion(s) in %.2f s';
    printf($format, count($sugg), $time_delta);
    print "\n";
    $times_linenum[] = $time_delta;

    $time_start = microtime(true);
    $sugg = $dbi->getLemmaSuggestionFromIdenticalAscii($modid);
    $time_end = microtime(true);
    $time_delta = $time_end - $time_start;
    $format = '  FromIdenticalAscii: %d suggestion(s) in %.2f s';
    printf($format, count($sugg), $time_delta);
    print "\n";
    $times_ascii[] = $time_delta;

    foreach ($qlist as $q) {
        $time_start = microtime(true);
        $sugg = $dbi->getLemmaSuggestionFromQueryString($q, $mod['text_id'], 10);
        $time_end = microtime(true);
        $time_delta = $time_end - $time_start;
        $format = '  FromQueryString:    %d suggestion(s) in %.2f s';
        printf($format, count($sugg), $time_delta);
        print "\n";
        $times_query[] = $time_delta;
    }
}

print "\n                           low      avg     high\n";
$format = '  FromLineNumber:      %7.2f  %7.2f  %7.2f';
printf($format,
       min($times_linenum),
       (array_sum($times_linenum) / count($times_linenum)),
       max($times_linenum));
print "\n";
$format = '  FromIdenticalAscii:  %7.2f  %7.2f  %7.2f';
printf($format,
       min($times_ascii),
       (array_sum($times_ascii) / count($times_ascii)),
       max($times_ascii));
print "\n";
$format = '  FromQueryString:     %7.2f  %7.2f  %7.2f';
printf($format,
       min($times_query),
       (array_sum($times_query) / count($times_query)),
       max($times_query));
print "\n";

?>
