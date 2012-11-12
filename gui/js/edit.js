var EditorModel = new Class({
    fileId: 0,
    lineTemplate: null,
    lastEditedRow: -1,
    activePage: 0,
    displayedLinesStart: 0,
    displayedLinesEnd: 0,
    lineCount: 0,
    data: {},
    changedLines: null,
    editTable: null,
    tries: 0,
    spinner: null,
    dynamicLoadRange: 5, // how many pages should be loaded in advance
    inputErrorClass: "", // browser-dependent CSS styling for input errors

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
	this.spinner = new Spinner($('overlay'), {message: "Speichere..."});

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
	et.addEvent(
	    'click:relay(div)',
	    function(event, target) {
		var new_value;
		var this_id = target.getParent('tr').get('id').substr(5);
		if(target.hasClass('editTableError')) {
		    new_value = target.hasClass('editTableErrorChecked') ? 0 : 1;
		    target.toggleClass('editTableErrorChecked');
		    ref.updateData(this_id, 'errorChk', new_value);
		} else if(target.hasClass('editTableProgress')) {
		    new_value = target.hasClass('editTableProgressChecked') ? false : true;
		    ref.updateProgress(this_id, new_value);
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
		    ref.updateData(this_id, 'tag_POS', new_value);
		    ref.renderMorphOptions(this_id, target.getParent('tr'), new_value);
		    if (userdata.showInputErrors)
			ref.updateInputError(target.getParent('tr'));
		} else if (parent.hasClass("editTable_Morph")) {
		    ref.updateData(this_id, 'tag_morph', new_value);
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
		    ref.updateData(this_id, 'tag_norm', new_value);
		    ref.updateProgress(this_id, true);
		} else if (parent.hasClass("editTable_Lemma")) {
		    ref.updateData(this_id, 'lemma', new_value);
		    ref.updateProgress(this_id, true);
		} else if (parent.hasClass("editTable_Comment")) {
		    ref.updateData(this_id, 'comment', new_value);
		}
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

	/* render pages panel and set start page */
	start_page = Number.from(start_page);
	if(start_page==null || start_page<1) { start_page = 1; }
	this.renderPagesPanel(start_page);
	this.displayPage(start_page);
	this.activePage = start_page;
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
	var pselect, ptag, mselect, mtag;
	try {
	    pselect = tr.getElement('td.editTable_POS select');
	    ptag = pselect.getSelected()[0].get('value');
	    mselect = tr.getElement('td.editTable_Morph select');
	    mtag = mselect.getSelected()[0].get('value');
	}
	catch(err) {
	    // row doesn't have the select, or the select is empty
	    return;
	}

	if(ptag!="") {
	    if(!fileTagset.pos.contains(ptag)) {
		pselect.addClass(iec);
	    } else {
		pselect.removeClass(iec);
	    }

	    if(mtag!="" && (fileTagset.morph[ptag]==null ||
	       !fileTagset.morph[ptag].contains(mtag))) {
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

    /* Function: renderMorphOptions

       Re-render the morphology tag drop-down box when POS tag
       changes.

       For performance reasons, this function is currently only called
       when the POS tag changes.  The functionality is replicated in
       displayPage() where it is used during page rendering.
    
       Parameters:
        id - line ID
	tr - table row object of that line
	postag - the new POS tag
    */
    renderMorphOptions: function(id, tr, postag) {
	var morphopt, suggestions, line;
	var mselect = tr.getElement('.editTable_Morph select');

	if (postag) {
	    //postag = postag.replace(/\s[\d\.]+/g,"");
	    morphopt = new Element('optgroup', {'label': "Alle Tags für '"+postag+"'",
					       'html': fileTagset.morphHTML[postag]});
	}

	line = this.data[id];
	if (line.suggestions_morph) {
	    suggestions = new Element('optgroup', {'label': 'Vorgeschlagene Tags', 'class': 'lineSuggestedTag'});
	    line.suggestions_morph.each(function(opt){
		suggestions.grab(new Element('option',{
		    html: opt.tag_name+" ("+opt.tag_probability+")",
		    value: opt.tag_name,
		    'class': 'lineSuggestedTag'
		}),'top');
            });
	}

	mselect.empty();
	mselect.grab(new Element('option',{
	    html: line.tag_morph,
	    value: line.tag_morph,
	    selected: 'selected',
	    'class': 'lineSuggestedTag'
	}),'top');
	if (suggestions) {
	    mselect.grab(suggestions);
	}
	if (postag) {
	    mselect.grab(morphopt);
	    // If there's only one legal choice for morph, select it
	    if(morphopt.getChildren().length == 1) {
		morphopt.getChildren()[0].set('selected', 'selected');
	    }
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
		    var ps = $('pageSelector');
		    var new_page = ps.value.toInt() - 1;
		    if (new_page>0) {
			ps.set('value', new_page);
			ref.displayPage(new_page);
		    }
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
		    var ps = $('pageSelector');
		    var new_page = ps.value.toInt() + 1;
		    if (new_page<=max_page) {
			ps.set('value', new_page);
			ref.displayPage(new_page);
		    }
		}
	    },
	}));
	
        pp.adopt(new Element('a',{
	    href: 'last', text: '>>|',
	    events: {
		click: function(e) {
		    e.stop();
		    $('pageSelector').set('value', max_page);
		    ref.displayPage(max_page);
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
		new mBox.Notice({
		    type: 'error', position: {x: 'right'},
		    content: 'Bitte eine Zahl eingeben.'
		});
	    } else if(line_no>ref.lineCount || line_no<1) {
		new mBox.Notice({
		    type: 'error', position: {x: 'right'},
		    content: 'Zeilennummer existiert nicht.'
		});
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

       *Caution!* Code should never expect that the page display is
       complete when this function terminates! The function may
       terminate early when data needs to be loaded from the server; 
       in this case, it returns 'false' to indicate this.

       Parameters:
         page - Number of the page to be displayed

       Returns:
         'true' if the page was displayed successfully.
     */
    displayPage: function(page){
	var cl = userdata.contextLines;
	var pl = userdata.noPageLines;
	var data = this.data;
	var et = this.editTable;
	var ler = this.lastEditedRow;
	var sie = userdata.showInputErrors;
	var iec = this.inputErrorClass;
	var morphhtml = fileTagset.morphHTML;
	var morph = fileTagset.morph;
	var pos = fileTagset.pos;
	var end, start, tr, line, posopt, morphopt, mselect, trs, j;
	var optgroup, elem;
	var dlr, dynstart, dynend;

	/* calculate line numbers to be displayed */
	if (page==0) { page++; }
	end   = page * (pl - cl) + cl;
	start = end - pl;
	if (end>this.lineCount) { 
	    end = this.lineCount;
	    pl  = end - start;
	}

	/* check whether all lines are available in memory; if not,
	   request them from the server first */
	for (var i=start; i<end; i++) {
	    if(data[i] == undefined) {
		if(this.tries++>20) { // prevent endless recursion
		    alert("Ein Fehler ist aufgetreten: Zeilen "+i+" bis "+(end-1)+" können nicht geladen werden.");
		    return;
		}
		new Request.JSON({
		    url: 'request.php',
		    onSuccess: function(lineArray, text) {
			if (Object.getLength(lineArray)==0) {
			    alert("Ein Fehler ist aufgetreten: Server-Anfrage für benötigte Zeilen "+i+" bis "+(end-1)+" lieferte kein Ergebnis zurück.");
			    return;
			}
			Object.each(lineArray, function(ln) {
			    if (this.data[ln.line_id] == undefined) {
				this.data[ln.line_id] = ln;
			    }
			}.bind(this));
			/* lazy implementation: just retry the whole method */
			this.displayPage(page);
		    }.bind(this)
		}).get({'do': 'getLinesById', 'start_id': i, 'end_id': end});
		return false;
	    }
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
	    et.adopt(this.lineTemplate.clone());
	    j++;
	}

	/* build lines */
	trs = et.getElements('tr[id!=line_template]');
	j = 0;
	for (var i=start; i<end; i++) {
	    line = data[i];
	    j++;
	    tr = trs[j];

	    tr.set('id', 'line_'+line.line_id);
	    if (parseInt(line.line_id)<=ler) {
		tr.getElement('div.editTableProgress').addClass('editTableProgressChecked');
	    } else {
		tr.getElement('div.editTableProgress').removeClass('editTableProgressChecked');
	    }
	    if (line.errorChk != null && line.errorChk != 0) {
		tr.getElement('div.editTableError').addClass('editTableErrorChecked');
	    } else {
		tr.getElement('div.editTableError').removeClass('editTableErrorChecked');
	    }
	    tr.getElement('.editTable_token').set('html', line.token);
	    tr.getElement('.editTable_tokenid').set('html', i+1);
	    var norm_tr = tr.getElement('.editTable_Norm input');
	    if(norm_tr != null && norm_tr != undefined) {
		norm_tr.set('value', line.tag_norm);
	    }
	    tr.getElement('.editTable_Lemma input').set('value', line.lemma);
	    tr.getElement('.editTable_Comment input').set('value', line.comment);

            // POS
	    posopt = tr.getElement('.editTable_POS select');
	    posopt.getElements('.lineSuggestedTag').destroy();
	    if(line.suggestions_pos.length>0) {
		optgroup = new Element('optgroup', {'label': 'Vorgeschlagene Tags', 'class': 'lineSuggestedTag'});
		line.suggestions_pos.each(function(opt){
		    optgroup.grab(new Element('option',{
			html: opt.tag_name+" ("+opt.tag_probability+")",
			value: opt.tag_name,
			'class': 'lineSuggestedTag'
		    }),'top');
		});
		posopt.grab(optgroup, 'top');
	    }
	    if(sie){
		if(line.tag_POS!="" && !pos.contains(line.tag_POS)) {
		    posopt.addClass(iec);
		} else {
		    posopt.removeClass(iec);
		}
	    }

	    posopt.grab(new Element('option',{
		html: line.tag_POS,
		value: line.tag_POS,
		selected: 'selected',
		'class': 'lineSuggestedTag'
	    }),'top');
            // Morph
	    mselect = tr.getElement('.editTable_Morph select');
	    mselect.empty();
	    mselect.grab(new Element('option',{html: line.tag_morph,
					       value: line.tag_morph,
					       selected: 'selected',
					       'class': 'lineSuggestedTag'
					      }));
	    if(sie){
		if(morph[line.tag_POS]==null || !morph[line.tag_POS].contains(line.tag_morph)) {
		    mselect.addClass(iec);
		} else {
		    mselect.removeClass(iec);
		}
	    }

	    if (line.suggestions_morph.length>0) {
		optgroup = new Element('optgroup', {'label': 'Vorgeschlagene Tags', 'class': 'lineSuggestedTag'});
		line.suggestions_morph.each(function(opt){
		    optgroup.grab(new Element('option',{
			html: opt.tag_name+" ("+opt.tag_probability+")",
			value: opt.tag_name,
			'class': 'lineSuggestedTag'
		    }),'top');
		});
		mselect.grab(optgroup);
	    }

	    if (line.tag_POS!=null) {
		var postag = line.tag_POS; //.replace(/\s[\d\.]+/g,"");
		mselect.grab(new Element('optgroup', {'label': "Alle Tags für '"+postag+"'",
						      html: morphhtml[postag]}));
	    }
	}

	/* unhide the table */
	et.show();

	/* dynamically load context lines */
	dlr  = this.dynamicLoadRange * (userdata.noPageLines - cl);
	dynstart = start - dlr;
	dynend   = end   + dlr;
	this.dynamicLoadLines(dynstart, dynend);

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

    /* Function: dynamicLoadLines

       Load lines dynamically in the background.

       First searches lines already in memory to ensure that
       an optimal request is sent, i.e. only a single request
       which needs the least amount of data in order to ensure
       that all lines in the given range are loaded.

       Parameters:
         start - Number of the first line to load
	 end - Number of the line after the last line to load
    */
    dynamicLoadLines: function(start,end) {
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
	if (!brk) { return; } // all the lines are already there

	for (var j=end-1; j>=start; j--) {
	    if (!keys.contains(String.from(j))) {
		end = j+1;
		break;
	    }
	}

	new Request.JSON({
	    url: 'request.php',
	    async: true,
	    onSuccess: function(lineArray, text) {
		Object.each(lineArray, function(ln) {
		    if (this.data[ln.line_id] == undefined) {
			this.data[ln.line_id] = ln;
		    }
		}.bind(this));
	    }.bind(this)
	}).get({'do': 'getLinesById', 'start_id': start, 'end_id': end});
    },

    /* Function: saveData

       Send a server request to save the modified lines
       to the database.
     */
    saveData: function() {
	var req, cl, data, save, line, tp, tm;
	var ref = this;
	var spin = this.spinner;

	cl = this.changedLines;
	if (cl==null) { return true; }
	data = this.data;
	save = new Array();

	for (var i=0, len=cl.length; i<len; i++) {
	    line=data[cl[i]];
	    tp = line.tag_POS==null ? "" : line.tag_POS;
	    tm = line.tag_morph==null ? "" : line.tag_morph;
	    save.push({
		line_id: line.line_id,
		errorChk: line.errorChk,
		lemma: line.lemma,
		tag_POS: tp, //.replace(/\s[\d\.]+/g,""),
		tag_morph: tm, //.replace(/\s[\d\.]+/g,""),
		tag_norm: line.tag_norm,
		comment: line.comment
	    });
	}

	req = new Request.JSON({
	    url: 'request.php?do=saveData&lastEditedRow='+this.lastEditedRow,
	    onSuccess: function(status,xml) {
		var title="", message="", textarea="";

		if (status!=null && status.success) {
		    ref.changedLines = new Array(); // reset "changed lines" array
		    new mBox.Notice({
			type: 'ok',
			content: 'Speichern erfolgreich.',
			position: { x: 'right' }
		    });
		}
		else {
		    if (status==null) {
			message = 'Beim Speichern der Datei ist leider ein unbekannter Fehler aufgetreten.';
		    }
		    else {
			message = 'Beim Speichern der Datei ist leider ein Fehler aufgetreten.  Bitte melden Sie die folgende Fehlermeldung ggf. einem Administrator.';
			for(var i=0;i<status.errors.length;i++){
			    textarea += status.errors[i] + "\n";
			}
		    }
		    
		    if(textarea!='') {
			$('saveErrorPopup').getElement('p').set('html', message);
			$('saveErrorPopup').getElement('textarea').set('html', textarea);
			message = 'saveErrorPopup';
		    }
		    new mBox.Modal({
			title: 'Speichern fehlgeschlagen',
			content: message,
			buttons: [ {title: "OK"} ]
		    }).open();
		}
		//spin.hide();
		$('overlay').hide();
	    },
	    onFailure: function(xhr) {
		new mBox.Modal({
		    title: 'Speichern fehlgeschlagen',
		    content: 'Das Speichern der Datei war nicht erfolgreich! Server lieferte folgende Antwort: "'+xhr+'".'
		}).open();
		//spin.hide();
		$('overlay').hide();
	    }
	});
	$('overlay').show();
	//spin.show();
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
	    return confirm("Warnung: Sie sind im Begriff, diese Seite zu verlassen. Im geöffneten Dokument gibt es noch ungespeicherte Änderungen in "+chl+" "+zeile+", die verloren gehen, wenn Sie fortfahren.");
	} else {
	    return true;
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
