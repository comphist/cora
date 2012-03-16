<?php

/** @file addTagset.php
 * utility script for importing a tagset from a textfile
 * 
 * @author Lara Kresse
 * @date February 2012
 */

/* requires DBConnector class */
require_once('connect.php');


/* SETTINGS */
$file = "../tags.morph"; // file to import from
$tagsetName = "STTS.morph"; // tagset name short
$tagsetNameLong ="STTS Morph Tags"; // tagset name long
$lang = "de"; // language


/** This class inherits from the @c DBConnector @c class.
 * The only function is to import a tagset from a textfile into the database.
 * All work is done in the constructor.
 *
 * @extends DBConnector
 */
class addTagset extends DBConnector {
    
	/* Perfoms the relevent queries to transfer the 
	 * tagset to the database.
	 * 
	 * @param string $tagsetName tagset name (abbr)
	 * @param string $tagsetNameLong full tagset name (long version, language specific)
	 * @param string $file file path
	 * @param string $lang language constant (@c de or @c en)
	 **/
	function __construct($tagsetName,$tagsetNameLong,$file,$lang){
		
		/* refer parent constructor */
		parent::__construct();
	    $this->setDefaultDatabase( MAIN_DB );
	    
		/* create new empty tagset */
		$qs = "INSERT INTO tagsets (tagset) VALUES ('{$tagsetName}')";
		if($this->query($qs)){
			/* write long tagset name to database */
			$qs = " INSERT INTO tagset_strings (tagset, `id`, {$lang})
					VALUES ('{$tagsetName}', 0, '{$tagsetNameLong}')";
			$this->query($qs);
			/* read tagset from textfile into array (one item per line) */
			$tags = file($file);

			/* write each tagset into the data base (2 tables!) */
			foreach($tags as $index=>$tag){
				$i = $index+1;
				$tag = trim($tag);
				// store tag
				$qs = " INSERT INTO tagset_tags (tagset, `id`, shortname, type)
						VALUES ('{$tagsetName}', {$i}, '{$tag}', 'tag')";
				$this->criticalQuery($qs);
				// store tag description
				$qs = "	INSERT INTO tagset_strings (tagset, `id`, {$lang}) 
						VALUES ('{$tagsetName}', {$i}, '{$tag}')";
				$this->criticalQuery($qs);
		 	}
		} else {
			/* query returned false */
			die(mysql_error());
		}
	}
}


/* calls the addTagset constructor **/
new addTagset($tagsetName,$tagsetNameLong,$file,$lang);

?>