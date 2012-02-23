<?php

require_once('connect.php');

$file = "../tags.morph";
$tagsetName = "STTS.morph";
$tagsetNameLong ="STTS Morph Tags";
$lang = "de";

class addTagset extends DBConnector {

	function __construct($tagsetName,$tagsetNameLong,$file,$lang){
		
		parent::__construct();
	    $this->setDefaultDatabase( MAIN_DB );
	    
		
		$qs = "INSERT INTO tagsets (tagset) VALUES ('{$tagsetName}')";
		if($this->query($qs)){
			$qs = " INSERT INTO tagset_strings (tagset, `id`, {$lang})
					VALUES ('{$tagsetName}', 0, '{$tagsetNameLong}')";
			$this->query($qs);
			$tags = file($file);
			foreach($tags as $index=>$tag){
				$i = $index+1;
				$tag = trim($tag);
				$qs = " INSERT INTO tagset_tags (tagset, `id`, shortname, type)
						VALUES ('{$tagsetName}', {$i}, '{$tag}', 'tag')";
				$this->criticalQuery($qs);
		
				$qs = "	INSERT INTO tagset_strings (tagset, `id`, {$lang}) 
						VALUES ('{$tagsetName}', {$i}, '{$tag}')";
				$this->criticalQuery($qs);

				// foreach($entry['link'] as $i => $link_id) {
				//   $qs = "INSERT INTO {$this->db}.tagset_links (tagset, tag_id, attrib_id) 
				// 	     VALUES ('{$tagsetName}', {$index}, {$link_id})";
				//   $this->criticalQuery($qs);
				// }
		 	} 
		}
	}	
}

new addTagset($tagsetName,$tagsetNameLong,$file,$lang);

?>