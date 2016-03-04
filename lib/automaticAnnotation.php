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
*/
?>
<?php
/** @file automaticAnnotation.php
 * Features for automatic, dynamic training and annotation of texts
 * within CorA.
 *
 * @author Marcel Bollmann
 * @date June 2013
 */

//require_once( "documentModel.php" );
require_once ("cfg.php");
require_once ("exporter.php");

/** Wrapper for all automatic annotators.
 *
 * This class instantiates and operates on AutomaticAnnotator objects,
 * setting project-specific objects for them and checking their return values.
 */
class AutomaticAnnotationWrapper {
    protected $db; /**< A DBInterface object. */
    protected $exp; /**< An Exporter object. */
    protected $taggerid;
    protected $projectid;
    protected $tagger;
    protected $tagset_ids; /**< Array of associated tagset IDs. */
    protected $tagset_cls; /**< Array of associated tagset classes. */
    protected $tagsets; /**< Array of associated tagset metadata. */
    protected $trainable;
    protected $train_single_file = false; /**< Hack to make training work on
     current file only. */
    protected $paramdir = null;

    /** Construct a new AutomaticAnnotator object.
     *
     * Annotator objects are always specific to a combination of
     * annotator ("tagger") and CorA project.
     */
    function __construct($db, $exp, $taggerid, $projectid) {
        $this->db = $db;
        $this->exp = $exp;
        if (!isset($taggerid) || empty($taggerid)) {
            throw new Exception("Tagger ID cannot be empty."); //$LOCALE
        }
        $this->taggerid = $taggerid;
        if (!isset($projectid) || empty($projectid)) {
            throw new Exception("Project ID cannot be empty."); //$LOCALE
        }
        $this->projectid = $projectid;
        $this->paramdir = Cfg::get('external_param_dir');
        $this->instantiateTagger();
    }

    /** Instantiate the tagger object.
     */
    private function makeTaggerClass($class_name) {
        $class_file = __DIR__ . "/annotation/{$class_name}.php";
        if (!file_exists($class_file)) {
            throw new Exception("Tagger interface not found: {$class_name}"); //$LOCALE
        }
        require_once $class_file;
        $options = $this->db->getTaggerOptions($this->taggerid);
        if (array_key_exists('train_single_file', $options)) {
            $this->train_single_file = ($options['train_single_file'] == 1);
        }
        return new $class_name($this->getPrefix(), $options);
    }

    /** Fetch information about the tagger and its associated tagsets,
     *  and instantiate the respective tagger class.
     */
    private function instantiateTagger() {
        $tagger = $this->db->getTaggerList();
        if (!$tagger || empty($tagger) || !array_key_exists($this->taggerid, $tagger)) {
            throw new Exception("Illegal tagger ID: {$this->taggerid}");
        }
        // instantiate class object
        $this->trainable = $tagger[$this->taggerid]['trainable'];
        $class_name = $tagger[$this->taggerid]['class_name'];
        $this->tagger = $this->makeTaggerClass($class_name);
        // get info about associated tagsets
        $this->tagset_ids = $tagger[$this->taggerid]['tagsets'];
        $this->tagsets = $this->db->getTagsetMetadata($this->tagset_ids);
        $this->tagset_cls = array();
        foreach ($this->tagsets as $tagset) {
            $this->tagset_cls[] = $tagset['class'];
        }
    }

    /** Get the filename prefix for parameter files.
     */
    protected function getPrefix() {
        return $this->paramdir . "/" . $this->projectid . "-" . $this->taggerid . "-";
    }

    protected function containsOnlyValidAnnotations($anno) {
        foreach ($anno as $k => $v) {
            if ((substr($k, 0, 5) == "anno_") && (!in_array(substr($k, 5), $this->tagset_cls))) {
                return false;
            }
        }
        return true;
    }

    /** Updates the database with new annotations.
     *
     * @param string $fileid ID of the file to be updated
     * @param array $lines Output from the external tagger, expected to
     *                     have one mod per line
     * @param array $moderns Array of all mods as they are currently
     *                       stored in the database
     */
    protected function updateAnnotation($fileid, $tokens, $annotated) {
        $is_not_verified = function ($tok) {
            return !$tok['verified'];
        };
        $extract_id = function ($tok) {
            return $tok['id'];
        };
        $valid_id_list = array();
        foreach (array_filter($tokens, $is_not_verified) as $ftok) {
            $valid_id_list[$ftok['id']] = true;
        }
        $is_valid_annotation = function ($elem) use (&$valid_id_list) {
            return (!empty($elem) && isset($valid_id_list[$elem['id']])
                    && $this->containsOnlyValidAnnotations($elem));
        };
        $lines_to_save = array_filter($annotated, $is_valid_annotation);
        // warnings are ignored here ...
        $this->db->performSaveLines($fileid, $lines_to_save, null);
    }

    public function annotate($fileid) {
        /* TODO: verify that file belongs to project && has the necessary
        tagset links?
        */
        $tokens = $this->db->getAllModerns_simple($fileid, true);
        // ^-- seeing existing annotations is required for some Annotators,
        //     so we can't really ever set the second parameter to false...
        $annotated = $this->tagger->annotate($tokens);
        $this->updateAnnotation($fileid, $tokens, $annotated);
    }

    public function train($fileid) {
        if (!$this->trainable) return;
        if ($this->train_single_file) {
            $all_files = array(array('id' => $fileid));
        } else {
            $all_files = $this->db->getFilesForProject($this->projectid);
        }
        $this->tagger->startTrain();
        foreach ($all_files as $f) {
            $tokens = $this->db->getAllModerns_simple($f['id'], true);
            $this->tagger->bufferTrain($tokens);
        }
        $this->tagger->performTrain();
    }
}
?>
