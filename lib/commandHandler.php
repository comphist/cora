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

  function __construct($options=array()) {
      $this->options = $options;
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
    if($output[0]!=$mimetype) {
      array_unshift($errors, "Falsches Dateiformat (erwartet: {$mimetype}; gefunden: {$output[0]})");
    }
    return $errors;
  }

  /** Converts a file to UTF-8.  Returns an array of error messages;
      an empty array indicates success. */
  public function convertToUtf($filename, $encoding) {
    $errors = array(); $output = array();
    if (isset($encoding) && !empty($encoding) && $encoding!="utf-8") {
      $tmpfname = tempnam(sys_get_temp_dir(), 'cora');
      exec("uconv -f {$encoding} -t utf-8 {$filename} > {$tmpfname}");
      exec("mv {$tmpfname} {$filename}");
    }
    exec("iconv -f utf-8 -t utf-8 {$filename} 2>&1", $output, $retval);
    if ($retval) {
      array_unshift($errors, "Datei konnte nicht nach UTF-8 umgewandelt werden. Prüfen Sie, ob Sie das richtige Encoding angegeben haben.");
    }
    return $errors;
  }

  /** Checks and converts a single token. */
  public function checkConvertToken($token, &$errors) {
    if(!array_key_exists('cmd_edittoken', $this->options)
       || empty($this->options['cmd_edittoken'])) {
        return array("Kein Konvertierungsskript festgelegt!");
    }

    $tmpfname = $this->writeTokenToTmpfile($token);
    $output  = array();
    $retval  = 0;
    $command = $this->options['cmd_edittoken']." {$tmpfname} 2>&1";
    exec($command, $output, $retval);
    unlink($tmpfname);
    if($retval) {
        $errors = $output;
	array_unshift($errors, "Der Befehl gab den Status-Code {$retval} zurück.");
	return array();
    }
    $result = json_decode($output[0], true);
    if(is_null($result)) {
        $errors = $output;
        array_unshift($errors, "Das Konvertierungsskript lieferte ungültigen Output.");
        return array();
    }
    return $result;
  }

  /** Calls the import script to create an XML file for import. */
  public function callImport(&$infile, &$xmlfile, $logfile) {
      if(!array_key_exists('cmd_import', $this->options)
         || empty($this->options['cmd_import'])) {
          return array("Kein Importskript festgelegt!");
      }

      $xmlfile = tempnam(sys_get_temp_dir(), 'cora');
      $output  = array();
      $retval  = 0;
      $command = $this->options['cmd_import']
               . " {$infile} {$xmlfile} >>{$logfile} 2>&1";
      exec($command, $output, $retval);
      return $output;
  }

}

?>
