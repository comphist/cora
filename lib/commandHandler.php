<?php

/** @file commandHandler.php
 * Class for calling external commands
 * (e.g., for POS tagging, tokenization, etc.)
 *
 * @author Marcel Bollmann
 * @date February 2013
 */

class CommandHandler {

  private $check_script = "/usr/bin/ruby /usr/local/bin/convert_check.rb -C -L";
  private $conv_script  = "/usr/bin/ruby /usr/local/bin/convert_check.rb -T -L";
  private $conv_opt = array("mod_trans" => "-c orig -t all -p leave -r leave -i original -d leave -s delete",
			    "mod_ascii" => "-c simple -t all -p leave -r delete -i leave -d delete -s delete",
			    "mod_utf"   => "-c utf -t all -p leave -r delete -i leave -d delete -s delete",
			    "dipl_trans" => "-S -c orig -t historical -p leave -r leave -i original -s original -d leave",
			    "dipl_utf"   => "-S -c utf -t historical -p delete -r delete -i leave -s leave -d leave"
			    );

  function __construct($db) {
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

  /** Call the check script to verify the validity of a transcription.
   */
  public function checkToken($token) {
    $output = array();
    // check for misplaced newlines - little bit hacky to do this here ...
    $lines = explode("\n", $token);
    if(count($lines)>1) {
      array_pop($lines); // last token can be whatever ...
      foreach($lines as $line) {
	if(substr($line, -1)!=='=' && substr($line, -3)!=='(=)') {
	  return array("Zeilenumbrüche sind nur erlaubt, wenn ihnen eines der Trennzeichen = oder (=) vorangeht.  Transkription war:", $token);
	}
      }
    }
    // call check script
    $tmpfname = $this->writeTokenToTmpfile($token);
    $retval = 0;
    $command = $this->check_script ." ". $tmpfname ." 2>&1";
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
      $command = $this->conv_script . " {$flags} {$tmpfname} 2>&1";
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