<?php

/** @file RFTaggerAnnotator.php
 * Wrapper class for the RFTagger.
 *
 * @author Marcel Bollmann
 * @date March 2014
 */

require_once( "AutomaticAnnotator.php" );

class RFTaggerAnnotator extends AutomaticAnnotator {
    private $tmpfiles = array();
    private $minimum_span_size = 5;
    private $lowercase_all = false;
    private $use_norm = false;

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
        if(array_key_exists("use_norm", $this->options)) {
            $this->use_norm = (bool) $this->options["use_norm"];
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
            fwrite($handle, $tok['ascii']);
            if($training) {
                fwrite($handle, "\t".$tok['tags']['pos']);
            }
            fwrite($handle, "\n");
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
        if($mod['ascii'] != $line[0]) {
            throw new Exception("Token mismatch: ".$mod['ascii']." != ".$line[0]);
        }
        return array("id" => $mod['id'],
                     "ascii" => $mod['ascii'],
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
               && !empty($tok['ascii'])) {
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
            $tok['ascii'] = $tok['tags']['norm_broad'];
        }
        else if(isset($tok['tags']['norm']) && !empty($tok['tags']['norm'])) {
            $tok['ascii'] = $tok['tags']['norm'];
        }
        return $tok;
    }

    private function lowercaseAscii($tok) {
        if(isset($tok['ascii'])) {
            $tok['ascii'] = mb_strtolower($tok['ascii'], 'UTF-8');
        }
        return $tok;
    }

    public function annotate($tokens) {
        if($this->use_norm) {
            $tokens = array_map(array($this, 'mapNormToAscii'), $tokens);
        }
        if($this->lowercase_all) {
            $tokens = array_map(array($this, 'lowercaseAscii'), $tokens);
        }

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
            throw new Exception("RFTagger gab den Status-Code {$retval} zurück.");
                                // "\nAufruf war: {$cmd}"
        }

        // process RFTagger output & return
        return array_map(array($this, 'makeAnnotationArray'), $tokens, $output);
    }

    public function train($tokens) {
        if($this->use_norm) {
            $tokens = array_map(array($this, 'mapNormToAscii'), $tokens);
        }
        if($this->lowercase_all) {
            $tokens = array_map(array($this, 'lowercaseAscii'), $tokens);
        }

        list($tokens, $lextokens) = $this->filterForTraining($tokens);
        if(empty($tokens)) return array();

        // write tokens to temporary file
        $tmpfname = $this->writeTaggerInput($tokens, true);
        $flags = $this->options["flags"];
        if(!empty($lextokens)) {
            $tmplname = $this->writeTaggerInput($lextokens, true);
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
            throw new Exception("RFTagger gab den Status-Code {$retval} zurück.");
            // "\nAufruf war: {$cmd}");
        }

        return $tokens;
    }

}

?>
