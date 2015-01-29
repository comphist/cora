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

   /** Constructs a new automatic annotator.
    *
    * @param string $prfx  A filename prefix specific to this annotator
    *                      and the current project.  Can be used when the
    *                      annotator should be trainable from within CorA,
    *                      in which case it can use this prefix to build
    *                      filenames for storing its trained
    *                      parametrization (e.g., $prfx."paramfile.txt").
    * @param array $opts   An associative array containing tagger-specific
    *                      options that are set in the database.  This can
    *                      be arbitrary key/value pairs.
    */
    public function __construct($prfx, $opts) {
        $this->prefix  = $prfx;
        $this->options = $opts;
    }

    /** Annotate a set of tokens.
     *
     * @param array $tokens  An array of tokens, which are given as
     *                       associative arrays of the form:
     *                       Array
     *                       (
     *                           [id] => 995087
     *                           [ascii] => han
     *                           [tags] => Array
     *                               (
     *                                   [norm] => haben
     *                                   [pos] => VVINF
     *                                   ...
     *                               )
     *                           [verified] => 0
     *                       )
     *                       When annotating a file within CorA, this function
     *                       is always given **all** tokens in the file.
     *                       However, the wrapper function makes sure that
     *                       only tokens with [verified] => 0 can ever receive
     *                       new annotations.
     *
     * @return An array of tokens, where each token is an array of the form:
     *         Array
     *         (
     *             [id] => 995087
     *             [ascii] => han
     *             [anno_pos] => VVFIN.3.Sg.Past.Ind
     *         )
     *         Entries of the form "anno_<...>" contain the annotated
     *         elements that will be saved back to the database.
     */
    public function annotate($tokens) {
        return array();
        // wrapper will make sure that only valid IDs are considered, and
        // tokens with [verified] => 1 will never be overwritten
    }

    /** Start training process.
     *
     * Called at the beginning of a training process.
     *
     * @return Nothing.
     */
    public function startTrain() {
    }

    /** Process a batch of tokens for training.
     *
     * Preprocess the given array of tokens and store the information required
     * for training in an intermediate storage.
     *
     * @param array $tokens  An array of tokens, in the same form as the
     *                       parameter of the @c annotate() function.
     *                       When retraining from within CorA, this function
     *                       is called once for each file within the
     *                       current project.
     *
     * @return Nothing.
     */
    public function bufferTrain($tokens) {
    }

    /** Perform the actual training process.
     *
     * Use the intermediate data created by @c bufferTrain() to perform the
     * actual training process.
     *
     * @return Nothing.
     */
    public function performTrain() {
    }
}

?>
