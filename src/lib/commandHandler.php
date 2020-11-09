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
/** @file commandHandler.php
 * Class for calling external commands
 * (e.g., for POS tagging, tokenization, etc.)
 *
 * @author Marcel Bollmann
 * @date February 2013
 */

/** Handles calls to external commands.
 */
class CommandHandler {
    private $options;
    function __construct($options = array(), $lh) {
        $this->options = $options;
        $this->lh = $lh;
    }

    /** Create a temporary file containing the given token.
     *
     * @return The filename of the temporary file.
     */
    private function writeTokenToTmpfile($token) {
        $tmpfname = tempnam(sys_get_temp_dir(), 'cora');
        $handle = fopen($tmpfname, 'w');
        fwrite($handle, $token);
        fclose($handle);
        return $tmpfname;
    }

    /** Checks the MIME type of a file.
     */
    public function checkMimeType($filename, $mimetype) {
        $output = array();
        $errors = array();
        exec("file -b --mime-type " . $filename, $output);
        if ($output[0] != $mimetype) {
            array_unshift($errors,
                          $this->lh->_("ServerError.fileFormatMismatch",
                                    array("expected" => $mimetype, "found" => $output[0])));
        }
        return $errors;
    }

    /** Converts a file to UTF-8.  Returns an array of error messages;
     an empty array indicates success.
     */
    public function convertToUtf($filename, $encoding) {
        $errors = array();
        $output = array();
        if (isset($encoding) && !empty($encoding) && $encoding != "utf-8") {
            $tmpfname = tempnam(sys_get_temp_dir(), 'cora');
            exec("uconv -f {$encoding} -t utf-8 {$filename} > {$tmpfname}");
            exec("mv {$tmpfname} {$filename}");
        }
        exec("iconv -f utf-8 -t utf-8 {$filename} 2>&1", $output, $retval);
        if ($retval) {
            array_unshift($errors, $this->lh->_("ServerError.utf8Failure"));
        }
        return $errors;
    }

    /** Checks and converts a single token. */
    public function checkConvertToken($token, &$errors) {
        if (!array_key_exists('cmd_edittoken', $this->options)
            || empty($this->options['cmd_edittoken'])) {
            array_unshift($errors, $this->lh->_("ServerError.noConverterScript"));
            return array();
        }
        $tmpfname = $this->writeTokenToTmpfile($token);
        $output = array();
        $retval = 0;
        $command = $this->options['cmd_edittoken'] . " {$tmpfname} 2>&1";
        exec($command, $output, $retval);
        unlink($tmpfname);
        if ($retval) {
            $errors = $output;
            array_unshift($errors, $this->lh->_("ServerError.nonZeroExit", array("code" => $retval)));
            return array();
        }
        $result = json_decode($output[0], true);
        if (is_null($result)) {
            $errors = $output;
            array_unshift($errors, $this->lh->_("TranscriptionError.wrongConverterOutput"));
            return array();
        }
        return $result;
    }

    /** Calls the import script to create an XML file for import. */
    public function callImport(&$infile, &$xmlfile, $logfile) {
        if (!array_key_exists('cmd_import', $this->options) || empty($this->options['cmd_import'])) {
            return array($this->lh->_("ServerError.noImportScript"));
        }
        $xmlfile = tempnam(sys_get_temp_dir(), 'cora');
        $output = array();
        $retval = 0;
        $command = $this->options['cmd_import'] . " {$infile} {$xmlfile} 2>&1";
        exec($command, $output, $retval);
        return $output;
    }
}
?>
