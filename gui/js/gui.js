
/** @file
 * GUI-related functions
 *
 * @author Marcel Bollmann
 * @date January 2012
 */

var gui = {
    activeSpinner: null,
    activeSpinnerFadeDuration: 250,
    keepaliveRequest: null,
    editKeyboard: null,
    serverNoticeQueue: [],
    serverNoticeShowing: false,

    initialize: function() {
	this._addKeyboardShortcuts();
	this.addToggleEvents($$('.clappable'));
	this._activateKeepalive();
        this._defineDateParsers();
    },

    /* Function: addToggleEvents

       Enable clappable div containers.

       Adds onClick events to the .clapp element in each .clappable
       container that toggle the visibility of its contents.  Also,
       automatically hides all contents of .starthidden containers.
    */
    addToggleEvents: function(objects) {
        if(typeof(objects.each) != "function")
            objects = new Array(objects);

	objects.each(function (clappable) {
            var clapper, content;
	    
            // add toggle event
            clapper = clappable.getElement('.clapp');
            content = clappable.getElement('div');
            if (clapper !== null) {
                clapper.removeEvents();
		clapper.addEvent('click', function () {
                    clappable.toggleClass('clapp-hidden');
                    content.toggle();
		});
                // inject clapp-status icons
                if (clappable.hasClass('clapp-modern')
                    && !clapper.hasClass('has-clapp-icons')) {
                    clapper.grab(new Element('span',
                                             {'class': 'oi clapp-status-hidden',
                                              'data-glyph': 'caret-right',
                                              'title': 'Aufklappen',
                                              'aria-hidden': 'true'}),
                                 'top');
                    clapper.grab(new Element('span',
                                             {'class': 'oi clapp-status-open',
                                              'data-glyph': 'caret-bottom',
                                              'title': 'Zuklappen',
                                              'aria-hidden': 'true'}),
                                 'top');
                    clapper.addClass('has-clapp-icons');
                }
            }
            // hide content by default, if necessary
            if (clappable.hasClass('starthidden')) {
                clappable.addClass('clapp-hidden');
		content.hide();
            }
	});
    },
    /* Function: _addKeyboardShortcuts

       Sets up keyboard shortcuts used within the application.
     */
    _addKeyboardShortcuts: function() {
	this.editKeyboard = new Keyboard({
	    active: true,
	    events: {
		'ctrl+s': function(e) {
		    e.stop(); 
		    if(edit!==null && edit.editorModel!==null) {
			edit.editorModel.saveData();
		    }
		}
		/* ,
		'alt+left': function(e) {
		    e.stop(); 
		    if(edit!==null && edit.editorModel!==null) {
			edit.editorModel.pages.decrement();
			edit.editorModel.pages.render();
		    }
		},
		'alt+right': function(e) {
		    e.stop(); 
		    if(edit!==null && edit.editorModel!==null) {
			edit.editorModel.pages.increment();
			edit.editorModel.pages.render();
			edit.editorModel.focusFirstElement();
		    }
		} */
	    }
	});
    },

    /* Function: _activateKeepalive

       Sets up a periodical server request with the sole purpose of
       keeping the connection alive, i.e., telling the server that the
       user is still active (and has not, e.g., closed the browser
       window without logging out).
    */
    _activateKeepalive: function() {
	if(userdata.name != undefined) {
	    this.keepaliveRequest = new Request.JSON({
		url: 'request.php?do=keepalive',
		method: 'get',
		initialDelay: 1000,
		delay: 60000,
		limit: 60000,
                onSuccess: function(status, text) {
                    if (status && status.notices) {
                        Array.prototype.push.apply(this.serverNoticeQueue,
                                                   status.notices);
                        this.processServerNotices();
                    }
                }.bind(this)
	    });
	    this.keepaliveRequest.startTimer();
	}
    },

    /* Function: processServerNotices

       Shows the next server notice in the queue, and only shows
       another notice if the previous one has been closed.  Notices
       shown this way don't close automatically.
     */
    processServerNotices: function() {
        if (this.serverNoticeShowing || this.serverNoticeQueue.length < 1)
            return;
        var notice = this.serverNoticeQueue.shift();
        var n_type = (notice.type == 'alert') ? 'notice' : 'info';
        this.serverNoticeShowing = true;
        this.showNotice(n_type, notice.text, true,
                        function() {
                            this.serverNoticeShowing = false;
                            this.processServerNotices();
                        }.bind(this));
    },

    /* Function: changeTab

       Selects a new tab.

       Shows the content div corresponding to the selected menu item,
       while hiding all others and highlighting the correct menu
       button.

       Parameters:
        tabName - Internal name of the tab to be selected
    */
    changeTab: function(tabName) {
	var contentBox, tabButton, activeTab, i;

	// hide all tabs
	contentBox = $$(".content");
	for (i = 0; i < contentBox.length; i++) {
            contentBox[i].setStyle("display", "none");
	}
	
	// select correct tab button
	tabButton = $$(".tabButton");
	for (i = 0; i < tabButton.length; i++) {
            if (tabButton[i].id === tabName + "TabButton") {
		tabButton[i].set("active", "true");
            } else {
		tabButton[i].set("active", "false");
            }
	}

	// show active tab
	activeTab = $(tabName + "Div");
	if (activeTab === null) {
            alert(tabName + " tab not implemented!");
	}
	activeTab.setStyle("display", "block");
    },

    /* Function: showNotice

       Displays a floating notice, e.g., to indicate success.

       Parameters:
         ntype - Type of the notice ('ok', 'error')
         message - String to appear in the notice
         keepopen - If true, notice stays open (defaults to false)
         onclose - Callback function to invoke when notice is closed
    */
    showNotice: function(ntype, message, keepopen, onclose) {
	new mBox.Notice({
	    type: ntype,
	    position: {x: 'right'},
	    content: new Element('span', {text: message}),
            neverClose: (keepopen || false),
            onClose: onclose
	});
    },

    /* Function: confirm

       Presents a confirmation dialog with yes/no buttons to the user.

       Parameters:
         message - String to appear in the dialog
         action - Callback function on confirmation
         danger - If true, 'yes' button is displayed in red instead of green
     */
    confirm: function(message, action, danger) {
        new mBox.Modal.Confirm({
            content: message,
            confirmAction: action,
            onOpen: function() {
                this.footerContainer.getElement('.mBoxConfirmButtonSubmit')
                    .addClass('mform')
                    .removeClass('button_green')
                    .addClass((danger ? 'button_red' : 'button_green'))
                    .set('html', '<label>Ja, bestätigen</label>');
                this.footerContainer.getElement('.mBoxConfirmButtonCancel')
                    .addClass('mform')
                    .set('html', '<label>Nein, abbrechen</label>');
            }
        }).open();
    },

    /* Function: disableScrolling

       Disable scrolling for the page.
    */
    disableScrolling: function() {
        document.getElement('body').setStyle('overflow', 'hidden');
    },

    /* Function: enableScrolling

       Enable scrolling for the page.
    */
    enableScrolling: function() {
        document.getElement('body').setStyle('overflow', 'visible');
    },

    /* Function: showSpinner

       Displays a "loading" spinner.

       Parameters: 
        options - An object which may contain the following options:
	           * message - Message to display (default: none)
                   * noFx    - Display without fade-in (default: false)
    */
    showSpinner: function(options) {
	var options = options || {};
	var spinmsg = options.message || null;
	var nofx    = options.noFx || false;

        this.disableScrolling();
	$('overlay').show();
	$('spin-overlay').show();
	this.activeSpinner = new Spinner($('spin-overlay'),
					 {message: spinmsg,
                                          fxOptions:
                                          {duration: this.activeSpinnerFadeDuration}});
	this.activeSpinner.show(nofx);
    },

    /* Function: hideSpinner

       Hides the currently displayed spinner.
    */
    hideSpinner: function() {
	if(this.activeSpinner !== undefined && this.activeSpinner !== null) {
	    this.activeSpinner.hide();
	    $('overlay').hide();
	    $('spin-overlay').hide();
	}
        this.enableScrolling();
    },

    /* Function: showMsgDialog

       Displays a title-less modal dialog containing an icon and an
       informational message.

       Parameters:
         ntype - Type of the message ('ok', 'error')
         message - Message to display
     */
    showMsgDialog: function(ntype, message) {
        var cls = "mBoxModalInfo";
        if(ntype == "error")
            cls = "mBoxModalError";
        else if(ntype == "ok" || ntype == "success")
            cls = "mBoxModalSuccess";
        else if(ntype == "notice" || ntype == "attention")
            cls = "mBoxModalNotice";
	new mBox.Modal({
	    content: message,
            addClass: {wrapper: 'MessageDialog '+cls},
	    buttons: [ {title: "Schließen", addClass: "mform"} ]
	}).open();
    },

    /* Function: showTextDialog

       Displays a modal dialog that optionally includes a textarea for
       longer warnings, errors, etc.

       Parameters:
        title - Title of the dialog window
        message - First line of text in the dialog
	textarea - Content of the textarea; if empty, the textarea will
	           not be displayed, and the dialog will consist of the
		   message only; if this is an array, its elements will
		   be appended to the textarea separated by line breaks
    */
    showTextDialog: function(title, message, textarea) {
	var content;
	if(textarea===undefined || textarea=='') {
	    content = message;
	}
	else {
	    content = $('genericTextMsgPopup');
	    content.getElement('p').empty().appendText(message);
	    content.getElement('textarea').empty();
	    if(typeOf(textarea) == 'array') {
		Array.each(textarea, function(item, idx) {
		    content.getElement('textarea').appendText(item + '\n');
		});
	    }
	    else {
		content.getElement('textarea').appendText(textarea);
	    }
	}

	new mBox.Modal({
	    title: title,
	    content: content,
            addClass: {wrapper: 'TextDialog'},
	    closeOnBodyClick: false,
	    closeOnEsc: false,
	    buttons: [ {title: "Schließen", addClass: "mform"} ]
	}).open();
    },

    /* Function: _defineDateParsers

       Registers date parsers for our custom date formats.
     */
    _defineDateParsers: function() {
        Date.defineParser("%Y-%m-%d %H:%M:%S");
        Date.defineParser("%d.%m.%Y, %H:%M");
        Date.defineParser({
            re: /([hH]eute|[gG]estern|[vV]orgestern), (\d\d):(\d\d)/,
            handler: function (bits) {
                var date = new Date();
                if(bits[1].toUpperCase() === "GESTERN")
                    date.decrement('day', 1);
                else if(bits[1].toUpperCase() === "VORGESTERN")
                    date.decrement('day', 2);
                date.set('hours', bits[2]);
                date.set('minutes', bits[3]);
                return date;
            }
        });
    },

    /* Function: parseSQLDate

       Parses a date string in SQL format and returns a Date object.
     */
    parseSQLDate: function(datestring) {
        var d = new Date();
        var match = datestring.match(/(\d\d\d\d)-(\d\d)-(\d\d) (\d\d):(\d\d):(\d\d)/);
        if(match) {
            d.set({
                'year': match[1],
                'mo':   match[2]-1,
                'date': match[3],
                'hr':   match[4],
                'min':  match[5],
                'sec':  match[6],
                'ms':   0
            });
            return d;
        }
        return datestring;
    },

    /* Function: formatDateString

       Takes a date string or Date object and re-formats it for
       display within the GUI.

       Parameters:
         date - A date in string format, or as a Date object
     */
    formatDateString: function(date) {
        var format_string = '';
        var date_strings = ['Heute', 'Gestern', 'Vorgestern'];
        var now = Date.now();
        if(!(date instanceof Date)) {
            date = this.parseSQLDate(date);
            if(!(date instanceof Date))
                date = Date.parse(date);
        }
        if(!date.isValid() || date.get('year') < 1980)
            return "";
        if(date.diff(now) > 2 || date.diff(now) < 0)
            format_string += "%d.%m.%Y";
        else
            format_string += date_strings[date.diff(now)];
        format_string += ", %H:%M";
        return date.format(format_string);
    }
}

/** Perform initialization. Adds JavaScript events to interactive
 * navigation elements, e.g.\ clappable div containers, and selects
 * the default tab.
 */
function onLoad() {
    Locale.use("de-DE");
    gui.initialize();

    // default item defined in content.php, variable set in gui.php
    gui.changeTab(default_tab);
}

function onBeforeUnload() {
    if (typeof edit!="undefined" && edit.editorModel!==null) {
	var chl = edit.editorModel.changedLines.length;
	if (chl>0) {
	    var zeile = (chl>1) ? "Zeilen" : "Zeile";
	    return ("Im geöffneten Dokument gibt es noch ungespeicherte Änderungen in "+chl+" "+zeile+", die verloren gehen, wenn Sie fortfahren.");
	}
    }
}
