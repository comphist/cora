<?php

/** @file fileModel.php
 * Define classes related to file import.
 *
 * @author Lara Kresse
 * @date February 2012
 */

/** This class provides methods for importing new files and adding tagged data
 * to existing ones.
 *
 */
class fileModel{
	
	/** Create a new fileModel with the reference to the @c sessionHandler. */
	public function __construct(&$sh){
		$this->sh = $sh;
	}
	
	/** Check if a tag exists in the given tagset.
	 *
	 * @note Attention: at the moment NO TAGSET ALIGNMENT is done
	 * @todo enable tagset alignment
	 * @param string $tag the tag
	 * @return bool result of the @c in_array function
	 */
	function prooveTag($tag){
		if(in_array($tag,$this->tagsetTags)){
			return true;
		}
		return false;
	}
	
	/** Check if a attribute exists in the given tagset.
	 *
	 * @note Attention: at the moment NO TAGSET ALIGNMENT is done
 	 * @todo enable tagset alignment and implement the attribute look up
	 * @param string $attrib the tag
	 * @return bool result of the @c in_array function
	 */
	function prooveAttrib($attrib){
		if(in_array($attrib,$this->tagsetAttribs)){
			return true;
		}
		return false;		
	}
	
	/** Initialize the tagset.
	 *
	 * The tagset is retrieved from the database und than
	 * stored in two class arrays distinguished by type (tag or attribute).
	 *
	 * @note Attention: at the moment NO TAGSET ALIGNMENT is done (no @c preprocessTagset() is needed)
	 * @param string $tagset the tagset's name
	 */	
	function preprocessTagset($tagset){
		$dbTagset = $this->sh->getTagset($tagset);
		
		$tagsetTags = array();
		$tagsetAttribs = array();
		
		foreach($dbTagset['tags'] as $id => $tag){
			$tagsetTags[$id] = $tag['shortname'];			
		}
		foreach($dbTagset['attribs'] as $id => $attrib){
			$tagsetAttribs[$id] = $attrib['shortname'];
		}
		
		$this->tagsetTags = $tagsetTags;
		$this->tagsetAttribs = $tagsetAttribs;
	}
	
	/** Import an new file.
	 *
	 * Wrapper for @c processLine funtion.
	 * Uploaded data is splitted by lines.
	 *
	 * @param string $tagset the tagset's name used for the text
	 * @param bool $pos tag type of the text: POS (@true or @false), default: @c false
	 * @param bool $morph tag type of the text: Morph (@true or @false), default: @c false
	 * @param bool $norm tag type of the text: Norm (@true or @false), default: @c false
	 * @param string reference $data uploaded tag data (from @c file_get_contents())	
	 *
	 * @return an @em array with at least a @c status key which indicates the success of the import
	 * and a @c data key which contains either the imported data or the errors to pass them back to
	 * the user
	 */		
	public function importFile($tagset,$pos=false,$morph=false,$norm=false,&$data){
        
		/* TAGSET ALIGNEMENT IS DISABLED */
		// if($pos || $morph)
		// 	$this->preprocessTagset($tagset);
			
		$this->dbData = array();
		$this->errors = array();			

		$lines = explode("\n",$data);

		foreach($lines as $index=>$line){
			$this->processLine($line,$pos,$morph,$norm);
		}
		
		if(count($this->errors)>0)
			return array("status"=>false, "tagsetName"=> $tagset, "data"=>$this->errors);
		
		return array("status"=>true, "data"=>$this->dbData);
		
	}

	/** Get structured tag data from a text line.
	 *
	 * Lines are splitting by tab stops and resulting parts are wrote to an associative array, keys are 
	 * depending on the tag type (POS, Morph or Norm). The resulting array is appended to the class array
	 * @c dbData.
	 * Following formats are assumed:
	 * POS: token \t pos_tag \t lemma \t possibility
 	 * Morph: token \t morph_tag \t lemma \t possibility
	 * Norm: token \t normalised form \t possibility
	 *   
	 * @param string $line the text line
	 * @param bool $pos tag type of the text: POS (@true or @false)
	 * @param bool $morph tag type of the text: Morph (@true or @false)
	 * @param bool $norm tag type of the text: Norm (@true or @false)
	 *
	 */					
	public function processLine($line,$pos,$morph,$norm){
		
		if(empty($line)) return;
		
		$fields = explode("\t",$line,2);
		$parts = explode("\t",$fields[1]);                   

		if($pos || $morph || $norm){
			if(count($parts)>0){
				$lineArray = array();
				foreach($parts as $partId=>$part){
					$tmp = array();
					$splits = explode(" ",$part);
					if($pos){
						$tmp['pos'] = trim($splits[0]);
						/* TAGSET ALIGNEMENT IS DISABLED */
						// if(!$this->prooveTag($tmp['pos'])){
						// 	$this->errors[$tmp['pos']] = $partId;
						// 	continue;														      
						// }								
						$tmp['lemma'] = htmlspecialchars(trim($splits[1]));
						$tmp['possibility'] = trim($splits[2]);
					} elseif($morph){
						$tmp['morph'] = trim($splits[0]);
						/* TAGSET ALIGNEMENT IS DISABLED */
						// if(!$this->prooveAttrib($tmp['morph'])){
						// 	$this->errors[$tmp['morph']] = $partId;
						// 	continue;														      
						// }								
						$tmp['lemma'] = htmlspecialchars(trim($splits[1]));
						$tmp['possibility'] = trim($splits[2]);
						
					} elseif($norm){
						if(!empty($splits))
							$tmp['norm'] = trim($splits[0]);
							$tmp['possibility'] = 1;
					}
					$lineArray[] = $tmp;
				}
				$this->dbData[] = array('token' => trim($fields[0]), 'data' => $lineArray); 
			} else {
			}
		} else {
			$this->dbData[] = array('token' => trim($fields[0]));
		}
	}
		
	/** Add data to an existing file.
	 *
	 * Wrapper for @c processLine funtion.
	 * Uploaded data is splitted by lines.
	 * 
	 * Before passing data to the @ processLine method, a token check is performed: the
	 * token is compared to the token of the respective line in the database. In case of
	 * dismatch, the line is skipped and a @c diff counter stores the difference so the
	 * following lines are matched to the correct lines (new line id = old line id +- diff)!
	 * 
	 * @param int $fileid file ID where to add the tag data
	 * @param string $tagset the tagset's name used for the text
	 * @param bool $pos tag type of the text: POS (@true or @false), default: @c false
	 * @param bool $morph tag type of the text: Morph (@true or @false), default: @c false
	 * @param bool $norm tag type of the text: Norm (@true or @false), default: @c false
	 * @param string reference $data uploaded tag data (from @c file_get_contents())
	 *
	 * @return an @em array with at least a @c status key which indicates the success
	 * and a @c data key which contains either the added data or the errors to pass them back to
	 * the user
	 */
	public function addData($fileid,$tagset,$pos=false,$morph=false,$norm=false,&$data){
		
		/* TAGSET ALIGNEMENT IS DISABLED */
		// if($pos || $morph)
		// 	$this->preprocessTagset($tagset);

		$orig = $this->sh->getToken($fileid);					

		$this->dbData = array();
		$this->errors = array();			

		$lines = explode("\n",$data);

		foreach($lines as $index=>$line){
			$token = explode("\t",$line,2);
			$token = trim($token[0]);
			if($orig[$index] == $token || $orig[$index-$diff] == $token || $orig[$index+$diff] == $token){
				$this->processLine($line,$pos,$morph,$norm);
			}
			else { $diff++; }
		}
		
		if(count($this->errors)>0)
			return array("status"=>false, "tagsetName"=> $tagset, "data"=>$this->errors);
		
		return array("status"=>true, "data"=>$this->dbData);
		
	}
}

?>