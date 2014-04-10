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
  private $conv_script  = "/usr/bin/ruby /usr/local/bin/convert_check.rb -T";
  private $xml_script   = "/usr/bin/python -u /usr/local/bin/convert_coraxml.py -g";
  private $single_token_flag = "-L";
  private $conv_opt = array("mod_trans" => "-c orig -t all -p leave -r leave -i original -d leave -s delete -e leave",
			    "mod_ascii" => "-c simple -t all -p leave -r delete -i leave -d delete -s delete -e delete",
			    "mod_utf"   => "-c utf -t all -p leave -r delete -i leave -d delete -s delete -e delete",
			    "dipl_trans" => "-S -c orig -t historical -p leave -r leave -i original -s original -d leave -e leave",
			    "dipl_utf"   => "-S -c utf -t historical -p delete -r delete -i leave -s leave -d leave -e delete"
			    );

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

  /** Call the check script to verify the validity of a transcription.
   */
  public function checkToken($token) {
    $output = array();
    // check for misplaced newlines - little bit hacky to do this here ...
    $lines = explode("\n", $token);
    if(count($lines)>1) {
      array_pop($lines); // last token can be whatever ...
      foreach($lines as $line) {
	if(!Transcription::endsWithSeparator($line)) {
	  return array("Zeilenumbrüche sind nur erlaubt, wenn ihnen ein Trennzeichen vorangeht.  Transkription war:", $token);
	}
      }
    }
    // call check script
    $tmpfname = $this->writeTokenToTmpfile($token);
    $retval = 0;
    $command = $this->check_script ." ". $this->single_token_flag ." ". $tmpfname ." 2>&1";
    exec($command, $output, $retval);
    if($retval) {
      array_unshift($output, "Der Befehl gab den Status-Code {$retval} zurück:");
    }
    // return
    unlink($tmpfname);
    return $output;
  }

  /** Call the convert script to perform all possible conversions of
   * an input token.
   *
   * @return An array of the converted tokens indexed by mod/dipl and
   * trans/utf/ascii
   */
  public function convertToken($token, &$errors) {
    // do conversions
    $tmpfname = $this->writeTokenToTmpfile($token);
    $result = array();
    foreach($this->conv_opt as $opt => $flags) {
      $output = array();
      $retval = 0;
      $command = $this->conv_script ." ". $this->single_token_flag . " {$flags} {$tmpfname} 2>&1";
      exec($command, $output, $retval);
      if($retval) {
	$errors = $output;
	array_unshift($errors, "Der Befehl gab den Status-Code {$retval} zurück.");
	unlink($tmpfname);
	return $result;
      }
      $result[$opt] = $output;
    }
    // return
    unlink($tmpfname);
    return $result;
  }


}

?>