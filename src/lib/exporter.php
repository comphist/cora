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
/** @file exporter.php
 * Export of files.  Implements all file formats except XML,
 * which is implemented in @c xmlHandler.php.
 *
 * @author Marcel Bollmann
 * @date June 2013
 */
require_once ("documentModel.php");
require_once ("xmlHandler.php");

/** Data formats used for exporting. */
class ExportType {
    const CoraXML = 1; /**< CorA XML format */
    const Tagging = 2; /**< Tab-separated format containing
                          simplification and POS tags, suitable for
                          training a tagger model. */
    const Transcription = 3; // export in original transcription format
    const Normalization = 4; /**< Tab-separated format containing
                                simplification, normalization, modernization. */
    const CustomCSV = 5; /**< Customized tab-separated text file. */

    public static function mapToContentType($format) {
        switch ($format) {
            case ExportType::CoraXML:
                return "text/xml";
            break;
            case ExportType::Tagging:
            case ExportType::Transcription:
            case ExportType::Normalization:
                return "text/plain";
            break;
            case ExportType::CustomCSV:
                return "text/csv";
            break;
        }
    }

    public static function mapToExtension($format) {
        switch ($format) {
            case ExportType::CoraXML:
                return ".xml";
            break;
            case ExportType::Tagging:
            case ExportType::Transcription:
            case ExportType::Normalization:
            case ExportType::CustomCSV:
                return ".txt";
            break;
        }
    }
}

/** Performs export of documents. */
class Exporter {
    private $db; /**< A DBInterface object. */

    function __construct($db, $lh = null) {
        $this->db = $db;
        $this->lh = $lh;
    }

    /** Export a file.
     *
     * Requests the file to be exported from the database, then
     * delegates the call to specific exporters depending on the desired
     * target format.
     *
     * @param string $fileid ID of the file to be exported
     * @param string $format Desired format of the export file
     * @param array $options (optional, can be empty) Additional options
     * @param resource $handle A resource representing an output stream
     *                         where the exported data will be sent
     *
     * @return ???
     */
    public function export($fileid, $format, $options, $handle) {
        if ($format == ExportType::Tagging) {
            return $this->exportPOS($fileid, $handle);
        }
        if ($format == ExportType::Normalization) {
            return $this->exportNormalization($fileid, $handle);
        }
        if ($format == ExportType::CustomCSV) {
            return $this->exportCSV($fileid, $options, $handle);
        }
        // make a try..catch
        $doc = CoraDocument::fromDB($fileid, $this->db);
        if ($format == ExportType::CoraXML) {
            return $this->exportCoraXML($doc, $handle);
        }
    }

    protected function exportFileForTraining($fileid, $handle, $classes) {
        $tokens = $this->db->getAllTokens($fileid);
        $moderns = $tokens[2];
        $skip = false;
        foreach ($moderns as $mod) {
            if (!$mod['verified']) {
                if (!$skip) {
                    fwrite($handle, "\n");
                    $skip = true;
                }
                continue;
            }
            $skip = false;
            $annotations = array();
            foreach ($mod['tags'] as $tag) {
                if ($tag['selected'] == 1) {
                    $annotations[$tag['type']] = $tag['tag'];
                }
            }
            fwrite($handle, $mod['ascii']);
            foreach ($classes as $class) {
                $anno = array_key_exists($class, $annotations) ? $annotations[$class] : "";
                fwrite($handle, "\t" . $anno);
            }
            fwrite($handle, "\n");
        }
    }

    public function exportForTraining($projectid, $handle, $classes, $header = TRUE) {
        $all_files = $this->db->getFilesForProject($projectid);
        if ($header) {
            fwrite($handle, 'ascii');
            foreach ($classes as $class) {
                fwrite($handle, "\t" . $class);
            }
            fwrite($handle, "\n");
        }
        foreach ($all_files as $f) {
            $this->exportFileForTraining($f['id'], $handle, $classes);
        }
    }

    /** Export all mods with several annotation layers.
     *
     * Outputs a tab-separated text format with modern tokens (in their
     * simplified version) and any number of given annotation layers.
     *
     * @param string $fileid ID of the file to be exported
     * @param resource $handle A resource representing an output stream
     * @param array $classes An array containing the tagset classes
     *                       that should be exported
     * @param bool $header If true, the first output line will be
     *                     a header containing the tagset classes
     *
     * @return An array of mods as provided by @c
     * DBInterface::getAllTokens.
     */
    public function exportForTagging($fileid, $handle, $classes, $header = TRUE) {
        $tokens = $this->db->getAllTokens($fileid);
        $moderns = $tokens[2];
        if ($header) {
            fwrite($handle, 'ascii');
            foreach ($classes as $class) {
                fwrite($handle, "\t" . $class);
            }
            fwrite($handle, "\n");
        }
        foreach ($moderns as $mod) {
            $annotations = array();
            foreach ($mod['tags'] as $tag) {
                if ($tag['selected'] == 1) {
                    $annotations[$tag['type']] = $tag['tag'];
                }
            }
            fwrite($handle, $mod['ascii']);
            foreach ($classes as $class) {
                $anno = array_key_exists($class, $annotations) ? $annotations[$class] : "";
                fwrite($handle, "\t" . $anno);
            }
            fwrite($handle, "\n");
        }
        return $moderns;
    }

    /** Export a file with POS annotations.
     *
     * Outputs a tab-separated text format with modern tokens (in their
     * simplified version) and their selected POS tags.
     *
     * @param string $fileid ID of the file to be exported
     * @param resource $handle A resource representing an output stream
     *                         where the exported data will be sent
     */
    protected function exportPOS($fileid, $handle) {
        $moderns = $this->db->getAllModerns($fileid);
        foreach ($moderns as $mod) {
            $tok = $mod['ascii'];
            $pos = '';
            foreach ($mod['tags'] as $tag) {
                if ($tag['type'] == 'pos' && $tag['selected'] == 1) {
                    $pos = $tag['tag'];
                }
            }
            fwrite($handle, $tok . "\t" . $pos . "\n");
        }
    }

    /** Export a file with normalization annotations. */
    protected function exportNormalization($fileid, $handle) {
        $moderns = $this->db->getAllModerns($fileid);
        foreach ($moderns as $mod) {
            $tok = $mod['ascii'];
            $norm = '--';
            $normbroad = '--';
            $normtype = '';
            foreach ($mod['tags'] as $tag) {
                if ($tag['selected'] == 1) {
                    if ($tag['type'] == 'norm') {
                        $norm = $tag['tag'];
                    } else if ($tag['type'] == 'norm_broad') {
                        $normbroad = $tag['tag'];
                    } else if ($tag['type'] == 'norm_type') {
                        $normtype = $tag['tag'];
                    }
                }
            }
            fwrite($handle, $tok . "\t" . $norm);
            if ($normbroad != '--') {
                fwrite($handle, "\t" . $normbroad . "\t" . $normtype);
            }
            fwrite($handle, "\n");
        }
    }

    /** Export a file as CorA XML. */
    protected function exportCoraXML($document, $handle) {
        $xmlhandler = new XMLHandler($this->db, $this->lh);
        $dom = $xmlhandler->serializeDocument($document);
        $dom->formatOutput = true;
        fwrite($handle, $dom->saveXML());
    }

    /** Export a file as CSV. */
    protected function exportCSV($fileid, $options, $handle) {
        fwrite($handle, implode("\t", $options) . "\n");
        $moderns = $this->db->getAllModerns($fileid);
        foreach ($moderns as $mod) {
            // filter selected tags && flags
            foreach ($mod['tags'] as $tag) {
                if ($tag['selected'] == 1) $mod[$tag['type']] = $tag['tag'];
            }
            foreach ($mod['flags'] as $flag) {
                $mod['flag_' . str_replace(" ", "_", $flag) ] = "yes";
            }
            // output line
            $output = array();
            foreach ($options as $opt) {
                $output[] = (isset($mod[$opt]) ? $mod[$opt] : "");
            }
            fwrite($handle, implode("\t", $output));
            fwrite($handle, "\n");
        }
    }
}
?>
