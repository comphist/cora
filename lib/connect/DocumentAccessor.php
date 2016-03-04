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
/** @file DocumentAccessor.php
 * Functions related to loading and saving of documents.
 *
 * @author Marcel Bollmann
 * @date December 2013
 */
require_once 'TagsetAccessor.php';

class DocumentAccessViolation extends Exception {
}

/** Handles document access that is potentially used by several
 * operations (e.g., reading and writing).
 *
 * More specialized classes will extend this class.
 */
class DocumentAccessor {
    protected $dbi; /**< DBInterface object to use for queries */
    protected $dbo; /**< PDO object to use for own queries */
    protected $fileid; /**< ID of the associated file */
    protected $tagsets = array();
    protected $warnings = array();

    // The following variables are currently only used by child classes,
    // and not initialized by default.  Write initializers for them if
    // we want to access them in other contexts as well.
    protected $projectid; /**< ID of the associated project */
    protected $text_sigle = null; /**< Sigle of the text */
    protected $text_fullname = null; /**< Full name of the text */
    protected $text_header = null; /**< Header of the text */

    // SQL statements
    private $stmt_isValidModID = null;
    private $stmt_getSelectedAnnotations = null;

    /** Construct a new DocumentAccessor.
     *
     * @param DBInterface $parent A DBInterface object to use for queries
     * @param PDO $dbo A PDO database object passed from DBInterface
     * @param string $fileid ID of the file to be accessed
     */
    function __construct($parent, $dbo, $fileid) {
        $this->dbi = $parent;
        $this->dbo = $dbo;
        $this->fileid = $fileid;
        $this->prepare_isValidModID();
        $this->prepare_getSelectedAnnotations();
    }

    protected function warn($message) {
        $this->warnings[] = $message;
    }

    public function getWarnings() {
        return $this->warnings;
    }

    public function getTagsets() {
        return $this->tagsets;
    }

    /** Setter function for file ID, so other variables/settings that
     *  depend on this ID can be recreated if the ID changes.
     */
    protected function setFileID($id) {
        $this->fileid = $id;
        $this->prepare_isValidModID();
    }

    public function getFileID($id) {
        return $this->fileid;
    }

    /**********************************************
     ********* SQL Statement Preparations *********
     **********************************************/
    private function prepare_isValidModID() {
        $stmt = "SELECT COUNT(*) FROM modern "
              . " LEFT JOIN token     ON   modern.tok_id=token.id "
              . " LEFT JOIN text      ON   token.text_id=text.id "
              . "     WHERE text.id={$this->fileid} "
              . "           AND modern.id=?";
        $this->stmt_isValidModID = $this->dbo->prepare($stmt);
    }

    private function prepare_getSelectedAnnotations() {
        $stmt = "SELECT ts.id, ts.tag_id, ts.source, tag.value, "
              . "           LOWER(tagset.class) AS `class` "
              . "      FROM tag_suggestion ts "
              . " LEFT JOIN tag     ON tag.id=ts.tag_id "
              . " LEFT JOIN tagset  ON tagset.id=tag.tagset_id "
              . "     WHERE ts.selected=1 AND ts.mod_id=?";
        $this->stmt_getSelectedAnnotations = $this->dbo->prepare($stmt);
    }
    /**********************************************/

    /** Test whether a given mod ID belongs to the associated file.
     *
     * @param string $modid ID of the mod to be tested
     */
    public function isValidModID($modid) {
        $this->stmt_isValidModID->execute(array($modid));
        return ($this->stmt_isValidModID->fetchColumn() == 1);
    }

    /** Retrieve selected annotations for a given mod ID.
     *
     * @param string $modid A mod ID
     */
    public function getSelectedAnnotations($modid) {
        $this->stmt_getSelectedAnnotations->execute(array($modid));
        return $this->stmt_getSelectedAnnotations->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Retrieve selected annotations for a given mod ID and index them
     * by class attribute of the tagset.
     *
     * @param string $modid A mod ID
     */
    public function getSelectedAnnotationsByClass($modid) {
        $annotations = array();
        $selected = $this->getSelectedAnnotations($modid);
        foreach ($selected as $row) {
            $annotations[$row['class']] = $row;
        }
        return $annotations;
    }

    /** Determine if tagset values should be preloaded. */
    protected function isPreloadTagset($tagset) {
        return ($tagset['set_type'] == "closed" && $tagset['class'] != 'lemma_sugg');
    }

    /** Preload a tagset.
     *
     * Retrieves all tags for a given tagset and stores them for future
     * reference.
     */
    protected function preloadTagset($tagset) {
        $accessor = new TagsetAccessor($this->dbo, $tagset['id']);
        $values = array_values($accessor->entries());
        $this->tagsets[$tagset['class']]['tags'] = $values;
    }

    /** Returns a list of tagsets linked to the associated file.
     */
    protected function getTagsetLinks() {
        return $this->dbi->getTagsetsForFile($this->fileid);
    }

    /** Retrieve information about tagsets linked to the associated
     * file.
     *
     * Retrieves all associated tagsets and automatically loads all tag
     * values where appropriate.
     */
    public function retrieveTagsetInformation() {
        $tslist = $this->getTagsetLinks();
        foreach ($tslist as $ts) { // index by class
            $this->tagsets[$ts['class']] = $ts;
            if ($this->isPreloadTagset($ts)) {
                $this->preloadTagset($ts);
            }
        }
    }
}
