<?php

/** @file commandHandler.php
 * Class for calling external commands
 * (e.g., for POS tagging, tokenization, etc.)
 *
 * @author Marcel Bollmann
 * @date February 2013
 */

class Transcription {
  public static function endsWithSeparator($line) {
    return (substr($line, -1)=='=' ||
	    substr($line, -2)=='=|' ||
	    substr($line, -3)=='(=)' ||
	    substr($line, -3)=='<=>' ||
	    substr($line, -3)=='[=]' ||
	    substr($line, -4)=='<=>|' ||
	    substr($line, -4)=='[=]|' ||
	    substr($line, -5)=='<<=>>' ||
	    substr($line, -5)=='[[=]]' ||
	    substr($line, -6)=='<<=>>|' ||
	    substr($line, -6)=='[[=]]|');
  }
}

class CommandHandler {

  private $options;

  private $check_script = "/usr/bin/ruby /usr/local/bin/convert_check.rb -C";
  private $xml_script   = "/usr/bin/python -u /usr/local/bin/convert_coraxml.py -g";

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

  /** Call the check script to verify the validity of a file.
   */
  public function checkFile($filename) {
    $output = array();
    // call check script
    $retval = 0;
    $command = $this->check_script ." ". $filename ." 2>&1";
    exec($command, $output, $retval);
    if($retval) {
      if(count($output) > 500) {
	$diff = count($output) - 500;
	$output = array_slice($output, 0, 500);
	$output[] = "Es gab noch {$diff} weitere Zeilen, die hier ausgelassen wurden.";
      }
      array_unshift($output, "Die Prüfung der Transkription hat Fehler ergeben:");
      // return
      return $output;
    }
    else {
      return array();
    }
  }

  /** Call the conversion script to convert a transcription file to
      CorA XML. */
  public function convertTransToXML(&$transname, &$xmlname, $logfile, $cmdopt=null) {
    $output = array();
    $xmlname = tempnam(sys_get_temp_dir(), 'cora');
    $retval = 0;
    $command = $this->xml_script;
    if($cmdopt) {
      $command .= " " . $cmdopt;
    }
    $command .= " {$transname} {$xmlname} >>{$logfile} 2>&1";
    exec($command, $output, $retval);
    if($retval) {
      if(count($output) > 500) {
	$diff = count($output) - 500;
	$output = array_slice($output, 0, 500);
	$output[] = "Es gab noch {$diff} weitere Zeilen, die hier ausgelassen wurden.";
      }
      array_unshift($output, "Die Umwandlung der Transkription war nicht erfolgreich:");
      return $output;
    }
    else {
      return array();
    }
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
    if(!array_key_exists('cmd_edittoken', $this->options)) {
        $errors = array("Kein Konvertierungsskript festgelegt!");
        return array();
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
    $result = json_decode($output, true);
    if(is_null($result)) {
        $errors = $output;
        array_unshift($errors, "Das Konvertierungsskript lieferte ungültigen Output.");
        return array();
    }
    return $result;
  }

}

?>