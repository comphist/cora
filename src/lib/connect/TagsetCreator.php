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
/** @file TagsetCreator.php
 *
 * @author Marcel Bollmann
 * @date July 2015
 */
require_once ('TagsetAccessor.php');

/** Handles creation of new tagsets.
 */
class TagsetCreator extends TagsetAccessor {
    /** Construct a new TagsetCreator.
     *
     * @param PDO $dbo A PDO database object to use for queries
     * @param string $cls Class of the new tagset
     * @param string $settype Set type of the new tagset (open,closed)
     * @param string $name Name of the new tagset
     * @param string $settings (optional) Tagset-specific settings
     */
    function __construct($dbo, $cls, $settype, $name, $settings="") {
        parent::__construct($dbo, null);
        $this->tsclass = $cls;
        $this->settype = $settype;
        $this->name = $name;
        $this->settings = $settings;
    }

    /** Add a list of tags.
     *
     * Leading circumflex (^) will be interpreted as marking the tag in question
     * as 'needing revision'.  If this is not desired, add the tags individually
     * using TagsetAccessor::addTag().
     */
    public function addTaglist($taglist) {
        foreach ($taglist as $tag) {
            $tag = trim($tag);
            if (empty($tag)) continue;
            if (substr($tag, 0, 1) === '^') {
                $value = substr($tag, 1);
                $needs_rev = true;
            } else {
                $value = $tag;
                $needs_rev = false;
            }
            $this->addTag($value, $needs_rev);
        }
    }

    protected function executeCommitChanges() {
        $stmt = "INSERT INTO tagset (`name`, `set_type`, `class`, `settings`) "
              . "VALUES (:name, :settype, :class, :settings)";
        $data = array(':name' => $this->name, ':settype' => $this->settype,
                      ':class' => $this->tsclass, ':settings' => $this->settings);
        $stmt_createTagset = $this->dbo->prepare($stmt);
        $stmt_createTagset->execute($data);
        $this->id = $this->dbo->lastInsertId();
        parent::executeCommitChanges();
    }
}
?>
