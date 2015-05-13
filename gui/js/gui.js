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
    currentLocale: null,
    availableLocales: {},

    initialize: function() {
	this._addKeyboardShortcuts();
	this.addToggleEvents($$('.clappable'));
	this._activateKeepalive();
        this._defineDateParsers();

        var btn = $('logoutButton');
        if (btn) {
            btn.addEvent('click', this.logout.bind(this));
        }
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
		    if(cora.editor!==null && cora.editor.hasUnsavedChanges()) {
			cora.editor.save();
		    }
		},
		'ctrl+z': function(e) {
		    if(cora.editor!==null) {
		        e.stop();
			cora.editor.performUndo();
		    }
		},
		'ctrl+y': function(e) {
		    if(cora.editor!==null) {
		        e.stop();
			cora.editor.performRedo();
		    }
		},
		'ctrl+f': function(e) {
		    if(cora.editor != null
                       && cora.editor.tokenSearcher != null) {
		        e.stop();
			cora.editor.tokenSearcher.open();
		    }
		}
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
	if(cora.settings != undefined && cora.settings.get('name') != undefined) {
	    this.keepaliveRequest = new Request.JSON({
		url: 'request.php?do=keepalive',
		method: 'get',
		initialDelay: 1000,
		delay: 60000,
		limit: 60000,
                onSuccess: function(status, text) {
                    if (status) {
                        if (status.errcode == -1) {
                            this.keepaliveRequest.stopTimer();
                            this.login(function() {
                                this.keepaliveRequest.startTimer();
                            }.bind(this));
                        }
                        if (status.notices) {
                            Array.prototype.push.apply(this.serverNoticeQueue,
                                                       status.notices);
                            this.processServerNotices();
                        }
                    }
                }.bind(this)
	    });
	    this.keepaliveRequest.startTimer();
	}
    },

    /* Function: changeLocale

       Retrieve the locale file (if necessary) and update all GUI text.
     */
    changeLocale: function(locale) {
        if (locale === this.currentLocale)
            return;
        if (!this.availableLocales[locale]) {
            this.requestLocale(locale, function() {
                this.changeLocale(locale);
            }.bind(this));
            return;
        }
        Locale.use(locale);
        this.currentLocale = locale;
        this.updateAllLocaleText();
    },

    /* Function: requestLocale

       Requests the locale file from the server.
     */
    requestLocale: function(locale, callback) {
        new Request.JSON({
            url: 'locale/Locale.'+locale+'.json',
            async: true,
            onSuccess: function(json, text) {
                Object.each(json.sets, function(data, set) {
                    Locale.define(locale, set, data);
                });
                this.availableLocales[locale] = true;
                if (callback && typeof callback === "function")
                    callback();
            }.bind(this)
        }).get();
    },

    /* Function: updateAllLocaleText

       Updates all HTML elements with new translation strings.
     */
    updateAllLocaleText: function() {
        $$("[data-trans-id]").each(function(elem) {
            elem.set("text", Locale.get(elem.get("data-trans-id")));
        });
        $$("[data-trans-title-id]").each(function(elem) {
            elem.set("title", Locale.get(elem.get("data-trans-title-id")));
        });
    },

    /* Function: setHeader

       Sets the header text, typically reserved for the name of the
       currently opened file.
     */
    setHeader: function(text) {
	$('currentfile').set('text', text);
        return this;
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

    /* Function: showTab

       Makes a tab visible in the menu.

       Parameters:
        tabName - Internal name of the tab to be shown
     */
    showTabButton: function(tabName) {
        var tabButton = $(tabName+"TabButton");
        if (tabButton) {
            tabButton.show();
        }
        return this;
    },

    /* Function: hideTab

       Hides a tab in the menu.

       Parameters:
        tabName - Internal name of the tab to be hidden
     */
    hideTabButton: function(tabName) {
        var tabButton = $(tabName+"TabButton");
        if (tabButton) {
            tabButton.hide();
        }
        return this;
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
	contentBox = $$(".content").setStyle("display", "none");

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

        return this;
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
	return new mBox.Notice({
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
        return this;
    },

    /* Function: enableScrolling

       Enable scrolling for the page.
    */
    enableScrolling: function() {
        document.getElement('body').setStyle('overflow', 'auto');
        return this;
    },

    /* Function: lock

       Locks the screen by showing a transparent overlay.
     */
    lock: function() {
        $('overlay').show(); return this;
    },

    /* Function: unlock

       Unlocks a previously locked screen.
     */
    unlock: function() {
        $('overlay').hide(); return this;
    },

    /* Function: showSpinner

       Displays a "loading" spinner.

       Parameters:
        options - An object which may contain the following options:
	           * message - Message to display (default: none)
                   * noFx    - Display without fade-in (default: false)
    */
    showSpinner: function(myoptions) {
	var options = myoptions || {};
	var spinmsg = options.message || null;
	var nofx    = options.noFx || false;

        if(this.activeSpinner !== null) {
            if(!this.activeSpinner.hidden)  // don't replace an active spinner
                return this;
            this.activeSpinner.destroy();
        }
        this.disableScrolling();
        this.lock();
	$('spin-overlay').show();
	this.activeSpinner = new Spinner($('spin-overlay'),
					 {message: spinmsg,
                                          fxOptions:
                                          {duration: this.activeSpinnerFadeDuration}});
	this.activeSpinner.show(nofx);
        return this;
    },

    /* Function: hideSpinner

       Hides the currently displayed spinner.
    */
    hideSpinner: function() {
	if(this.activeSpinner !== undefined && this.activeSpinner !== null) {
	    this.activeSpinner.hide();
	    this.unlock();
	    $('spin-overlay').hide();
	}
        this.enableScrolling();
        return this;
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
    },

    /* Function: getPulse

       Gets the pulse element that indicates connectivity status.
     */
    getPulse: function() {
        return $('connectionInfo').getElement('.oi');
    },

    /* Function: onNotLoggedIn

       Global callback used by CoraRequest when user is no longer logged in.
     */
    onNotLoggedIn: function() {
        this.getPulse().removeClass("connected");
        this.keepaliveRequest.stopTimer();
        this.login(this.keepaliveRequest.startTimer.bind(this.keepaliveRequest));
    },

    /* Function: login

       Asks the user to re-enter his login information, e.g., when the client
       has been disconnected from the server for too long.

       Parameters:
         fn - Callback on successful login
     */
    login: function(fn) {
        this.showSpinner({message: 'Warte auf Authorisierung...'});
        var onSuccessfulRestore = function() {
            if(typeof(fn) === "function")
                fn();
        }.bind(this);
        var loginRequest = function(user, pw) {
            new CoraRequest({
                name: "login",
                onSuccess: function(status) {
                    if (cora.editor !== null)
                        cora.files.lock(
                            cora.current().id,
                            {onSuccess: onSuccessfulRestore,
                             onError: function(error) {
                                 this.showMsgDialog('error',
                                        "Anmeldung war erfolgreich, aber Zugriff "
                                        + "auf die aktuell geöffnete Datei ist "
                                        + "nicht möglich.  Eventuell wird "
                                        + "diese Datei bereits von einem anderen "
                                        + "Nutzer bearbeitet.");
                                 mbox.open();
                             }.bind(this),
                            onComplete: function() { this.hideSpinner(); }.bind(this)
                            }
                        );
                    else {
                        this.hideSpinner();
                        onSuccessfulRestore();
                    }
                }.bind(this),
                onError: function(error) {
                    this.showNotice('error', 'Anmeldung fehlgeschlagen.');
                    mbox.open();
                }.bind(this)
            }).get({'user': user, 'pw': pw});
        }.bind(this);
        var mbox = new mBox.Modal({
	    title: "Erneut anmelden",
	    content: 'confirmLoginPopup',
	    closeOnBodyClick: false,
	    closeOnEsc: false,
	    buttons: [ {title: "Anmelden",
                        addClass: "mform",
                        event: function() {
                            var user = mbox.content.getElement('#lipu_un').get('value'),
                                pw = mbox.content.getElement('#lipu_pw').get('value');
                            mbox.close();
                            loginRequest(user, pw);
                        }.bind(this)
                       }]
        });
        mbox.open();
    },

    /* Function: logout

       Performs a logout for the current user.
     */
    logout: function() {
        if (cora.editor !== null && cora.editor.hasUnsavedChanges()) {
            cora.editor.confirmClose(this.logout.bind(this));
        } else {
            window.location.href = './index.php?do=logout';
        }
    },

    /* Function: download

       Initiates a download from a given URL

       Parameters:
         url - URL to download from
         query - (optional) Object to use as query string
     */
    download: function(url, query) {
        var src = url;
        if(typeof(query) === "object")
            src += "?" + Object.toQueryString(query);
        window.location = src;
    },

    /* Function: showNews
     */
    showNews: function(force) {
        var div = $('whatsNew'),
            cookie = Cookie.read('whatsNew');
        if (div == null)
            return;
        if (cookie == div.get('class') && !force)
            return;
        if (this.newsDialog == null) {
	    this.newsDialog = new mBox.Modal({
	        title: div.getElement('.whats-new-title'),
	        content: div.getElement('.whats-new-content'),
                addClass: {wrapper: 'WhatsNewDialog'},
	        closeOnBodyClick: false,
	        closeOnEsc: true,
	        buttons: [
                    {title: "Schließen und nicht wieder anzeigen",
                     addClass: "mform button_left",
                     event: function() {
                         Cookie.write('whatsNew', div.get('class'), {duration: 365});
                         this.close();
                         gui.showNotice('info', "Sie können sich die Neuigkeiten "
                                        + "jederzeit über den Tab 'Hilfe' erneut "
                                        + "anzeigen lassen.");
                     }},
                    {title: "Schließen", addClass: "mform"}
                ]
	    });
        }
        this.newsDialog.open();
    }
};

/** Perform initialization. Adds JavaScript events to interactive
 * navigation elements, e.g.\ clappable div containers, and selects
 * the default tab.
 */
window.addEvent('domready', function() {
    gui.initialize();
    gui.changeLocale(cora.settings.get("locale"));

    // default item defined in content.php, variable set in gui.php
    gui.changeTab(default_tab);
    gui.showNews();
});

window.onbeforeunload = function() {
    if (cora.editor !== null && cora.editor.hasUnsavedChanges()) {
        cora.editor.save();
	return ("Es gibt noch ungespeicherte Änderungen, die verloren gehen könnten, wenn Sie fortfahren!");
    }
}
