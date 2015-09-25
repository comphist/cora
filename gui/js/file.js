/*
 * Copyright (C) 2015 Marcel Bollmann <bollmann@linguistics.rub.de>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy of
 * this software and associated documentation files (the "Software"), to deal in
 * the Software without restriction, including without limitation the rights to
 * use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of
 * the Software, and to permit persons to whom the Software is furnished to do so,
 * subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS
 * FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR
 * COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER
 * IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN
 * CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */

/*************************************************************************
 ************ Projects and Files *****************************************
 *************************************************************************/

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
        new CoraRequest({
            name: 'getProjectsAndFiles',
    	    onSuccess: function(status) {
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
                    handler(status);
                });
                if(typeof(fn) == "function")
                    fn(status);
	    }
    	}).get();
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
        return (cora.isAdmin() || (file.creator_name == cora.settings.get('name')));
    },

    /* Function: deleteFile

       Sends a server request to delete the file with the given id.

       Parameters:
         fid - ID of the file to be deleted
         options - Additional options for the request object
     */
    deleteFile: function(fid, options) {
        if(typeof(options) !== "object" || options === null)
            options = {};
        options.name = 'deleteFile';
        new CoraRequest(options).post({'file_id': fid});
    },

    /* Function: lock

       Sends a server request to lock a given file.

       Parameters:
         fid - ID of the file to be locked
         options - Additional options for the request object
     */
    lock: function(fid, options) {
        if(typeof(options) !== "object" || options === null)
            options = {};
        options.name = 'lockFile';
        new CoraRequest(options).get({'fileid': fid});
    },

    /* Function: open

       Sends a server request to open a given file.

       If tagset and tagger associations are not already set, this
       information is automatically set from the returned data.

       Parameters:
         fid - ID of the file to be opened
         options - Additional options for the request object
     */
    open: function(fid, options) {
        var fn;
        if(typeof(options) !== "object" || options === null)
            options = {};
        fn = options.onSuccess;
        options.name = 'openFile';
        options.onSuccess = function(status) {
            // tagset assocs?
            if(status.data && status.data.tagsets) {
                this.tagsetsByID[fid] = {};
                Object.each(status.data.tagsets, function(ts) {
                    this.tagsetsByID[fid][ts['class']] = cora.tagsets.get(ts.id);
                }.bind(this));
            }
            // tagger assoc?
            if(status.data && status.data.taggers) {
                this.taggersByID[fid] = status.data.taggers;
            }
            if(typeof(fn) === "function")
                fn(status);
        }.bind(this);
        new CoraRequest(options).get({'fileid': fid});
    },

    /* Function: close

       Sends a server request to close/unlock a given file.

       Parameters:
         fid - ID of the file to be closed/unlocked
         options - Additional options for the request object
     */
    close: function(fid, options) {
        if(typeof(options) !== "object" || options === null)
            options = {};
        options.name = 'unlockFile';
        new CoraRequest(options).get({'fileid': fid});
    },

    /* Function: prefetchTagsets

       Sends a server request to fetch all closed tagsets associated
       with the given file, and automatically triggers pre-processing
       for these tagsets as well.

       Parameters:
         fid - ID of the file
         options - Additional options for the request object
     */
    prefetchTagsets: function(fid, options) {
        var fn;
        if(typeof(options) !== "object" || options === null)
            options = {};
        fn = options.onSuccess;
        options.name = 'fetchTagsetsForFile';
        if(this.allTagsetsPrefetched(fid)) {
            if(typeof(fn) == "function")
                fn({});
            return;
        }
        options.onSuccess = function(status) {
            if(status.data) {
                Object.each(status.data, function(tagset, cls) {
                    cora.tagsets.preprocess(tagset);
                });
            }
            if(typeof(fn) === "function")
                fn(status);
        };
        new CoraRequest(options).get({'fileid': fid});
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
                  var input = myform.getElement('input[name="'+fin+'"]')
                      .get('value');
                  if(!input || input.length === 0) {
                      myform.getElements('p.error_text_import').show();
                      e.stop();
                  } else {
                      myform.getElements('p.error_text').hide();
                  }
                  if (trans) {
                      input = myform.getElement('input[name="transName"]');
                      if (!input.get('value')) {
                          input.addClass("input_error");
                          e.stop();
                      } else {
                          input.removeClass("input_error");
                      }
                  }
              });
    },

    /* Function: showImportResponseDialog

       Parses the server response after an import request and displays
       an informational dialog to the user.

       Parameters:
        response - The server response as a JSON string wrapped in
                   <pre class="json">...</pre>
        error_only - If true, only show a dialog on error,
                     and just return true otherwise

       Returns:
        True if the server response indicates a successful import,
        false otherwise
     */
    showImportResponseDialog: function(response, error_only) {
        var title="", message="", textarea="", tmp;
        var success=false;
        try {
            tmp = new Element('div');
            tmp.innerHTML = response;
            response = JSON.decode(tmp.getElement('pre.json').get('text'));
        }
        catch (err) {
            title = "Fehlerhafte Server-Antwort";
            message = "Der Server lieferte eine ungültige Antwort zurück. "
                    + "Das Importieren der Datei war möglicherweise nicht erfolgreich.";
            textarea = "Fehler beim Interpretieren der Server-Antwort:\n\t"
                     + err.message + "\n\nDer Server antwortete:\n" + response;
        }
        if(message != "") {}
        else if(!response || typeof response.success == "undefined") {
            message = "Beim Hinzufügen der Datei ist ein unbekannter Fehler aufgetreten.";
            gui.showMsgDialog('error', message);
            return false;
        }
        else if(!response.success) {
            title = "Datei-Import fehlgeschlagen";
            message = "Beim Hinzufügen der Datei "
                    + ((response.errors.length>1) ?
                       ("sind " + response.errors.length) : "ist ein")
                    + " Fehler aufgetreten:";
            textarea = response.errors.join("\n");
        }
        else if(error_only) {
            return true;
        }
        else {
            success = true;
            title = "Datei-Import erfolgreich";
            message = "Die Datei wurde erfolgreich hinzufügt.";
            if(response.warnings instanceof Array && response.warnings.length>0) {
                message += " Das System lieferte "
                         + ((response.warnings.length>1) ?
                            (response.warnings.length + " Warnungen") : "eine Warnung")
                         + " zurück:";
                textarea = response.warnings.join("\n");
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
                    gui.showNotice('notice', "Keine Verbindung zum Server.", true);
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
        this._autoOpen();
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
        this._prepareFileViewEvents();
        this._prepareFileExportEvents();
    },

    /* Function: _prepareFileViewEvents
     */
    _prepareFileViewEvents: function() {
        $('fileViewRefresh').addEvent('click', function (e) {
            e.stop();
            cora.projects.performUpdate();
        });
        gui.addToggleEventCollapseAll('fileViewCollapseAll', 'div#files');
        gui.addToggleEventExpandAll('fileViewExpandAll', 'div#files');
    },

    /* Function: _autoOpen

       Opens a file automatically, if desired, based on URL query string or
       whether a file is opened on server-side.
     */
    _autoOpen: function() {
        var fid = null;
        var uri = new URI();
        if(uri.parsed && uri.parsed.query) {  // ?f=... in query string?
            fid = uri.parsed.query.parseQueryString()["f"];
        }
        fid = fid || cora.settings.get('currentFileId');  // file open on server-side?
        history.replaceState({}, "", "./");
        if(fid) this.openFile(fid);
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
                if(cora.isAdmin()) {
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
        tr.getElement('td.ftr-sigle a')
            .set('text', file.sigle ? '['+file.sigle+']' : '')
            .set('href', '?f=' + file.id);
        tr.getElement('td.ftr-filename a').set('text', file.fullname)
                                          .set('href', '?f=' + file.id);
        // changer & creator info
        tr.getElement('td.ftr-changed-at')
            .set('text', gui.formatDateString(file.changed));
        tr.getElement('td.ftr-changed-by').set('text', file.changer_name);
        tr.getElement('td.ftr-created-at')
            .set('text', gui.formatDateString(file.created));
        tr.getElement('td.ftr-created-by').set('text', file.creator_name);
        // close button
        if(file.opened && (cora.isAdmin() || (file.opened == cora.settings.get('name')))) {
            tr.getElement('a.closeFileLink').removeClass('start-hidden');
        }
        // admin stuff
        if(cora.isAdmin())
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
        var startChain,
            onLockSuccess, onOpenSuccess, onInitSuccess,
            onLockError,   onOpenError;

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
            cora.files.lock(fid, {onSuccess: onLockSuccess, onError: onLockError});
        };

        // 2. If lock was successful, open the file
        onLockSuccess = function() {
            gui.showSpinner({message: "Datei wird geöffnet..."});
            cora.files.open(fid, {onSuccess: onOpenSuccess, onError: onOpenError});
        };
        onLockError = function(error) {
            if(error.name === 'Handled' && error.status.lock) {
                gui.showMsgDialog('info',
                    "Das Dokument wird zur Zeit bearbeitet von Benutzer '"
                    + error.status.lock.locked_by + "' seit "
                    + gui.formatDateString(error.status.lock.locked_since).toLowerCase()
                    + ".");
            } else {
                gui.showNotice('error', "Fehler beim Öffnen der Datei.");
                error.showAsDialog();
            }
        };

        // 3. If open was successful, fetch and preprocess any closed tagsets
        onOpenSuccess = function(status) {
            this.currentFileId = fid;
            if(history.state !== null && typeof(history.state.f) === "undefined")
                history.pushState({"f": fid}, "", "?f="+fid);
            gui.setHeader(cora.files.getDisplayName(fid));
            cora.projects.performUpdate();
            cora.files.prefetchTagsets(
                fid,
                {onSuccess: function() {
                    status.onInit = onInitSuccess;
                    cora.editor = new EditorModel(fid, status);
                 },
                 onError: function(error) {
                     gui.hideSpinner();
                     gui.showNotice('error', "Fehler beim Laden der Tagsets.");
                     error.showAsDialog();
                     this.closeCurrentlyOpened();
                 }.bind(this)
                }
            );
        }.bind(this);
        onOpenError = function(error) {
            gui.hideSpinner();
            gui.showNotice('error', "Fehler beim Laden der Tagsets.");
            error.showAsDialog();
        };

        // 4. Show the editor tab and clean up
        onInitSuccess = function() {
            default_tab = 'edit';
            gui.showTabButton('edit').changeTab('edit');
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
            if(!cora.isAdmin()) {
                gui.showMsgDialog('error',
                           "Sie haben keine Berechtigung, diese Datei zu schließen.");
            } else {
                gui.confirm(
                    "Sie sind dabei, eine Datei zu schließen, die aktuell nicht von "
                        +"Ihnen bearbeitet wird. Falls jemand anderes diese Datei "
                        +"zur Zeit bearbeitet, könnten Datenverluste auftreten. "
                        +"Trotzdem schließen?",
                    function() {
                        cora.files.close(
                            fid,
                            {onSuccess: cora.projects.performUpdate.bind(cora.projects),
                             noticeOnError: true}
                        );
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
            cora.files.close(
                this.currentFileId,
                {onComplete: function() {
                    this.currentFileId = null;
                    cora.editor.destruct();
                    cora.editor = null;
                    cora.projects.performUpdate();
                    gui.setHeader("").hideTabButton('edit').changeTab('file').unlock();
                    if(history.state !== null && typeof(history.state.f) !== "undefined")
                        history.pushState({}, "", "./");
                    if(typeof(fn) == "function")
                        fn();
                }.bind(this)}
            );
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
            gui.showSpinner({message: "Bitte warten..."});
            cora.files.deleteFile(
                fid,
                {noticeOnError: true,
                 onSuccess: function(status) {
		     gui.showNotice('ok', "Datei gelöscht.");
                 },
                 onComplete: function() {
                     gui.hideSpinner();
                     cora.projects.performUpdate();
                 }
                }
            );
        };
        gui.confirm(message, performDelete, true);
    },

    /****** FILE EXPORT *******************************************************/

    /* Function: _prepareFileExportEvents

       Sets up events for the file export dialog.
     */
    _prepareFileExportEvents: function() {
        var div = $('fileExportPopup');
        if (div == null) return;
        div.addEvent(
            'change:relay(input[name=file-export-format])',
            function(e, target) {
                var elem, value = div.getElement('input:checked').get('value');
                div.getElements('.for-fileexport').hide();
                elem = div.getElement('.for-'+value);
                if (elem != null)
                    elem.show();
            }
        );
    },

    /* Function: exportMultiSelectForCSV

       Creates a dropdown box using MultiSelect.js for selecting columns in a
       CustomCSV export.
     */
    exportMultiSelectForCSV: function(fid) {
        var fixed,
            div = new Element('div', {'class': 'MultiSelect export_CustomCSV_MS'}),
            makeMultiSelectEntry = function(div, value, text) {
                var entry = new Element('input', {type: 'checkbox',
                                                  id: 'ccsv_'+value,
                                                  name: 'ccsv[]',
                                                  value: value});
                var label = new Element('label', {for: 'ccsv_'+value,
                                                  text: text});
                div.grab(entry).grab(label);
            };
        fixed = [['trans', "Token (Transkription)"],
                 ['ascii', "Token (ASCII)"],
                 ['utf', "Token (UTF)"]];
        Array.each(fixed, function(elem) {
            makeMultiSelectEntry(div, elem[0], elem[1]);
        });
        Array.each(cora.files.get(fid).tagset_links, function(tid) {
            var ts = cora.tagsets.get(tid);
            if (ts.exportable)
                makeMultiSelectEntry(div, ts.class, ts.classname);
        });
        Object.each(cora.flags, function(flag, key) {
            makeMultiSelectEntry(div, key, flag.displayname);
        });
        new MultiSelect(div, {monitorText: ' Spalte(n) ausgewählt'});
        return div;
    },

    /* Function: exportFile

       Displays a dialog to export a file and triggers the export.

       Parameters:
         fid - ID of the file to be exported
     */
    exportFile: function(fid){
        var content = $('fileExportPopup');
        this.exportMultiSelectForCSV(fid)
            .replaces(content.getElement('.export_CustomCSV_MS'));
	new mBox.Modal({
	    content: content,
	    title: 'Datei exportieren',
	    buttons: [
		{title: 'Abbrechen', addClass: 'mform'},
		{title: 'Exportieren', addClass: 'mform button_green',
		 event: function() {
                     var ccsv = [],
                         data = {do: 'exportFile', fileid: fid};
		     data.format = this.content
                         .getElement('input[name=file-export-format]:checked')
                         .get('value');
                     this.content.getElements('input[name="ccsv[]"]').each(
                         function(el) {
                             if(el.get('checked'))
                                 ccsv.push(el.get('value'));
                         }
                     );
                     if(ccsv) data.ccsv = ccsv;
		     this.close();
                     gui.showNotice('info', "Datei wird exportiert...");
                     gui.download('request.php', data);
		 }
		}
	    ]
	}).open();
    },

    /****** TAGSET ASSOCIATIONS ***********************************************/

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
        new CoraRequest({
            name: 'changeTagsetsForFile',
            textDialogOnError: true,
            onSuccess: function() {
                gui.showNotice('ok', "Tagset-Verknüpfungen geändert.");
                cora.projects.performUpdate();
            }
        }).get({'file_id': fileid, 'linktagsets': tagsets});
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
        var tagsetLinks = cora.files.get(fileid).tagset_links;
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
	contentdiv.getElement('table.tagset-list')
	    .getElements('input').each(function(input) {
		var checked = tagsetLinks.contains(input.value) ? "yes" : "";
		input.set('checked', checked);
	    });
	content.open();
    }
};

/*************************************************************************
 ************ Convenience functions **************************************
 *************************************************************************/

cora.current = function() {
    return cora.files.get(cora.fileManager.currentFileId);
};

cora.currentTagset = function(cls) {
    if (cls == "morph") cls = "pos";
    return cora.current().tagsets[cls];
};

cora.currentHasTagset = function(cls) {
    if (cls == "morph") cls = "pos";
    return (typeof(cora.current().tagsets[cls]) !== "undefined");
};

/*************************************************************************
 ************ ONPOPSTATE BINDINGS ****************************************
 *************************************************************************/

/* Make browser back/forward buttons usable (somewhat). */
window.onpopstate = function(event) {
    if(cora.fileManager !== null) {
        var eventStateHasFileID = (event.state !== null
                                   && typeof(event.state['f']) !== "undefined"),
            fid = cora.fileManager.currentFileId;
        if (!eventStateHasFileID && cora.fileManager.isFileOpened()) {
            cora.fileManager.closeFile(fid);
        } else if (eventStateHasFileID && event.state['f'] != fid) {
            cora.fileManager.openFile(event.state['f']);
        }
    }
};
