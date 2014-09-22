
// ***********************************************************************
// ********** Global Variables *******************************************
// ***********************************************************************

var debugMode = false;

// ***********************************************************************
// ********** Projects and Files *****************************************
// ***********************************************************************

/* Class: cora.projects

   Acts as a wrapper for an array containing all project information,
   including associated files.

   This class allows simple access to projects via their ID, sends
   AJAX requests to update the project information, and stores
   functions that should be called whenever the project list updates.
*/
cora.projects = {
    initialized: false,
    data: [],
    byID: {},
    onUpdateHandlers: [],
    onInitHandlers: [],

    /* Function: get

       Return a project by ID.

       Parameters:
        pid - ID of the project to be returned
     */
    get: function(pid) {
        var idx = this.byID[pid];
        if(idx == undefined)
            return Object();
        return this.data[idx];
    },

    /* Function: getAll

       Return an array containing all projects.
    */
    getAll: function() {
        return this.data;
    },

    /* Function: isEmpty

       Returns a boolean indicating whether the project list is empty.
    */
    isEmpty: function() {
        return (typeof(this.data) === 'undefined' || this.data.length == 0);
    },

    /* Function: onUpdate

       Add a callback function to be called whenever the project list
       is updated.

       Parameters:
        fn - function to be called
     */
    onUpdate: function(fn) {
        if(typeof(fn) == "function")
            this.onUpdateHandlers.push(fn);
        return this;
    },

    /* Function: onInit

       Add a callback function to be called after the project list has
       been first initialized (or immediately if it already has).

       Parameters:
        fn - function to be called
     */
    onInit: function(fn) {
        if(typeof(fn) == "function") {
            if(this.initialized)
                fn();
            else
                this.onInitHandlers.push(fn);
        }
        return this;
    },

    /* Function: performUpdate
       
       Perform a server request to update the project data.  Calls any
       handlers previously registered via onUpdate().

       Parameters:
        fn - function to be called after successful request
     */
    performUpdate: function(fn){
        var ref = this;
        var files = new Request.JSON({
            url:'request.php',
    		onSuccess: function(status, text) {
                    ref.data = status['data'];
                    ref.byID = {};
                    Array.each(ref.data, function(prj, idx) {
                        ref.byID[prj.id] = idx;
                    });
                    if(!ref.initialized) {
                        ref.initialized = true;
                        Array.each(ref.onInitHandlers, function(handler) {
                            handler();
                        });
                    }
                    Array.each(ref.onUpdateHandlers, function(handler) {
                        handler(status, text);
                    });
                    if(typeof(fn) == "function")
                        fn(status, text);
		}
    	});
        files.get({'do': 'getProjectsAndFiles'});
        return this;
    }
};

// ***********************************************************************
// ********** Tagset Information *****************************************
// ***********************************************************************

/* Class: cora.tagsets

   Acts as a wrapper for an array containing all tagset information.
*/
cora.tagsets = {
    initialized: false,
    data: [],
    byID: {},
    onInitHandlers: [],

    /* Function: get

       Return a tagset by ID.

       Parameters:
        pid - ID(s) of the tagset(s) to be returned
     */
    get: function(pid) {
        if(pid instanceof Array) {
            var data = [];
            pid.each(function(p) {
                var idx = this.byID[p];
                if(idx != undefined)
                    data.push(this.data[idx]);
            }.bind(this));
            return data;
        }
        else {
            var idx = this.byID[pid];
            if(idx == undefined)
                return Object();
            return this.data[idx];
        }
    },

    /* Function: getAll

       Return an array containing all projects.
    */
    getAll: function() {
        return this.data;
    },

    /* Function: onInit

       Add a callback function to be called after the project list has
       been first initialized (or immediately if it already has).

       Parameters:
        fn - function to be called
     */
    onInit: function(fn) {
        if(typeof(fn) == "function") {
            if(this.initialized)
                fn();
            else
                this.onInitHandlers.push(fn);
        }
        return this;
    },

    /* Function: makeMultiSelectBox
       
       Creates and returns a dropdown box using MultiSelect.js with all
       available tagsets as entries.
       
       Parameters:
        tagsets - Array of tagset IDs that should be pre-selected
        name    - Name of the input array
        ID      - ID of the selector div
    */
    makeMultiSelectBox: function(tagsets, name, id) {
        var multiselect = new Element('div',
                                      {'class': 'MultiSelect',
                                       'id':    id});
        Array.each(this.data, function(tagset, idx) {
            var entry = new Element('input',
                                    {'type': 'checkbox',
                                     'id':   name+'_'+tagset.id,
                                     'name': name+'[]',
                                     'value': tagset.id});
            var textr = "["+tagset['class']+"] "+tagset.longname+" (id: "+tagset.id+")";
            var label = new Element('label',
                                    {'for':  name+'_'+tagset.id,
                                     'text': textr});
            if(tagsets.some(function(el){ return el == tagset.id; }))
                entry.set('checked', 'checked');
            multiselect.grab(entry).grab(label);
        });
        new MultiSelect(multiselect,
                        {monitorText: ' Tagset(s) ausgewählt'});
        return multiselect;
    },

    /* Function: performUpdate
       
       Analogous to cora.projects.performUpdate(), this function is
       supposed to update the tagset information.  Currently doesn't
       perform a server request, but reads the data from another
       PHP-generated variable.  (HACK)
     */
    performUpdate: function(){
        this.data = PHP_tagsets;
        this.byID = {};
        Array.each(this.data, function(prj, idx) {
            this.byID[prj.id] = idx;
        }.bind(this));
        if(!this.initialized) {
            this.initialized = true;
            Array.each(this.onInitHandlers, function(handler) {
                handler();
            });
        }
        return this;
    }
};

// ***********************************************************************
// ********** Importing files ********************************************
// ***********************************************************************

/* Class: fileImporter

   Handles file importing: manages the dialogs, sends server requests,
   and shows import progress when importing from text files.
 */
cora.fileImporter = {
    transImportProgressBar: null,
    transImportProgressDialog: null,
    waitForInitializedObjects: 2,

    /* Function: initialize

       Initializes the file importer: sets up the import dialogs.
     */
    initialize: function() {
        this._activateImportFormXML();
        this._activateImportFormTrans();
        cora.projects.onInit(this._objInitialized.bind(this));
        cora.tagsets.onInit(this._objInitialized.bind(this));
    },

    /* Function: _objInitialized

       Called when an object that we're waiting for is initialized;
       decreases the wait counter and enables the import buttons if
       applicable.
     */
    _objInitialized: function() {
        this.waitForInitializedObjects--;
        if(!this.waitForInitializedObjects) {
            $('importNewXMLLink').set('disabled', false);
            $('importNewTransLink').set('disabled', false);
        }
    },

    /* Function: _activateImportFormXML

       Activates the form for importing CorA XML files.  Needs to be
       called only once when initializing.
     */
    _activateImportFormXML: function() {
        var ref = this;
        var importform = $('newFileImportForm');
        var mbox = new mBox.Modal({
            title: "Importieren aus CorA-XML-Format",
            content: 'fileImportXMLForm',
            attach: 'importNewXMLLink',
            closeOnBodyClick: false,
        });
        this._prepareImportFormEvents(importform, 'xmlFile', false);

        new iFrameFormRequest(importform, {
            onRequest: function() {
                mbox.close();
                gui.showSpinner({message: 'Importiere Daten...'});
            },
            onComplete: function(response) {
                var success = ref.showImportResponseDialog(response);
                if(success) {
                    ref.resetImportForm(importform);
                    cora.projects.performUpdate();
                }
                gui.hideSpinner();
            },
            onFailure: function() {
                gui.showNotice('error', "Import aus unbekannten Gründen fehlgeschlagen!");
                gui.hideSpinner();
            }
        });
    },

    /* Function: _activateImportFormTrans

       Activates the form for importing transcription files.  Needs to
       be called only once when initializing.
    */
    _activateImportFormTrans: function() {
        var ref = this;
        var importform = $('newFileImportTransForm');
        var mbox = new mBox.Modal({
            title: "Importieren aus Textdatei",
            content: 'fileImportTransForm',
            attach: 'importNewTransLink',
            closeOnBodyClick: false,
        });
        this._prepareImportFormEvents(importform, 'transFile', true);
        this._activateImportElementsTrans();

        new iFrameFormRequest(importform, {
            onRequest: function() {
                mbox.close();
                gui.showSpinner();
                ref.transImportProgressDialog.open();
            },
            onComplete: function(response) {
                ref.transImportProgressBar.set(1);
                var success = ref.showImportResponseDialog(response, true);
                if(success) {
                    ref.startImportProgressTimer(function(status) {
                        gui.hideSpinner();
		        if(status.done == "success") {
                            ref.resetImportForm(importform);
                            cora.projects.performUpdate();
			    gui.showNotice('ok', "Datei erfolgreich importiert.");
		        } else {
                            gui.showTextDialog("Importieren fehlgeschlagen",
                                               status.message, status.output);
			    gui.showNotice('error', "Importieren fehlgeschlagen.");
		        }
                    });
                }
                else {
                    ref.transImportProgressDialog.close();
                    gui.hideSpinner();
                }
            },
            onFailure: function() {
                gui.showNotice('error', "Import aus unbekannten Gründen fehlgeschlagen!");
                gui.hideSpinner();
            }
        });
    },

    /* Function: _activateImportElementsTrans

       Sets up progress bar and dialog window for import from
       transcription file.
     */
    _activateImportElementsTrans: function() {
        var ref = this;
        this.transImportProgressBar = new ProgressBar({
	    container: $('tIS_progress'),
	    startPercentage: 0,
	    speed: 500,
	    boxID: 'tISPB_box1',
	    percentageID: 'tISPB_perc1',
	    displayID: 'tISPB_disp1',
	    displayText: true	    
        });
        this.transImportProgressDialog = new mBox.Modal({
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
                ref.resetImportProgress();
	    }
        });
        this.resetImportProgress();
    },

    /* Function: _prepareImportFormEvents
       
       Sets up events for the import form.  Called internally by
       activateImportForm.

       Parameters:
        myform - The form element to set events for
        fin    - Name of the file selector element
        trans  - Whether we're preparing for the transcription import form
     */
    _prepareImportFormEvents: function(myform, fin, trans) {
        cora.projects.onUpdate(function() {
            this.updateProjectList(myform);
            this.updateTagsetList(myform);
            if(trans)
                this.checkForCmdImport(myform);
        }.bind(this));
        cora.tagsets.onInit(function() {
            this.updateTagsetList(myform);
        }.bind(this));

        myform.getElement('select[name="project"]')
              .addEvent('change', function(e) {
                  this.updateTagsetList(myform);
                  if(trans)
                      this.checkForCmdImport(myform);
              }.bind(this));

        myform.getElement('input[type="submit"]')
              .addEvent('click', function(e) {
                  var file = myform.getElement('input[name="'+fin+'"]')
                      .get('value');
                  if(!file || file.length === 0) {
                      myform.getElements('p.error_text_import').show();
                      e.stop();
                  }
                  else {
                      myform.getElements('p.error_text').hide();
                  }
              });
    },

    /* Function: showImportResponseDialog

       Parses the server response after an import request and displays
       an informational dialog to the user.

       Parameters:
        re_json - The server response as a JSON string
        error_only - If true, only show a dialog on error,
                     and just return true otherwise
       
       Returns:
        True if the server response indicates a successful import,
        false otherwise
     */
    showImportResponseDialog: function(re_json, error_only) {
        var title="", message="", textarea="", re;
        var success=false;
        try {
            re = JSON.decode(re_json);
        }
        catch (err) {
            title = "Fehlerhafte Server-Antwort";
            message = "Der Server lieferte eine ungültige Antwort zurück. "
                    + "Das Importieren der Datei war möglicherweise nicht erfolgreich.";
            textarea = "Fehler beim Interpretieren der Server-Antwort:\n\t"
                     + err.message + "\n\nDer Server antwortete:\n" + re_json;
        }
        if(message != "") {}
        else if(!re || typeof re.success == "undefined") {
            title = "Datei-Import fehlgeschlagen";
            message = "Beim Hinzufügen der Datei ist ein unbekannter Fehler aufgetreten.";
        }
        else if(!re.success) {
            title = "Datei-Import fehlgeschlagen";
            message = "Beim Hinzufügen der Datei "
                    + ((re.errors.length>1) ? ("sind " + re.errors.length) : "ist ein")
                    + " Fehler aufgetreten:";
            textarea = re.errors.join("\n");
        }
        else if(error_only) {
            return true;
        }
        else {
            title = "Datei-Import erfolgreich";
            message = "Die Datei wurde erfolgreich hinzufügt.";
            if(re.warnings instanceof Array && re.warnings.length>0) {
                message += " Das System lieferte "
                         + ((re.warnings.length>1) ?
                            (re.warnings.length + " Warnungen") : "eine Warnung")
                         + " zurück:";
                textarea = re.warnings.join("\n");
            }
            success = true;
        }
        gui.showTextDialog(title, message, textarea);
        return success;
    },

    /* Function: startImportProgressTimer

       Sets up and starts a request for import status updates in
       regular intervals.

       Parameters:
        fn - Callback function to invoke when the import has finished
     */
    startImportProgressTimer: function(fn) {
        var ref = this;
	$('tIS_upload').getElement('td.proc').set('class', 'proc proc-success');
	$('tIS_check').getElement('td.proc').set('class', 'proc proc-running');
	var import_update = new Request({
	    method: 'get',
	    url: 'request.php?do=getImportStatus',
	    initialDelay: 1000,
	    delay: 1000,
	    limit: 5000,
	    onComplete: function(response) {
		var status = ref.updateImportProgress(response);
		if(status.done != "running") {
		    import_update.stopTimer();
		    $$('.tIS_cb').set('disabled', false);
                    if(typeof(fn) == "function")
                        fn(status);
		}
	    }
	});
        import_update.startTimer();
    },
    
    /* Function: updateImportProgress

       Updates the import progress dialog with status information from
       the server.

       Parameters:
        re_json - The server response as a JSON string
       
       Returns:
        A status object {done: ..., message: ..., output: ...}
     */
    updateImportProgress: function(re_json) {
        var process;
	var status = {'done': 'running'};
	var get_css_code = function(status_code) {
	    if(status_code == "begun")        { return "proc-running"; }
	    else if(status_code == "success") { return "proc-success"; }
            else                              { return "proc-error";   }
	}
        var update_status = function(status_code, elem) {
            if(status_code != null)
                elem.getElement('td.proc').set('class', 'proc')
                    .addClass(get_css_code(status_code));
        }

        try {
            process = JSON.decode(re_json);
        }
        catch (err) {
            status.done    = 'error';
            status.message = "Der Server lieferte eine ungültige Antwort zurück.";
            status.output  = "Fehler beim Interpretieren der Server-Antwort:\n\t"
                             + err.message + "\n\nDer Server antwortete:\n" + re_json;
            return status;
        }
        update_status(process.status_CHECK,  $('tIS_check'));
        update_status(process.status_XML,    $('tIS_convert'));
        update_status(process.status_TAG,    $('tIS_tag'));
        update_status(process.status_IMPORT, $('tIS_import'));
	if(process.progress != null) {
	    this.transImportProgressBar.set(process.progress * 100.0);
	}
	if(process.in_progress != null && !process.in_progress) {
	    if(process.progress != null && process.progress == 1.0) {
		status.done = 'success';
	    } else {
		status.done = 'error';
		if(process.output != null) {
		    status.message = "Beim Importieren sind Fehler aufgetreten.";
                    status.output  = process.output;
		}
	    }
	}
	return status;
    },

    /* Function: resetImportProgress

       Resets the import progress dialog.
     */
    resetImportProgress: function() {
	$('tIS_upload').getElement('td.proc').set('class', 'proc proc-running');
	$('tIS_check').getElement('td.proc').set('class', 'proc');
	$('tIS_convert').getElement('td.proc').set('class', 'proc');
	$('tIS_tag').getElement('td.proc').set('class', 'proc');
	$('tIS_import').getElement('td.proc').set('class', 'proc');
	$$('.tIS_cb').set('disabled', true);
	this.transImportProgressBar.set(0);
    },

    /* Function: resetImportForm
       
       Resets an import form.

       Parameters:
        myform - Name of the form to reset
    */
    resetImportForm: function(myform) {
        form.reset(myform);
        myform.getElements('.error_text').hide();
        myform.getElement('input[type="submit"]').set('disabled', false);
    },

    /* Function: updateProjectList

       Rebuilds the project dropdown box.

       Parameters:
        myform - the form element to update
     */
    updateProjectList: function(myform) {
        var value  = -1;
        var select = myform.getElement('select[name="project"]');
        if(select.getSelected().length>0)
            value  = select.getSelected()[0].get('value');
        select.empty();
        Array.each(cora.projects.getAll(), function(prj) {
            var option = new Element('option',
                                     {'value': prj.id,
                                      'text':  prj.name});
            select.grab(option);
            if(value == prj.id)
                option.set('selected', true);
        });
    },

    /* Function: updateTagsetList

       Rebuilds the tagset list and sets default values for tagset
       associations depending on the selected project.

       The table updated by this function is typically not visible in
       the GUI except for administrators, but still important for the
       import process.

       Parameters:
         myform - the form element to update
     */
    updateTagsetList: function(myform) {
        var selected = myform.getElement('select[name="project"]')
                             .getSelected();
        if(selected.length == 0) return;
	var pid = selected[0].get('value');
        var prj = cora.projects.get(pid);
	var tlist = ('tagsets' in prj) ? prj.tagsets : [];
        var olddiv = myform.getElement('.fileImportTagsetLinks div');
        cora.tagsets.makeMultiSelectBox(tlist, 'linktagsets', '')
            .replaces(olddiv);
    },

    /* Function: checkForCmdImport

       Checks whether selected project has an import command set, and
       disables the import (with a warning text) if it hasn't.

       Parameters:
         myform - the form element to update
     */
    checkForCmdImport: function(myform) {
        var selected = myform.getElement('select[name="project"]')
                             .getSelected();
        if(selected.length == 0) return;
	var pid = selected[0].get('value');
        var prj = cora.projects.get(pid);
        if (!prj.settings || !prj.settings.cmd_import) {
            myform.getElement('input[type="submit"]').set('disabled', 'disabled');
            myform.getElements('p.error_text_cmdimport').show();
        }
        else {
            myform.getElement('input[type="submit"]').set('disabled', false);
            myform.getElements('p.error_text_cmdimport').hide();
        }
    },
};

var file = {
    tagsets: {},
    tagsetlist: [],
    taggers: [],

    initialize: function(){
        cora.projects.onUpdate(function(status, text) {
            this.renderFileTable();
        }.bind(this));
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
        cora.projects.performUpdate();
    },

    renderFileTable: function() {
	/* TODO: isn't it completely unnecessary to completely
	 * re-create the whole table each time a change occurs?
	 * couldn't this be done more efficiently? */
        var ref = this;
        var files_div = $('files').empty();
        if(cora.projects.isEmpty()) {
            files_div.grab($('noProjectGroups').clone());
            return;
        }
        Array.each(cora.projects.getAll(), function(project) {
            var prj_div   = $('fileGroup').clone();
            var prj_table = prj_div.getElement('table');
            prj_div.getElement('h4.projectname').empty().appendText(project.name);
            Array.each(project.files, function(file) {
                prj_table.adopt(ref.renderTableLine(file));
            });
            if(project.files.length == 0)
                $('noProjectFiles').clone().replaces(prj_table);
            prj_div.inject(files_div);
        });
        gui.addToggleEvents(files_div.getElements('.clappable'));
    },

    renderTableLine: function(file){
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

    /* Function: performChangeTagsetAssoc

       Performs a server request to change tagset associations for a
       single file.  Will only succeed if user is admin.

       Parameters:
         fileid - ID of the file
         div    - Content div with the currently selected tagsets
     */
    performChangeTagsetAssoc: function(fileid, div) {
        var tagsets = div.getElements('input[name="linktagsets[]"]')
                         .filter(function(elem) { return elem.get('checked'); })
                         .get('value');
        new Request.JSON(
    	    {'url': 'request.php',
    	     'async': false,
    	     onFailure: function(xhr) {
                 gui.showNotice('error',
                                "Serverfehler beim Ändern der Tagset-Verknüpfungen.");
    	     },
    	     onSuccess: function(status, x) {
                 if(status.success) {
                     gui.showNotice('ok',
                                    "Tagset-Verknüpfungen geändert.");
                 }
                 else {
                     gui.showNotice('error',
                                    "Tagset-Verknüpfungen nicht geändert.");
                     if(status.errors && status.errors.length>0) {
                         gui.showTextDialog("Ändern fehlgeschlagen",
                                            "Tagset-Verknüpfungen konnten nicht "
                                            +"geändert werden.",
                                            status.errors);
                     }
                 }
    	     }
    	    }
    	).get({'do': 'changeTagsetsForFile',
               'file_id': fileid, 'linktagsets': tagsets});
    },

    /* Function: editTagsetAssoc

       Shows a dialog with the associated tagsets for a file.

       Parameters:
         fileid - ID of the file
	 fullname - Name of the file
     */
    editTagsetAssoc: function(fileid, fullname) {
        var ref = this;
	var contentdiv = $('tagsetAssociationTable');
	var spinner = new Spinner(contentdiv);
	var content = new mBox.Modal({
	    title: "Tagset-Verknüpfungen für '"+fullname+"'",
	    content: contentdiv,
	    buttons: [ {title: "Ändern", addClass: "mform button_red",
			id: "editTagsetAssocPerform",
			event: function() {
                            ref.performChangeTagsetAssoc(fileid, contentdiv);
                            this.close();
                        }},
                       {title: "Schließen", addClass: "mform",
			id: "editTagsetAssocOK",
			event: function() { this.close(); }}                       
		     ]
	});
	content.open();
	spinner.show();

	new Request.JSON(
    	    {'url': 'request.php',
    	     'async': false,
    	     onFailure: function(xhr) {
                 gui.showNotice('error',
                                "Serverfehler beim Laden der Tagset-Verknüpfungen.");
		 spinner.destroy();
		 content.close();
    	     },
    	     onSuccess: function(tlist, x) {
		 // show tagset associations
		 if(!tlist['success']) {
                     gui.showNotice('error',
                                    "Fehler beim Laden der Tagset-Verknüpfungen.");
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
    cora.tagsets.performUpdate();
    cora.fileImporter.initialize();
    file.initialize();    

    $$('div.fileViewRefresh img').addEvent('click',function(e){ e.stop(); file.listFiles() });
    $$('div.fileViewRefresh a.collapseAll').addEvent('click',function(e){ e.stop(); $$('div#files .clappable div').hide(); });

    file.listFiles();
    if(userdata.currentFileId)
        file.openFile(userdata.currentFileId);
});
