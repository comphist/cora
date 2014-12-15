/* Class: EditorModel

   Main class representing an instance of the CorA editor.
 */
var EditorModel = new Class({
    fileId: 0,
    lastEditedRow: -1,
    header: "",
    changedLines: null,
    tries: 0,
    maximumTries: 20,     // max. number of load requests before giving up
    dynamicLoadPages: 5,  // min. number of pages in each direction to be pre-fetched
    dynamicLoadLines: 50, // min. number of lines in each direction to be pre-fetched
    data: {},
    dataTable: null,
    idlist: {},
    lineRequestInProgress: false,
    horizontalTextView: null,
    flagHandler: null,

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
	this.changedLines = new Array();
	this.fileId = fileid;
        this.header = options.data.header;
        Array.each(options.data.idlist, function(item, idx) {
            this.idlist[item] = idx;
        }.bind(this));
        this.flagHandler = new FlagHandler();

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
                           onUpdateProgress: function(n){
                               this.lastEditedRow = n;
                           }.bind(this)
                          }
                         );
	this.initializeColumnVisibility();
        this.dataTable.table.replaces($('editTable'));
        this.dataTable.table.set('id', 'editTable');

        this.dataTable.addDropdownEntries([
            {name: 'Edit',
             text: 'Token bearbeiten...',
             action: this.editToken.bind(this)},
            {name: 'Add',
             text: 'Token hinzufügen...',
             action: this.addToken.bind(this)},
            {name: 'Delete',
             text: 'Token löschen',
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

	/* Activate extra menu bar */
	mr = $('menuRight');
	btn = mr.getElement('#saveButton');
	btn.removeEvents();
	btn.addEvent('click', function(e) {
	    e.stop();
	    this.saveData();
	}.bind(this));
	btn = mr.getElement('#closeButton');
	btn.removeEvents();
	btn.addEvent('click', function(e) {
	    e.stop();
	    cora.fileManager.closeFile(this.fileId);
	}.bind(this));

	/* Prepare automatic annotation dialog */
	btn = mr.getElement('#tagButton');
	if(btn && btn !== undefined) {
	    btn.removeEvents();
	    this.activateAnnotationDialog(btn);
	    this.prepareAnnotationOptions();
	}
	mr.getElements('.when-file-open-only').addClass('file-open');

        /* Activate "edit metadata" form */
        this._activateMetadataForm($('pagePanel'));

	/* render start page */
        if(options.onInit)
            this.dataTable.addEvent('render:once', options.onInit);
        this.dataTable.render();
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
                var buttons = [{title: "Schließen", addClass: "mform"}];
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
                        title: "Ändern", addClass: "mform button_red",
                        event: function() {
                            ref.saveMetadataFromForm(this.content);
                            this.close();
                        }
                    });
                }
                new mBox.Modal({
                    content: content,
                    title: "Metadaten",
                    buttons: buttons
                }).open();
            }.bind(this));
        }
    },

    /* Function: destruct

       Clean-up when closing the editor.
     */
    destruct: function() {
        this.dataTable.hide();
        $$('div#menuRight .when-file-open-only').removeClass('file-open');
    },

    /* Function: saveMetadataFromForm

       Sends a server request to save metadata changes.
     */
    saveMetadataFromForm: function(form) {
        var sigle, name, header;
        sigle  = form.getElement('input[name="fmf-sigle"]').get('value');
        name   = form.getElement('input[name="fmf-name"]').get('value');
        header = form.getElement('textarea').get('value');
        new Request.JSON({
            url: "request.php?do=saveMetadata",
            async: true,
            onSuccess: function(status) {
                if (status.success) {
                    cora.projects.performUpdate();
                    gui.setHeader(cora.files.getDisplayName(
                        {id: this.fileId,
                         sigle: sigle,
                         fullname: name}
                    ));
                    this.header = header;
                    gui.showNotice("ok", "Metadaten erfolgreich geändert.");
                } else {
                    gui.showNotice("error", "Metadaten konnten nicht geändert werden.");
                }
            }.bind(this)
        }).post({'id': this.fileId, 'sigle': sigle,
                 'name': name, 'header': header});
    },

    /* Function: initializeColumnVisibility

       Hide or show columns based on whether the required tagsets are
       associated with the currently opened file.
    */
    initializeColumnVisibility: function() {
        // first, determine visibility
        var visibility = {};
	var eshc = $('editorSettingsHiddenColumns');
        var isSetToVisible = function(value) {
            return eshc.getElement('input#eshc-'+value).get('checked');
        };
        Array.each(cora.supportedTagsets, function(ts) {
            visibility[ts] = cora.currentHasTagset(ts) && isSetToVisible(ts);
        });

	// Show/hide columns and settings checkboxes
	Object.each(visibility, function(visible, value) {
            this.dataTable.setVisibility(value, visible);
	    if(cora.currentHasTagset(value)) {
		eshc.getElements('input#eshc-'+value+']').show();
		eshc.getElements('label[for="eshc-'+value+'"]').show();
	    } else {
		eshc.getElements('input#eshc-'+value+']').hide();
		eshc.getElements('label[for="eshc-'+value+'"]').hide();
	    }
	}.bind(this));
    },

    /* Function: setColumnVisibility

       Sets visibility of an annotation column.

       Parameters:
         name - Column name (e.g., "pos")
         visible - Whether the column should be visible
     */
    setColumnVisibility: function(name, visible) {
        this.dataTable.setVisibility(name, visible);
    },

    /* Function: updateShowInputErrors

       Show or hide input error visualization for each row in the table.  Used
       when the corresponding user setting changes.
     */
    updateShowInputErrors: function(no_redraw) {
        Object.each(cora.current().tagsets, function(tagset) {
            tagset.setShowInputErrors(userdata.showInputErrors);
        });
        if(!no_redraw)
            this.dataTable.empty().render();
    },

    /* Function: get

       Retrieves a data entry by its index.

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
        var spinner,
            slice = function(x, a, b) {
                var arr = [];
                for(var j=a; j<b; ++j) {
                    arr.push(x[j]);
                }
                return arr;
            };

        if(this.isRangeLoaded(start, end)) {
            if (typeof(callback) === "function")
                callback(slice(this.data, start, end));
            return true;
        }

        spinner = new Spinner(this.dataTable.table, {class: 'bg-color-page'});
        spinner.show();
        this.requestLines(start, end,
                          function() {  // onSuccess
                              spinner.hide().destroy();
                              callback(slice(this.data, start, end));
                          }.bind(this),
                          function(e) {  // onError
                              spinner.hide().destroy();
                              gui.showNotice('error',
                                             "Problem beim Laden des Dokuments.");
                              gui.showMsgDialog('error', e.message);
                          }
                         );
        return false;
    },

    /* Function: update

       Callback function of DataTable that is invoked whenever an annotation
       changes.
     */
    update: function(elem, data, cls, value) {
        if(!this.changedLines.contains(data.num))
            this.changedLines.push(data.num);
        if (data.num > this.lastEditedRow)
            this.dataTable.updateProgressBar(data.num);
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
                           (userdata.noPageLines - userdata.contextLines),
                           this.dynamicLoadLines);

	this.horizontalTextView.update(start, end);
        this.requestLines(start - dlr, end + dlr);  // dynamic pre-fetching
	this.tries = 0;
    },

    /* Function: renderLines

       LEGACY code -- to be removed after PageModel is integrated in DataTable
     */
    renderLines: function(start, end){
        this.dataTable.renderLines(start, end);
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
        var range, handlers;
        /* request in progress? -> come back later */
        if(this.lineRequestInProgress) {
            setTimeout(function(){this.requestLines(start,end,fn);}.bind(this),
                       10);
            return;
        }

        /* get minimum required range */
	range = this.getMinimumLineRange(start, end);
	if(range.length == 0) { // success!
	    this.tries = 0;
            if(typeof(fn) === "function")
                fn();
	    return;
	}
	if(this.tries++>20) { // prevent endless recursion
            if(typeof(onerror) === "function")
                onerror({
		    'name': 'FailureToLoadLines',
		    'message': "Ein Fehler ist aufgetreten: Zeilen "+start+" bis "+(end-1)+" können nicht geladen werden.  Überprüfen Sie ggf. Ihre Internetverbindung."
	        });
            return;
	}

        this.lineRequestInProgress = true;
	new Request.JSON({
	    url: 'request.php',
	    async: true,
	    onSuccess: function(status, text) {
                this.lineRequestInProgress = false;
                var line,
                    lineArrayLength = (status.success ? status['data'].length : false),
                    data = this.data;
		if (!lineArrayLength) {
                    if(typeof(onerror) === "function")
                        onerror({
			    'name': 'EmptyRequest',
			    'message': "Ein Fehler ist aufgetreten: Server-Anfrage für benötigte Zeilen "+start+" bis "+(end-1)+" lieferte kein Ergebnis zurück."
		        });
                    return;
		}
                for(var i = 0; i < lineArrayLength; i++) {
                    line = status['data'][i];
                    if (typeof(data[line.num]) === "undefined")
                        data[line.num] = line;
                }
		this.requestLines(start, end, fn, onerror);
	    }.bind(this)
	}).get({'do': 'getLinesById', 'start_id': range[0], 'end_id': range[1]});
    },

    /* Function: saveData

       Send a server request to save the modified lines
       to the database.
     */
    saveData: function() {
	var req, cl, data, save, save_obj, line, ler;
	var ref = this;

	cl = this.changedLines;
	if (cl==null) { return; }
	data = this.data;
	save = new Array();

	for (var i=0, len=cl.length; i<len; i++) {
	    line = data[cl[i]];
            save_obj = {id: line.id};
            Object.each(cora.current().tagsets, function(tagset, cls) {
                Object.append(save_obj, tagset.getValues(line));
            });
            Object.append(save_obj, this.flagHandler.getValues(line));
            save.push(save_obj);
	}

	ler = (!(this.lastEditedRow in data) ? this.lastEditedRow : data[this.lastEditedRow].id);
	req = new Request.JSON({
	    url: 'request.php?do=saveData&lastEditedRow='+ler,
	    onSuccess: function(status,xml) {
		var title="", message="", textarea="";

		if (status!=null && status.success) {
		    ref.changedLines = new Array(); // reset "changed lines" array
		    gui.showNotice('ok', 'Speichern erfolgreich.');
		}
		else {
		    if (status==null) {
			message = 'Beim Speichern der Datei ist ein unbekannter Fehler aufgetreten.';
		    }
		    else {
			message = 'Beim Speichern der Datei ist leider ein Fehler aufgetreten.  Bitte melden Sie die folgende Fehlermeldung ggf. einem Administrator.';
			for(var i=0;i<status.errors.length;i++){
			    textarea += status.errors[i] + "\n";
			}
		    }

		    gui.showTextDialog('Speichern fehlgeschlagen', message, textarea);
		}
		gui.hideSpinner();
	    },
	    onFailure: function(xhr) {
		var message = 'Das Speichern der Datei war nicht erfolgreich! Server lieferte folgende Antwort:';
		gui.showTextDialog('Speichern fehlgeschlagen', message,
				   xhr.responseText+' ('+xhr.statusText+')');
		gui.hideSpinner();
	    }
	});
	gui.showSpinner({message: 'Speichere...'});
	req.post(JSON.encode(save));
    },

    /* Function: confirmClose

       If any changes have been made, prompt for confirmation
       to close the currently opened file.

       Note that the actual closing of the file is not implemented
       in this class, but in file.js.

       Parameters:
         fn - Callback function if closing is confirmed
    */
    confirmClose: function(fn) {
	var chl = this.changedLines.length;
	if (chl>0) {
	    var zeile = (chl>1) ? "Zeilen" : "Zeile";
            gui.confirm("Warnung: Im geöffneten Dokument gibt es noch ungespeicherte Änderungen in "+chl+" "+zeile+", die verloren gehen, wenn Sie fortfahren.  Wirklich fortfahren?", fn);
	} else {
            fn();
	}
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
	tok_id = Number.from(tok_id);
	// delete all lines after the changed line from memory
	this.data = Object.filter(this.data, function(item, index) {
	    return index < tok_id;
	});
	this.changedLines = this.changedLines.filter(function(val) {
	    return val < tok_id;
	});
	// update line count and re-load page
        this.dataTable.setLineCount(this.dataTable.lineCount + lcdiff).render();
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

	$('deleteTokenToken').empty().appendText(old_token);
	var confirmbox = new mBox.Modal({
	    title: 'Löschen bestätigen',
	    content: 'deleteTokenWarning',
	    buttons: [
		{title: 'Nein, abbrechen', addClass: 'mform'},
		{title: 'Ja, löschen', addClass: 'mform button_red',
		 event: function() {
		     confirmbox.close();
		     gui.showSpinner({message: 'Bitte warten...'});
		     new Request.JSON({
			 url: 'request.php',
			 async: true,
			 onSuccess: function(status, text) {
			     if (status!=null && status.success) {
				 gui.showNotice('ok', 'Token gelöscht.');
				 ref.updateDataArray(tok_id, -Number.from(status.oldmodcount));
			     }
			     else {
				 var rows = (status!=null ? status.errors : ["Ein unbekannter Fehler ist aufgetreten."]);
				 gui.showTextDialog("Löschen fehlgeschlagen", "Das Löschen des Tokens war nicht erfolgreich.", rows);
			     }
			     gui.hideSpinner();
			 },
			 onFailure: function(xhr) {
			     new mBox.Modal({
				 title: 'Bearbeiten fehlgeschlagen',
				 content: 'Ein interner Fehler ist aufgetreten. Server lieferte folgende Antwort: "'+xhr.responseText+'" ('+xhr.statusText+').'
			     }).open();
			     gui.hideSpinner();
			 }
		     }).get({'do': 'deleteToken', 'token_id': db_id});
		 }
		}
	    ],
	    closeOnBodyClick: false
	});
	confirmbox.open();
    },

    /* Function: _resizeTextarea

       Resizes a <textarea> element based on the required number of lines.

       Parameters:
         event - A trigger event (e.g. keydown) that called this function
    */
    _resizeTextarea: function(event) {
        event.target.rows = event.target.get('value').split("\n").length;
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

	var performEdit = function(mbox) {
	    var new_token = $('editTokenBox').get('value').trim();
	    if(new_token == old_token) {
		mbox.close();
		return false;
	    }
	    if(!new_token) {
		gui.showNotice('error', "Transkription darf nicht leer sein!");
		return false;
	    }
	    performEditRequest(mbox, new_token);
	}

	var performEditRequest = function(mbox, new_token) {
	    gui.showSpinner({message: 'Bitte warten...'});
	    new Request.JSON({
		url: 'request.php',
		async: true,
		onSuccess: function(status, text) {
		    mbox.close();
		    if (status!=null && status.success) {
			gui.showNotice('ok', 'Transkription erfolgreich geändert.');
			// update data array if number of mods has changed
			ref.updateDataArray(tok_id, Number.from(status.newmodcount)-Number.from(status.oldmodcount));
		    }
		    else {
			var rows = (status!=null ? status.errors : ["Ein unbekannter Fehler ist aufgetreten."]);
			gui.showTextDialog("Bearbeiten fehlgeschlagen", "Das Ändern der Transkription war nicht erfolgreich.", rows);
		    }
		    gui.hideSpinner();
		},
		onFailure: function(xhr) {
		    new mBox.Modal({
			title: 'Bearbeiten fehlgeschlagen',
			content: 'Ein interner Fehler ist aufgetreten. Server lieferte folgende Antwort: "'+xhr.responseText+'" ('+xhr.statusText+').'
		    }).open();
		    gui.hideSpinner();
		}
	    }).get({'do': 'editToken', 'token_id': db_id, 'value': new_token});
	    mbox.close();
	};

	$('editTokenBox').set('value', old_token);
	if(this.changedLines.some(function(val) {return val >= tok_id;})) {
	    $('editTokenWarning').show();
	}
	else {
	    $('editTokenWarning').hide();
	}
	var editTokenBox = new mBox.Modal({
	    title: 'Transkription bearbeiten',
	    content: 'editTokenForm',
	    buttons: [
		{title: 'Abbrechen', addClass: 'mform'},
		{title: 'Speichern', addClass: 'mform button_green',
		 event: function() {
		     performEdit(this);
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

	var performAdd = function(mbox) {
	    var new_token = $('addTokenBox').get('value').trim();
	    if(!new_token) {
		gui.showNotice('error', "Transkription darf nicht leer sein!");
		return false;
	    }
	    gui.showSpinner({message: 'Bitte warten...'});
	    new Request.JSON({
		url: 'request.php',
		async: true,
		onSuccess: function(status, text) {
		    mbox.close();
		    if (status!=null && status.success) {
			gui.showNotice('ok', 'Transkription erfolgreich hinzugefügt.');
			ref.updateDataArray(tok_id, Number.from(status.newmodcount));
		    }
		    else {
			var rows = (status!=null ? status.errors : ["Ein unbekannter Fehler ist aufgetreten."]);
			gui.showTextDialog("Hinzufügen fehlgeschlagen", "Das Hinzufügen der Transkription war nicht erfolgreich.", rows);
		    }
		    gui.hideSpinner();
		},
		onFailure: function(xhr) {
		    new mBox.Modal({
			title: 'Hinzufügen fehlgeschlagen',
			content: 'Ein interner Fehler ist aufgetreten. Server lieferte folgende Antwort: "'+xhr.responseText+'" ('+xhr.statusText+').'
		    }).open();
		    gui.hideSpinner();
		}
	    }).get({'do': 'addToken', 'token_id': db_id, 'value': new_token});
	    mbox.close();
            return true;
	};

	$('addTokenBox').set('value', '');
	$('addTokenBefore').empty().appendText(old_token);
	$('addTokenLineinfo').empty().appendText(lineinfo);
	if(this.changedLines.some(function(val) {return val >= tok_id;})) {
	    $('addTokenWarning').show();
	}
	else {
	    $('addTokenWarning').hide();
	}
	var addTokenBox = new mBox.Modal({
	    title: 'Transkription hinzufügen',
	    content: 'addTokenForm',
	    buttons: [
		{title: 'Abbrechen', addClass: 'mform'},
		{title: 'Speichern', addClass: 'mform button_green',
		 event: function() {
		     performAdd(this);
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
            aaselect.appendText("Keine Tagger verfügbar.");
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
	var content = $('automaticAnnotationForm');
	var performAnnotation = function(action) {
	    var taggerID = $('automaticAnnotationForm').getElement('input[name="aa_tagger_select"]:checked').get('value');
	    gui.showSpinner({message: 'Bitte warten...'});
	    new Request.JSON({
		url: 'request.php',
		async: true,
		onComplete: function(response) {
		    gui.hideSpinner();
		    if(response && response.success) {
                        if(action == "train") {
                            gui.showNotice('ok', 'Neu trainieren war erfolgreich.');
                        }
                        else {
			    gui.showNotice('ok', 'Automatische Annotation war erfolgreich.');
			    // clear and reload all lines
			    ref.updateDataArray(0, 0);
                        }
		    } else {
			gui.showNotice('error', 'Annotation fehlgeschlagen.');
			gui.showTextDialog('Annotation fehlgeschlagen',
					   'Bei der automatischen Annotation ist ein Fehler aufgetreten.',
					   response.errors);
		    }
		},
		onError: function(response) {
		    new mBox.Modal({
			title: 'Annotation fehlgeschlagen',
			content: 'Ein unbekannter Fehler ist aufgetreten.'
		    }).open();
		    gui.hideSpinner();
		},
		onFailure: function(xhr) {
		    new mBox.Modal({
			title: 'Annotation fehlgeschlagen',
			content: 'Ein interner Fehler ist aufgetreten. Server lieferte folgende Antwort: "'+xhr.responseText+'" ('+xhr.statusText+').'
		    }).open();
		    gui.hideSpinner();
		}
	    }).get({'do': 'performAnnotation', 'action': action, 'tagger': taggerID});
	    mbox.close();
	};

	// define the dialog window
	mbox = new mBox.Modal({
	    title: 'Automatisch neu annotieren',
	    content: 'automaticAnnotationForm',
	    attach: button,
	    buttons: [ {title: "Neu trainieren", addClass: "mform button_left button_yellow",
                        id: "trainStartButton",
                        event: function() { this.close();
                                            performAnnotation("train"); }},
                       {title: "Annotieren", addClass: "mform button_green",
			id: "annoStartButton",
			event: function() { this.close();
					    performAnnotation("anno"); }},
		       {title: "Abbrechen", addClass: "mform",
			event: function() { this.close(); }}
		     ]
	});
    }
});
