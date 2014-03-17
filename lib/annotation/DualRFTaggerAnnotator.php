<?php

/** @file DualRFTaggerAnnotator.php
 * Combination of two RFTaggers.
 *
 * @author Marcel Bollmann
 * @date March 2014
 */

require_once("RFTaggerAnnotator.php");

class DualRFTaggerAnnotator extends AutomaticAnnotator {
    protected $name = "DualRFTagger";
    private $fixedRFT;
    private $variableRFT;
    private $vocabulary = array();
    private $threshold  = 1;

    public function __construct($prfx, $opts) {
        parent::__construct($prfx, $opts);
        $this->fixedRFT = new RFTaggerAnnotator($prfx, $opts);
        $this->variableRFT = new RFTaggerAnnotator($prfx, $opts);
        $this->variableRFT->setParameterFile(null);

        if(!array_key_exists("vocab", $this->options)) {
            $this->options["vocab"] = $this->prefix . "RFTagger.vocab";
        }
    }

    public function getThreshold() { return $this->threshold; }
    public function setThreshold($t) { $this->threshold = $t; }

    private function loadVocabulary() {
        $this->vocabulary = array();
        if(!is_file($this->options["vocab"])
           || !is_readable($this->options["vocab"])) {
            return;
        }
        $vocabfile = split("\n", file_get_contents($this->options["vocab"]));
        foreach($vocabfile as $vocabline) {
            $line = split("\t", $vocabline);
            $this->vocabulary[$line[0]] = $line[1];
        }
    }

    private function chooseTag($fixline, $varline) {
        if($fixline["id"] != $varline["id"]) {
            throw new Exception("Fehler beim Zusammenführen der Tagger-Outputs:"
                                ."Token sind nicht identisch.");
        }
        $fix = $fixline["anno_POS"];
        $var = $varline["anno_POS"];
        if($fix == "?") {
            return array("id" => $fixline["id"], "anno_POS" => $var);
        }
        if($var == "?") {
            return array("id" => $fixline["id"], "anno_POS" => $fix);
        }
        if(array_key_exists($fixline["ascii"], $this->vocabulary)
           && $this->vocabulary[$fixline["ascii"]] >= $this->threshold) {
            return array("id" => $fixline["id"], "anno_POS" => $var);
        }
        return array($fixline["id"], $fix);
    }

    /** 
     */
    // $tokens should be what DBInterface::getAllModerns($fileid) returns
    public function annotate($tokens) {
        $fixed = $this->fixedRFT->annotate($tokens);
        $variable = $this->variableRFT->annotate($tokens);
        $this->loadVocabulary();
        
        return array_map($this->chooseTag, $fixed, $variable);
    }

    public function train($tokens) {
        $this->variableRFT->train($tokens);
    }

}

?>