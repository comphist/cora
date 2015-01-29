<?php

/** @file DualRFTaggerAnnotator.php
 * Combination of two RFTaggers.
 *
 * @author Marcel Bollmann
 * @date March 2014
 */

require_once( "AutomaticAnnotator.php" );
require_once( "RFTaggerAnnotator.php" );

class DualRFTaggerAnnotator extends AutomaticAnnotator {
    private $fixedRFT;
    private $variableRFT;
    private $vocabulary = array();
    private $threshold  = 1;
    private $use_layer = "ascii";

    public function __construct($prfx, $opts) {
        parent::__construct($prfx, $opts);
        $this->fixedRFT = new RFTaggerAnnotator($prfx, $opts);
        $this->variableRFT = new RFTaggerAnnotator($prfx, $opts);
        $this->variableRFT->setParameterFile(null);

        if(!array_key_exists("vocab", $this->options)) {
            $this->options["vocab"] = $this->prefix . "RFTagger.vocab";
        }
        if(array_key_exists("threshold", $this->options)) {
            $this->threshold = $this->options["threshold"];
        }
        if(array_key_exists("use_layer", $this->options)) {
            $this->use_layer = $this->options["use_layer"];
        }
    }

    public function getThreshold() { return $this->threshold; }
    public function setThreshold($t) { $this->threshold = $t; }

    /** Load a vocabulary file.
     *
     * Will only actually read the file if vocabulary is currently
     * empty or $force parameter is set to true.
     */
    private function loadVocabulary($force=false) {
        if(!empty($this->vocabulary) && !$force) return;
        $this->vocabulary = array();
        if(!is_file($this->options["vocab"])
           || !is_readable($this->options["vocab"])) {
            return;
        }
        $vocabfile = explode("\n", file_get_contents($this->options["vocab"]));
        foreach($vocabfile as $vocabline) {
            $line = explode("\t", $vocabline);
            $this->vocabulary[$line[0]] = $line[1];
        }
    }

    /** Constructs the internal vocabulary out of a list of tokens.
     */
    private function makeVocabulary($tokens) {
        foreach($tokens as $tok) {
            if(!array_key_exists($this->use_layer, $tok)) continue;
            if(!array_key_exists($tok[$this->use_layer], $this->vocabulary)) {
                $this->vocabulary[$tok[$this->use_layer]] = 1;
            }
            else {
                $this->vocabulary[$tok[$this->use_layer]] += 1;
            }
        }
    }

    /** Saves the vocabulary to a file.
     */
    private function saveVocabulary() {
        $filename = $this->options["vocab"];
        $handle = fopen($filename, "w");
        foreach($this->vocabulary as $ascii => $count) {
            fwrite($handle, $ascii."\t".strval($count)."\n");
        }
        fclose($handle);
    }

    /** Chooses between the output of the two tagger configurations.
     *
     * The output from the variable RFTagger will be chosen iff the
     * fixed RFTagger assigned the POS tag "?", or the variable
     * RFTagger didn't assign the POS tag "?" and the token is in the
     * vocabulary with a frequency higher than {$this->threshold}.
     *
     * @param array $fixline Annotated token returned by fixed RFTagger
     * @param array $varline Annotated token returned by variable RFTagger
     *
     * @return Either $fixline or $varline
     */
    private function chooseTag($fixline, $varline) {
        if($fixline["id"] != $varline["id"]) {
            throw new Exception("Fehler beim Zusammenführen der Tagger-Outputs:"
                                ."Token sind nicht identisch.");
        }
        if(isset($fixline["anno_pos"]) && $fixline["anno_pos"] == "?") {
            return $varline;
        }
        if(isset($varline["anno_pos"]) && $varline["anno_pos"] == "?") {
            return $fixline;
        }
        if(array_key_exists($fixline[$this->use_layer], $this->vocabulary)
           && $this->vocabulary[$fixline[$this->use_layer]] >= $this->threshold) {
            return $varline;
        }
        return $fixline;
    }

    public function annotate($tokens) {
        $fixed = $this->fixedRFT->annotate($tokens);
        $variable = $this->variableRFT->annotate($tokens);
        $this->loadVocabulary();

        return array_map(array($this, 'chooseTag'), $fixed, $variable);
    }

    public function startTrain() {
        $this->variableRFT->startTrain();
        $this->vocabulary = array();
    }

    public function bufferTrain($tokens) {
        $this->variableRFT->bufferTrain($tokens);
        $this->makeVocabulary($tokens);
    }

    public function performTrain() {
        $this->variableRFT->performTrain();
        $this->saveVocabulary();
    }
}

?>