var LineJumper = new Class({
    parent: null,
    mbox: null,

    initialize: function(parent, content) {
        var ref = this;
        this.parent = parent;
        this.mbox = new mBox.Modal({
	    content: content,
	    title: 'Springe zu Zeile',
	    buttons: [
		{title: 'Abbrechen', addClass: 'mform'},
		{title: 'OK', addClass: 'mform button_green',
		 event: function() {
		     ref.jump();
		 }
		}
	    ],
	    onOpenComplete: function() {
                var box = this.content.getElement('input[name="jumpTo"]');
                box.removeEvents('keydown');
                box.addEvent('keydown', function(event) {
                    if(event.key == "enter")
                        ref.jump();
                });
		box.focus();
		box.select();
	    }
	});
    },

    open: function() {
        this.mbox.open();
    },

    jump: function() {
        var value = Number.from(this.mbox.content
                                .getElement('input[name="jumpTo"]').value);
        if (value == null) {
	    gui.showNotice('error', 'Bitte eine Zahl eingeben.');
        } else if (!this.parent.isValidLine(value)) {
	    gui.showNotice('error', 'Zeilennummer existiert nicht.');
        } else {
            this.parent.parent.onRenderOnce(function() {
                this.highlightRow(value - 1);
            }.bind(this.parent.parent));
            this.parent.set(this.parent.getPageByLine(value)).render();
            this.mbox.close();
        }
    }
});

var TokenSearcher = new Class({
    parent: null,
    mbox: null,
    flexrow: null,

    initialize: function(parent, content) {
        this.parent = parent;
        this.flexrow = new FlexRowList(content.getElement('.flexrow-container'),
                                       $('editSearchCriterionTemplate'));
        this.flexrow.grabNewRow();
        this.mbox = new mBox.Modal({
	    content: content,
	    title: 'Suchen',
	    buttons: [
		{title: 'Abbrechen', addClass: 'mform'},
		{title: 'Suchen', addClass: 'mform button_green',
		 event: function() {
		     // TODO
		 }
		}
	    ]
	});
    },

    open: function() {
        this.mbox.open();
    }
});

var PageModel = new Class({
    parent: null,
    lineJumper: null,
    tokenSearcher: null,
    panels: [],
    maxPage: 0,
    activePage: 0,

    initialize: function(parent) {
        this.parent = parent;
        this._calculateMaxPage();
        this.lineJumper = new LineJumper(this, $('jumpToLineForm'));
        this.tokenSearcher = new TokenSearcher(this, $('searchTokenForm'));
    },
    
    /* Function: _calculateMaxPage

       Calculates the total number of pages with the given display
       settings.      
     */
    _calculateMaxPage: function() {
        var lines_per_page = userdata.noPageLines;
        var lines_context  = userdata.contextLines;
        var x = (this.parent.lineCount - lines_context);
        var y = (lines_per_page - lines_context);
        this.maxPage = (x % y) ? Math.ceil(x/y) : (x/y);
        return this;
    },

    /* Function: update

       Recalculate the page count and change the active page to a
       valid number, if necessary.
     */
    update: function() {
        this._calculateMaxPage();
        this.set(this.activePage);
        return this;
    },

    /* Function: addPanel

       Adds a <div> toolbar element that acts as a container for this
       page panel.  Recognized elements within this <div> are attached
       events and are updated when the page panel updates.

       Parameters:
         div - A toolbar <div> to contain this page panel
     */
    addPanel: function(div) {
        this._activatePageBackForward(div)
            ._activateJumpToLine(div)
            ._activateSearch(div)
            ._activateJumpToPage(div);
        this.panels.push(div);
        return this;
    },

    /* Function: _activatePageBackForward

       Activates buttons to jump back/forward by one page.
     */
    _activatePageBackForward: function(div) {
        var elem;
        /* page back */
        elem = div.getElement('span.btn-page-back');
        if (elem != null) {
            elem.removeEvents('click');
            elem.addEvent('click', function() {
                this.set(this.activePage - 1).render();
            }.bind(this));
        }
        /* page forward */
        elem = div.getElement('span.btn-page-forward');
        if (elem != null) {
            elem.removeEvents('click');
            elem.addEvent('click', function() {
                this.set(this.activePage + 1).render();
            }.bind(this));
        }
        return this;
    },

    /* Function: _activateJumpToLine

       Activates button that allows jumping to a specific line.
     */
    _activateJumpToLine: function(div) {
        var elem = div.getElement('span.btn-jump-to');
        if (elem != null) {
            elem.removeEvents('click');
            elem.addEvent('click', function() {
                this.lineJumper.open();
            }.bind(this));
        }
        return this;
    },

    /* Function: _activateSearch

       Activates button that allows searching within the text.
     */
    _activateSearch: function(div) {
        var elem = div.getElement('span.btn-text-search');
        if (elem != null && userdata.admin) {  // HACK while this is in development
            elem.removeEvents('click');
            elem.addEvent('click', function() {
                this.tokenSearcher.open();
            }.bind(this));
        }
        return this;
    },

    /* Function: _activateJumpToPage

       Activates element that allows jumping to a specific page.
     */
    _activateJumpToPage: function(div) {
        var elem = div.getElement('span.btn-page-count');
        if (elem == null)
            return;
        var input = elem.getElement('input.btn-page-to');
        var span  = elem.getElement('span.page-active');
        if (input != null && span != null) {
            var changePage = function(event) {
                this.set(input.get('value').toInt()).render();
                input.hide();
                span.show('inline');
            }.bind(this);
            elem.removeEvents('click');
            elem.addEvent('click', function() {
                if (span.isVisible()) {
                    span.hide();
                    input.set('value', this.activePage).show('inline').focus();
                    input.select();
                }
            }.bind(this));
            input.removeEvents();
            input.addEvents({
                keydown: function(event) {
                    if (event.key == "enter")
                        changePage();
                },
                blur: changePage,
                mousewheel: function(event) {
                    var i = event.target, v = i.get('value').toInt();
                    i.focus();
                    if (event.wheel > 0 && v < this.maxPage)
                        v++;
                    else if (event.wheel < 0 && v > 1)
                        v--;
                    i.set('value', v);
                    event.stop();
                }.bind(this)
            });
        }
        return this;
    },

    /* Function: _updatePageCounter

       Updates the element that displays current and maximum page
       numbers.
     */
    _updatePageCounter: function() {
        var elem = null;
        Array.each(this.panels, function(panel) {
            elem = panel.getElement('span.page-active');
            if (elem != null)
                elem.set('text', this.activePage);
            elem = panel.getElement('span.page-max');
            if (elem != null)
                elem.set('text', this.maxPage);
        }.bind(this));
        return this;
    },

    /* Function: set

       Sets the active page to a specific page number.
     */
    set: function(page) {
        if (page === null || page < 1) {
            this.activePage = 1;
        } else if (page > this.maxPage) {
            this.activePage = this.maxPage;
        } else {
            this.activePage = page;
        }
        this._updatePageCounter();
        return this;
    },

    /* Function: increment

       Increments the active page by one, if possible.

       Returns:
         True if the page number changed, false otherwise.
    */
    increment: function() {
        var former = this.activePage;
        this.set(this.activePage + 1);
        return (this.activePage > former);
    },

    /* Function: decrement

       Decrements the active page by one, if possible.

       Returns:
         True if the page number changed, false otherwise.
    */
    decrement: function() {
        var former = this.activePage;
        this.set(this.activePage - 1);
        return (this.activePage < former);
    },

    /* Function: getRange

       Gets the line numbers where a given page starts and ends.

       Parameters:
         page - Number of the page

       Returns:
         {from: <start>, to: <end>}, where <start> is the first
         line of the given page and <end> is the last
     */
    getRange: function(page) {
        var start, end;
	var cl = userdata.contextLines;
	var pl = userdata.noPageLines;
	if (page === null || page < 1) {
            page = 1;
        } else if (page > this.maxPage) {
            page = this.maxPage;
        }
	end   = page * (pl - cl) + cl;
	start = end - pl;
        end   = Math.min(end, this.parent.lineCount);
        return {from: start, to: end};
    },

    /* Function: getPageByLine

       Calculates the page number which contains a given line.

       Parameters:
         line - Number of the line

       Returns:
         The page number that holds the given line.
     */
    getPageByLine: function(line) {
	if (line > this.parent.lineCount) {
	    line = this.parent.lineCount;
	}
	var y = (userdata.noPageLines - userdata.contextLines);
	return (line % y) ? Math.ceil(line/y) : (line/y);
    },

    /* Function: isValidLine

       Checks if a given line number is valid.
     */
    isValidLine: function(line) {
        return (line > 0 && line <= this.parent.lineCount);
    },

    /* Function: render

       Makes the parent editor model (re-)render the currently active
       page.
     */
    render: function() {
        var range = this.getRange(this.activePage);
        this.parent.renderLines(range.from, range.to);
        return this;
    }
});

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
    inputErrorClass: "",  // browser-dependent CSS styling for input errors
    dropdown: null,  // contains the currently opened dropdown menu, if any
    useLemmaLookup: false,
    lineRequestInProgress: false,
    horizontalViewSpinner: null,
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

	if(Browser.chrome) {
	    this.inputErrorClass = "input_error_chrome";
	} else {
	    this.inputErrorClass = "input_error";
	}

	this.lineCount = Number.from(options.maxLinesNo);
	if(options.lastEditedRow !== null) {
	    this.lastEditedRow = Number.from(options.lastEditedRow);
	}
	this.changedLines = new Array();
	this.fileId = fileid;
        this.header = options.data.header;

	this.editTable = $('editTable');
	et = this.editTable;

	this.useLemmaLookup = false;
	$('horizontalTextView').empty().set('text', "Text-Vorschau wird geladen...");

	/* set up the line template */
	elem = $('line_template');
	
	spos = new Element('select');
	spos.grab(cora.currentTagset("pos").optgroup.clone());                  
	td = elem.getElement('td.editTable_pos');
	td.empty();
	td.adopt(spos);
	
	smorph = new Element('select');
	td = elem.getElement('td.editTable_morph');
	td.empty();
	td.adopt(smorph);

	if(cora.currentHasTagset("lemmapos")) {
	    slempos = new Element('select');
	    slempos.grab(cora.currentTagset("lemmapos").optgroup.clone());
	    td = elem.getElement('td.editTable_lemmapos');
	    td.empty();
	    td.adopt(slempos);
	}

	this.lineTemplate = elem;

	/* clear out any previously generated lines */
	et.getElements('tr[id^=line][id!=line_template]').destroy();

	/* define delegated events */
	et.removeEvents();
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
		if(target.hasClass('editTableError')) {
		    new_value = target.hasClass('editTableErrorChecked') ? 0 : 1;
		    target.toggleClass('editTableErrorChecked');
		    ref.updateData(this_id, 'general_error', new_value);
		} else if(target.hasClass('editTableProgress')) {
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
	    	} else if(target.hasClass('editTableLemma')) {
		    new_value = target.hasClass('editTableLemmaChecked') ? 0 : 1;
		    target.toggleClass('editTableLemmaChecked');
		    ref.updateData(this_id, 'lemma_verified', new_value);
		} else if(target.hasClass('editTableLemmaLink')) {
		    cora_external_lemma_link(target.getSiblings("input")[0].get('value'));
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
	    'change:relay(select)',
	    function(event, target) {
		var this_id = ref.getRowNumberFromElement(target);
		var parent = target.getParent('td');
		var new_value = target.getSelected()[0].get('value');
		if (parent.hasClass("editTable_pos")) {
		    ref.updateData(this_id, 'anno_pos', new_value);
		    ref.renderMorphOptions(this_id, target.getParent('tr'), new_value);
		    if (userdata.showInputErrors)
			ref.updateInputError(target.getParent('tr'));
		} else if (parent.hasClass("editTable_morph")) {
		    ref.updateData(this_id, 'anno_morph', new_value);
		    if (userdata.showInputErrors)
			ref.updateInputError(target.getParent('tr'));
		} else if (parent.hasClass("editTable_mod")) {
		    ref.updateData(this_id, 'anno_modtype', new_value);
		    if (userdata.showInputErrors)
			ref.updateInputError(target.getParent('tr'));
		} else if (parent.hasClass("editTable_lemmapos")) {
		    ref.updateData(this_id, 'anno_lemmapos', new_value);
		    if (userdata.showInputErrors)
			ref.updateInputError(target.getParent('tr'));
		}

		ref.updateProgress(this_id, true);
	    }
	);
	et.addEvent(
	    'change:relay(input)',
	    function(event, target) {
		var this_id = ref.getRowNumberFromElement(target);
		var parent = target.getParent('td');
		var new_value = target.get('value');
		if (parent.hasClass("editTable_norm")) {
		    ref.updateData(this_id, 'anno_norm', new_value);
		    parent.getSiblings("td.editTable_mod input")[0].set('placeholder', new_value);
		    ref.updateProgress(this_id, true);
		} else if (parent.hasClass("editTable_mod")) {
		    ref.updateData(this_id, 'anno_mod', new_value);
		    //ref.updateModSelect(parent, new_value);
		    if (userdata.showInputErrors)
			ref.updateInputError(target.getParent('tr'));
		    ref.updateProgress(this_id, true);
		} else if (parent.hasClass("editTable_lemma")) {
		    ref.updateData(this_id, 'anno_lemma', new_value);
		    ref.updateProgress(this_id, true);
		    // deselect "lemma verified" box after a change
		    parent.getElement('div.editTableLemma').removeClass('editTableLemmaChecked');
		    ref.updateData(this_id, 'lemma_verified', 0);
		} else if (parent.hasClass("editTable_Comment")) {
		    ref.updateData(this_id, 'comment', new_value);
		}
	    }
	);
	et.addEvent(
	    'keyup:relay(input)',
	    function(event, target) {
		var parent = target.getParent('td');
                var this_class = parent.get('class');
		var new_value = target.get('value');
		var new_row;
		if (parent.hasClass("editTable_mod")) {
		    ref.updateModSelect(parent, new_value);
		}
                var shiftFocus = function(nr, tc) {
                    if(nr == null) return;
		    var new_target = nr.getElement('td.'+tc+' input');
		    if(new_target != null) {
			new_target.focus();
		    }
                };
		if (event.code == 40) { // down arrow
		    if(event.control || this_class != "editTable_lemma") {
			new_row = ref.getRowNumberFromElement(parent) + 1;
                        if (ref.getRowFromNumber(new_row) !== null) {
                            shiftFocus(ref.getRowFromNumber(new_row), this_class);
                        } else {
                            if (ref.pages.increment()) {
                                ref.onRenderOnce(function() {
                                    shiftFocus(ref.getRowFromNumber(new_row), this_class);
                                });
                                ref.pages.render();
                            }
                        }
		    }
		}
		if (event.code == 38) { // up arrow
		    if(event.control || this_class != "editTable_lemma") {
			new_row = ref.getRowNumberFromElement(parent) - 1;
                        if (ref.getRowFromNumber(new_row) !== null) {
                            shiftFocus(ref.getRowFromNumber(new_row), this_class);
                        } else {
                            if (ref.pages.decrement()) {
                                ref.onRenderOnce(function() {
                                    shiftFocus(ref.getRowFromNumber(new_row), this_class);
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
		ref.highlightHorizontalView(this_id);
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
	var visibility = {
	    "norm":     false,
	    "mod":      false,
	    "lemma":    false,
	    "lemmapos": false
	};
	var normbroad = false;
	var normtype = false;
	/* Check tagset associations */
        if(cora.currentHasTagset("lemma"))
            visibility["lemma"] = true;
        if(cora.currentHasTagset("lemma_sugg"))
            this.useLemmaLookup = true;
        if(cora.currentHasTagset("lemmapos"))
            visibility["lemmapos"] = true;
        if(cora.currentHasTagset("norm"))
            visibility["norm"] = true;
        if(cora.currentHasTagset("norm_broad") && cora.currentHasTagset("norm_type"))
            visibility["mod"] = true;

	/* Show/hide columns and settings checkboxes */
	var eshc = $('editorSettingsHiddenColumns');
	Object.each(visibility, function(visible, value) {
	    if(visible) {
		eshc.getElements('input#eshc-'+value+']').show();
		eshc.getElements('label[for="eshc-'+value+'"]').show();
		if(eshc.getElement('input#eshc-'+value+']').get('checked')) {
		    $('editTable').getElements(".editTable_"+value).show();
		} else {
		    $('editTable').getElements(".editTable_"+value).hide();
		}
	    } else {
		eshc.getElements('input#eshc-'+value+']').hide();
		eshc.getElements('label[for="eshc-'+value+'"]').hide();
		$('editTable').getElements(".editTable_"+value).hide();
	    }
	});
	if(this.useLemmaLookup) {
	    $('editTable').getElements(".editTableLemmaLink").show('inline');
	} else {
	    $('editTable').getElements(".editTableLemmaLink").hide();
	}
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
    
    /* Function: isValidTagCombination

       Check whether a given combination of POS and morph tag is valid
       with regard to the currently loaded tagset.

       Parameters:
        pos - the POS tag
	morph - the morphology tag
     */
    isValidTagCombination: function(pos, morph) {
        var tag = (morph && morph !== "--") ? (pos+"."+morph) : pos;
        return (cora.currentTagset("pos").tags.contains(tag));
    },

    /* Function: updateModSelect

       Update status of the modernisation type select box.

       Parameters:
        td - current table data object
	mod - current modernisation value
    */
    updateModSelect: function(td, mod) {
	if(!mod || mod=="") {
	    td.getElement("option[value='']").set('selected', 'selected');
	    td.getElement("select").set('disabled', 'disabled');
	} else {
	    td.getElement("select").set('disabled', null);
	}
    },

    /* Function: updateInputError

       Visualize whether selected tags are legal values.

       Checks legality of the selected tags in a given row with regard
       to the currently loaded tagset (and, in case of morphology
       tags, to the selected POS tag), then adds or removes the class
       signalling an illegal input value.

       Parameters:
        tr - table row object to examine
    */
    updateInputError: function(tr) {
	var iec = this.inputErrorClass;
	var tselect, ttag, modval;
	var pselect, ptag, mselect, mtag;
	try {
	    tselect = tr.getElement('td.editTable_mod select');
	    ttag = tselect.getSelected()[0].get('value');
	    modval = tr.getElement('td.editTable_mod input').get('value');
	} catch(err) {}
	if(tselect) {
	    if(modval!="" && ttag=="") {
		tselect.addClass(iec);
	    } else {
		tselect.removeClass(iec);
	    }
	}

	try {
	    pselect = tr.getElement('td.editTable_pos select');
	    ptag = pselect.getSelected()[0].get('value');
	    mselect = tr.getElement('td.editTable_morph select');
	    mtag = mselect.getSelected()[0].get('value');
	} catch(err) {	// row doesn't have the select, or the select is empty
	    return;
	}
	if(ptag!="") {
            if(typeof(cora.currentTagset("pos").tags_for[ptag]) == "undefined") {
		pselect.addClass(iec);
	    } else {
		pselect.removeClass(iec);
	    }

	    if(!this.isValidTagCombination(ptag,mtag)) {
		mselect.addClass(iec);
	    } else {
		mselect.removeClass(iec);
	    }
	}
    },

    /* Function: updateShowInputErrors

       Show or hide input error visualization for each row in the
       table.

       Calls updateInputError() for each row in the table.  Used when
       the corresponding user setting changes. */
    updateShowInputErrors: function() {
	if(userdata.showInputErrors) {
	    $('editTable').getElements('tr').each(this.updateInputError.bind(this));
	} else {
	    $$('.editTable_pos select').removeClass(this.inputErrorClass);
	    $$('.editTable_morph select').removeClass(this.inputErrorClass);
	}
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

    /* Function: renderMorphOptions

       Re-render the morphology tag drop-down box when POS tag
       changes.

       This function is only called when the POS tag changes.  The
       functionality is replicated in displayPage() where it is used
       during page rendering (but see below).

       CAUTION: This function now also guarantees that only valid
       morphology tags for the selected POS tag are displayed, and
       auto-selects the first valid morphology tag if necessary.  This
       is not desired during the initial page rendering, though, which
       is another reason (besides performance) why this function
       should not be called during page rendering!
    
       Parameters:
        id - line ID
	tr - table row object of that line
	postag - the new POS tag
    */
    renderMorphOptions: function(id, tr, postag) {
	var morphopt, suggestions, line, isvalid;
	var ref = this;
	var mselect = tr.getElement('.editTable_morph select');

	if (!postag) {
	    mselect.empty();
	    this.updateData(id, 'anno_morph', '');
	    return;
	}

        morphopt = cora.currentTagset("pos").optgroup_for[postag];
        if(typeof(morphopt) !== "undefined")
            morphopt = morphopt.clone();
        else
            morphopt = cora.tagsets.generateOptgroup([]);
	line = this.data[id];
	if (line.suggestions) {
	    suggestions = new Element('optgroup', {'label': 'Vorgeschlagene Tags', 'class': 'lineSuggestedTag'});
	    line.suggestions.each(function(opt){
		if(ref.isValidTagCombination(postag, opt.morph)) {
		    suggestions.grab(new Element('option',{
			text: opt.morph+" ("+opt.score+")",
			value: opt.morph,
			'class': 'lineSuggestedTag'
		    }),'top');
		}
            });
	    if(suggestions.getChildren().length == 0) {
		suggestions = false;
	    }
	}

	isvalid = this.isValidTagCombination(postag, line.anno_morph);
	mselect.empty();
	if(isvalid) {
	    mselect.grab(new Element('option',{
		text: line.anno_morph,
		value: line.anno_morph,
		selected: 'selected',
		'class': 'lineSuggestedTag'
	    }),'top');
	}
	if(suggestions) {
	    mselect.grab(suggestions);
	}
	mselect.grab(morphopt);
	if(!isvalid) {
	    var newmorph;
	    if(suggestions) {
		newmorph = suggestions.getChildren()[0];
	    } else {
		newmorph = morphopt.getChildren()[0];
	    }
	    if(newmorph) {
		this.updateData(id, 'anno_morph', newmorph.get('value'));
		newmorph.set('selected', 'selected');
	    }
	}
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

    /* Function: updateData
       
       Updates a field of data of a given line in memory.

       Parameters:
         line_id - ID of the line to be modified
	 data_type - Name of the field to be changed
	 new_data - Data to be written to that field
     */
    updateData: function(line_id, data_type, new_data){
	var line = this.data[line_id];
	if (line != undefined) {
	    line[data_type] = new_data;
	    if(!this.changedLines.contains(line_id)) {
		this.changedLines.push(line_id);
	    }
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
	var tr, line, posopt, morphopt, mselect, trs, j;
	var optgroup, elem, lemma_input;
	var dlr, dynstart, dynend;
	var lineinfo;
        var et_spinner;
        var fn_callback, fn_onerror;
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
            return;
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
	while (trs[j] != undefined) {
	    if (j>pl) { // remove superfluous lines
		trs[j].destroy();
	    }
	    j++;
	}
	while (j<=pl) { // add missing lines
	    tr_clone = this.lineTemplate.clone();
	    et.adopt(tr_clone);
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
	    if (line.general_error != null && line.general_error == 1) {
		tr.getElement('div.editTableError').addClass('editTableErrorChecked');
	    } else {
		tr.getElement('div.editTableError').removeClass('editTableErrorChecked');
	    }
	    if (line.lemma_verified != null && line.lemma_verified == 1) {
		tr.getElement('div.editTableLemma').addClass('editTableLemmaChecked');
	    } else {
		tr.getElement('div.editTableLemma').removeClass('editTableLemmaChecked');
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
	    tr.getElement('.editTable_Comment input').set('value', line.comment);

	    // build annotation elements
	    var norm_tr = tr.getElement('.editTable_norm input');
	    var mod_tr  = tr.getElement('.editTable_mod input');
	    var mod_trs = tr.getElement('.editTable_mod select');
	    if(norm_tr != null && norm_tr != undefined) {
		norm_tr.set('value', line.anno_norm);
	    }
	    if(mod_tr != null && mod_tr != undefined) {
		mod_tr.set('value', line.anno_mod);
		mod_tr.set('placeholder', line.anno_norm);
	    }
	    if(mod_trs != null && mod_trs != undefined) {
		if(line.anno_mod != null && line.anno_mod != undefined) {
		    mod_trs.set('disabled', null);
		} else {
		    mod_trs.set('disabled', 'disabled');
		}
		mod_trs.getElement("option[value='']").set('selected', 'selected');
		if(line.anno_modtype != null && line.anno_modtype != undefined) {
		    mod_trs = mod_trs.getElement("option[value='" + line.anno_modtype + "']");
		    if(mod_trs != null) {
			mod_trs.set('selected', 'selected');
		    }
		}
	    }

	    // Lemma auto-completion
	    lemma_input = tr.getElement('.editTable_lemma input');
	    lemma_input.removeEvents();
	    lemma_input.set('value', line.anno_lemma);
	    /* Auto-completion now returns more than just results from
	     * a closed lemma tagset, so it should always be
	     * instantiated regardless of this.useLemmaLookup */
	    this.makeNewAutocomplete(lemma_input, line.num);

	    // Lemma-POS
	    var lemma_pos = tr.getElement('.editTable_lemmapos select');
	    if(lemma_pos != null && lemma_pos != undefined) {
		lemma_pos.getElements('.lineSuggestedTag').destroy();
		lemma_pos.grab(new Element('option',{
		    text: (line.anno_lemmapos == undefined) ? '' : line.anno_lemmapos,
		    value: line.anno_lemmapos,
		    selected: 'selected',
		    'class': 'lineSuggestedTag'
		}),'top');
	    }

            // POS
	    posopt = tr.getElement('.editTable_pos select');
	    posopt.getElements('.lineSuggestedTag').destroy();
	    if(line.suggestions.length>0) {
		optgroup = new Element('optgroup', {'label': 'Vorgeschlagene Tags', 'class': 'lineSuggestedTag'});
		line.suggestions.each(function(opt){
		    optgroup.grab(new Element('option',{
			text: opt.pos+" ("+opt.score+")",
			value: opt.pos,
			'class': 'lineSuggestedTag'
		    }),'top');
		});
		posopt.grab(optgroup, 'top');
	    }
	    posopt.grab(new Element('option',{
		text: (line.anno_pos == undefined) ? '' : line.anno_pos,
		value: line.anno_pos,
		selected: 'selected',
		'class': 'lineSuggestedTag'
	    }),'top');
            
            // Morph
	    mselect = tr.getElement('.editTable_morph select');
	    mselect.empty();
	    mselect.grab(new Element('option',{
                text: line.anno_morph,
		value: line.anno_morph,
		selected: 'selected',
		'class': 'lineSuggestedTag'
	    }));

	    if (line.suggestions.length>0) {
		optgroup = new Element('optgroup', {'label': 'Vorgeschlagene Tags', 'class': 'lineSuggestedTag'});
		line.suggestions.each(function(opt){
		    optgroup.grab(new Element('option',{
			text: opt.morph+" ("+opt.score+")",
			value: opt.morph,
			'class': 'lineSuggestedTag'
		    }),'top');
		});
		mselect.grab(optgroup);
	    }

            var m_optgroup = cora.currentTagset("pos").optgroup_for[line.anno_pos];
	    if (typeof(m_optgroup) !== "undefined") {
                mselect.grab(m_optgroup.clone());
	    }

	    if(userdata.showInputErrors) {
		this.updateInputError(tr);
	    }
	}

	/* unhide the table */
	et.show();

	/* horizontal text view */
	this.updateHorizontalView(start, end);

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
		var lineArray = status['data'];
		if (Object.getLength(lineArray)==0) {
                    if(typeof(onerror) === "function")
                        onerror({
			    'name': 'EmptyRequest',
			    'message': "Ein Fehler ist aufgetreten: Server-Anfrage für benötigte Zeilen "+start+" bis "+(end-1)+" lieferte kein Ergebnis zurück."
		        });
                    return;
		}
		Object.each(lineArray, function(ln) {
		    if (this.data[ln.num] == undefined) {
			this.data[ln.num] = ln;
		    }
		}.bind(this));
		this.requestLines(start, end, fn, onerror);
	    }.bind(this)
	}).get({'do': 'getLinesById', 'start_id': range[0], 'end_id': range[1]});
    },

    /* Function: saveData

       Send a server request to save the modified lines
       to the database.
     */
    saveData: function() {
	var req, cl, data, save, line, tp, tm, ler;
	var ref = this;

	cl = this.changedLines;
	if (cl==null) { return true; }
	data = this.data;
	save = new Array();

	for (var i=0, len=cl.length; i<len; i++) {
	    line=data[cl[i]];
	    tp = line.anno_pos==null ? "" : line.anno_pos;
	    tm = line.anno_morph==null ? "" : line.anno_morph;
	    save.push({
		id: line.id,
		general_error: line.general_error,
		lemma_verified: line.lemma_verified,
		anno_lemma: line.anno_lemma,
		anno_lemmapos: line.anno_lemmapos,
		anno_pos: tp, //.replace(/\s[\d\.]+/g,""),
		anno_morph: tm, //.replace(/\s[\d\.]+/g,""),
		anno_norm: line.anno_norm,
		anno_mod: line.anno_mod,
		anno_modtype: line.anno_modtype,
		comment: line.comment
	    });
	}

	var ler = (!(this.lastEditedRow in data) ? this.lastEditedRow : data[this.lastEditedRow].id);
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
	}

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
	}

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

    /* Function: makeNewAutocomplete

       Activates auto-complete functionality for an input text box.

       Parameters:
         inputfield - The element to be given auto-complete functionality
	 linenum - The line number of the element, supplied to the AJAX query
	           for auto-complete suggestions
     */
    makeNewAutocomplete: function(inputfield, linenum) {
	var ref = this;
	var splitExternalId = function(text) {
	    var re = new RegExp("^(.*) \\[(.*)\\]$");
	    var match = re.exec(text);
	    return (match == null) ? [text, ""] : [match[1], match[2]];
	};
	linenum = this.data[linenum].id;
	new Meio.Autocomplete(inputfield,
			      'request.php?do=fetchLemmaSugg', {
				  delay: 100,
				  urlOptions: {
				      extraParams: [
					  {'name': 'linenum', 'value': linenum}
				      ]
				  },
				  filter: {
				      //type: 'startswith',
				      //path: 'v',
				      filter: function(text, data) {
					  if(data.t=="s" || data.t=="c") {
					      return true;
					  }
					  return text ? data.v.standardize().test(new RegExp("^" + text.standardize().escapeRegExp(), 'i')) : true;
				      },
				      formatMatch: function(text, data, i){
					  //return splitExternalId(data.v)[0];
					  return data.v;
				      },
				      formatItem: function(text, data){
					  var sdata = splitExternalId(data.v);
					  var item = (text && data.t=="q") ? ('<strong>'  + sdata[0].substr(0, text.length) + '</strong>' + sdata[0].substr(text.length)) : sdata[0];
					  if(sdata[1]!="") {
					      item = item + " <span class='ma-greyed'>[" + sdata[1] + "]</span>";
					  }
					  if(data.t=="c") {
					      item = "<span class='ma-confirmed'>" + item + "</span>";
					  }
					  if(data.t=="s" || data.t=="c") {
					      item = "<div class='ma-sugg'>" + item + "</div>";
					  }
					  return item;
				      }
				  }
			      }).addEvent('select', function(e,v,text,index) { 
				  var parent = e.field.node.getParent("tr");
				  var this_id = ref.getRowNumberFromElement(parent);
				  var verified = (v.t=="c") ? 1 : 0;

				  if (verified) {
				      parent.getElement('div.editTableLemma').addClass('editTableLemmaChecked');
				  } else {
				      parent.getElement('div.editTableLemma').removeClass('editTableLemmaChecked');
				  }

				  ref.updateData(this_id, 'anno_lemma', text);
				  ref.updateData(this_id, 'lemma_verified', verified);
				  ref.updateProgress(this_id, true);
			      });
    },

    /* Function: updateHorizontalView

       Updates the horizontal text preview.

       TODO: This implementation is rather hacky and inelegant. It
       contains several hardcoded values, and probably, all functions
       related to the preview should be encapsulated in a separate
       object.
     */
    updateHorizontalView: function(start, end) {
	var data = this.data;
	var currenttok = -1;
	var span;
        var container = $('horizontalTextViewContainer');
	var view = $('horizontalTextView');
	var terminators = ['(.)', '(!)', '(?)'];
	var maxctxlen = 30;
	var startlimit = Math.max(0, start - maxctxlen);
	var endlimit   = Math.min(this.lineCount, end + maxctxlen);
        var fn_callback, fn_onerror;

        if(userdata.textPreview == "off") {
            container.hide();
            $('editPanelDiv').setStyle('margin-bottom', 0);
            return;
        }
        if(userdata.textPreview == "utf") {
            view.addClass("text-preview-utf");
        } else {
            view.removeClass("text-preview-utf");
        }
        container.show();

        if(!this.isRangeLoaded(startlimit, endlimit)) {
            if(this.horizontalViewSpinner == null || this.horizontalViewSpinner.hidden) {
                this.horizontalViewSpinner = new Spinner($('horizontalTextView'));
                this.horizontalViewSpinner.show();
            }
            fn_callback = function() {
                this.updateHorizontalView(start, end);
            }.bind(this);
            fn_onerror  = function(e) {
                gui.showNotice('error', "Problem beim Laden der Text-Vorschau.");
            };
            this.requestLines(startlimit, endlimit, fn_callback, fn_onerror);
            return;
        }
	// find nearest sentence boundaries
	for (; start>=startlimit; start--) {
	    if(data[start]!==undefined && terminators.contains(data[start].trans)) {
		break;
	    }
	}
	start++;
	for (; end<endlimit; end++) {
	    if(data[end]!==undefined && terminators.contains(data[end].trans)) {
		end++;
		break;
	    }
	}

	// update the view
	view.empty();
	for (var i=start; i<end; i++) {
	    line = data[i];
	    span = new Element('span', {'id': 'htvl_'+line.num,
					      'text': line[userdata.textPreview]});
	    if(line.tok_id != currenttok) {
		view.appendText(" ");
		currenttok = line.tok_id;
	    }
	    view.adopt(span);
	}
        this.horizontalViewSpinner.hide();

        // ensure there's enough margin after the editor <div>
        // to compensate for the preview pane
        if(container.isVisible()) {
            setTimeout(function(){ 
                $('editPanelDiv').setStyle('margin-bottom', container.getHeight());
            }, 100);
        } else {
            container.measure(function() {
                $('editPanelDiv').setStyle('margin-bottom', this.getHeight());
            });
        }
    },

    highlightHorizontalView: function(lineid) {
        if(userdata.textPreview == "off") return;
	var view = $('horizontalTextView');
	var span;
	view.getElements('.highlighted').removeClass('highlighted');
	span = view.getElement('#htvl_'+lineid);
	if(span != null) {
	    span.addClass('highlighted');
	}
    },

    /* Function: prepareAnnotationOptions
     */
    prepareAnnotationOptions: function() {
	var aaselect = $('automaticAnnotationForm').getElement('#aa_tagger_select');
        var alltaggers = cora.files.getTaggers(cora.current());
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
	}

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

cora.editor = null;

window.addEvent('domready',function(){
    $('editTabButton').hide();
})
