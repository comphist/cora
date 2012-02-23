<?php

class fileModel{
	
	public function __construct(&$sh){
		$this->sh = $sh;
	}
	
	function prooveTag($tag){
		if(in_array($tag,$this->tagsetTags)){
			return true;
		}
		return false;
	}
	
	function prooveAttrib(){
		if(in_array($tag,$this->tagsetAttribs)){
			return true;
		}
		return false;		
	}
	
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
	
	public function importFile($tagset,$pos=false,$morph=false,$norm=false,&$data){

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
						// if(!$this->prooveTag($tmp['pos'])){
						// 	$this->errors[$tmp['pos']] = $partId;
						// 	continue;														      
						// }								
						$tmp['lemma'] = htmlspecialchars(trim($splits[1]));
						$tmp['possibility'] = trim($splits[2]);
					} elseif($morph){
						$tmp['morph'] = trim($splits[0]);
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
		
	
	public function addData($fileid,$tagset,$pos=false,$morph=false,$norm=false,&$data){
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