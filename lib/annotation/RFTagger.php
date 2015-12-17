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

/** @file RFTagger.php
 * Wrapper class for the RFTagger.
 *
 * @author Marcel Bollmann
 * @date March 2014
 */

require_once( "AutomaticAnnotator.php" );

/** Annotates POS tagsets by wrapping the RFTagger.
 */
class RFTagger extends AutomaticAnnotator {
    private $tmpfiles = array();
    private $minimum_span_size = 5;
    private $lowercase_all = false;
    private $use_layer = "ascii";
    private $train_lines = null;
    private $train_lex_lines = null;

    public function __construct($prfx, $opts) {
        parent::__construct($prfx, $opts);
        if(!array_key_exists("par", $this->options)) {
            $this->options["par"] = $this->prefix . "RFTagger.par";
        }
        if(!array_key_exists("wc", $this->options)) {
            $this->options["wc"] = $this->prefix . "RFTagger.wc";
        }
        if(!array_key_exists("flags", $this->options)) {
            $this->options["flags"] = "-c 2 -q";
        }
        if(array_key_exists("use_layer", $this->options)) {
            $this->use_layer = $this->options["use_layer"];
        }
        if(array_key_exists("use_norm", $this->options)) {  // backwards-compatibility
            if((bool) $this->options["use_norm"]) {
                $this->use_layer = "norm";
            }
        }
        if(array_key_exists("lowercase_all", $this->options)) {
            $this->lowercase_all = (bool) $this->options["lowercase_all"];
        }
        if(array_key_exists("minimum_span_size", $this->options)) {
            $this->minimum_span_size = $this->options["minimum_span_size"];
        }
    }

    public function __destruct() {
        foreach($this->tmpfiles as $tmpfile) {
            unlink($tmpfile);
        }
    }

    public function setParameterFile($filename) {
        if(empty($filename)) { // revert to default
            $this->options["par"] = $this->prefix . "RFTagger.par";
        }
        else {
            $this->options["par"] = $filename;
        }
    }

    /** Writes an input file for RFTagger.
     *
     * @param array $tokens Tokens to write
     * @param boolean $training If true, include POS tags
     *
     * @return Name of the newly created file
     */
    private function writeTaggerInput($tokens, $training=false) {
        $filename = tempnam(sys_get_temp_dir(), "cora_rft");
        $this->tmpfiles[] = $filename;
        $handle = fopen($filename, "w");
        foreach($tokens as $tok) {
            fwrite($handle, $tok[$this->use_layer]);
            if($training) {
                fwrite($handle, "\t".$tok['tags']['pos']);
            }
            fwrite($handle, "\n");
        }
        fclose($handle);
        return $filename;
    }

    /** Writes an input file for training RFTagger.
     *
     * @param array $lines Lines to write
     *
     * @return Name of the newly created file
     */
    private function writeTrainInput($lines) {
        $filename = tempnam(sys_get_temp_dir(), "cora_rft");
        $this->tmpfiles[] = $filename;
        $handle = fopen($filename, "w");
        foreach($lines as $line) {
            fwrite($handle, $line);
        }
        fclose($handle);
        return $filename;
    }

    /** Converts RFTagger output to an array for saving it back to CorA.
     *
     * @param array $mod The original input token
     * @param string $line Output line from RFTagger
     *
     * @return An array element suitable for DBInterface::saveLines()
     */
    private function makeAnnotationArray($mod, $line) {
        $line = explode("\t", $line);
        if(empty($line) || empty($mod) || count($line) != 2) {
            return array();
        }
        if($mod[$this->use_layer] != $line[0]) {
            throw new Exception("Token mismatch: ".$mod[$this->use_layer]." != ".$line[0]);  //$LOCALE
        }
        return array("id" => $mod['id'],
                     $this->use_layer => $mod[$this->use_layer],
                     "anno_pos" => $line[1]);
    }


    /** Filter a list of tokens to be used for training.
     *
     * Only non-empty tokens marked as 'verified' and having a
     * non-empty POS annotation will be used for training.
     * Furthermore, as POS tagging is n-gram-based, only spans
     * containing at least {$this->minimum_span_size} of such tokens
     * are considered.  Tokens discarded via this span criterion can
     * still be used as a lexical resource and are returned as a
     * separate array.
     *
     * @param array $tokens An array of tokens
     *
     * @return 1) A filtered sequence of tokens to be used for
     * training; and 2) an array of tokens to be used as an additional
     * lexicon.
     */
    private function filterForTraining($tokens) {
        $filtered = array();   /**< sequence of tokens for training */
        $additional = array(); /**< tokens discarded for training due to
                                    context length restrictions, but still
                                    usable for lexicon comparison */
        $currentlist = array();
        $currentspan = 0;
        foreach($tokens as $tok) {
            if($tok['verified'] && isset($tok['tags']['pos'])
               && !empty($tok[$this->use_layer])) {
                $currentspan++;
                $currentlist[] = $tok;
            }
            else {
                if($currentspan == 0) continue;
                if($currentspan >= $this->minimum_span_size) {
                    $filtered = array_merge($filtered, $currentlist);
                }
                else {
                    $additional = array_merge($additional, $currentlist);
                }
                $currentlist = array();
                $currentspan = 0;
            }
        }
        return array($filtered, $additional);
    }

    private function mapNormToAscii($tok) {
        if(isset($tok['tags']['norm_broad']) && !empty($tok['tags']['norm_broad'])) {
            $tok['norm'] = $tok['tags']['norm_broad'];
        }
        else if(isset($tok['tags']['norm']) && !empty($tok['tags']['norm'])) {
            $tok['norm'] = $tok['tags']['norm'];
        }
        return $tok;
    }

    private function lowercaseAscii($tok) {
        if(isset($tok[$this->use_layer])) {
            $tok[$this->use_layer] = mb_strtolower($tok[$this->use_layer], 'UTF-8');
        }
        return $tok;
    }

    private function filterEmpty($tok) {
        return !empty($tok[$this->use_layer]);
    }

    protected function preprocessTokens($tokens) {
        if($this->use_layer == "norm") {
            $tokens = array_map(array($this, 'mapNormToAscii'), $tokens);
        }
        if($this->lowercase_all) {
            $tokens = array_map(array($this, 'lowercaseAscii'), $tokens);
        }
        return $tokens;
    }

    public function annotate($tokens) {
        $tokens = array_filter($this->preprocessTokens($tokens), array($this, 'filterEmpty'));

        // write tokens to temporary file
        $tmpfname = $this->writeTaggerInput($tokens, false);

        // call RFTagger
        $output = array();
        $retval = 0;
        $cmd = implode(" ", array($this->options["annotate"],
                                  $this->options["par"],
                                  $tmpfname,
                                  "2>/dev/null"));
        exec($cmd, $output, $retval);
        if($retval) {
            error_log("CorA: RFTaggerAnnotator.php: RFTagger returned status code {$retval}; call was: {$cmd}"); 
            throw new Exception("RFTagger gab den Status-Code {$retval} zurück.");   //$LOCALE
                                // "\nAufruf war: {$cmd}"
        }

        // process RFTagger output & return
        return array_map(array($this, 'makeAnnotationArray'), $tokens, $output);
    }

    public function startTrain() {
        $this->train_lines = array();
        $this->train_lex_lines = array();
    }

    public function bufferTrain($tokens) {
        list($tokens, $lextokens)
            = $this->filterForTraining($this->preprocessTokens($tokens));
        if(empty($tokens)) return;

        foreach($tokens as $tok) {
            $this->train_lines[]
                = $tok[$this->use_layer] . "\t" . $tok['tags']['pos'] . "\n";
        }
        foreach($lextokens as $tok) {
            $this->train_lex_lines[]
                = $tok[$this->use_layer] . "\t" . $tok['tags']['pos'] . "\n";
        }
    }

    public function performTrain() {
        if(empty($this->train_lines)) return;
        $tmpfname = $this->writeTrainInput($this->train_lines);
        $flags = $this->options["flags"];
        if(!empty($this->train_lex_lines)) {
            $tmplname = $this->writeTrainInput($this->train_lex_lines);
            $flags = $flags . " -l " . $tmplname;
        }

        // call RFTagger
        $output = array();
        $retval = 0;
        $cmd = implode(" ", array($this->options["train"],
                                  $tmpfname,
                                  $this->options["wc"],
                                  $this->options["par"],
                                  $flags));
        exec($cmd, $output, $retval);
        if($retval) {
            throw new Exception("RFTagger gab den Status-Code {$retval} zurück.");  //$LOCALE
            // "\nAufruf war: {$cmd}");
        }
        $this->train_lines = null;
        $this->train_lex_lines = null;
    }
}

?>
