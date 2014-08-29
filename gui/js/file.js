
// ***********************************************************************
// ********** GLOBALE VARIABLEN ******************************************
// ***********************************************************************

var debugMode = false;

// ***********************************************************************
// ********** CLASS FILE ******************************************
// ***********************************************************************

var file = {
    transImportProgressBar: null,
    tagsets: {},
    tagsetlist: [],
    taggers: [],

    initialize: function(){
        this.activateImportForm();
        this.activateTransImportForm();
    },

    resetImportProgress: function() {
	$('tIS_upload').getElement('td.proc').set('class', 'proc proc-running');
	$('tIS_check').getElement('td.proc').set('class', 'proc');
	$('tIS_convert').getElement('td.proc').set('class', 'proc');
	$('tIS_tag').getElement('td.proc').set('class', 'proc');
	$('tIS_import').getElement('td.proc').set('class', 'proc');
	this.transImportProgressBar.set(0);
    },

    updateImportProgress: function(process) {
	var done = "running";
	var get_css_code = function(status_code) {
	    if(status_code == "begun") {
		return "proc-running";
	    } else if(status_code == "success") {
		return "proc-success";
	    } else {
		return "proc-error";
	    }
	}
	if(process.status_CHECK != null) {
	    var c = get_css_code(process.status_CHECK);
	    $('tIS_check').getElement('td.proc').set('class', 'proc').addClass(c);
	}
	if(process.status_XML != null) {
	    var c = get_css_code(process.status_XML);
	    $('tIS_convert').getElement('td.proc').set('class', 'proc').addClass(c);
	}
	if(process.status_TAG != null) {
	    var c = get_css_code(process.status_TAG);
	    $('tIS_tag').getElement('td.proc').set('class', 'proc').addClass(c);
	}
	if(process.status_IMPORT != null) {
	    var c = get_css_code(process.status_IMPORT);
	    $('tIS_import').getElement('td.proc').set('class', 'proc').addClass(c);
	}
	if(process.progress != null) {
	    this.transImportProgressBar.set(process.progress * 100.0);
	}
	if(process.in_progress != null && !process.in_progress) {
	    if(process.progress != null && process.progress == 1.0) {
		done = "success";
	    } else {
		done = "error";
		if(process.output != null) {
		    message = "Beim Importieren sind Fehler aufgetreten.";
		    gui.showTextDialog("Fehler beim Importieren", message, process.output);
		}
	    }
	}
	return done;
    },

    // activates the transcription import form -- in big parts a clone
    // of activateImportForm -- could they be combined?
    activateTransImportForm: function() {
	if($('noProjectGroups')) {
	    return;
	}

	var formname = 'newFileImportTransForm';
        var ref = this;
	var import_progress;
	var import_mbox = new mBox.Modal({
	    title: 'Importieren aus Transkriptionsdatei',
	    content: 'fileImportTransForm',
	    attach: 'importNewTransLink'
	});
	// check if a file has been selected
	$('newFileImportTransForm').getElement('input[type="submit"]').addEvent('click', function(e) {
	    var importfile = $('newFileImportTransForm').getElement('input[name="transFile"]').get('value');
	    if(importfile==null || importfile=="") {
		$$('#newFileImportTransForm p.error_text').show();
		e.stop();
	    } else {
		$$('#newFileImportTransForm p.error_text').hide();
	    }
	});

	// set project default values for tagset association
	// HACK: requires project_specific_hacks.php
	this.setTagsetDefaults($(formname));
	$(formname).getElement('select[name="project"]')
	    .addEvent('change', function(e) { ref.setTagsetDefaults($(formname)); });

	this.transImportProgressBar = new ProgressBar({
	    container: $('tIS_progress'),
	    startPercentage: 0,
	    speed: 500,
	    boxID: 'tISPB_box1',
	    percentageID: 'tISPB_perc1',
	    displayID: 'tISPB_disp1',
	    displayText: true	    
	});

        var iFrame = new iFrameFormRequest(formname,{
            onFailure: function(xhr) {
		// never fires?
       		alert("Speichern nicht erfolgreich: Der Server lieferte folgende Fehlermeldung zurück:\n\n" +
       		      xhr.responseText);
		gui.hideSpinner();
       	    },
	    onRequest: function(){
		import_mbox.close();
		gui.showSpinner();
		ref.resetImportProgress();
		import_progress = new mBox.Modal({
		    title: "Importiere Daten...",
		    content: $('transImportSpinner'),
		    closeOnBodyClick: false,
		    closeOnEsc: false,
		    closeInTitle: false,
		    buttons: [ {title: "OK", addClass: "mform button_green tIS_cb",
			        id: "importCloseButton", 
			        event: function() {
				    this.close();
				}} ],
		    onCloseComplete: function() {
			// reset progress bar here so the animation isn't noticeable
			ref.transImportProgressBar.set(0);
		    }
		});
		$$('.tIS_cb').set('disabled', true);
		import_progress.open();
	    },
	    onComplete: function(response){
		var title="", message="", textarea="";
		try {
		    response = JSON.decode(response);
		} catch(err) {
		    title = "Datei-Import fehlgeschlagen";
		    message = "Der Server lieferte eine ungültige Antwort zurück.";
		    textarea += "Fehler beim Interpretieren der Server-Antwort:\n\t" + err.message;
		    textarea += "\n\nDie Server-Antwort lautete:\n" + response;
		}
		
		ref.transImportProgressBar.set(1);
		if(message!="") {}
		else if(response==null || typeof response.success == "undefined"){
		    title = "Datei-Import fehlgeschlagen";
		    message = "Beim Hinzufügen der Datei ist ein unbekannter Fehler aufgetreten.";
		}
		else if(!response.success){
		    title = "Datei-Import fehlgeschlagen";
		    message = "Beim Hinzufügen der Datei sind Fehler aufgetreten:";
		    for(var i=0;i<response.errors.length;i++){
			textarea += response.errors[i] + "\n";
		    }
		} 
		else {
		    $('tIS_upload').getElement('td.proc').set('class', 'proc proc-success');
		    $('tIS_check').getElement('td.proc').set('class', 'proc proc-running');
		    // set up periodical poll
		    var import_update = new Request({
			method: 'get',
			url: 'request.php?do=getImportStatus',
			initialDelay: 1000,
			delay: 1000,
			limit: 5000,
			onComplete: function(response) {
			    var done = false;
			    try {
				done = ref.updateImportProgress(JSON.decode(response));
			    }
			    catch(err) { }
			    if(done != "running") {
				import_update.stopTimer();
				$$('.tIS_cb').set('disabled', false);
				gui.hideSpinner();
				if(done == "success") {
				    form.reset($(formname));
				    $(formname).getElements('.error_text').hide();
				    ref.setTagsetDefaults($(formname));
				    ref.listFiles();
				    gui.showNotice('ok', "Datei erfolgreich importiert.");
				} else {
				    gui.showNotice('error', "Importieren fehlgeschlagen.");
				}
			    }
			}
		    });
		    import_update.startTimer();
		    return;
		}

//		    title = "Datei-Import erfolgreich";
//		    message = "Die Datei wurde erfolgreich hinzugefügt.";
//		    if((typeof response.warnings !== "undefined") && response.warnings.length>0) {
//			message += " Das System lieferte ";
//			message += response.warnings.length>1 ? response.warnings.length + " Warnungen" : "eine Warnung";
//			message += " zurück:";
//
//			for(var i=0;i<response.warnings.length;i++){
//			    textarea += response.warnings[i] + "\n";
//			}
//		    }

		gui.showTextDialog(title, message, textarea);
		import_progress.close();
		gui.hideSpinner();
            }
	});
    },

    // activates the XML import form
    activateImportForm: function(){
	if($('noProjectGroups')) {
	    return;
	}

	var formname = 'newFileImportForm';
        var ref = this;

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

	// set project default values for tagset association
	// HACK: requires project_specific_hacks.php
	this.setTagsetDefaults($(formname));
	$(formname).getElement('select[name="project"]')
	    .addEvent('change', function(e) { ref.setTagsetDefaults($(formname)); });
	
        var iFrame = new iFrameFormRequest(formname,{
            onFailure: function(xhr) {
		// never fires?
       		alert("Speichern nicht erfolgreich: Der Server lieferte folgende Fehlermeldung zurück:\n\n" +
       		      xhr.responseText);
       	    },
	    onRequest: function(){
		import_mbox.close();
		gui.showSpinner({message: 'Importiere Daten...'});
	    },
	    onComplete: function(response){
		var title="", message="", textarea="";
		response = JSON.decode(response);
		
		if(response==null || typeof response.success == "undefined"){
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
		    ref.setTagsetDefaults($(formname));
		    ref.listFiles();
                }

		gui.showTextDialog(title, message, textarea);
		gui.hideSpinner();
            }
	});
    },
    
    /* Function: setTagsetDefaults

       Sets default values for tagset associations depending on the
       selected project.

       Project default values are retrieved from the global variable
       cora_projects_default_tagsets, which should probably be
       refactored in the future.  The table updated by this function
       is typically not visible in the GUI except for administrators,
       but still important for the import process.

       Parameters:
         form - the form element to update
     */
    setTagsetDefaults: function(form) {
	var pid = form.getElement('select[name="project"]')
	    .getSelected()[0]
	    .get('value');
	var tlist = cora_project_default_tagsets[pid];
	if(tlist == undefined || tlist == null || !tlist) {
	    tlist = cora_project_default_tagsets['default'];
	}
	form.getElement('table.tagset-list')
	    .getElements('input').each(function(input) {
		var checked = tlist.contains(input.value.toInt()) ? "yes" : "";
		input.set('checked', checked);
	    });
    },
    
    copyTagset: function(){
        
    },
    
    /* Function: preprocessTagset

       Parses tagset data and builds HTML code for drop-down boxes.
    */
    preprocessTagset: function(tslist) {
	var ref = this;
	var splitAtFirstDot;

	splitAtFirstDot = function(tag) {
	    var dotidx = tag.indexOf('.');
	    var pos = null; var morph = null;
	    if(dotidx<0 || dotidx==(tag.length-1)) {
		pos = tag;
	    }
	    else {
		pos = tag.substr(0, dotidx);
		morph = tag.substr(dotidx+1);
	    }
	    return [pos, morph];
	};

	// collect tags into arrays, splitting POS if applicable
	Object.each(tslist, function(tagset, tclass) {
	    if(typeof tagset.tags === 'undefined'
	       || tagset.tags.length < 1) return;
	    ref.tagsets[tclass] = {'tags': [],
				   'html': ""};
	    if(tclass === "POS") {
		ref.tagsets["morph"] = {};
	    }
	    Array.each(tagset.tags, function(data) {
		var tag;
		if(data.needs_revision==1) return;
		if(tclass === "POS") {
		    tag = splitAtFirstDot(data.value);
		    ref.tagsets["POS"]['tags'].push(tag[0]);
		    if(tag[1] != null) {
			if(!(tag[0] in ref.tagsets["morph"])) {
			    ref.tagsets["morph"][tag[0]] = {'tags': [],
							    'html': ""};
			}
			ref.tagsets["morph"][tag[0]]['tags'].push(tag[1]);
		    }
		}
		else {
		    tag = data.value;
		    ref.tagsets[tclass]['tags'].push(tag);
		}
	    });
	});

	if("POS" in ref.tagsets) {
	    ref.tagsets["POS"]['tags'] = ref.tagsets["POS"]['tags'].unique();
	}
	
	// generate HTML code
	Object.each(ref.tagsets, function(tagset, tclass) {
	    if(tclass==="morph") return;
	    var optgroup_tclass = new Element('optgroup',
                                              {'label': 'Alle Tags'});
	    Array.each(tagset.tags, function(tag) {
                optgroup_tclass.grab(new Element('option',
                                                 {text: tag, value: tag}));
		if(tclass==="POS") {
		    var optgroup_morph = new Element('optgroup',
                                                     {'label': "Alle Tags für '"
                                                                +tag+"'"});
                    // new Elements();
		    if(ref.tagsets['morph'][tag]) {
			Array.each(ref.tagsets['morph'][tag]['tags'], function(morph) {
                            optgroup_morph.grab(new Element('option',
                                                            {text: morph,
                                                             value: morph}));
			});
		    }
		    else {
                        optgroup_morph.grab(new Element('option',
                                                        {text: '--',
                                                         value: '--'}));
			ref.tagsets['morph'][tag] = {'tags': ['--']};
		    }
		    ref.tagsets['morph'][tag]['elems'] = optgroup_morph;
		}
	    });
	    ref.tagsets[tclass]['elems'] = optgroup_tclass;
	});
    },
    
    openFile: function(fileid) {
        var ref = this;
	var emf = (edit.editorModel!=null && edit.editorModel.fileId!=null);

	if (emf && !edit.editorModel.confirmClose()) {
	    return false;
	}
        
        var lock = new Request.JSON({
            url:'request.php',
    	    onSuccess: function(data, text) {
    		if(data.success) {
        	    var request = new Request.JSON({
        		url: 'request.php',
        		onComplete: function(fileData) {        		         
        		    if(fileData.success){
				ref.tagsetlist = fileData.data.tagsets;
				ref.taggers = fileData.data.taggers;

       				// load tagset
        		        var afterLoadTagset = function() {
				    // code that depends on the tagsets being fully loaded
				    edit.editorModel = new EditorModel(fileid, fileData.maxLinesNo, fileData.lastEditedRow, fileData.lastPage);
				    $('editTabButton').show();
				    default_tab = 'edit';
				    gui.changeTab('edit');
				};
				
        		        new Request.JSON({
        		            url: "request.php",
        		            async: true,
				    method: 'get',
				    data: {'do':'fetchTagsetsForFile','file_id':fileid},
        		            onComplete: function(response){
					// TODO: error handling?!
					ref.preprocessTagset(response['data']);
					afterLoadTagset();
        		            }
        		        }).send();
				
				$('currentfile').set('text','['+fileData.data.sigle+'] '+fileData.data.fullname);
				ref.listFiles();
			    }
        		}
        	    }).get({'do': 'openFile', 'fileid': fileid});
    		} else {
    		    var msg = "Das Dokument wird bereits bearbeitet von Benutzer '" + data.lock.locked_by + "' seit " +
    			data.lock.locked_since + ".";
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
		new Request.JSON({
		    url: "request.php",
		    onSuccess: function(data) {
			if (!data.success) {
			    console.log("Error closing file?");
			}
			ref.listFiles();
			edit.editorModel = null;
			default_tab = 'file';
			gui.changeTab(default_tab);
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
			ref.listFiles();
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
    		onSuccess: function(status, text) {
		    if(!status['success']) {
			gui.showNotice('error',
                                       "Fehler beim Laden der Dateiliste.");
			return;
		    }

		    var files_div = $('files').empty();
		    var filesArray = status['data'];

		    var fileHash = {};
		    var projectNames = {};
                    var projectIds = [];
		    filesArray.each(function(file){
			var prj = file.project_id;
			if(fileHash[prj]) {
			    fileHash[prj].push(file);
			} else {
			    fileHash[prj] = [file];
			}
			projectNames[prj] = file.project_name;
		    });
                    // sort projects alphabetically by name
                    projectIds = Object.keys(projectNames);
                    projectIds.sort(function(obj1, obj2) {
                        return projectNames[obj1].localeCompare(projectNames[obj2]);
                    });
		    
		    ref.fileHash = fileHash;
		    Array.each(projectIds, function(project){
                        var fileArray = fileHash[project];
			var project_div = $('fileGroup').clone();
			var project_table = project_div.getElement('table');
			project_div.getElement('h4.projectname').empty().appendText(projectNames[project]);
			fileArray.each(function(file) {
			    project_table.adopt(ref.renderTableLine(file));
			});
			project_div.inject(files_div);
		    });
                    gui.addToggleEvents(files_div.getElements('.clappable'));
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
        if((file.creator_name == userdata.name) || userdata.admin){
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

	/* the following lines have been uncommented as the field is
	 * not currently used */
        tr.adopt(new Element('td',{ html: file.changed }));
        tr.adopt(new Element('td',{ html: file.changer_name }));                    
        tr.adopt(new Element('td',{ html: file.created }));
        tr.adopt(new Element('td',{ html: file.creator_name }));
        tr.adopt(new Element('td',{'class':'exportFile'}).adopt(
	    new Element('a',{ html: 'Exportieren...', 'class': 'exportFileLink' })
		.addEvent('click', function(){ ref.exportFile(file.id); } )));
        if(userdata.admin){
            tr.adopt(new Element('td',{'class':'editTagsetAssoc'}).adopt(
		new Element('a',{ html: 'Tagsets...', 'class': 'editTagsetAssocLink' })
		    .addEvent('click', function(){ ref.editTagsetAssoc(file.id,file.fullname); } )));
        } else { tr.adopt(new Element('td')); }
        if((file.opened == userdata.name ) || (opened && userdata.admin)){
            tr.adopt(new Element('td',{'class':'closeFile'}).adopt(
		new Element('a',{ html: 'Schließen', 'class': 'closeFileLink' })
		    .addEvent('click', function(){ ref.closeFile(file.id); } )));
        } else { tr.adopt(new Element('td')); }
        return tr;
    },
    
    deleteFile: function(fileid,filename){
        var ref = this;

        var dialog = "Soll das Dokument '" + filename + "' wirklich gelöscht werden? Dieser Schritt kann nicht rückgängig gemacht werden!";
        if(!confirm(dialog))
            return;
        
        var req = new Request.JSON(
    	    {'url': 'request.php?do=deleteFile',
    	     'async': false,
    	     'data': 'file_id='+fileid,
    	     onFailure: function(xhr) {
    		 alert("Fehler: Der Server lieferte folgende Fehlermeldung zurück:\n\n" + xhr.responseText);
    	     },
    	     onSuccess: function(status, blubb) {
		 if(!status || !status.success) {
		     gui.showNotice('error', "Konnte Datei nicht löschen.");
		     if(status.error_msg) {
    			 alert("Fehler: Der Server lieferte folgende Fehlermeldung zurück:\n\n" + status.error_msg);
		     }
		     else {
    			 alert("Ein unbekannter Fehler ist aufgetreten.");
		     }
		 }
		 else {
		     gui.showNotice('ok', "Datei gelöscht.");
		 }
    		 ref.listFiles();
    	     }
    	    }
    	).post();
    },

    /* Function: editTagsetAssoc

       Shows a dialog with the associated tagsets for a file.

       Parameters:
         fileid - ID of the file
	 fullname - Name of the file
     */
    editTagsetAssoc: function(fileid, fullname) {
	var contentdiv = $('tagsetAssociationTable');
	var spinner = new Spinner(contentdiv);
	var content = new mBox.Modal({
	    title: "Tagset-Verknüpfungen für '"+fullname+"'",
	    content: contentdiv,
	    buttons: [ {title: "OK", addClass: "mform button_green",
			id: "editTagsetAssocOK",
			event: function() {
			    this.close();
			}}
		     ]
	});

	content.open();
	spinner.show();

	new Request.JSON(
    	    {'url': 'request.php',
    	     'async': false,
    	     onFailure: function(xhr) {
    		 alert("Fehler: Der Server lieferte folgende Fehlermeldung zurück:\n\n" + xhr.responseText);
		 spinner.destroy();
		 content.close();
    	     },
    	     onSuccess: function(tlist, x) {
		 // show tagset associations
		 if(!tlist['success']) {
		     alert("Fehler: Konnte Tagset-Verknüpfungen nicht laden.");
		     spinner.destroy();
		     content.close();
		 }
		 contentdiv.getElement('table.tagset-list')
		     .getElements('input').each(function(input) {
			 var checked = tlist['data'].contains(input.value) ? "yes" : "";
			 input.set('checked', checked);
		     });
		 spinner.destroy();
    	     }
    	    }
    	).get({'do': 'getTagsetsForFile', 'file_id': fileid});
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
		     window.location = 'request.php?do=exportFile&fileid='+fileid+'&format='+format;
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
    $$('div.fileViewRefresh a.collapseAll').addEvent('click',function(e){ e.stop(); $$('div#files .clappable div').hide(); });

    file.listFiles();
    if(userdata.currentFileId)
        file.openFile(userdata.currentFileId);
});
