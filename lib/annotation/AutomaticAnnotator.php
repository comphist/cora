<?php

/** @file AutomaticAnnotator.php
 * Base class for an automatic annotator object.
 *
 * @author Marcel Bollmann
 * @date February 2014
 */

/** Base class for an automatic annotator.
 *
 * This class should be extended by any other class intending to
 * provide automatic annotation functionality.  It can be used as an
 * annotator on its own, and will effectively do nothing then.
 */
class AutomaticAnnotator {
    protected $options = array();
    protected $prefix = "";

    public function __construct($prfx, $opts) {
        $this->prefix  = $prfx;
        $this->options = $opts;
    }

    /*
    private function parseOptions($opts) {
        $pieces = explode(",", $opts);
        foreach($pieces as $piece) {
            $opt = explode("=", $piece, 2);
            if(count($opt) == 2) {
                $options[$opt[0]] = $opt[1];
            }
        }
    }
    */

    public function getName() {
        return $name;
    }

    /** 
     */
    // $tokens should be what DBInterface::getAllModerns($fileid) returns
    public function annotate($tokens) {
        return array();
        /* array(
                 array("id" => 42, "anno_pos" => "PPER", ...),
                 ...
                 );
        */
        // wrapper will filter this output based on "verified"-ness of
        // tokens, then send it straight to save lines?
        // --> but check class restrictions (may not change classes
        //     that are not linked to the tagger)
    }

    public function train($tokens) {
    }

}

?>