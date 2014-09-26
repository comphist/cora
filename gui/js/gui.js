/** @file
 * GUI-related functions
 *
 * @author Marcel Bollmann
 * @date January 2012
 */

var gui = {
    activeSpinner: null,
    keepaliveRequest: null,
    editKeyboard: null,

    initialize: function() {
	this.addKeyboardShortcuts();
	this.addToggleEvents($$('.clappable'));
	this.activateKeepalive();
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
                    content.toggle();
		});
            }
            // hide content by default, if necessary
            if (clappable.hasClass('starthidden')) {
		content.hide();
            }
	});
    },

    /* Function: addKeyboardShortcuts

       Sets up keyboard shortcuts used within the application.
     */
    addKeyboardShortcuts: function() {
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
			edit.editorModel.displayPreviousPage();
		    }
		},
		'alt+right': function(e) {
		    e.stop(); 
		    if(edit!==null && edit.editorModel!==null) {
			edit.editorModel.displayNextPage();
			edit.editorModel.focusFirstElement();
		    }
		} */
	    }
	});
	
    },

    /* Function: activateKeepalive

       Sets up a periodical server request with the sole purpose of
       keeping the connection alive, i.e., telling the server that the
       user is still active (and has not, e.g., closed the browser
       window without logging out).
    */
    activateKeepalive: function() {
	if(userdata.name != undefined) {
	    this.keepaliveRequest = new Request({
		url: 'request.php?do=keepalive',
		method: 'get',
		initialDelay: 60000,
		delay: 300000,
		limit: 300000
	    });
	    this.keepaliveRequest.startTimer();
	}
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
         ntype - Type of the notice ('ok' or 'error')
         message - String to appear in the notice
    */
    showNotice: function(ntype, message) {
	new mBox.Notice({
	    type: ntype,
	    position: {x: 'right'},
	    content: message
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
    */
    showSpinner: function(options) {
	var options = options || {};
	var spinmsg = options.message || null;

        this.disableScrolling();
	$('overlay').show();
	$('spin-overlay').show();
	this.activeSpinner = new Spinner($('spin-overlay'),
					 {message: spinmsg});
	this.activeSpinner.show();
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

    /* Function: showInfoDialog

       Displays a modal dialog containing an informational message.

       Parameters:
         message - Message to display
         title - (optional) Title of the dialog window
     */
    showInfoDialog: function(message, title) {
	new mBox.Modal({
	    title: title,
	    content: message,
            addClass: {wrapper: 'InfoDialog'},
	    buttons: [ {title: "Schließen", addClass: "mform button_green"} ]
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
	    buttons: [ {title: "Schließen", addClass: "mform button_green"} ]
	}).open();
    }
}

/** Perform initialization. Adds JavaScript events to interactive
 * navigation elements, e.g.\ clappable div containers, and selects
 * the default tab.
 */
function onLoad() {
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
