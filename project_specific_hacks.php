<?php
/** @file project_specific_hacks.php
 *
 * This file contains ugly hard-coded stuff. It is included into the
 * <head> of the page. Most of the contents here should be refactored
 * at some time (e.g. into the database).
 */



// session_handler.php contains another hack marked with a "HACK"
comment

?>

<script type="text/javascript">
   var cora_project_default_tagsets = {
     // ReF
     6: [3, 8, 14],
     // Übung(en) ReF
     5: [3, 8, 14],
     10: [3, 8, 14],
     11: [3, 8, 14],
     12: [3, 8, 14],
     // Anselm
     7: [2, 3, 10, 11, 13],
     9: [2, 3, 10, 11, 13],
     // Pilotstudie MHD
     13: [2, 3, 10, 11, 17],
     // Default
     'default': [2, 3, 10, 11, 8]
 };

     var cora_external_lemma_link = function(entry) {
        var targetUrl;
	var splitExternalId = function(text) {
	    var re = new RegExp("^(.*) \\[(.*)\\]$");
	    var match = re.exec(text);
	    return (match == null) ? [text, ""] : [match[1], match[2]];
	};

	if(!entry || entry == "") {
	  targetUrl = "http://www.woerterbuchnetz.de/DWB/";
	}
	else {
	  split = splitExternalId(entry);
	  if(split[1] && split[1] != "") {
	    targetUrl = "http://www.woerterbuchnetz.de/DWB?lemid=" + split[1];
	  } else {
	    targetUrl = "http://www.woerterbuchnetz.de/DWB?lemma=" + split[0];
	  }
	}

	window.open(targetUrl, "coraExternalLemmaLink");
     };

</script>