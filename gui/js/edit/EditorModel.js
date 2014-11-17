/* Class: EditorModel

   Main class representing an instance of the CorA editor.
 */
var EditorModel = new Class({
    fileId: 0,
    lineTemplate: null,
    lastEditedRow: -1,
    pages: null,
    displayedLinesStart: 0,
    displayedLinesEnd: 0,
    lineCount: 0,
    data: {},
    header: "",
    changedLines: null,
    editTable: null,
    tries: 0,
    maximumTries: 20,     // max. number of load requests before giving up
    dynamicLoadPages: 5,  // min. number of pages in each direction to be pre-fetched
    dynamicLoadLines: 50, // min. number of lines in each direction to be pre-fetched
    dropdown: null,  // contains the currently opened dropdown menu, if any
    lineRequestInProgress: false,
    horizontalTextView: null,
    flagHandler: null,
    onRenderOnceHandlers: [],

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
	var elem, td, spos, smorph, slempos, et, mr, btn, start_page;
	var ref = this;

        /* File-specific options */
	this.lineCount = Number.from(options.maxLinesNo);
	if(options.lastEditedRow !== null) {
	    this.lastEditedRow = Number.from(options.lastEditedRow);
	}
	this.changedLines = new Array();
	this.fileId = fileid;
        this.header = options.data.header;

        /* General options */
	this.editTable = $('editTable');
	et = this.editTable;
	et.removeEvents();

        this.horizontalTextView =
            new HorizontalTextPreview(this, $('horizontalTextViewContainer'));
        this.updateShowInputErrors(true);
        Object.each(cora.current().tagsets, function(tagset) {
            tagset.setUpdateAnnotation(this.updateAnnotation.bind(this));
            tagset.defineDelegatedEvents(et);
        }.bind(this));
        this.flagHandler = new FlagHandler();
        this.flagHandler.setUpdateAnnotation(this.updateAnnotation.bind(this));
        this.flagHandler.defineDelegatedEvents(et);

	/* set up the line template */
	this.lineTemplate = $('line_template');
        Object.each(cora.current().tagsets, function(tagset, cls) {
            var elem = this.lineTemplate.getElement('td.editTable_'+cls);
            if(typeof(elem) !== "undefined") {
                tagset.buildTemplate(elem);
            }
        }.bind(this));

	/* clear out any previously generated lines */
	et.getElements('tbody tr[id!=line_template]').destroy();

	/* define delegated events */
	$(document.body).addEvent(
	    'click',
	    function(event, target) {
		if(ref.dropdown!==null &&
		   (!event.target || !$(event.target).hasClass("editTableDropdownIcon"))) {
		    ref.dropdown.hide();
		    ref.dropdown = null;
		}
	    }
	);
	et.addEvent(
	    'click:relay(div)',
	    function(event, target) {
		var new_value;
		var this_id = ref.getRowNumberFromElement(target);
		if(target.hasClass('editTableProgress')) {
		    new_value = target.hasClass('editTableProgressChecked') ? false : true;
		    ref.updateProgress(this_id, new_value);
		} else if(target.hasClass('editTableDropdown')) {
		    new_value = target.getSiblings('div.editTableDropdownMenu')[0];
		    if(ref.dropdown!==null) {
			ref.dropdown.hide();
			if(ref.dropdown==new_value) {
			    ref.dropdown = null;
			    return;
			}
		    }
		    ref.dropdown = new_value;
		    ref.dropdown.show();
	    	}
	    }
	);
	et.addEvent(
	    'click:relay(a)',
	    function(event, target) {
		var this_id = ref.getRowNumberFromElement(target);
		if(target.hasClass('editTableDdButtonDelete')) {
		    ref.deleteToken(this_id);
		} else if(target.hasClass('editTableDdButtonEdit')) {
		    ref.editToken(this_id);
		} else if(target.hasClass('editTableDdButtonAdd')) {
		    ref.addToken(this_id);
		}
	    }
	);
	et.addEvent(
            'keyup:relay(input)',
            function(event, target) {
	        var this_id = ref.getRowNumberFromElement(target);
		var parent = target.getParent('td');
		var new_value = target.get('value');
		var this_class = ref.getRowClassFromElement(parent);
                var new_row;
                var shiftFocus = function(nr) {
                    if(nr == null) return;
		    var new_target = nr.getElement('td.'+this_class+' input');
		    if(new_target != null) {
			new_target.focus();
		    }
                };
		if (event.code == 40) { // down arrow
		    if(event.control || !target.hasClass("editTable_lemma")) {
			new_row = ref.getRowNumberFromElement(parent) + 1;
                        if (ref.getRowFromNumber(new_row) !== null) {
                            shiftFocus(ref.getRowFromNumber(new_row));
                        } else {
                            if (ref.pages.increment()) {
                                ref.onRenderOnce(function() {
                                    shiftFocus(ref.getRowFromNumber(new_row));
                                });
                                ref.pages.render();
                            }
                        }
		    }
		}
		if (event.code == 38) { // up arrow
		    if(event.control || !target.hasClass("editTable_lemma")) {
			new_row = ref.getRowNumberFromElement(parent) - 1;
                        if (ref.getRowFromNumber(new_row) !== null) {
                            shiftFocus(ref.getRowFromNumber(new_row));
                        } else {
                            if (ref.pages.decrement()) {
                                ref.onRenderOnce(function() {
                                    shiftFocus(ref.getRowFromNumber(new_row));
                                });
                                ref.pages.render();
                            }
                        }
		    }
		}
	    }
	);
	et.addEvent(
	    'dblclick:relay(td)',
	    function(event, target) {
		var this_id = ref.getRowNumberFromElement(target);
		if (target.hasClass("editTable_token") ||
		    target.hasClass("editTable_tok_trans")) {
		    // we don't want text to get selected here:
		    if (window.getSelection)
			window.getSelection().removeAllRanges();
		    else if (document.selection)
			document.selection.empty();
		    ref.editToken(this_id);
		}
	    }
	);
	et.addEvent(
	    'focus:relay(input,select)',
	    function(event, target) {
                // highlight in text preview
		var this_id = ref.getRowNumberFromElement(target);
		ref.horizontalTextView.highlight(this_id);
                // is hidden behind header?
                var scrolltop_min = target.getTop() - $('header').getHeight() - 10;
                if(window.getScrollTop() > scrolltop_min) {
                    window.scrollTo(0, scrolltop_min);
                }
                // is hidden behind horizontal text view?
                var scrolltop_max = target.getTop()
                    - window.getHeight()
                    + $('horizontalTextViewContainer').getHeight()
                    + target.getHeight() + 10;
                if(scrolltop_max > window.getScrollTop()) {
                    window.scrollTo(0, scrolltop_max);
                }
	    }
	);

	/* activate extra menu bar */
	mr = $('menuRight');
	btn = mr.getElement('#saveButton');
	btn.removeEvents();
	btn.addEvent('click', function(e) {
	    e.stop();
	    ref.saveData();
	});
	btn = mr.getElement('#closeButton');
	btn.removeEvents();
	btn.addEvent('click', function(e) {
	    e.stop();
	    cora.fileManager.closeFile(ref.fileId);
	});
	/* prepare automatic annotation dialog */
	btn = mr.getElement('#tagButton');
	if(btn && btn !== undefined) {
	    btn.removeEvents();
	    this.activateAnnotationDialog(btn);
	    this.prepareAnnotationOptions();
	}
	mr.getElements('.when-file-open-only').addClass('file-open');

	this.initializeColumnVisibility();
        this._activateMetadataForm($('pagePanel'));

	/* render pages panel and set start page */
	start_page = Number.from(options.lastPage);
        this.pages = new PageModel(this);
        this.pages.addPanel($('pagePanel')).addPanel($('pagePanelBottom'));
        if(options.onInit)
            this.onRenderOnce(options.onInit);
        this.pages.set(start_page).render();
    },

    /* Function: _activateMetadataForm

       Activates form the view/edit file metadata such as name and
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
        this.editTable.hide();
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
        var visibility = {};
        Array.each(cora.supportedTagsets, function(ts) {
            visibility[ts] = cora.currentHasTagset(ts);
        });

        // HACKS we want to get rid of as well:
        if(visibility["pos"])
            visibility["morph"] = true;

	/* Show/hide columns and settings checkboxes */
	var eshc = $('editorSettingsHiddenColumns');
	Object.each(visibility, function(visible, value) {
	    if(visible) {
		eshc.getElements('input#eshc-'+value+']').show();
		eshc.getElements('label[for="eshc-'+value+'"]').show();
		if(eshc.getElement('input#eshc-'+value+']').get('checked')) {
		    $('editTable').getElements(".editTable_"+value).show('table-cell');
		} else {
		    $('editTable').getElements(".editTable_"+value).hide();
		}
	    } else {
		eshc.getElements('input#eshc-'+value+']').hide();
		eshc.getElements('label[for="eshc-'+value+'"]').hide();
		$('editTable').getElements(".editTable_"+value).hide();
	    }
	});
    },

    /* Function: onRenderOnce

       Registers a callback function to invoke after the next
       successful line rendering.
     */
    onRenderOnce: function(fn) {
        if(typeof(fn) == "function")
            this.onRenderOnceHandlers.push(fn);
        return this;
    },

    /* Function: getRowClassFromElement

       Gets the "editTable_*" class of the <td> cell.
     */
    getRowClassFromElement: function(td) {
        for (var i = 0; i < td.classList.length; i++) {
            var item = td.classList.item(i);
            if(item.substr(0, 10) === "editTable_")
                return item;
        }
        return "";
    },

    /* Function: getTagsetClassFromElement

       Gets the tagset class of the <td> cell containing the supplied element.
     */
    getTagsetClassFromElement: function(elem) {
        var parent = elem.getParent('td');
        if(!parent.hasClass("et-anno"))  // not an annotation element
            return "";
        return this.getRowClassFromElement(parent).substr(10);
    },

    /* Function: getRowNumberFromElement

       Gets the number of the row (used in '#' column and the <tr
       id=...> attribute) containing the supplied element.
     */
    getRowNumberFromElement: function(elem) {
        var id = elem.get('id');
        if(id === null || id.substr(0, 5) !== "line_") {
            id = elem.getParent('tr').get('id');
        }
        return Number.from(id.substr(5));
    },

    /* Function: getRowFromNumber

       Gets the <tr> element corresponding to a given row number.
     */
    getRowFromNumber: function(num) {
        return $('line_'+num);
    },

    /* Function: updateShowInputErrors

       Show or hide input error visualization for each row in the table.  Used
       when the corresponding user setting changes. */
    updateShowInputErrors: function(no_redraw) {
        Object.each(cora.current().tagsets, function(tagset) {
            tagset.setShowInputErrors(userdata.showInputErrors);
        });
        if(!no_redraw)
            this.forcePageRedraw();
    },

    /* Function: forcePageRedraw

       Deletes all rows in the editor table and recreates them.

       This operation is purely cosmetic and should be used after
       significant changes to the table row content (e.g., displaying
       previously hidden columns).
    */
    forcePageRedraw: function() {
	$('editTable').getElements('tr[id^=line][id!=line_template]').destroy();
	this.renderLines(this.displayedLinesStart, this.displayedLinesEnd+1);
    },

    /* Function: focusFirstElement

       Sets focus on the first editable element (input or select) in
       the editor table.
    */
    focusFirstElement: function() {
	var visible = this.editTable.getElements("input,select").filter(function(elem) {
	    return elem.isVisible();
	});
	if(visible!==null && visible.length) {
	    visible[0].focus();
	}
    },

    /* Function: highlightRow

       Visually highlights a certain row in the editor table, and
       positions it in the middle of the screen if possible.

       Parameters:
         number - Number of the row to highlight
     */
    highlightRow: function(number) {
        var row = this.getRowFromNumber(number);
        if (row != null) {
            /* scroll to element */
            window.scrollTo(0, (row.getTop() - (window.getHeight() / 2)));
            /* tween background color */
            row.setStyle('background-color', '#999');
            setTimeout(function() {
                new Fx.Tween(row, {
                    duration: 'long',
                    property: 'background-color'
                }).start('#999', '#f8f8f8');
            }, 200);
        }
    },

    /* Function: updateProgress

       Update information about editing progress and modify the
       visual representation accordingly, if necessary.

       Parameters:
         line_id - The line number up to which progress should be
	           marked, or from which on it should be unmarked
         marked  - Whether to mark or unmark progress
     */
    updateProgress: function(line_id, marked) {
	var pl = userdata.noPageLines;
	var id = parseInt(line_id);
	var end = this.displayedLinesEnd;
	var start = this.displayedLinesStart;
	var et = this.editTable;
	if (id <= this.lastEditedRow) {
	    if (!marked) {
		if (id>=start) {
		    for (var j=id;j<=end;j++) {
			et.getElement('tr#line_'+j+' div.editTableProgress').removeClass("editTableProgressChecked");
		    }
		}
		this.lastEditedRow = id - 1;
	    }
	} else {
	    if (marked) {
		var k = (id<=end) ? id : end;
		for (var j=start;j<=k;j++) {
		    et.getElement('tr#line_'+j+' div.editTableProgress').addClass("editTableProgressChecked");
		}
		this.lastEditedRow = id;
	    }
	}
    },

    /* Function: updateAnnotation

       Update the annotation of a particular tagset.  Calls update() triggers
       for all tagsets.

       Parameters:
         target - The element on which the update triggered
         cls - Tagset class to be updated
         value - New value of the annotation
     */
    updateAnnotation: function(target, cls, value) {
        var tr = target.getParent('tr');
        var this_id = this.getRowNumberFromElement(tr);
        var data = this.data[this_id];
        console.log(this_id + ": changing '" + cls + "' to '" + value + "'");
        if (typeof(data) !== "undefined") {
            Object.each(cora.current().tagsets, function(tagset) {
                tagset.update(tr, data, cls, value);
            });
            this.flagHandler.update(tr, data, cls, value);
            if (!this.changedLines.contains(this_id))
                this.changedLines.push(this_id);
            this.updateProgress(this_id, true);
        }
    },

    /* Function: renderLines

       Render a given range of lines.

       If lines are not in memory, they are dynamically fetched from
       the server. Lines in the editor are constructed by hiding the
       editor table, modifying the existing HTML table rows in place
       by filling them with the new data, then showing the editor
       table again. If there are not enough lines (e.g.  on first
       load, or because editor settings have changed), new lines are
       constructed from the class's line template.

       Parameters:
         start - First line to be rendered
         end   - Last line to be rendered

       Returns:
         'true' if lines were rendered successfully.
     */
    renderLines: function(start, end){
        var et_spinner, fn_callback, fn_onerror;  // when lines are not loaded yet
        var trs, j;  // for ensuring correct number of rows
        var line, tr, lineinfo;  // for building lines
        var dlr, dynstart, dynend;  // for dynamically loading context lines
	var data = this.data;
	var et = this.editTable;
	var cl = userdata.contextLines;
        var pl = end - start;
	var ler = this.lastEditedRow;

	/* ensure all lines are in memory */
        if(!this.isRangeLoaded(start, end)) {
            et_spinner = new Spinner(et, {style: {'background': '#f8f8f8'}});
            et_spinner.show();
            fn_callback = function() {
                et_spinner.hide().destroy();
                this.renderLines(start, end);
            }.bind(this);
            fn_onerror = function(e) {
                gui.showNotice('error', "Problem beim Laden des Dokuments.");
	        gui.showMsgDialog('error', e.message);
            };
            this.requestLines(start, end, fn_callback, fn_onerror);
            return false;
        }

	/* hide the table */
	et.hide();

	/* ensure the correct number of rows; needed for first page
	   load, and whenever the lines-per-page setting changes --
	   this could be solved more efficiently, but it takes almost
	   no computing time anyway (0-3ms) as long as nothing
	   changes */
	trs = et.getElements('tr[id!=line_template]');
	j = 1;
	while (typeof(trs[j]) !== "undefined") {
	    if (j>pl)  // remove superfluous lines
		trs[j].destroy();
	    j++;
	}
	while (j<=pl) {  // add missing lines
	    et.getElement("tbody").adopt(this.lineTemplate.clone());
	    j++;
	}

	/* build lines */
	trs = et.getElements('tr[id!=line_template]');
	j = 0;
	for (var i=start; i<end; i++) {
	    line = data[i];
	    j++;
	    tr = trs[j];

	    tr.set('id', 'line_'+line.num);
	    if (parseInt(line.num)<=ler) {
		tr.getElement('div.editTableProgress').addClass('editTableProgressChecked');
	    } else {
		tr.getElement('div.editTableProgress').removeClass('editTableProgressChecked');
	    }
	    if(line.page_name !== undefined) {
		lineinfo = line.page_name + line.page_side + line.col_name + "," + line.line_name;
	    }
	    else {
		lineinfo = "";
	    }
	    tr.getElement('.editTable_line').empty().appendText(lineinfo);
	    tr.getElement('.editTable_tok_trans').empty().appendText(line.trans);
	    tr.getElement('.editTable_token').empty().appendText(line.utf);
	    tr.getElement('.editTable_tokenid').empty().appendText(i+1);

	    // build annotation elements
            Object.each(cora.current().tagsets, function(tagset) {
                tagset.fill(tr, line);
            }.bind(this));

            // handle flags
            this.flagHandler.fill(tr, line);
	}

	/* unhide the table */
	et.show();

	/* horizontal text view */
	this.horizontalTextView.update(start, end);

	/* dynamically load context lines */
	dlr  = Math.max(this.dynamicLoadPages * (userdata.noPageLines - cl),
                        this.dynamicLoadLines);
	dynstart = start - dlr;
	dynend   = end   + dlr;
	this.requestLines(dynstart, dynend);

	this.displayedLinesStart = start;
	this.displayedLinesEnd = end - 1;
	this.tries = 0;

        Array.each(this.onRenderOnceHandlers, function(handler) {
            handler();
        });
        this.onRenderOnceHandlers = [];
	return true;
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
	if (end>this.lineCount) { end = this.lineCount; }

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
                var line,  // lineArray = status['data'],
                    lineArrayLength = status['data'].length,
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
	this.lineCount = this.lineCount + lcdiff;
	this.pages.update().render();
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
