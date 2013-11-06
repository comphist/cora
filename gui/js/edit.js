var EditorModel = new Class({
    fileId: 0,
    lineTemplate: null,
    lastEditedRow: -1,
    activePage: 0,
    maxPage: 0,
    displayedLinesStart: 0,
    displayedLinesEnd: 0,
    lineCount: 0,
    data: {},
    changedLines: null,
    editTable: null,
    tries: 0,            
    maximumTries: 20,    // maximum number of load requests before giving up
    dynamicLoadRange: 5, // how many pages should be loaded in advance
    inputErrorClass: "", // browser-dependent CSS styling for input errors
    dropdown: null,  // contains the currently opened dropdown menu, if any
    useLemmaLookup: false,

    /* Constructor: EditorModel

       Initialize the editor model.

       Parameters:
         fileid - ID of the file represented by this model
	 line_count - Total number of lines in the file
	 last_edited_row - Line number that was last edited
	 start_page - The page to first display
    */
    initialize: function(fileid, line_count, last_edited_row, start_page) {
	var elem, td, spos, smorph, et, mr, btn;
	var ref = this;

	if(Browser.chrome) {
	    this.inputErrorClass = "input_error_chrome";
	} else {
	    this.inputErrorClass = "input_error";
	}

	this.lineCount = Number.from(line_count);
	if(last_edited_row!==null) {
	    this.lastEditedRow = Number.from(last_edited_row);
	}
	this.changedLines = new Array();
	this.fileId = fileid;

	this.editTable = $('editTable');
	et = this.editTable;

	this.useLemmaLookup = false;

	/* set up the line template */
	elem = $('line_template');
	
	spos = new Element('select');
	spos.grab(new Element('optgroup', {'html': fileTagset.posHTML, 'label': 'Alle Tags'}));
	td = elem.getElement('td.editTable_POS')
	td.empty();
	td.adopt(spos);
	
	smorph = new Element('select');
	td = elem.getElement('td.editTable_Morph');
	td.empty();
	td.adopt(smorph);
	
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
		var this_id = target.getParent('tr').get('id').substr(5);
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
		var this_id = target.getParent('tr').get('id').substr(5);
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
		var this_id = target.getParent('tr').get('id').substr(5);
		var parent = target.getParent('td');
		var new_value = target.getSelected()[0].get('value');
		if (parent.hasClass("editTable_POS")) {
		    ref.updateData(this_id, 'anno_POS', new_value);
		    ref.renderMorphOptions(this_id, target.getParent('tr'), new_value);
		    if (userdata.showInputErrors)
			ref.updateInputError(target.getParent('tr'));
		} else if (parent.hasClass("editTable_Morph")) {
		    ref.updateData(this_id, 'anno_morph', new_value);
		    if (userdata.showInputErrors)
			ref.updateInputError(target.getParent('tr'));
		} else if (parent.hasClass("editTable_Mod")) {
		    ref.updateData(this_id, 'anno_modtype', new_value);
		    if (userdata.showInputErrors)
			ref.updateInputError(target.getParent('tr'));
		}
		ref.updateProgress(this_id, true);
	    }
	);
	et.addEvent(
	    'change:relay(input)',
	    function(event, target) {
		var this_id = target.getParent('tr').get('id').substr(5);
		var parent = target.getParent('td');
		var new_value = target.get('value');
		if (parent.hasClass("editTable_Norm")) {
		    ref.updateData(this_id, 'anno_norm', new_value);
		    parent.getSiblings("td.editTable_Mod input")[0].set('placeholder', new_value);
		    ref.updateProgress(this_id, true);
		} else if (parent.hasClass("editTable_Mod")) {
		    ref.updateData(this_id, 'anno_mod', new_value);
		    //ref.updateModSelect(parent, new_value);
		    if (userdata.showInputErrors)
			ref.updateInputError(target.getParent('tr'));
		    ref.updateProgress(this_id, true);
		} else if (parent.hasClass("editTable_Lemma")) {
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
		var new_value = target.get('value');
		var new_row, new_target, this_class;
		if (parent.hasClass("editTable_Mod")) {
		    ref.updateModSelect(parent, new_value);
		}
		if (event.code == 40) { // down arrow
		    this_class = parent.get('class');
		    if(event.control || this_class != "editTable_Lemma") {
			new_row = parent.getParent('tr').getNext('tr');
			if(new_row != null) {
			    new_target = new_row.getElement('td.'+this_class+' input');
			    if(new_target != null) {
				new_target.focus();
			    }
			}
		    }
		}
		if (event.code == 38) { // up arrow
		    this_class = parent.get('class');
		    if(event.control || this_class != "editTable_Lemma") {
			new_row = parent.getParent('tr').getPrevious('tr');
			if(new_row != null) {
			    new_target = new_row.getElement('td.'+this_class+' input');
			    if(new_target != null) {
				new_target.focus();
			    }
			}
		    }
		}
	    }
	);
	et.addEvent(
	    'dblclick:relay(td)',
	    function(event, target) {
		var this_id = target.getParent('tr').get('id').substr(5);
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
		var this_id = target.getParent('tr').get('id').substr(5);
		ref.highlightHorizontalView(this_id);
	    }
	);

	/* activate extra menu bar */
	mr = $('menuRight');
	btn = mr.getElement('li#saveButton');
	btn.removeEvents();
	btn.addEvent('click', function(e) {
	    e.stop();
	    ref.saveData();
	});
	btn = mr.getElement('li#closeButton');
	btn.removeEvents();
	btn.addEvent('click', function(e) {
	    e.stop();
	    file.closeFile(ref.fileId); // breaks OO...
	});
	mr.show();

	this.initializeColumnVisibility();

	/* render pages panel and set start page */
	start_page = Number.from(start_page);
	if(start_page==null || start_page<1) { start_page = 1; }
	this.renderPagesPanel(start_page);
	this.displayPage(start_page);
	this.activePage = start_page;
    },

    /* Function: initializeColumnVisibility
       
       Hide or show columns based on whether the required tagsets are
       associated with the currently opened file.
    */
    initializeColumnVisibility: function() {
	var visibility = {
	    "Norm":  false,
	    "Mod":   false,
	    "Lemma": false,
	};
	var normbroad = false;
	var normtype = false;
	/* Check tagset associations */
	fileTagset.list.each(function(tagset) {
	    if(tagset['class'] == "lemma") {
		if(tagset['set_type'] == "open") {
		    visibility["Lemma"] = true;
		} else {
		    this.useLemmaLookup = true;
		}
	    }
	    if(tagset['class'] == "norm") { visibility["Norm"] = true; }
	    if(tagset['class'] == "norm_broad") { normbroad = true; }
	    if(tagset['class'] == "norm_type") { normtype = true; }
	}.bind(this));
	if(normbroad && normtype) {
	    visibility["Mod"] = true;
	}

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

    /* Function: isValidTagCombination

       Check whether a given combination of POS and morph tag is valid
       with regard to the currently loaded tagset.

       Parameters:
        pos - the POS tag
	morph - the morphology tag
     */
    isValidTagCombination: function(pos,morph) {
	if(pos && morph && fileTagset.pos.contains(pos) &&
	   fileTagset.morph[pos]!==null &&
	   fileTagset.morph[pos].contains(morph)) {
		return true;
	}
	return false;
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
	    tselect = tr.getElement('td.editTable_Mod select');
	    ttag = tselect.getSelected()[0].get('value');
	    modval = tr.getElement('td.editTable_Mod input').get('value');
	} catch(err) {}
	if(tselect) {
	    if(modval!="" && ttag=="") {
		tselect.addClass(iec);
	    } else {
		tselect.removeClass(iec);
	    }
	}

	try {
	    pselect = tr.getElement('td.editTable_POS select');
	    ptag = pselect.getSelected()[0].get('value');
	    mselect = tr.getElement('td.editTable_Morph select');
	    mtag = mselect.getSelected()[0].get('value');
	} catch(err) {	// row doesn't have the select, or the select is empty
	    return;
	}
	if(ptag!="") {
	    if(!fileTagset.pos.contains(ptag)) {
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
	    $$('.editTable_POS select').removeClass(this.inputErrorClass);
	    $$('.editTable_Morph select').removeClass(this.inputErrorClass);
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
	this.displayPage(this.activePage);
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
	var mselect = tr.getElement('.editTable_Morph select');

	if (!postag) {
	    mselect.empty();
	    return;
	}

	morphopt = new Element('optgroup', {'label': "Alle Tags für '"+postag+"'"});
	if (fileTagset.morphHTML[postag] != undefined) {
	    morphopt.set('html', fileTagset.morphHTML[postag]);
	}
	else { // ensure there is always at least the empty selection
	    morphopt.set('html', '<option value="--">--</option>');
	}
	line = this.data[id];
	if (line.suggestions) {
	    suggestions = new Element('optgroup', {'label': 'Vorgeschlagene Tags', 'class': 'lineSuggestedTag'});
	    line.suggestions.each(function(opt){
		if(ref.isValidTagCombination(postag, opt.morph)) {
		    suggestions.grab(new Element('option',{
			html: opt.morph+" ("+opt.score+")",
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
		html: line.anno_morph,
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

    /* Function: displayNextPage

       Displays the next page of the document.
    */
    displayNextPage: function() {
	var ps = $('pageSelector');
	var new_page = ps.value.toInt() + 1;
	if (new_page<=this.maxPage) {
	    ps.set('value', new_page);
	    this.displayPage(new_page);
	}
    },

    /* Function: displayPreviousPage

       Displays the previous page of the document.
    */
    displayPreviousPage: function() {
	var ps = $('pageSelector');
	var new_page = ps.value.toInt() - 1;
	if (new_page>0) {
	    ps.set('value', new_page);
	    this.displayPage(new_page);
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

    /* Function: renderPagesPanel

       Render the page navigator panel.

       Disposes of the old panel (if present) and creates a new 
       one. Typically called on first page load and whenever 
       editor settings change, affecting the total number of
       pages.

       Parameters:
         active_page - The page number to set as the active page.
	               Does not cause that page to be displayed;
		       for this, a separate call to <displayPage>
		       is required.
    */
    renderPagesPanel: function(active_page) {
	var x, y, max_page, dropdown, el;
	var jumpto, jumptoFunc, jumptoBox;
	var pp = $('pagePanel');
	var ref = this;
	active_page = Number.from(active_page);
	
	pp.hide();
        pp.getElements('a').dispose();
	pp.getElements('select').dispose();

	/* calculate the total number of pages */
	x = (this.lineCount - userdata.contextLines);
	y = (userdata.noPageLines - userdata.contextLines);
	max_page = (x % y) ? Math.ceil(x/y) : (x/y);
	this.maxPage = max_page;

	if (active_page==null || active_page<1) {
	    active_page = 1;
	} else if (active_page>max_page) { 
	    active_page = max_page;
	}

	/* create navigation elements */
        pp.adopt(new Element('a',{
	    href: 'first', text: '|<<',
	    events: {
		click: function(e) {
		    e.stop();
		    $('pageSelector').set('value', 1);
		    ref.displayPage(1);
		}
	    },
	}));
	
        pp.adopt(new Element('a',{
	    href: 'back', text: '<',
	    events: {
		click: function(e) {
		    e.stop();
		    ref.displayPreviousPage();
		}
	    },
	}));
	
	dropdown = new Element('select', {
	    id: 'pageSelector',
	    name: 'pages',
	    size: 1,
	    events: {
		change: function(e) {
		    e.stop();
		    ref.displayPage(this.value);
		}
	    }
	});
        for (var i=1; i<=max_page; i++) {
	    el = new Element('option', {text: i});
	    if(i==active_page) { el.set('selected', 'selected'); }
	    dropdown.adopt(el);
        };
	pp.adopt(dropdown);
	
        pp.adopt(new Element('a',{
	    href: 'forward', text: '>',
	    events: {
		click: function(e) {
		    e.stop();
		    ref.displayNextPage();
		}
	    },
	}));
	
        pp.adopt(new Element('a',{
	    href: 'last', text: '>>|',
	    events: {
		click: function(e) {
		    e.stop();
		    $('pageSelector').set('value', ref.maxPage);
		    ref.displayPage(ref.maxPage);
		}
	    },
	}));

	// Jump to line
	// Whoa, this is a mess ...
	jumpto = new Element('a',{
	    href: 'javascript:;', text: 'Springe zu Zeile...',
	});
	jumptoFunc = function(mbox) {
	    var line_no = Number.from($('jumpToBox').value);
	    if(line_no==null) {
		gui.showNotice('error', 'Bitte eine Zahl eingeben.');
	    } else if(line_no>ref.lineCount || line_no<1) {
		gui.showNotice('error', 'Zeilennummer existiert nicht.');
	    } else {
		var new_page = ref.displayPageByLine(line_no);
		$('pageSelector').set('value', new_page);
		mbox.close();
	    }
	}
	pp.adopt(jumpto);
	jumptoBox = new mBox.Modal({
	    content: 'jumpToLineForm',
	    title: 'Springe zu Zeile',
	    buttons: [
		{title: 'Abbrechen', addClass: 'mform'},
		{title: 'OK', addClass: 'mform button_green',
		 event: function() {
		     jumptoFunc(this);
		 }
		}
	    ],
	    onOpenComplete: function() {
		$('jumpToBox').focus();
		$('jumpToBox').select();
	    },
	    attach: jumpto
	});
	$('jumpToBox').removeEvents('keydown');
	$('jumpToBox').addEvent('keydown', function(event) {
	    if(event.key == "enter") {
		jumptoFunc(jumptoBox);
	    }
	});

	pp.show();
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

    /* Function: displayPage

       Render a page of editor lines.

       Takes a page number and renders all lines belonging to that
       page number. If lines are not in memory, they are dynamically
       fetched from the server. Lines in the editor are constructed
       by hiding the editor table, modifying the existing HTML table
       rows in place by filling them with the new data, then showing
       the editor table again. If there are not enough lines (e.g.
       on first load, or because editor settings have changed),
       new lines are constructed from the class's line template.

       Parameters:
         page - Number of the page to be displayed

       Returns:
         'true' if the page was displayed successfully.
     */
    displayPage: function(page){
	var ref = this;
	var cl = userdata.contextLines;
	var pl = userdata.noPageLines;
	var data = this.data;
	var et = this.editTable;
	var ler = this.lastEditedRow;
	var morphhtml = fileTagset.morphHTML;
	var morph = fileTagset.morph;
	var pos = fileTagset.pos;
	var end, start, tr, line, posopt, morphopt, mselect, trs, j;
	var optgroup, elem, lemma_input;
	var dlr, dynstart, dynend;
	var lineinfo;

	/* calculate line numbers to be displayed */
	if (page==0) { page++; }
	end   = page * (pl - cl) + cl;
	start = end - pl;
	if (end>this.lineCount) { 
	    end = this.lineCount;
	    pl  = end - start;
	}

	/* ensure all lines are in memory */
	try { 
	    this.requestLines(start, end, false);
	}
	catch(e) {
	    alert(e.message);
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
	j=1;
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
	    var norm_tr = tr.getElement('.editTable_Norm input');
	    var mod_tr  = tr.getElement('.editTable_Mod input');
	    var mod_trs = tr.getElement('.editTable_Mod select');
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
	    lemma_input = tr.getElement('.editTable_Lemma input');
	    lemma_input.removeEvents();
	    lemma_input.set('value', line.anno_lemma);
	    /* Auto-completion now returns more than just results from
	     * a closed lemma tagset, so it should always be
	     * instantiated regardless of this.useLemmaLookup */
	    this.makeNewAutocomplete(lemma_input, line.num);

            // POS
	    posopt = tr.getElement('.editTable_POS select');
	    posopt.getElements('.lineSuggestedTag').destroy();
	    if(line.suggestions.length>0) {
		optgroup = new Element('optgroup', {'label': 'Vorgeschlagene Tags', 'class': 'lineSuggestedTag'});
		line.suggestions.each(function(opt){
		    optgroup.grab(new Element('option',{
			html: opt.POS+" ("+opt.score+")",
			value: opt.POS,
			'class': 'lineSuggestedTag'
		    }),'top');
		});
		posopt.grab(optgroup, 'top');
	    }
	    posopt.grab(new Element('option',{
		html: line.anno_POS,
		value: line.anno_POS,
		selected: 'selected',
		'class': 'lineSuggestedTag'
	    }),'top');

            // Morph
	    mselect = tr.getElement('.editTable_Morph select');
	    mselect.empty();
	    mselect.grab(new Element('option',{html: line.anno_morph,
					       value: line.anno_morph,
					       selected: 'selected',
					       'class': 'lineSuggestedTag'
					      }));

	    if (line.suggestions.length>0) {
		optgroup = new Element('optgroup', {'label': 'Vorgeschlagene Tags', 'class': 'lineSuggestedTag'});
		line.suggestions.each(function(opt){
		    optgroup.grab(new Element('option',{
			html: opt.morph+" ("+opt.score+")",
			value: opt.morph,
			'class': 'lineSuggestedTag'
		    }),'top');
		});
		mselect.grab(optgroup);
	    }

	    if (line.anno_POS!=null) {
		mselect.grab(new Element('optgroup', {'label': "Alle Tags für '"+line.anno_POS+"'",
						      html: morphhtml[line.anno_POS]}));
	    }

	    if(userdata.showInputErrors) {
		this.updateInputError(tr);
	    }
	}

	/* unhide the table */
	et.show();

	/* dynamically load context lines */
	dlr  = this.dynamicLoadRange * (userdata.noPageLines - cl);
	dynstart = start - dlr;
	dynend   = end   + dlr;
	this.requestLines(dynstart, dynend, true);

	/* horizontal text view */
	this.updateHorizontalView(start, end);

	this.activePage = page;
	this.displayedLinesStart = start;
	this.displayedLinesEnd = end - 1;
	this.tries = 0;
	return true;
    },

    /* Function: displayPageByLine

       Display page where a given line number appears.

       Parameters:
         line - Number of the line to display

       Returns:
         The page number that holds the given line.
     */
    displayPageByLine: function(line) {
	if(line>this.lineCount){
	    line = this.lineCount;
	}
	y = (userdata.noPageLines - userdata.contextLines);
	page_no = (line % y) ? Math.ceil(line/y) : (line/y);
	this.displayPage(page_no);
	return page_no;
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


    /* Function: requestLines

       Ensures that lines in a given range are loaded in memory, and
       requests them from the server if necessary.

       Parameters:
         start - Number of the first line to load
	 end - Number of the line after the last line to load
	 async - Whether or not to perform asynchronous calls
    */
    requestLines: function(start, end, async) {
	var range = this.getMinimumLineRange(start, end);
	if(range.length == 0) { // success!
	    this.tries = 0;
	    return true;
	}
	if(this.tries++>20) { // prevent endless recursion
	    throw {
		'name': 'FailureToLoadLines',
		'message': "Ein Fehler ist aufgetreten: Zeilen "+start+" bis "+(end-1)+" können nicht geladen werden."
	    };
	    return false;
	}

	new Request.JSON({
	    url: 'request.php',
	    async: async,
	    onSuccess: function(status, text) {
		var lineArray = status['data'];
		if (Object.getLength(lineArray)==0) {
		    throw {
			'name': 'EmptyRequest',
			'message': "Ein Fehler ist aufgetreten: Server-Anfrage für benötigte Zeilen "+start+" bis "+(end-1)+" lieferte kein Ergebnis zurück."
		    };
		    return;
		}
		Object.each(lineArray, function(ln) {
		    if (this.data[ln.num] == undefined) {
			this.data[ln.num] = ln;
		    }
		}.bind(this));
		this.requestLines(start, end, async);
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
	    tp = line.anno_POS==null ? "" : line.anno_POS;
	    tm = line.anno_morph==null ? "" : line.anno_morph;
	    save.push({
		id: line.id,
		general_error: line.general_error,
		lemma_verified: line.lemma_verified,
		anno_lemma: line.anno_lemma,
		anno_POS: tp, //.replace(/\s[\d\.]+/g,""),
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
		    
		    if(textarea!='') {
			$('saveErrorPopup').getElement('p').empty().appendText(message);
			$('saveErrorPopup').getElement('textarea').empty().appendText(textarea);
			message = 'saveErrorPopup';
		    }
		    new mBox.Modal({
			title: 'Speichern fehlgeschlagen',
			content: message,
			buttons: [ {title: "OK"} ]
		    }).open();
		}
		gui.hideSpinner();
	    },
	    onFailure: function(xhr) {
		new mBox.Modal({
		    title: 'Speichern fehlgeschlagen',
		    content: 'Das Speichern der Datei war nicht erfolgreich! Server lieferte folgende Antwort: "'+xhr.responseText+'" ('+xhr.statusText+').'
		}).open();
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
    */
    confirmClose: function() {
	var chl = this.changedLines.length;
	if (chl>0) {
	    var zeile = (chl>1) ? "Zeilen" : "Zeile";
	    return confirm("Warnung: Im geöffneten Dokument gibt es noch ungespeicherte Änderungen in "+chl+" "+zeile+", die verloren gehen, wenn Sie fortfahren.");
	} else {
	    return true;
	}
    },

    /* Function: showFailureMessage

       Generic function to display an error popup with a textarea.

    */
    showFailureMessage: function(message, rows, title) {
	var textarea = "";
	for(var i=0;i<rows.length;i++){
	    textarea += rows[i] + "\n";
	}
	if(textarea!='') {
	    $('saveErrorPopup').getElement('p').empty().appendText(message);
	    $('saveErrorPopup').getElement('textarea').empty().appendText(textarea);
	    message = 'saveErrorPopup';
	}
	new mBox.Modal({
	    title: title,
	    content: message,
	    buttons: [ {title: "OK"} ],
	    options: {fade: {close: false}},
	    closeOnBodyClick: false,
	}).open();
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
	this.renderPagesPanel(this.activePage);
	this.displayPage(this.activePage);
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
				 ref.showFailureMessage("Das Löschen des Tokens war nicht erfolgreich.", rows, "Löschen fehlgeschlagen");
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
		alert("Transkription darf nicht leer sein!");
		return false;
	    }
	    if(new_token.indexOf("=")>-1) {
		checkstr = new_token.replace(/=\) /g, "").replace(/= /g, "")
		    .replace(/=\| /g, "").replace(/<=> /g, "").replace(/<=>\| /g, "")
		    .replace(/<<=>> /g, "").replace(/<<=>>\| /g, "")
		    .replace(/\[=\] /g, "").replace(/\[=\]\| /g, "")
		    .replace(/\[\[=\]\] /g, "").replace(/\[\[=\]\]\| /g, "");
		if(checkstr.indexOf("=")>-1) {
		    new mBox.Modal({
			title: 'Sind Sie sicher?',
			content: 'editTokenConfirm',
			buttons: [
			    {title: 'Abbrechen', addClass: 'mform'},
			    {title: 'Ja', addClass: 'mform button_green',
			     event: function() {
				 this.close();
				 performEditRequest(mbox, new_token);
			     }
			    }
			],
			closeOnBodyClick: false,
			closeInTitle: false
		    }).open();
		    return false;
		}
	    }
	    performEditRequest(mbox, new_token);
	}

	var performEditRequest = function(mbox, new_token) {
	    // spaces are treated as line breaks atm
	    new_token = new_token.replace(/ /g, "\n");
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
			ref.showFailureMessage("Das Ändern der Transkription war nicht erfolgreich.", rows, "Bearbeiten fehlgeschlagen");
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
	$('editTokenBox').removeEvents('keydown');
	$('editTokenBox').addEvent('keydown', function(event) {
	    if(event.key == "enter") {
		performEdit(editTokenBox);
	    }
	});
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
		alert("Transkription darf nicht leer sein!");
		return false;
	    }
	    if(new_token.indexOf(" ") !== -1) {
		alert("Transkription darf keine Leerzeichen enthalten!");
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
			ref.showFailureMessage("Das Hinzufügen der Transkription war nicht erfolgreich.", rows, "Hinzufügen fehlgeschlagen");
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
	$('addTokenBox').removeEvents('keydown');
	$('addTokenBox').addEvent('keydown', function(event) {
	    if(event.key == "enter") {
		performAdd(addTokenBox);
	    }
	});
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
				  },
			      }).addEvent('select', function(e,v,text,index) { 
				  var parent = e.field.node.getParent("tr");
				  var this_id = parent.get("id").substr(5);
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
	var view = $('horizontalTextView');
	var terminators = ['(.)', '(!)', '(?)'];
	var maxctxlen = 50;
	var startlimit = Math.max(0, start - maxctxlen);
	var endlimit   = Math.min(this.lineCount, end + maxctxlen);
	this.requestLines(startlimit, endlimit, false);
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
					    'text': line.trans});
	    if(line.tok_id != currenttok) {
		view.appendText(" ");
		currenttok = line.tok_id;
	    }
	    view.adopt(span);
	}
    },

    highlightHorizontalView: function(lineid) {
	var view = $('horizontalTextView');
	var span;
	view.getElements('.highlighted').removeClass('highlighted');
	span = view.getElement('#htvl_'+lineid);
	if(span != null) {
	    span.addClass('highlighted');
	}
    }

});

var edit = {
    editorModel: null,
    
    initialize: function(){
	$('editTabButton').hide();
        this.lastEditLine = 0;
    },
}

window.addEvent('domready',function(){
    edit.initialize();
})
