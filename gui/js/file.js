
// ***********************************************************************
// ********** GLOBALE VARIABLEN ******************************************
// ***********************************************************************

var debugMode = false;

var fileTagset = {
    pos: null,
    morph: null,
    posHTML: "",
    morphHTML: {}
};

// ***********************************************************************
// ********** CLASS FILE ******************************************
// ***********************************************************************

var file = {
    initialize: function(){
        this.activateImportForm();
    },

    activateImportForm: function(){
	if($('noProjectGroups')) {
	    return;
	}

	var formname = 'newFileImportForm';
        var ref = this;
	var spinner;

	var import_mbox = new mBox.Modal({
	    title: 'Importieren aus CorA-XML-Format',
	    content: 'fileImportForm',
	    attach: 'importNewXMLLink'
	});

	// check if a file has been selected
	$('newFileImportForm').getElement('input[type="submit"]').addEvent('click', function(e) {
	    var importfile = $('newFileImportForm').getElement('input[name="xmlFile"]').get('value');
	    if(importfile==null || importfile=="") {
		$$('#newFileImportForm p.error_text').show();
		e.stop();
	    } else {
		    $$('#newFileImportForm p.error_text').hide();
	    }
	});
	
        var iFrame = new iFrameFormRequest(formname,{
            onFailure: function(xhr) {
		// never fires?
       		alert("Speichern nicht erfolgreich: Der Server lieferte folgende Fehlermeldung zurück:\n\n" +
       		      xhr.responseText);
       	    },
	    onRequest: function(){
		import_mbox.close();
		$('overlay').show();
		spinner = new Spinner($('overlay'),
				      {message: "Importiere Daten..."});
		spinner.show();
	    },
	    onComplete: function(response){
		var title="", message="", textarea="";
		response = JSON.decode(response);
		
		if(response==null){
		    title = "Datei-Import fehlgeschlagen";
		    message = "Beim Hinzufügen der Datei ist ein unbekannter Fehler aufgetreten.";
		}
		else if(!response.success){
		    title = "Datei-Import fehlgeschlagen";
		    message  = "Beim Hinzufügen der Datei ";
		    message += response.errors.length>1 ? "sind " + response.errors.length : "ist ein";
		    message += " Fehler aufgetreten:";

		    for(var i=0;i<response.errors.length;i++){
			textarea += response.errors[i] + "\n";
		    }
		} 
		else { 
		    title = "Datei-Import erfolgreich";
		    message = "Die Datei wurde erfolgreich hinzugefügt.";
		    if((typeof response.warnings !== "undefined") && response.warnings.length>0) {
			message += " Das System lieferte ";
			message += response.warnings.length>1 ? response.warnings.length + " Warnungen" : "eine Warnung";
			message += " zurück:";

			for(var i=0;i<response.warnings.length;i++){
			    textarea += response.warnings[i] + "\n";
			}
		    }

		    form.reset($(formname));
		    $(formname).getElements('.error_text').hide();
		    ref.listFiles();
                }

		if(textarea!='') {
		    $('fileImportPopup').getElement('p').set('html', message);
		    $('fileImportPopup').getElement('textarea').set('html', textarea);
		    message = 'fileImportPopup';
		}
		new mBox.Modal({
		    title: title,
		    content: message,
		    closeOnBodyClick: false,
		    buttons: [ {title: "OK"} ]
		}).open();

		spinner.hide();
		$('overlay').hide();                
            }
	});
    },
    
    copyTagset: function(){
        
    },
    
    /* Function: preprocessTagset

       Parses tagset data and builds HTML code for drop-down boxes.
    */
    preprocessTagset: function(taglist) {
	var posarray = new Array();
	fileTagset.pos = new Array();
	fileTagset.morph = {};

	Array.each(taglist, function(data) {
	    var tag = data.value;
	    var pos = "", morph = "";
	    // split into POS + morph
	    var dotidx = tag.indexOf('.');
	    if(dotidx<0 || dotidx==(tag.length-1)) {
		pos = tag;
	    }
	    else {
		pos = tag.substr(0, dotidx);
		morph = tag.substr(dotidx+1);
	    }

	    // add POS
	    posarray.push(pos);
	    // add morph
	    if(morph !== "") {
		if(!(pos in fileTagset.morph)) {
		    fileTagset.morph[pos] = new Array();
		}
		fileTagset.morph[pos].push(morph);
	    }
	});
	fileTagset.pos = posarray.unique();
	
	// generate HTML code
	var posHTML = "";
	Array.each(fileTagset.pos, function(pos) {
	    var morphHTML = "";
	    posHTML += '<option value="';
	    posHTML += pos;
	    posHTML += '">';
	    posHTML += pos;
	    posHTML += "</option>";
	    if(fileTagset.morph[pos]) {
		Array.each(fileTagset.morph[pos], function(morph) {
		    morphHTML += '<option value="';
		    morphHTML += morph;
		    morphHTML += '">';
		    morphHTML += morph;
		    morphHTML += "</option>";
		});
	    }
	    else {
		fileTagset.morph[pos] = new Array("--");
		morphHTML = '<option value="--">--</option>';
	    }
	    fileTagset.morphHTML[pos] = morphHTML;
	});
	fileTagset.posHTML = posHTML;
    },
    
    openFile: function(fileid) {
        var ref = this;
        
        var lock = new Request.JSON({
            url:'request.php',
    	    onSuccess: function(data, text) {
    		if(data.success) {
        	    var request = new Request.JSON({
        		url: 'request.php',
        		onComplete: function(fileData) {        		         
        		    if(fileData.success){
       				// load tagset
        		        var afterLoadTagset = function() {
				    // code that depends on the tagsets being fully loaded
				    edit.editorModel = new EditorModel(fileid, fileData.maxLinesNo, fileData.lastEditedRow, fileData.lastPage);
				    $('editTabButton').show();
				    default_tab = 'edit';
				    changeTab('edit');
				};
				
        		        new Request.JSON({
        		            url: "request.php",
        		            async: true,
				    method: 'get',
				    data: {'do':'fetchTagset','tagset_id':fileData.data.tagset_id,'limit':'legal'},
        		            onComplete: function(response){
					ref.preprocessTagset(response);
					afterLoadTagset();
        		            }
        		        }).send();
				
				$('currentfile').set('text',fileData.data.fullname);
				ref.listFiles();
			    }
        		}
        	    }).get({'do': 'openFile', 'fileid': fileid});
    		} else {
    		    var msg = "Das Dokument wird zur Zeit bereits bearbeitet:" + data.lock.locked_by + ", " +
    			data.lock.locked_since;
    		    alert(msg);
    		}
    	    }
    	});
        lock.get({'do': 'lockFile', 'fileid': fileid});
    },
    
    closeFile: function(fileid){        
        var ref = this;
	var emf = (edit.editorModel!=null && edit.editorModel.fileId==fileid);

	if (emf) {
	    if (edit.editorModel.confirmClose()) {
		$('overlay').show();
		new Request({
		    url: "request.php",
		    onSuccess: function(data) {
			if (!data) {
			    console.log("Error closing file?");
			}
			ref.listFiles();
			edit.editorModel = null;
			default_tab = 'file';
			changeTab(default_tab);
			$('menuRight').hide();
			$('editTable').hide();
			$('editTabButton').hide();
			$('currentfile').set('text','');
			$('overlay').hide();
		    }
		}).get({'do': 'unlockFile', 'fileid': fileid});
	    }
	} else {
	    var doit = confirm("Sie sind dabei, eine Datei zu schließen, die aktuell nicht von Ihnen bearbeitet wird. Falls jemand anderes diese Datei zur Zeit bearbeitet, könnten Datenverluste auftreten.");
	    if (doit) {
		new Request({
		    url:"request.php",
		    onSuccess: function(data){
			if(data){
			    ref.listFiles();
			}
		    }
		}).get({'do': 'unlockFile', 'fileid': fileid, 'force': true});
	    }
	}
    },
    
    listFiles: function(){
	if($('noProjectGroups')) {
	    return;
	}

        var ref = this;
        var files = new Request.JSON({
            url:'request.php',
    		onSuccess: function(filesArray, text) {
		    var files_div = $('files').empty();

		    var fileHash = {};
		    var projectNames = {};
		    filesArray.each(function(file){
			var prj = file.project_id;
			if(fileHash[prj]) {
			    fileHash[prj].push(file);
			} else {
			    fileHash[prj] = [file];
			}
			projectNames[prj] = file.project_name;
		    });
		    
		    ref.fileHash = fileHash;
		    Object.each(fileHash, function(fileArray, project){
			var project_div = $('fileGroup').clone();
			var project_table = project_div.getElement('table');
			project_div.getElement('h4.projectname').set('html', projectNames[project]);
			fileArray.each(function(file) {
			    project_table.adopt(ref.renderTableLine(file));
			});
			project_div.inject(files_div);
		    });
		}
    	});
        files.get({'do': 'listFiles'});
        
    },

    renderTableLine: function(file){
	/* TODO: isn't it completely unnecessary to completely
	 * re-create the whole table each time a change occurs?
	 * couldn't this be done more efficiently? */
	var ref = this;
        var opened = file.opened ? 'opened' : '';
	var displayed_name = '';
	if(file.sigle!=null && file.sigle!=''){
	    displayed_name = '[' + file.sigle + '] ';
	}
	displayed_name += file.fullname;
        var tr = new Element('tr',{id: 'file_'+file.id, 'class': opened});
        if((file.byUser == userdata.name) || userdata.admin){
            var delTD = new Element('td',{ html: '<img src="gui/images/proxal/delete.ico" />', 'class': 'deleteFile' });
            delTD.addEvent('click', function(){ ref.deleteFile(file.id,file.fullname); } );
            tr.adopt(delTD);
        } else {
	    tr.adopt(new Element('td'));
	}
        var chkImg = '<img src="gui/images/chk_on.png" />';
        tr.adopt(new Element('td',{'class': 'filename'}).adopt(new Element('a',{ html: displayed_name }).addEvent('click',function(){ ref.openFile(file.id); })));
//        tr.adopt(new Element('td',{ 'class': 'tagStatusPOS', html: (file.POS_tagged == 1) ? chkImg : '--' }));
//        tr.adopt(new Element('td',{ 'class': 'tagStatusMorph', html: (file.morph_tagged == 1) ? chkImg : '--' }));

//	if(userdata.usenorm) {
//            tr.adopt(new Element('td',{ 'class': 'tagStatusNorm', html: (file.norm == 1) ? chkImg : '--' }));
//	}
	/* the following lines have been uncommented as the field is
	 * not currently used */
        tr.adopt(new Element('td',{ html: file.changed }));
        tr.adopt(new Element('td',{ html: file.changer_name }));                    
        tr.adopt(new Element('td',{ html: file.created }));
        tr.adopt(new Element('td',{ html: file.creator_name }));
        tr.adopt(new Element('td',{'class':'exportFile'}).adopt(new Element('a',{ html: 'Exportieren...', 'class': 'exportFileLink' }).addEvent('click', function(){ ref.exportFile(file.id); } )));
        if((file.opened == userdata.name ) || (opened && userdata.admin)){
            tr.adopt(new Element('td',{'class':'closeFile'}).adopt(new Element('a',{ html: 'Schließen', 'class': 'closeFileLink' }).addEvent('click', function(){ ref.closeFile(file.id); } )));
        } else {
	    tr.adopt(new Element('td'));
	}
        return tr;
    },
    
    deleteFile: function(fileid,filename){
        var ref = this;

        var dialog = "Soll das Dokument '" + filename + "' wirklich gelöscht werden? Dieser Schritt kann nicht rückgängig gemacht werden!";
        if(!confirm(dialog))
            return;
        
        var req = new Request(
    	    {'url': 'request.php?do=deleteFile',
    	     'async': false,
    	     'data': 'file_id='+fileid,
    	     onFailure: function(xhr) {
    		 alert("Fehler: Der Server lieferte folgende Fehlermeldung zurück:\n\n" + xhr.responseText);
    	     },
    	     onSuccess: function(data, xml) {    	         
    		     ref.listFiles();
    	     }
    	    }
    	).post();
    },

    exportFile: function(fileid){
	new mBox.Modal({
	    content: 'fileExportPopup',
	    title: 'Datei exportieren',
	    buttons: [
		{title: 'Abbrechen', addClass: 'mform'},
		{title: 'Exportieren', addClass: 'mform button_green',
		 event: function() {
		     var format = $('fileExportFormat').getSelected()[0].get('value');
		     this.close();
		     window.location = 'request.php?do=exportFile&fileId='+fileid+'&format='+format;
		 }
		}
	    ]
	}).open();
    }
};


// ***********************************************************************
// ********** DOMREADY BINDINGS ******************************************
// ***********************************************************************

window.addEvent('domready', function() {
    file.initialize();    
                    
    $$('div.fileViewRefresh img').addEvent('click',function(e){ e.stop(); file.listFiles() });
    
    file.listFiles();
    if(userdata.currentFileId)
        file.openFile(userdata.currentFileId);
});
