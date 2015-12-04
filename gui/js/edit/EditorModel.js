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

/* Class: EditorModel

   Main class representing an instance of the CorA editor.
 */
var EditorModel = new Class({
    Implements: [Events, DataSource, EditorModelUndo],

    fileId: 0,
    lastEditedRow: -1,
    header: "",
    dynamicLoadPages: 5,  // min. number of pages in each direction to be pre-fetched
    dynamicLoadLines: 50, // min. number of lines in each direction to be pre-fetched
    data: [],
    dataTable: null,
    searchResults: null,
    idlist: {},
    lineRequestInProgress: false,
    horizontalTextView: null,
    flagHandler: null,

    currentChanges: null,
    pendingChanges: null,
    saveRequest: null,
    saveFailures: 0,
    saveFailureNotice: null,
    autosaveInterval: 60,  // in seconds
    autosaveTimerId: null,

    getRangeRetryTimer: null,
    getRangeStart: null,
    getRangeEnd: null,

    visibility: {},  // column visibility -- to be refactored soon

    /* Constructor: EditorModel

       Initialize the editor model.

       Parameters:
         fileid - ID of the file represented by this model
         options - Object containing the following options:
           * data.header - File header
	   * maxLinesNo - Total number of lines in the file
	   * lastEditedRow - Line number that was last edited
           * lastPage - The page to first display
           * onInit - Callback function after successful initialization
    */
    initialize: function(fileid, options) {
        var mr, btn;

        /* File-specific variables */
	if(options.lastEditedRow !== null) {
	    this.lastEditedRow = Number.from(options.lastEditedRow);
	}
	this.fileId = fileid;
        this.header = options.data.header;
        this._initializeIdList(options.data.idlist);
        this.flagHandler = new FlagHandler();
        this.addEvent('applyChanges', function(){ gui.getPulse().addClass("unsaved"); });

        /* Search function */
        this.tokenSearcher =
            new TokenSearcher(this,
                              cora.current().tagsets, this.flagHandler,
                              {content: 'searchTokenForm',
                               template: 'editSearchCriterionTemplate',
                               panels: ['pagePanel', 'searchPagePanel'],
                               onSearchRequest: this.onSearchRequest.bind(this),
                               onSearchSuccess: this.onSearchSuccess.bind(this)
                              });

        /* Data table */
        this.dataTable =
            new DataTable(this,
                          cora.current().tagsets, this.flagHandler,
                          {lineCount: Number.from(options.maxLinesNo),
                           progressMarker: this.lastEditedRow,
                           pageModel: {
                               panels: ['pagePanel', 'pagePanelBottom'],
                               startPage: Number.from(options.lastPage)
                           },
                           onFocus: this.onDataTableFocus.bind(this),
                           onRender: this.onDataTableRender.bind(this),
                           onUpdate: this.update.bind(this),
                           onUpdateProgress: this.updateProgress.bind(this)
                          }
                         );
	this.initializeColumnVisibility();
        this.dataTable.table.replaces($('editTable'));
        this.dataTable.table.set('id', 'editTable');

        this.dataTable.addDropdownEntries([
            {name: 'Search',
             text: _("EditorTab.dropDown.searchSimilar"),
             action: this.searchSimilarTokens.bind(this)}
        ]);
        if (cora.files.supportsTokenEditing(fileid)) {
            this.dataTable.addDropdownEntries([
                {name: 'Edit',
                 text: _("EditorTab.dropDown.editToken"),
                 action: this.editToken.bind(this)},
                {name: 'Add',
                 text: _("EditorTab.dropDown.addToken"),
                 action: this.addToken.bind(this)},
                {name: 'Delete',
                 text: _("EditorTab.dropDown.delToken"),
                 action: this.deleteToken.bind(this)}
            ]);
	    this.dataTable.addEvent(
	        'dblclick',  // triggers on <td> elements
	        function(target, id) {
		    if (target.hasClass("editTable_token") ||
		        target.hasClass("editTable_tok_trans")) {
		        // we don't want text to get selected here:
		        if (window.getSelection)
			    window.getSelection().removeAllRanges();
		        else if (document.selection)
			    document.selection.empty();
		        this.editToken(id);
		    }
	        }.bind(this)
	    );
        } // endif cora.files.supportsTokenEditing(fileid)
	this.dataTable.addEvent(
	    'focus',  // triggers on contained <input> and <select> elements
	    function(target, id) {
                // Highlight target in text preview
		this.horizontalTextView.highlight(id);
                // Is target hidden behind header?
                var scrolltop_min = target.getTop() - $('header').getHeight() - 10;
                if(window.getScrollTop() > scrolltop_min) {
                    window.scrollTo(0, scrolltop_min);
                }
                // Is target hidden behind text preview?
                var scrolltop_max = target.getTop()
                    - window.getHeight()
                    + $('horizontalTextViewContainer').getHeight()
                    + target.getHeight() + 10;
                if(scrolltop_max > window.getScrollTop()) {
                    window.scrollTo(0, scrolltop_max);
                }
	    }.bind(this)
	);

        /* Child objects */
        this.horizontalTextView =
            new HorizontalTextPreview(this, $('horizontalTextViewContainer'));
        this.updateShowInputErrors(true);

        /* Save functionality */
        this._activateSaveFunctionality();

	/* Activate extra menu bar */
	mr = $('menuRight');
	btn = mr.getElement('#closeButton');
	btn.removeEvents();
	btn.addEvent('click', function(e) {
	    e.stop();
	    cora.fileManager.closeFile(this.fileId);
	}.bind(this));

	/* Prepare automatic annotation dialog */
	btn = $('pagePanel').getElement('.btn-text-annotate');
	if(btn && btn !== undefined) {
	    btn.removeEvents();
	    this.activateAnnotationDialog(btn);
	    this.prepareAnnotationOptions();
	}
	mr.getElements('.when-file-open-only').addClass('file-open');

        /* Prepare undo/redo functionality */
        this.activateUndoButtons(['pagePanel']);

        /* Activate "edit metadata" form */
        this._activateMetadataForm($('pagePanel'));

        if(typeof(cora.initAdminLogging) === "function")
            cora.initAdminLogging(this);

	/* render start page */
        if(options.onInit)
            this.dataTable.addEvent('render:once', options.onInit);
        this.dataTable.render();
    },

    /* Function: _initializeIdList

     */
    _initializeIdList: function(idlist) {
        this.idlist = {};
        Array.each(idlist, function(item, idx) {
            this[item] = idx;
        }.bind(this.idlist));
    },

    /* Function: _activateSaveFunctionality

       All initialization code related to saving of documents.
     */
    _activateSaveFunctionality: function() {
        var ref = this;
        var activateAutosaveTimer = function() {
            this.autosaveTimerId = setInterval(this.save.bind(this),
                                               this.autosaveInterval * 1000);
        }.bind(this);
        this.currentChanges = new DataChangeSet();
        this.pendingChanges = null;
        this.saveRequest = new CoraRequest({
            name: 'saveData',
            method: 'post',
            onSuccess: function(status) {
                ref.pendingChanges = null;
                ref.updateSaveStatus(true);
                this.fireEvent('processed', [true, status]);
            },
            onError: function(error) {
                ref.currentChanges.merge(ref.pendingChanges);
                ref.pendingChanges = null;
                ref.updateSaveStatus(false);
                this.fireEvent('processed', [false, error]);
                if(error.name === 'Handled') {
                    gui.showTextDialog(
                        "Kritischer Fehler",
                        "Beim Speichern des Dokuments ist ein server-seitiger "
                            + "Fehler aufgetreten.  Um Datenverluste zu vermeiden, "
                            + "bearbeiten Sie das Dokument bitte nicht weiter und "
                            + "melden diesen Fehler einem Administrator.",
                        error.details
                    );
                }
            }
        });
        activateAutosaveTimer();
    },

    /* Function: _activateMetadataForm

       Activates form to view/edit file metadata such as name and
       header.
    */
    _activateMetadataForm: function(div) {
        var ref  = this;
        var elem = div.getElement('span.btn-text-info');
        if (elem != null) {
            elem.removeEvents('click');
            elem.addEvent('click', function() {
                var content = $('fileMetadataForm');
                var current = cora.files.get(this.fileId);
                var buttons = [{title: _("Action.close"), addClass: "mform"}];
                var may_modify = cora.files.mayDeleteFile(this.fileId);
                content.getElement('input[name="fmf-sigle"]')
                    .set('value', current.sigle)
                    .set('disabled', !may_modify);
                content.getElement('input[name="fmf-name"]')
                    .set('value', current.fullname)
                    .set('disabled', !may_modify);
                content.getElement('textarea')
                    .set('value', this.header)
                    .set('disabled', !may_modify);
                if(may_modify) {
                    buttons.unshift({
                        title: _("Action.change"), addClass: "mform button_red",
                        event: function() {
                            ref.saveMetadataFromForm(this);
                        }
                    });
                }
                new mBox.Modal({
                    content: content,
                    title: _("EditorTab.metaData"),
                    buttons: buttons,
                    closeOnBodyClick: false
                }).open();
            }.bind(this));
        }
    },

    /* Function: destruct

       Clean-up when closing the editor.
     */
    destruct: function() {
        if (this.autosaveTimerId !== null)
            clearInterval(this.autosaveTimerId);
        if (this.searchResults !== null)
            this.searchResults.destroy();
        this.dataTable.hide();
        $$('div#menuRight .when-file-open-only').removeClass('file-open');
    },

    /* Function: saveMetadataFromForm

       Sends a server request to save metadata changes.
     */
    saveMetadataFromForm: function(mbox) {
        var sigle, name, header, form = mbox.content;
        form.spin();
        sigle  = form.getElement('input[name="fmf-sigle"]').get('value');
        name   = form.getElement('input[name="fmf-name"]').get('value');
        header = form.getElement('textarea').get('value');
        new CoraRequest({
            name: "saveMetadata",
            textDialogOnError: true,
            onSuccess: function(status) {
                cora.projects.performUpdate();
                gui.setHeader(cora.files.getDisplayName(
                    {id: this.fileId,
                     sigle: sigle,
                     fullname: name}
                ));
                this.header = header;
                gui.showNotice("ok", "Metadaten erfolgreich geändert.");
                mbox.close();
            }.bind(this),
            onComplete: function() { form.unspin(); }
        }).post({'id': this.fileId, 'sigle': sigle,
                 'name': name, 'header': header});
    },

    /* Function: initializeColumnVisibility

       Hide or show columns based on whether the required tagsets are
       associated with the currently opened file.
    */
    initializeColumnVisibility: function() {
        // Determine visibility
        this.visibility = {
            'tokenid': cora.settings.isColumnVisible('tokenid'),
            'token': cora.settings.isColumnVisible('token'),
            'tok_trans': cora.settings.isColumnVisible('tok_trans')
        };

        // Check all tagsets
        Array.each(cora.supportedTagsets, function(ts) {
            this.visibility[ts] = cora.currentHasTagset(ts)
                                  && cora.settings.isColumnVisible(ts);
            cora.settings.setColumnActive(ts, cora.currentHasTagset(ts));
        }.bind(this));

	// Show/hide columns
	Object.each(this.visibility, function(visible, value) {
            this.dataTable.setVisibility(value, visible);
	}.bind(this));
    },

    /* Function: setColumnVisibility

       Sets visibility of an annotation column.

       Parameters:
         name - Column name (e.g., "pos")
         visible - Whether the column should be visible
     */
    setColumnVisibility: function(name, visible) {
        this.visibility[name] = visible;
        this.dataTable.setVisibility(name, visible);
        if (this.searchResults !== null)
            this.searchResults.dataTable.setVisibility(name, visible);
    },

    /* Function: updateShowInputErrors

       Show or hide input error visualization for each row in the table.  Used
       when the corresponding user setting changes.
     */
    updateShowInputErrors: function(no_redraw) {
        Object.each(cora.current().tagsets, function(tagset) {
            tagset.setShowInputErrors(cora.settings.get('showInputErrors'));
        });
        if(!no_redraw)
            this.dataTable.empty().render();
    },

    /* Function: get

       Retrieves a data entry by its num attribute.

       Parameters:
         num - Index of the data entry

       Returns:
         The respective data entry; if it does not exist or has not been
         fetched from the server yet, undefined is returned instead.
     */
    get: function(num) {
        return this.data[num];
    },

    /* Function: getRange

       Retrieves a set of data entries in a given range.

       Parameters:
         start - Index of the first data entry to be retrieved
         end   - Index right *after* the last data entry to be retrieved
         callback - Callback function to invoke, with the set of data entries
                    as the first argument

       Returns:
         False if the data does not exist or an asynchronous server call has
         to be made first; true if the data was already loaded.  Use the
         callback parameter to actually get to and process the data.
     */
    getRange: function(start, end, callback) {
        // Reset any timers, and store the current (start, end) values in a
        // class variable.  This way, the callbacks can see if their parent
        // getRange() call has been superseded by a newer getRange() call.
        this.getRangeStart = start;
        this.getRangeEnd = end;
        if(this.getRangeRetryTimer !== null)
            clearTimeout(this.getRangeRetryTimer);
        // Do we need to do anything?
        if(this.isRangeLoaded(start, end)) {
            if (typeof(callback) === "function")
                callback(this._slice(this.data, start, end));
            return true;
        }
        // Perform server request
        this.requestLines(
            start, end,
            function() {  // onSuccess
                if (this.getRangeStart !== start || this.getRangeEnd !== end)
                    return;  // we're too late
                if (typeof(callback) === "function")
                    callback(this._slice(this.data, start, end));
            }.bind(this),
            function(e) {  // onError
                if (this.getRangeStart !== start || this.getRangeEnd !== end)
                    return;  // we're too late
                gui.showNotice('error', e.message,
                               (e.name === 'FailureToLoadLines'));
                this.getRangeRetryTimer = setTimeout(
                    function(){
                        this.getRangeRetryTimer = null;
                        this.getRange(start, end, callback);
                    }.bind(this),
                    5000);
            }.bind(this)
        );
        return false;
    },

    /* Function: applyChanges

       Apply a set of changes to a data object.

       Parameters:
         data - The original data object
         changes - A set of changes to apply
         caller - (optional) Source of the call, if not triggered by user change
     */
    applyChanges: function(data, changes, caller) {
        var changedData, id;
        // handle lastEditedRow
        if(typeof(changes['lastEditedRow']) !== "undefined") {
            this.lastEditedRow = changes['lastEditedRow'];
            id = (this.lastEditedRow < 0) ? -1 : this.get(this.lastEditedRow).id;
            this.currentChanges.lastEditedRow = id;
            this.dataTable.redrawProgressMarker(this.lastEditedRow);
            if (this.searchResults !== null)
                this.searchResults.dataTable.redrawProgressMarker(this.lastEditedRow);
        }
        // all token data
        if(data && typeof(data.num) !== "undefined") {
            changedData = this.currentChanges.at(data.num);
            changedData['id'] = data.id;
            Object.each(changes, function(value, key) {
                if(key === "lastEditedRow")
                    return;
                data[key] = value;
                changedData[key] = value;
            }.bind(this));
            if (typeof(caller) !== "undefined")
                this.dataTable.redrawRow(data.num, data);
            if (caller !== 'search' && this.searchResults !== null)
                this.searchResults.dataTable.redrawRow(data.num, data);
        }
        // fire event, e.g. for logging
        this.fireEvent('applyChanges', [data, changes, caller]);
    },

    /* Function: update

       Callback function of DataTable that is invoked whenever an annotation
       changes.
     */
    update: function(elem, data, changes, cls, value) {
        if (data.num > this.lastEditedRow)
            changes['lastEditedRow'] = data.num;
        this.logUndoInformation(data, changes, this.lastEditedRow);
    },

    /* Function: updateProgress

       Callback function of DataTable that is invoked whenever the progress bar
       updates.

       Currently ONLY called on progress change triggered by user!!!
     */
    updateProgress: function(num, changes) {
        changes['lastEditedRow'] = num;
        this.logUndoInformation({}, changes, this.lastEditedRow);
    },

    /* Function: onDataTableFocus

       Whenever focus in the data table changes, highlight the respective token
       in the text preview and make sure the focussed element is not hidden
       behind other UI elements.
     */
    onDataTableFocus: function(target, id) {
        // Highlight target in text preview
	this.horizontalTextView.highlight(id);
        // Is target hidden behind header?
        var scrolltop_min = target.getTop() - $('header').getHeight() - 10;
        if(window.getScrollTop() > scrolltop_min) {
            window.scrollTo(0, scrolltop_min);
        }
        // Is target hidden behind text preview?
        var scrolltop_max = target.getTop()
                - window.getHeight()
                + $('horizontalTextViewContainer').getHeight()
                + target.getHeight() + 10;
        if(scrolltop_max > window.getScrollTop()) {
            window.scrollTo(0, scrolltop_max);
        }
    },

    /* Function: onDataTableRender

       Callback function that is invoked whenever the data table is re-rendered.
     */
    onDataTableRender: function(data) {
        var start = data[0].num,
            end = data[data.length-1].num,
            dlr = Math.max(this.dynamicLoadPages *
                           (cora.settings.get('noPageLines')
                            - cora.settings.get('contextLines')),
                           this.dynamicLoadLines);

	this.horizontalTextView.update(start, end);
        this.requestLines(start - dlr, end + dlr);  // dynamic pre-fetching
    },

    /* Function: onSearchRequest

       Callback function that is invoked whenever a search query is started.
     */
    onSearchRequest: function() {
    },

    /* Function: onSearchSuccess

       Callback function that is invoked whenever a search query successfully
       returns results.
     */
    onSearchSuccess: function(criteria, status) {
        var data;
        Array.each(status['results'], this.setLineFromServer.bind(this));
        data = status['results'].map(function(line) {
            return this.get(line.num);
        }.bind(this));
        if(this.searchResults !== null) {
            this.searchResults.destroy();
        }
        this.searchResults = new SearchResults(this, criteria, data, 'pagePanel');
        gui.changeTab('search');
    },

    /* Function: searchSimilarTokens

       Opens the token searcher with predefined fields to search for tokens
       similar to the selected one.

       Parameters:
         tok_id - The line ID of the token that was selected
     */
    searchSimilarTokens: function(tok_id) {
        var data = this.data[tok_id];
        if (typeof(data) !== "undefined" && this.tokenSearcher != null)
            this.tokenSearcher.setFromData(data).open();
    },

    /* Function: getMinimumLineRange

       Calculates the minimum range of lines that must be requested
       from the server to ensure that all lines in a given range are
       in memory. Can be used to check whether lines in a given range
       are already loaded.

       Parameters:
         start - Number of the first line
         end - Number of the line after the last line

       Returns:
         An array containing an optimal pair of (start, end)
         values, or an empty array if all lines are already loaded.
     */
    getMinimumLineRange: function(start, end) {
	var keys = Object.keys(this.data);
	var brk  = false;

	if (start<0) { start = 0; }
	if (end>this.dataTable.lineCount) { end = this.dataTable.lineCount; }

	for (var j=start; j<end; j++) {
	    if (!keys.contains(String.from(j))) {
		start = j;
		brk = true;
		break;
	    }
	}
	if (!brk) { // all the lines are already there
	    return [];
	}

	for (var j=end-1; j>=start; j--) {
	    if (!keys.contains(String.from(j))) {
		end = j+1;
		break;
	    }
	}

	return [start, end];
    },

    /* Function: isRangeLoaded

       Checks whether the lines in the given range are already loaded
       in memory and need not be requested from the server.

       Parameters:
         start - Number of the first line
         end - Number of the line after the last line

       Returns:
         True if all lines are already in memory
     */
    isRangeLoaded: function(start, end) {
	return (this.getMinimumLineRange(start, end).length === 0);
    },

    /* Function: setLineFromServer

     */
    setLineFromServer: function(line) {
        var num = line.num;
        if (num === null || typeof(num) === "undefined") {
            num = this.idlist[line.id];
            line.num = num;
        }
        if (typeof(num) !== "undefined" && typeof(this.data[num]) === "undefined")
            this.data[num] = line;
    },

    /* Function: requestLines

       Ensures that lines in a given range are loaded in memory, and
       requests them from the server if necessary.

       Parameters:
         start - Number of the first line to load
	 end - Number of the line after the last line to load
         fn - Callback function to invoke when the given range is in memory
         onerror - Callback function to invoke on error
    */
    requestLines: function(start, end, fn, onerror) {
        var range, internal_error;
        /* request in progress? -> come back later */
        if(this.lineRequestInProgress) {
            setTimeout(function(){this.requestLines(start,end,fn,onerror);}.bind(this),
                       50);
            return;
        }
        /* get minimum required range */
	range = this.getMinimumLineRange(start, end);
	if(range.length === 0) {  // no server request necessary
            if(typeof(fn) === "function")
                fn();
	    return;
	}
        /* start request */
        internal_error = function() {
            if(typeof(onerror) === "function")
                onerror({
		    'name': 'FailureToLoadLines',
		    'message': "Ein interner Fehler ist aufgetreten: Zeilen "+start+" bis "+(end-1)+" können nicht geladen werden."
	        });
        };
        this.lineRequestInProgress = true;
	new CoraRequest({
            name: 'getLinesById',
	    onSuccess: function(status) {
                if(typeof(status.data) === "undefined" || !status.data) {
                    internal_error(); return;
                }
                for(var i = 0; i < status.data.length; i++) {
                    this.setLineFromServer(status.data[i]);
                }
                if (this.getMinimumLineRange(start, end).length === 0) {
                    if(typeof(fn) === "function")
                        fn();
                } else {
                    internal_error();
                }
	    }.bind(this),
            onError: function(error) {
                if(typeof(onerror) === "function")
                    onerror({'name': error.name, 'message': error.message});
            },
            onComplete: function() { this.lineRequestInProgress = false; }.bind(this)
	}).get({'start_id': range[0], 'end_id': range[1]});
    },

    /* Function: hasUnsavedChanges

       Whether there are any unsaved changes.
     */
    hasUnsavedChanges: function() {
        return !(this.currentChanges.isEmpty() && this.pendingChanges === null);
    },

    /* Function: save

       Send a server request to save the modified lines to the database.

       NOTE: When a server request to save data is already running, this
       function simply returns.  This means that calling save() does NOT
       guarantee that all data will be saved afterwards; use whenSaved() for
       that.
     */
    save: function() {
        if(!this.hasUnsavedChanges() || this.pendingChanges !== null)
            return false;

        this.pendingChanges = this.currentChanges;
        this.currentChanges = new DataChangeSet();
        this.saveRequest.send(this.pendingChanges.json());
        return true;
    },

    /* Function: updateSaveStatus

       Keeps track if (and how often) saving fails and updates a status
       indicator accordingly.
     */
    updateSaveStatus: function(success) {
        var pulse = gui.getPulse();
        if(success) {
            pulse.removeClass("unsaved");
            if(this.saveFailures > 0 && this.saveFailureNotice !== null)
                this.saveFailureNotice.close();
            this.saveFailures = 0;
        } else {
            pulse.addClass("unsaved");
            this.saveFailures++;
            if(this.saveFailures > 2) {
                this.saveFailureNotice = gui.showNotice('notice',
                    "Dokument kann derzeit nicht gespeichert werden.");
            }
        }
    },

    /* Function: whenSaved

       Saves any remaining changes to the current document, and calls a supplied
       function when and only when all changes have been saved.

       Parameters:
         fn - Function to call after successful saving
         spinnerOptions - When given, shows a spinner while waiting for changes
                          to be saved
         fail - Function to call when saving fails
     */
    whenSaved: function(fn, spinnerOptions, fail) {
        if (!this.hasUnsavedChanges()) {
            if (spinnerOptions)
                gui.hideSpinner();
            fn();
            return;
        }

        if (spinnerOptions)
            gui.showSpinner(spinnerOptions);
        this.saveRequest.addEvent('processed:once', function(success, details) {
            if (spinnerOptions)
                gui.hideSpinner();
            if (success) {
                fn();
            } else {
                gui.showMsgDialog('error',
                    "Das Dokument kann derzeit nicht gespeichert werden; der "
                    + "aktuelle Vorgang wird daher abgebrochen.  Überprüfen Sie "
                    + "Ihre Internetverbindung und versuchen Sie es ggf. erneut.");
                if (typeof(fail) === "function")
                    fail();
            }
        }.bind(this));
        this.save();
    },

    /* Function: confirmClose

       Makes sure that all unsaved changes have been saved before allowing the
       file to be closed.

       Note that the actual closing of the file is not implemented
       in this class, but in file.js.

       Parameters:
         fn - Function to call when closing is safe
    */
    confirmClose: function(fn) {
        this.whenSaved(fn, {message: "Bitte warten..."});
    },

    /* Function: updateDataArray

       Updates the line array after a server-side edit operation.

       All lines after the token which has changed are deleted from
       memory, all changes after that line are reset, and the
       currently displayed page is refreshed (fetching the previously
       deleted lines anew from the server, if necessary).

       Parameters:
        tok_id - the ID of the token which is affected by the edit
	lcdiff - the difference in line count after the edit operation
    */
    updateDataArray: function(tok_id, lcdiff) {
        var afterRequestIdList = function() {
	    tok_id = Number.from(tok_id);
	    // delete all lines after the changed line from memory
	    this.data = Object.filter(this.data, function(item, index) {
	        return index < tok_id;
	    });
            // clear undo/redo information
            this.clearUndoStack().clearRedoStack();
	    // update line count and re-load page
            this.dataTable.setLineCount(this.dataTable.lineCount + lcdiff).render();
        }.bind(this);

        // request new ID list
        new CoraRequest({
            name: 'getAllModernIDs',
            onSuccess: function(status) {
                this._initializeIdList(status.data);
                afterRequestIdList();
            }.bind(this),
            onError: function(error) {
                // try closing and re-opening
                var id = cora.current().id;
                cora.fileManager.closeCurrentlyOpened(function() {
                    cora.fileManager.openFile(id);
                });
            }.bind(this),
            onComplete: function() { gui.hideSpinner(); }
        }).get();
    },

    /* Function: _resizeTextarea

       Resizes a <textarea> element based on the required number of lines.

       Parameters:
         event - A trigger event (e.g. keydown) that called this function
    */
    _resizeTextarea: function(event) {
        event.target.rows = event.target.get('value').split("\n").length;
    },

    /* Function: sendEditRequest

       Sends a request to edit the primary data.
     */
    sendEditRequest: function(options) {
        new CoraRequest({
            name: options.requestName,
            textDialogOnError: true,
            onSuccess: function(status) {
                gui.showNotice('ok', options.successNotice);
                this.updateDataArray(options.tokId, options.getDiff(status));
            }.bind(this),
            onError: function(error) {
                gui.showNotice('error', 'Primärdaten nicht geändert.');
                gui.hideSpinner();
            }
        }).get(options.request);
    },

    /* Function: deleteToken

       Display a pop-up with the option to delete a token.

       Parameters:
         tok_id - The line ID of the token to be deleted
     */
    deleteToken: function(tok_id) {
	var ref = this;
	var old_token = this.data[tok_id]['full_trans'];
	var db_id = this.data[tok_id]['tok_id'];
	while(this.data[tok_id-1] !== undefined && this.data[tok_id-1]['tok_id'] === db_id) {
	    // set tok_id to the first line corresponding to the token to be edited
	    tok_id = tok_id - 1;
	}

    $('deleteTokenToken').empty().appendText(_("EditorTab.Forms.deletionPrompt", {'tok2del' : old_token}));
	new mBox.Modal({
	    title: _("EditorTab.Forms.confirmDeletion"),
	    content: 'deleteTokenWarning',
	    buttons: [
		{title: _("Action.noCancel"), addClass: 'mform'},
		{title: _("Action.yesDelete"), addClass: 'mform button_red',
		 event: function() {
		     this.close();
		     gui.showSpinner({message: 'Bitte warten...'});
                     ref.whenSaved(
                         function() {
                             ref.sendEditRequest({
                                 tokId: tok_id,
                                 getDiff: function(s){
                                     return -Number.from(s.oldmodcount);
                                 },
                                 successNotice: "Token gelöscht.",
                                 request: {'token_id': db_id},
                                 requestName: 'deleteToken'
                             });
                         },
                         null,
                         function() { gui.hideSpinner(); }
                     );
		 }
		}
	    ],
	    closeOnBodyClick: false
	}).open();
        this.save();
    },

    /* Function: editToken

       Display a pop-up with the option to edit the transcription of a
       token.

       Parameters:
         tok_id - The line ID of the token to be edited
     */
    editToken: function(tok_id) {
	var ref = this;
	var old_token = this.data[tok_id]['full_trans'];
	var db_id = this.data[tok_id]['tok_id'];
	while(this.data[tok_id-1] !== undefined && this.data[tok_id-1]['tok_id'] === db_id) {
	    // set tok_id to the first line corresponding to the token to be edited
	    tok_id = tok_id - 1;
	}

	$('editTokenBox').set('value', old_token);
	var editTokenBox = new mBox.Modal({
	    title: _("EditorTab.Forms.editTranscription"),
	    content: 'editTokenForm',
	    buttons: [
		{title: _("Action.cancel"), addClass: 'mform'},
		{title: _("Action.save"), addClass: 'mform button_green',
		 event: function() {
	             var new_token = $('editTokenBox').get('value').trim();
                     if (!new_token) {
                         gui.showNotice('error', "Transkription darf nicht leer sein!");
                     }
                     this.close();
                     if (new_token == old_token)
                         return;
		     gui.showSpinner({message: 'Bitte warten...'});
                     ref.whenSaved(
                         function() {
                             ref.sendEditRequest({
                                 tokId: tok_id,
                                 getDiff: function(s){
                                     return Number.from(s.newmodcount)-Number.from(s.oldmodcount);
                                 },
                                 successNotice: "Token erfolgreich bearbeitet.",
                                 request: {'token_id': db_id, 'value': new_token},
                                 requestName: 'editToken'
                             });
                         },
                         null,
                         function() { gui.hideSpinner(); }
                     );
                 }
		}
	    ],
	    onOpenComplete: function() {
		$('editTokenBox').focus();
	    },
	    closeOnBodyClick: false
	});
	$('editTokenBox').removeEvents('keyup');
	$('editTokenBox').addEvent('keyup', this._resizeTextarea);
        this._resizeTextarea({target: $('editTokenBox')});
        this.save();
	editTokenBox.open();
    },

    /* Function: addToken

       Display a pop-up with the option to add a new token to the
       transcription.

       Parameters:
         tok_id - The line ID of the token that was selected
     */
    addToken: function(tok_id) {
	var ref = this;
	var old_token = this.data[tok_id]['full_trans'];
	var db_id = this.data[tok_id]['tok_id'];
	while(this.data[tok_id-1] !== undefined && this.data[tok_id-1]['tok_id'] === db_id) {
	    // set tok_id to the first line corresponding to the token to be edited
	    tok_id = tok_id - 1;
	}
	var lineinfo = "";
	if(this.data[tok_id]['page_name'] !== undefined) {
	    lineinfo = this.data[tok_id]['page_name'] + this.data[tok_id]['page_side']
		+ this.data[tok_id]['col_name'] + "," + this.data[tok_id]['line_name'];
	}

	$('addTokenBox').set('value', '');
        $('addTokenBefore').empty().appendText(
            _("EditorTab.Forms.newTransInfo3",
              {'tokInFront' : old_token, 'lineInfo' : lineinfo})
        );

	var addTokenBox = new mBox.Modal({
	    title: _("EditorTab.Forms.addTranscription"),
	    content: 'addTokenForm',
	    buttons: [
		{title: _("Action.cancel"), addClass: 'mform'},
		{title: _("Action.save"), addClass: 'mform button_green',
		 event: function() {
	             var new_token = $('addTokenBox').get('value').trim();
	             if(!new_token) {
		         gui.showNotice('error', "Transkription darf nicht leer sein!");
                         return;
	             }
	             this.close();
	             gui.showSpinner({message: 'Bitte warten...'});
                     ref.whenSaved(
                         function() {
                             ref.sendEditRequest({
                                 tokId: tok_id,
                                 getDiff: function(s){
                                     return Number.from(s.newmodcount);
                                 },
                                 successNotice: "Token erfolgreich hinzugefügt.",
                                 request: {'token_id': db_id, 'value': new_token},
                                 requestName: 'addToken'
                             });
                         },
                         null,
                         function() { gui.hideSpinner(); }
                     );
		 }
		}
	    ],
	    onOpenComplete: function() {
		$('addTokenBox').focus();
	    },
	    closeOnBodyClick: false
	});
	$('addTokenBox').removeEvents('keyup');
	$('addTokenBox').addEvent('keyup', this._resizeTextarea);
        this._resizeTextarea({target: $('addTokenBox')});
        this.save();
	addTokenBox.open();
    },

    /* Function: prepareAnnotationOptions
     */
    prepareAnnotationOptions: function() {
	var aaselect = $('automaticAnnotationForm').getElement('#aa_tagger_select');
        var alltaggers = cora.current().taggers;
	var onTaggerChange = function(e) {
	    var id;
	    var trainbtn = e.target.getParent("div.mBoxContainer").getElement("#trainStartButton");
	    try {
		id = e.target.get('value');
	    } catch(e) {
		trainbtn.set('disabled', true);
		return;
	    }
	    Array.each(alltaggers, function(tagger) {
		if(tagger.id == id) {
		    trainbtn.set('disabled', !tagger.trainable);
		}
	    });
	};
	aaselect.empty();
        if(alltaggers.length > 0) {
	    Array.each(alltaggers, function(tagger) {
	        aaselect.adopt(new Element('input',
				           {'value': tagger.id,
                                            'type':  'radio',
                                            'name':  'aa_tagger_select',
                                            'id':    'aats_'+tagger.id}));
                aaselect.adopt(new Element('label',
                                           {'for':   'aats_'+tagger.id,
                                            'text':  ' '+tagger.name}));
                aaselect.adopt(new Element('br'));
	    });
	    aaselect.getElements("input").removeEvents();
	    aaselect.getElements("input").addEvent('change', onTaggerChange);
            aaselect.getElements("input")[0].set('checked', true);
	    onTaggerChange({'target': aaselect.getElements("input")[0]});
            aaselect.getParent("div.mBoxContainer").getElement("#annoStartButton").set("disabled", false);
        }
        else {
            aaselect.appendText(_("EditorTab.noTaggerAvailable"));
            aaselect.getParent("div.mBoxContainer").getElement("#trainStartButton").set("disabled", true);
            aaselect.getParent("div.mBoxContainer").getElement("#annoStartButton").set("disabled", true);
        }
    },

    /* Function: activateAnnotationDialog

       Activates the dialog window for performing automatic annotation
       on the currently opened file, along with the GUI functionality
       for requesting the annotation.

       Parameters:
         button - the element to which the dialog should be attached
     */
    activateAnnotationDialog: function(button) {
	var ref = this;
	var mbox;
        this._initializeAnnotationDialog();
        button.addEvent('click', function() { ref.mboxAnnotation.open(); });
        gui.onLocaleChange(function() {
            this._initializeAnnotationDialog();
            this.prepareAnnotationOptions();
        }.bind(this));
    },

    _initializeAnnotationDialog: function() {
        var ref = this;
	var content = 'automaticAnnotationForm';
	var performAnnotation = function(action) {
	    var taggerID = $(content)
                    .getElement('input[name="aa_tagger_select"]:checked')
                    .get('value');
            new CoraRequest({
                name: 'performAnnotation',
                textDialogOnError: true,
                retry: 0,
                onSuccess: function(status) {
                    if(action == "train") {
                        gui.showNotice('ok', 'Neu trainieren war erfolgreich.');
		        gui.hideSpinner();
                    } else {
			gui.showNotice('ok', 'Automatische Annotation war erfolgreich.');
			// clear and reload all lines
			ref.updateDataArray(0, 0);
                    }
                },
                onError: function(error) {
		    gui.hideSpinner();
                }
            }).get({'action': action, 'tagger': taggerID});
	    ref.mboxAnnotation.close();
	};

	// define the dialog window
	ref.mboxAnnotation = new mBox.Modal({
	    title: _("EditorTab.autoAnnotationTitle"),
	    content: content,
            onOpen: function() { ref.save(); },
	    buttons: [ {title: _("Action.retrain"), addClass: "mform button_left button_yellow",
                        id: "trainStartButton",
                        event: function() {
                            this.close();
	                    gui.showSpinner({message: 'Bitte warten...'});
                            ref.whenSaved(
                                function() { performAnnotation("train"); },
                                null,
                                function() { gui.hideSpinner(); }
                            );
                        }},
                       {title: _("Action.annotate"), addClass: "mform button_green",
			id: "annoStartButton",
			event: function() {
                            this.close();
	                    gui.showSpinner({message: 'Bitte warten...'});
                            ref.whenSaved(
                                function() { performAnnotation("anno"); },
                                null,
                                function() { gui.hideSpinner(); }
                            );
                        }},
		       {title: _("Action.cancel"), addClass: "mform",
			event: function() { this.close(); }}
		     ]
	});
    }
});
