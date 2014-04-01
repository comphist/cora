<?php

/** @file DualRFTaggerAnnotator.php
 * Combination of two RFTaggers.
 *
 * @author Marcel Bollmann
 * @date March 2014
 */

require_once( "AutomaticAnnotator.php" );

class Lemmatizer extends AutomaticAnnotator {
    private $tmpfiles = array();
    private $dictionary = array();
    private $lowercase_all = false;
    private $usepos = true;

    // lemmatizer return value if not lemma could be found:
    private $unknown_lemma = "<unknown>";    

    public function __construct($prfx, $opts) {
        parent::__construct($prfx, $opts);
        if(!array_key_exists("par", $this->options)) {
            $this->options["par"] = $this->prefix . "Lemmatizer.par";
        }
        if(array_key_exists("use_pos", $this->options)) {
            $this->usepos = ($this->options["use_pos"] == 1) ? true : false;
        }
        if(array_key_exists("lowercase_all", $this->options)) {
            $this->lowercase_all = (bool) $this->options["lowercase_all"];
        }
    }

    public function __destruct() {
        foreach($this->tmpfiles as $tmpfile) {
            unlink($tmpfile);
        }
    }

    /** Writes an input file for the lemmatization script.
     *
     * @param array $tokens Tokens to write
     *
     * @return Name of the newly created file
     */
    private function writeLemmatizerInput($tokens) {
        $filename = tempnam(sys_get_temp_dir(), "cora_lem");
        $this->tmpfiles[] = $filename;
        $handle = fopen($filename, "w");
        foreach($tokens as $tok) {
            fwrite($handle, $tok['ascii']);
            fwrite($handle, "\t".$tok['tags']['POS']);
            fwrite($handle, "\n");
        }
        fclose($handle);
        return $filename;
    }

    private function lowercaseAscii($tok) {
        if(array_key_exists('ascii', $tok)) {
            $tok['ascii'] = mb_strtolower($tok['ascii'], 'UTF-8');
        }
        return $tok;
    }

    /** Converts lemmatizer output to an array for saving it back to CorA.
     *
     * @param array $mod The original input token
     * @param string $line Output line from lemmatizer
     *
     * @return An array element suitable for DBInterface::saveLines()
     */
    private function makeAnnotationArray($mod, $line) {
        if($line == $this->unknown_lemma) {
            return array();
        }
        return array("id" => $mod['id'],
                     "ascii" => $mod['ascii'],
                     "anno_lemma" => $line);
    }

    public function annotate($tokens) {
        if($this->lowercase_all) {
            $tokens = array_map(array($this, 'lowercaseAscii'), $tokens);
        }

        $tmpfname = $this->writeLemmatizerInput($tokens);

        // call lemmatizer
        $output = array();
        $retval = 0;
        $cmd = implode(" ", array($this->options["bin"],
                                  $this->options["par"],
                                  $tmpfname));
        exec($cmd, $output, $retval);
        if($retval) {
            throw new Exception("RFTagger gab den Status-Code {$retval} zurück.".
                                "\nAufruf war: {$cmd}");
        }

        return array_map(array($this, 'makeAnnotationArray'), $tokens, $output);
    }

    public function train($tokens) {
        //$this->saveDictionary();
    }

}

?>