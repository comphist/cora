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
    
    private function writeTaggerInput($tokens, $training=false) {
        $filename = tempnam(sys_get_temp_dir(), "cora_rft");
        $this->tmpfiles[] = $filename;
        $handle = fopen($filename, "w");
        foreach($tokens as $tok) {
            fwrite($handle, $tok['ascii']);
            if($training) {
                fwrite($handle, "\t".$tok['tags']['POS']);
            }
            fwrite($handle, "\n");
        }
        fclose($handle);
        return $filename;
    }

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
                     "anno_POS" => $line[1]);
    }

    /** 
     */
    // $tokens should be what DBInterface::getAllModerns($fileid) returns
    public function annotate($tokens) {
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
            throw new Exception("RFTagger gab den Status-Code {$retval} zurück.".
                                "\nAufruf war: {$cmd}");
        }

        // process RFTagger output & return
        return array_map(array($this, 'makeAnnotationArray'), $tokens, $output);
    }

    private function filterForTraining($tokens) {
        $filtered = array();
        $currentlist = array();
        $currentspan = 0;
        foreach($tokens as $tok) {
            if($tok['verified'] && isset($tok['tags']['POS'])
               && !empty($tok['ascii'])) {
                $currentspan++;
                $currentlist[] = $tok;
            }
            else {
                if($currentspan == 0) continue;
                if($currentspan >= $this->minimum_span_size) {
                    $filtered = array_merge($filtered, $currentlist);
                }
                $currentlist = array();
                $currentspan = 0;
            }
        }
        return $filtered;
    }

    public function train($tokens) {
        $tokens = $this->filterForTraining($tokens);

        // write tokens to temporary file
        $tmpfname = $this->writeTaggerInput($tokens, true);

        // call RFTagger
        $output = array();
        $retval = 0;
        $cmd = implode(" ", array($this->options["train"],
                                  $tmpfname,
                                  $this->options["wc"],
                                  $this->options["par"],
                                  $this->options["flags"]));
        exec($cmd, $output, $retval);
        if($retval) {
            throw new Exception("RFTagger gab den Status-Code {$retval} zurück.\n".
                                "\nAufruf war: {$cmd}");
        }
    }

}

?>