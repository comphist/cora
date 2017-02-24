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
    onLocaleChangeHandlers: [],

    initialize: function(options) {
        if (options.locale)
            this.useLocale(options.locale);

	this._addKeyboardShortcuts();
	this.addToggleEvents($$('.clappable'));
	this._activateKeepalive();

        var btn = $('logoutButton');
        if (btn) {
            btn.addEvent('click', this.logout.bind(this));
        }

        if (options.startHidden) {
            options.startHidden.each(function(tab) {
                this.hideTabButton(tab);
            }.bind(this));
        }
        if (options.startTab)
            this.changeTab(options.startTab);
        if (options.showNews)
            this.showNews();
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
                                              'title': _("Gui.expand"),
                                              'aria-hidden': 'true'}),
                                 'top');
                    clapper.grab(new Element('span',
                                             {'class': 'oi clapp-status-open',
                                              'data-glyph': 'caret-bottom',
                                              'title': _("Gui.collapse"),
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

    /* Function: addToggleEventCollapseAll

       Enable an element to collapse all clappables when clicked.
     */
    addToggleEventCollapseAll: function(activator, region) {
        $(activator).addEvent(
            'click',
            function(e) {
                e.stop();
                $$(region + ' .clappable').each(function (clappable) {
                    clappable.addClass('clapp-hidden');
                    clappable.getElement('div').hide();
                });
            }
        );
    },

    /* Function: addToggleEventExpandAll

       Enable an element to expand all clappables when clicked.
     */
    addToggleEventExpandAll: function(activator, region) {
        $(activator).addEvent(
            'click',
            function(e) {
                e.stop();
                $$(region + ' .clappable').each(function (clappable) {
                    clappable.removeClass('clapp-hidden');
                    clappable.getElement('div').show();
                });
            }
        );
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

    /* Function: onEditorLocaleChange

       Add a callback function to be called whenever the current locale setting
       changes.  These callbacks are tied to the editor and can be reset when
       the editor destructs.

       Parameters:
        fn - function to be called
     */
    onEditorLocaleChange: function(fn) {
        if(typeof(fn) == "function")
            this.onLocaleChangeHandlers.push(fn);
        return this;
    },

    /* Function: resetOnEditorLocaleChange

       Remove all registered locale-change callbacks.
     */
    resetOnEditorLocaleChange: function() {
        this.onLocaleChangeHandlers = [];
    },

    /* Function: useLocale

       Retrieve the locale file (if necessary) and update all GUI text.
     */
    useLocale: function(locale) {
        if (locale === this.currentLocale)
            return;
        var setLocale = function() {
            var old_locale = this.currentLocale;
            Locale.use(locale);
            this.currentLocale = locale;
            this._defineDateParsers();
            if (old_locale !== null) {
                this.updateAllLocaleText();
                cora.projects.performUpdate();
                Array.each(this.onLocaleChangeHandlers, function(handler) {
                    handler();
                });
            }
        }.bind(this);
        if (!this.availableLocales[locale]) {
            this.requestLocale(locale, setLocale);
        } else {
            setLocale();
        }
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
            elem.set("html", this.localizeText(elem.get("data-trans-id")));
        }.bind(this));
        $$("[data-trans-title-id]").each(function(elem) {
            elem.set("title", this.localizeText(elem.get("data-trans-title-id")));
        }.bind(this));
        $$("[data-trans-placeholder-id]").each(function(elem) {
            elem.set("placeholder", this.localizeText(elem.get("data-trans-placeholder-id")));
        }.bind(this));
        $$("[data-trans-value-id]").each(function(elem) {
            elem.set("value", this.localizeText(elem.get("data-trans-value-id")));
        }.bind(this));
    },

    /* Function: localizeText

       Localizes a text string with optional arguments, and interprets **bold**
       and _italic_ markup syntax.
     */
    localizeText: function(cat, args) {
        var str = Locale.get(cat).substitute(args);
        // HTML-escaping
        str = new Element("textarea", {text: str}).get('html');
        // Interpreting markups
        str = str.replace(/\*\*([^*]+)\*\*/g, "<strong>$1</strong>")
                 .replace(/_([^_]+)_/g, "<em>$1</em>");
        return str;
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
        var n_type = this.mapServerNoticeType(notice.type);
        this.serverNoticeShowing = true;
        this.showNotice(n_type, notice.text, true,
                        function() {
                            this.serverNoticeShowing = false;
                            this.processServerNotices();
                        }.bind(this));
    },

    /* Function: mapServerNoticeType

       Maps a server notice type to mBox dialog type.
     */
    mapServerNoticeType: function(stype) {
        return (stype == 'alert') ? 'notice' : 'info';
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

    /* Function: hideTabButton

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
                    .set('html', '<label>'+_("Action.yesConfirm")+'</label>');
                this.footerContainer.getElement('.mBoxConfirmButtonCancel')
                    .addClass('mform')
                    .set('html', '<label>'+_("Action.noCancel")+'</label>');
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
	    buttons: [ {title: _("Action.close"), addClass: "mform"} ]
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
	    buttons: [ {title: _("Action.close"), addClass: "mform"} ]
	}).open();
    },

    /* Function: _defineDateParsers

       Registers date parsers for our custom date formats.
     */
    _defineDateParsers: function() {
        var re, daysRelative = Locale.get("Date.daysRelative"),
            equalIgnoringCase = function(a, b) {
                if (String.prototype.toLocaleUpperCase === undefined)
                    return (a.toUpperCase() === b.toUpperCase());
                return (a.toLocaleUpperCase() === b.toLocaleUpperCase());
            };
        re = "(" + daysRelative.join("|") + "), (\\d\\d):(\\d\\d)";
        // NOTE --
        // Accessing Date.parsePatterns directly is deprecated according to
        // MooTools 1.5.1 docs, but currently the only way to make sure this
        // pattern has precedence over system-defined ones, particularly
        // the stupid /^(?:tod|tom|yes)/i pattern which can easily interfere
        // with localized patterns.
        Date.parsePatterns.unshift({
            re: new RegExp(re, "i"),
            handler: function (bits) {
                var date = new Date();
                for (var i=0; i<daysRelative.length; ++i) {
                    if (equalIgnoringCase(bits[1], daysRelative[i])) {
                        date.decrement('day', i);
                        break;
                    }
                }
                date.set('hours', bits[2]);
                date.set('minutes', bits[3]);
                return date;
            }
        });
        Date.defineParser(Locale.get("Date.shortDate") + ", "
                          + Locale.get("Date.shortTime"));
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
        var daysRelative = Locale.get("Date.daysRelative");
        var datediff;
        if(!(date instanceof Date)) {
            date = this.parseSQLDate(date);
            if(!(date instanceof Date))
                date = Date.parse(date);
        }
        if(!date.isValid() || date.get('year') < 1980)
            return "";
        datediff = date.diff(Date.now());
        if(datediff >= daysRelative.length || datediff < 0)
            format_string += Locale.get("Date.shortDate");
        else
            format_string += daysRelative[datediff];
        format_string += ", " + Locale.get("Date.shortTime");
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
        this.showSpinner({message: _("Gui.waitForAuthorization")});
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
                                        _("Gui.loginAccessError"));
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
                    this.showNotice('error', _("Forms.loginFailed"));
                    mbox.open();
                }.bind(this)
            }).get({'user': user, 'pw': pw});
        }.bind(this);
        var mbox = new mBox.Modal({
	    title: _("Gui.confirmLogin.title"),
	    content: 'confirmLoginPopup',
	    closeOnBodyClick: false,
	    closeOnEsc: false,
	    buttons: [ {title: _("Forms.buttonLogin"),
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
                    {title: _("Help.dontShowAgain"),
                     addClass: "mform button_left",
                     event: function() {
                         Cookie.write('whatsNew', div.get('class'), {duration: 365});
                         this.close();
                         gui.showNotice('info', _("Help.dontShowAgainInfo"));
                     }},
                    {title: _("Action.close"), addClass: "mform"}
                ]
	    });
        }
        this.newsDialog.open();
    }
};

/** Alias for localization function. */
var _ = gui.localizeText;
