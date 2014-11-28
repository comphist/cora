
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

   This object allows simple access to projects via their ID, sends
   AJAX requests to update the project information, and stores
   functions that should be called whenever the project list updates.
*/
cora.projects = {
    initialized: false,
    data: [],
    byID: {},              // maps project ID to array index
    byFileID: {},          // maps file ID to tuple (project idx, files idx)
    onUpdateHandlers: [],  // list of callback functions when data updates
    onInitHandlers: [],    // list of callback functions after initialization

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

    /* Function: getFile

       Return a file by ID.

       Parameters:
        fid - ID of the file to be returned
     */
    getFile: function(fid) {
        var tuple = this.byFileID[fid];
        if(tuple == undefined)
            return Object();
        return this.data[tuple[0]].files[tuple[1]];
    },

    /* Function: getProjectForFile

       Return the project which contains a given file.

       Parameters:
        fid - ID of the file
    */
    getProjectForFile: function(fid) {
        var tuple = this.byFileID[fid];
        if(tuple == undefined)
            return null;
        return this.data[tuple[0]];
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
                    ref.byFileID = {};
                    Array.each(ref.data, function(prj, idx) {
                        ref.byID[prj.id] = idx;
                        Array.each(prj.files, function(file, idy) {
                            ref.byFileID[file.id] = [idx, idy];
                        });
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

/* Class: cora.files

   Provides access to file-specific functions, including aliases for
   the file-specific functions in cora.projects.
*/
cora.files = {
    getProject: cora.projects.getProjectForFile.bind(cora.projects),
    tagsetsByID: {},
    taggersByID: {},

    /* Function: get

       Return a file by ID.

       Parameters:
        fid - ID of the file to be returned
     */
    get: function(fid) {
        var file = cora.projects.getFile(fid);
        if(typeof(this.tagsetsByID[fid]) !== "undefined")
            file.tagsets = this.tagsetsByID[fid];
        if(typeof(this.taggersByID[fid]) !== "undefined")
            file.taggers = this.taggersByID[fid];
        return file;
    },

    /* Function: _performGETRequest

       Performs an asynchronous GET request that includes a file ID.

       Parameters:
         name - Name of request
         fid - ID of the file
         fn  - Callback function to invoke after successful request
    */
    _performGETRequest: function(name, fid, fn) {
        new Request.JSON(
            {'url': 'request.php',
             'async': true,
             onComplete: function(status, text) {
                 if(typeof(fn) == "function")
                     fn(status, text);
             }
            }
        ).get({'do': name, 'fileid': fid});
    },

    /* Function: getTagsets

       Returns tagset associations for a given file, if available.

       Parameters:
         file - A file object or a file ID
     */
    getTagsets: function(file) {
        var id = file.id || file;
        if(typeof(this.tagsetsByID[id]) === "undefined")
            return {};
        return this.tagsetsByID[id];
    },

    /* Function: getTaggers

       Returns a list of taggers for a given file, if available.

       Parameters:
         file - A file object or a file ID
     */
    getTaggers: function(file) {
        var id = file.id || file;
        if(typeof(this.taggersByID[id]) === "undefined")
            return {};
        return this.taggersByID[id];
    },

    /* Function: getDisplayName

       Returns the name of a file as it should be displayed on the
       interface.

       Parameters:
         file - A file object, e.g. as returned by cora.files.get,
                or a file ID
    */
    getDisplayName: function(file) {
        if(!file.id) {
            file = this.get(file);
            if(!file.id)
                return null;
        }
        if(file.sigle)
            return '[' + file.sigle + '] ' + file.fullname;
        return file.fullname;
    },

    /* Function: mayDeleteFile

       Checks whether the current user is allowed to delete the file
       with the given id.
     */
    mayDeleteFile: function(fid) {
        var file = this.get(fid);
        if (file == null)
            return false;
        return (userdata.admin || (file.creator_name == userdata.name));
    },

    /* Function: deleteFile

       Sends a server request to delete the file with the given id.

       Parameters:
         fid - ID of the file to be deleted
         fn  - Callback function to invoke after successful request
     */
    deleteFile: function(fid, fn) {
        new Request.JSON(
    	    {'url': 'request.php?do=deleteFile',
    	     'async': false,
    	     'data': 'file_id='+fid,
    	     onSuccess: function(status, text) {
                 if(typeof(fn) == "function")
                     fn(status, text);
    	     }
    	    }
    	).post();
    },

    /* Function: lock

       Sends a server request to lock a given file.

       Parameters:
         fid - ID of the file to be locked
         fn  - Callback function to invoke after successful request
     */
    lock: function(fid, fn) {
        this._performGETRequest("lockFile", fid, fn);
    },

    /* Function: open

       Sends a server request to open a given file.

       If tagset and tagger associations are not already set, this
       information is automatically set from the returned data.

       Parameters:
         fid - ID of the file to be opened
         fn  - Callback function to invoke after successful request
     */
    open: function(fid, fn) {
        this._performGETRequest("openFile", fid, function(status, text) {
            // tagset assocs?
            if(status.data && status.data.tagsets) {
                this.tagsetsByID[fid] = {  // HACK for the time being
                    // every file gets the comment "tagset" associated with it
                    'comment': new CommentTagset({
                        id: -1,
                        shortname: '-1',
                        longname: 'Kommentar',
                        set_type: 'open',
                        class: 'comment'
                    })
                };
                Object.each(status.data.tagsets, function(ts) {
                    this.tagsetsByID[fid][ts['class']] = cora.tagsets.get(ts.id);
                }.bind(this));
            }
            // tagger assoc?
            if(status.data && status.data.taggers) {
                this.taggersByID[fid] = status.data.taggers;
            }
            if(typeof(fn) == "function")
                fn(status, text);
        }.bind(this));
    },

    /* Function: close

       Sends a server request to close/unlock a given file.

       Parameters:
         fid - ID of the file to be closed/unlocked
         fn  - Callback function to invoke after successful request
     */
    close: function(fid, fn) {
        this._performGETRequest("unlockFile", fid, fn);
    },

    /* Function: prefetchTagsets

       Sends a server request to fetch all closed tagsets associated
       with the given file, and automatically triggers pre-processing
       for these tagsets as well.

       Parameters:
         fid - ID of the file
         fn  - Callback function to invoke after successful request
     */
    prefetchTagsets: function(fid, fn) {
        if(this.allTagsetsPrefetched(fid)) {
            if(typeof(fn) == "function")
                fn({success: true}, '');
            return;
        }
        this._performGETRequest("fetchTagsetsForFile", fid, function(status, text) {
            if(status.success && status.data) {
                Object.each(status.data, function(tagset, cls) {
                    cora.tagsets.preprocess(tagset);
                });
            }
            if(typeof(fn) == "function")
                fn(status, text);
        });
    },

    /* Function: allTagsetsPrefetched

       Check if all tagsets associated with the given file have
       already been fetched, so we don't need to issue another server
       request.

       Parameters:
         fid - ID of the file
     */
    allTagsetsPrefetched: function(fid) {
        var tagsets = this.getTagsets(fid);
        if(tagsets) {
            return Object.values(tagsets).every(function(ts) {
                return ts.needsProcessing();
            });
        }
        return false;
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
            $('importNewXMLLink').removeClass('start-disabled');
            $('importNewTransLink').removeClass('start-disabled');
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
            closeOnBodyClick: false
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
            closeOnBodyClick: false
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
            message = "Beim Hinzufügen der Datei ist ein unbekannter Fehler aufgetreten.";
            gui.showMsgDialog('error', message);
            return false;
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
            success = true;
            title = "Datei-Import erfolgreich";
            message = "Die Datei wurde erfolgreich hinzufügt.";
            if(re.warnings instanceof Array && re.warnings.length>0) {
                message += " Das System lieferte "
                         + ((re.warnings.length>1) ?
                            (re.warnings.length + " Warnungen") : "eine Warnung")
                         + " zurück:";
                textarea = re.warnings.join("\n");
            } else {
                gui.showMsgDialog('info', message);
                return true;
            }
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
        var failures = 0;
	$('tIS_upload').getElement('td.proc').set('class', 'proc proc-success');
	$('tIS_check').getElement('td.proc').set('class', 'proc proc-running');
	var import_update = new Request.JSON({
	    method: 'get',
	    url: 'request.php?do=getImportStatus',
	    initialDelay: 1000,
	    delay: 1000,
	    limit: 5000,
	    onSuccess: function(response) {
                failures = 0;
		var status = ref.updateImportProgress(response);
		if(status.done != "running") {
		    import_update.stopTimer();
		    $$('.tIS_cb').set('disabled', false);
                    if(typeof(fn) == "function")
                        fn(status);
		}
	    },
            onFailure: function(xhr) {
                if(failures++ > 3) {
                    gui.showNotice('notice',
                                   "Keine Verbindung zum Server.");
                }
            }
	});
        import_update.startTimer();
    },

    /* Function: updateImportProgress

       Updates the import progress dialog with status information from
       the server.

       Parameters:
        process - The server response as an object

       Returns:
        A status object {done: ..., message: ..., output: ...}
     */
    updateImportProgress: function(process) {
	var status = {'done': 'running'};
	var get_css_code = function(status_code) {
	    if(status_code == "begun")        { return "proc-running"; }
	    else if(status_code == "success") { return "proc-success"; }
            else                              { return "proc-error";   }
	};
        var update_status = function(status_code, elem) {
            if(status_code != null)
                elem.getElement('td.proc').set('class', 'proc')
                    .addClass(get_css_code(status_code));
        };

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
    }
};

// ***********************************************************************
// ********** File manager ***********************************************
// ***********************************************************************

/* Class: fileManager

   Manages opening/closing of files and renders the file table.
 */
cora.fileManager = {
    content: null,
    currentFileId: null,

    initialize: function() {
        this.content = $('files');
        this._prepareEvents();
    },

    /* Function: _prepareEvents

       Sets up events for links/buttons in the file table.
     */
    _prepareEvents: function() {
        this.content.addEvent(
            'click:relay(a)',
            function(event, target) {
                var parent = target.getParent('tr');
                if(typeof(parent) == "undefined")
                    return;
                var this_id = parent.get('id').substr(5);
                event.stop();
                if(target.hasClass("filenameOpenLink")) {
                    this.openFile(this_id);
                } else if(target.hasClass("deleteFileLink")) {
                    this.deleteFile(this_id);
                } else if(target.hasClass("exportFileLink")) {
                    this.exportFile(this_id);
                } else if(target.hasClass("editTagsetAssocLink")) {
                    this.editTagsetAssoc(this_id);
                } else if(target.hasClass("closeFileLink")) {
                    this.closeFile(this_id);
                }
            }.bind(this)
        );
    },

    /* Function: isFileOpened

       Checks whether a file is currently opened in this CorA instance.
     */
    isFileOpened: function() {
        return (this.currentFileId !== null);
    },

    /* Function: render

       (Re-)renders the file & project listing table.
     */
    render: function() {
        if(cora.projects.isEmpty()) {
            this.content.empty().grab($('noProjectGroups').clone());
            return this;
        }
        var last_div = null;
        Array.each(cora.projects.getAll(), function(project) {
            var prj_div, prj_table, html_table;
            var div_id = 'proj_'+project.id;
            prj_div = this.content.getElementById(div_id);
            if(prj_div === null) {  // create project div if necessary
                prj_div = $('fileGroup').clone();
                prj_div.set('id', div_id);
                prj_div.getElement('.projectname')
                    .empty().appendText(project.name);
                if(userdata.admin) {
                    prj_div.getElement('.projectname')
                        .appendText(' (id: '+project.id+')');
                    prj_div.getElements('.admin-only').removeClass('start-hidden');
                }
                if(last_div !== null)
                    prj_div.inject(last_div, 'after');
                else
                    prj_div.inject(this.content);
                gui.addToggleEvents(prj_div);
                html_table = new HtmlTable(prj_div.getElement('table'),
                                           {sortable: true,
                                            parsers: ['string', 'string',
                                                      'date',   'string',
                                                      'date',   'string']});
                prj_div.store('HtmlTable', html_table);
            }
            prj_table = prj_div.getElement('tbody').empty();
            if(project.files.length == 0) {  // no files?
                prj_table.adopt($('noProjectFiles').clone());
            }
            else {  // rebuild list of files from scratch
                Array.each(project.files, function(file) {
                    prj_table.adopt(this.renderTableLine(file));
                }.bind(this));
                prj_div.retrieve('HtmlTable').reSort();
            }
            last_div = prj_div;
        }.bind(this));
        return this;
    },

    /* Function: renderTableLine

       Renders a line of the file listing table.

       Parameters:
        file - Object representing the file to be rendered

       Returns:
        A <tr> Element for the file listing table.
     */
    renderTableLine: function(file){
        var td;
        var tr = $('fileTableRow').clone();
        tr.set('id', 'file_'+file.id);
        if(file.opened)
            tr.addClass('opened');
        // delete icon (if applicable)
        if(cora.files.mayDeleteFile(file.id)) {
            tr.getElement('a.deleteFileLink').removeClass('start-hidden');
        }
        // filename
        tr.getElement('td.ftr-id').set('text', file.id);
        tr.getElement('td.ftr-sigle a').set('text', '['+file.sigle+']');
        tr.getElement('td.ftr-filename a').set('text', file.fullname);
        // changer & creator info
        tr.getElement('td.ftr-changed-at')
            .set('text', gui.formatDateString(file.changed));
        tr.getElement('td.ftr-changed-by').set('text', file.changer_name);
        tr.getElement('td.ftr-created-at')
            .set('text', gui.formatDateString(file.created));
        tr.getElement('td.ftr-created-by').set('text', file.creator_name);
        // close button
        if(file.opened && (userdata.admin || (file.opened == userdata.name))) {
            tr.getElement('a.closeFileLink').removeClass('start-hidden');
        }
        // admin stuff
        if(userdata.admin)
            tr.getElements('.admin-only').removeClass('start-hidden');
        // done!
        return tr;
    },

    /* Function: openFile

       Opens a file, instantiating the editor.

       Parameters:
         fid - ID of the file to open
    */
    openFile: function(fid) {
        var startChain, onLockSuccess, onOpenSuccess, onInitSuccess;

        // Is there a file already opened?
        if (this.isFileOpened()) {
            cora.editor.confirmClose(function() {
                this.closeCurrentlyOpened(function() {
                    this.openFile(fid);
                }.bind(this));
            }.bind(this));
            return;
        }

        // 1. Acquire the lock
        startChain = function() {
            cora.files.lock(fid, onLockSuccess);
        };

        // 2. If lock was successful, open the file
        onLockSuccess = function(status) {
            if(status.success) {
                gui.showSpinner({message: "Datei wird geöffnet..."});
                cora.files.open(fid, onOpenSuccess);
            } else {
                if(status.lock) {
                    gui.showMsgDialog('info',
                        "Das Dokument wird zur Zeit bearbeitet von Benutzer '"
                        + data.lock.locked_by + "' seit "
                        + gui.formatDateString(data.lock.locked_since).toLowerCase()
                        + ".");
                } else {
                    gui.showMsgDialog('error',
                                      "Das Dokument konnte nicht geöffnet werden.");
                }
            }
        };

        // 3. If open was successful, fetch and preprocess any closed tagsets
        onOpenSuccess = function(status) {
            var response = status;
            if(response.success) {
                this.currentFileId = fid;
                gui.setHeader(cora.files.getDisplayName(fid));
                cora.projects.performUpdate();
                cora.files.prefetchTagsets(fid, function(status) {
                    if(status.success) {
                        response.onInit = onInitSuccess;
                        cora.editor = new EditorModel(fid, response);
                    } else {
                        gui.showMsgDialog('error', "Fehler beim Laden der Tagsets.");
                        this.closeCurrentlyOpened();
                    }
                }.bind(this));
            } else {
                gui.showMsgDialog('error', "Das Dokument konnte nicht geöffnet werden.");
            }
        }.bind(this);

        // 4. Show the editor tab and clean up
        onInitSuccess = function() {
            $('editTabButton').show();
            default_tab = 'edit';
            gui.changeTab('edit');
            gui.hideSpinner();
        };

        startChain();
    },

    /* Function: closeFile

       Closes a file, asking for confirmation if there are any unsaved
       changes or the file is currently opened by someone else.

       Parameters:
         fid - ID of the file to close
     */
    closeFile: function(fid) {
        // are we closing the currently opened file?
        if (this.currentFileId == fid) {
            cora.editor.confirmClose(this.closeCurrentlyOpened.bind(this));
        } else {
            // if we're admin, we can close any file
            if(!userdata.admin) {
                gui.showMsgDialog('error',
                           "Sie haben keine Berechtigung, diese Datei zu schließen.");
            } else {
                gui.confirm(
                    "Sie sind dabei, eine Datei zu schließen, die aktuell nicht von "
                        +"Ihnen bearbeitet wird. Falls jemand anderes diese Datei "
                        +"zur Zeit bearbeitet, könnten Datenverluste auftreten. "
                        +"Trotzdem schließen?",
                    function() {
                        cora.files.close(fid, function() {
                            cora.projects.performUpdate();
                        });
                    }
                );
            }
        }
    },

    /* Function: closeCurrentlyOpened

       Closes the currently opened file and destructs the editor tab.

       TODO: this function certainly belongs somewhere else
     */
    closeCurrentlyOpened: function(fn) {
        if (this.isFileOpened()) {
            gui.lock();
            cora.files.close(this.currentFileId, function(status) {
                this.currentFileId = null;
                cora.editor.destruct();
                cora.editor = null;
                cora.projects.performUpdate();
                gui.setHeader("").hideTab('edit').changeTab('file').unlock();
                if(typeof(fn) == "function")
                    fn();
            }.bind(this));
        }
    },

    /* Function: deleteFile

       Asks for confirmation to delete a file and, if confirmed, sends
       a server request for deletion.

       Parameters:
         fid - ID of the file to be deleted
     */
    deleteFile: function(fid) {
        var message = "Soll das Dokument '" + cora.files.getDisplayName(fid)
            + "' wirklich gelöscht werden? Dieser Schritt kann nicht rückgängig "
            + "gemacht werden!";
        var performDelete = function() {
            cora.files.deleteFile(fid, function(status) {
		if(!status || !status.success)
		    gui.showNotice('error', "Konnte Datei nicht löschen.");
		else
		    gui.showNotice('ok', "Datei gelöscht.");
                cora.projects.performUpdate();
            });
        };
        gui.confirm(message, performDelete, true);
    },

    /* Function: exportFile

       Displays a dialog to export a file and triggers the export.

       Parameters:
         fid - ID of the file to be exported
     */
    exportFile: function(fid){
	new mBox.Modal({
	    content: 'fileExportPopup',
	    title: 'Datei exportieren',
	    buttons: [
		{title: 'Abbrechen', addClass: 'mform'},
		{title: 'Exportieren', addClass: 'mform button_green',
		 event: function() {
		     var format = $('fileExportFormat').getSelected()[0].get('value');
		     this.close();
		     $('fileDownloadTarget').src = 'request.php?do=exportFile'
                         +'&fileid='+fid+'&format='+format;
		 }
		}
	    ]
	}).open();
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
    	     onSuccess: function(status, x) {
                 if(status.success) {
                     gui.showNotice('ok', "Tagset-Verknüpfungen geändert.");
                 }
                 else {
                     gui.showNotice('error', "Tagset-Verknüpfungen nicht geändert.");
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
     */
    editTagsetAssoc: function(fileid) {
        var ref = this;
        var fullname = cora.files.getDisplayName(fileid);
	var contentdiv = $('tagsetAssociationTable');
	var spinner = new Spinner(contentdiv);
	var content = new mBox.Modal({
	    title: "Tagset-Verknüpfungen für '"+fullname+"'",
	    content: contentdiv,
	    buttons: [ {title: "Schließen", addClass: "mform",
			id: "editTagsetAssocOK",
			event: function() { this.close(); }},
                       {title: "Ändern", addClass: "mform button_red",
			id: "editTagsetAssocPerform",
			event: function() {
                            ref.performChangeTagsetAssoc(fileid, contentdiv);
                            this.close();
                        }}
		     ]
	});
	content.open();
	spinner.show();

	new Request.JSON(
    	    {'url': 'request.php',
    	     'async': false,
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
    }
};

// ***********************************************************************
// ********** Convenience functions **************************************
// ***********************************************************************

cora.current = function() {
    return cora.files.get(cora.fileManager.currentFileId);
};

cora.currentTagset = function(cls) {
    return cora.current().tagsets[cls];
};

cora.currentHasTagset = function(cls) {
    return (typeof(cora.current().tagsets[cls]) !== "undefined");
};

// ***********************************************************************
// ********** DOMREADY BINDINGS ******************************************
// ***********************************************************************

window.addEvent('domready', function() {
    cora.fileImporter.initialize();
    cora.fileManager.initialize();
    cora.projects.onUpdate(cora.fileManager.render.bind(cora.fileManager));

    $('fileViewRefresh').addEvent('click', function (e) {
        e.stop();
        cora.projects.performUpdate();
    });
    $('fileViewCollapseAll').addEvent('click',
        function(e){
            e.stop();
            $$('div#files .clappable').each(function (clappable) {
                clappable.addClass('clapp-hidden');
		clappable.getElement('div').hide();
            });
        });
    $('fileViewExpandAll').addEvent('click',
        function(e){
            e.stop();
            $$('div#files .clappable').each(function (clappable) {
                clappable.removeClass('clapp-hidden');
		clappable.getElement('div').show();
            });
        });

    cora.projects.performUpdate();
    if(userdata.currentFileId)
        cora.fileManager.openFile(userdata.currentFileId);
});
