<?php
/** @file file.php
 * The document selection page.
 */

$filelist = $sh->getFiles(); 
$tagsets = $sh->getTagsetList();

?>

<div id="fileDiv" class="content" style="display: none;">

<!-- <div class="panel" id="test">
	<button id="testButton">TEST</button>
</div>	 -->

<div class="panel" id="fileImport">
	<h3>Füge neue Datei hinzu</h3>
	<form action="request.php" id="newFileImportForm" method="post" accept-charset="utf-8" enctype="multipart/form-data">
		<p>
		<label for="xmlFile">Datei: </label>
		<input type="file" name="xmlFile" />
		</p>

		<p>
		<label for="xmlFile">Name: </label>
		<input type="text" name="xmlName" />
		</p>

		<p>
		<label for="sigle">Sigle: </label>
		<input type="text" name="sigle" />
		</p>

		<p>
		<label for="tagset">Tagset: </label>
		<select name="tagset" size="1">
			<?php foreach($tagsets as $set):?>
			<option value="<?php echo $set['shortname'];?>"><?php echo "{$set['shortname']} ({$set['longname']}) ";?></option>
			<?php endforeach;?>
		</select>
		</p>

		<p><input type="hidden" name="action" value="importXMLFile" /></p>
		<p><input type="submit" value="Hinzufügen &rarr;" /></p>
	</form>
</div>

<div class="templateHolder" style="display: none;">
  <div id="fileImportPopup">
    <p></p>
    <p><textarea cols="80" rows="10" readonly="readonly"></textarea></p>
  </div>
</div>	

<div class="panel">
	<div class="fileViewRefresh">	
	<h3>Datei öffnen</h3>
		<img src="gui/images/View-refresh.svg" width="20px" height="20px"/>
	</div>
	<div id="files">
		<table id="fileList">
			<tr class="fileTableHeadLine">
				<th></th>
				<th>Dateiname</th>
				<th>POS getaggt</th>
				<th>Morph getaggt</th>
				<th>normalisiert</th>
				<th colspan="2">zuletzt bearbeitet am/von</th>
				<th colspan="2">erstellt am/von</th>
				<th></th>
				<th></th>
		    </tr>

		<!-- this table is filled by file.listFiles() @ file.js -->
		</table>
	</div>

</div>

</div>