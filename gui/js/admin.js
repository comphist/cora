/** @file
 * Functions related to the administrator tab.
 *
 * Provides the user, project, and tagset editor.
 *
 * @author Marcel Bollmann
 * @date January 2012 - September 2014
 */

// ***********************************************************************
// ********** USER MANAGEMENT ********************************************
// ***********************************************************************

cora.users = {
    data: [],
    byID: {},
    onUpdateHandlers: [],

    /* Function: get

       Return a user by ID.

       Parameters:
        uid - ID of the user to be returned
     */
    get: function(uid) {
        var idx = this.byID[uid];
        if(idx == undefined)
            return Object();
        return this.data[idx];
    },

    /* Function: getAll

       Return an array containing all users.
    */
    getAll: function() {
        return this.data;
    },

    /* Function: onUpdate

       Add a callback function to be called whenever the user list is
       updated.

       Parameters:
        fn - function to be called
     */
    onUpdate: function(fn) {
        if(typeof(fn) == "function")
            this.onUpdateHandlers.push(fn);
        return this;
    },

    /* Function: createUser
       
       Sends a server request to create a new user.

       Parameters:
        name - Desired username
        pw   - Desired password
        fn   - Callback function to invoke after the request
    */
    createUser: function(name, pw, fn) {
        var ref = this;
	new Request.JSON(
	    {'url': 'request.php?do=createUser',
	     'async': false,
	     'data': {'username': name, 'password': pw},
	     onSuccess: function(status, text) {
		 ref.performUpdate();
                 if(typeof(fn) == "function")
                     fn(status, text);
	     }
	}).post();
    },

    /* Function: deleteUser

       Sends a server request to delete a user.

       Parameters:
        id - ID of the user to delete
        fn - Callback function to invoke after the request
     */
    deleteUser: function(id, fn) {
        var ref = this;
	new Request.JSON({
            'url': 'request.php?do=deleteUser',
	    'async': false,
	    'data': {'id': id},
	    onSuccess: function(status, text) {
                ref.performUpdate();
                if(typeof(fn) == "function")
                    fn(status, text);
	    }
	}).post();
    },

    /* Function: toggleAdmin

       Sends a server request to toggle admin status of a user.  Does
       not trigger functions in onUpdateHandler.

       Parameters:
        id - ID of the user
        fn - Callback function to invoke after the request
     */
    toggleAdmin: function(id, fn) {
        var ref = this;
	new Request.JSON({
            'url': 'request.php?do=toggleAdmin',
	    'async': false,
	    'data': {'id': id},
	    onSuccess: function(status, text) {
                if(status['success']) {
                    var value = ref.data[ref.byID[id]].admin=="1" ? "0" : "1";
                    ref.data[ref.byID[id]].admin = value;
                }
                if(typeof(fn) == "function")
                    fn(status, text);
            }
	}).post();
    },

    /* Function: changePassword

       Sends a server request to change the password of a user.

       Parameters:
        id - ID of the user
        pw - New password
        fn - Callback function to invoke after the request
    */
    changePassword: function(id, pw, fn) {
	new Request.JSON({
            'url': 'request.php?do=changePassword',
	    'async': false,
	    'data': {'id': id, 'password': pw},
	    onSuccess: function(status, text) {
                if(typeof(fn) == "function")
                    fn(status, text);
	    }
	}).post();
    },

    /* Function: makeMultiSelectBox

       Creates and returns a dropdown box using MultiSelect.js with
       all available users as entries.

       Parameters:
        users - Array of user IDs that should be pre-selected
        name  - Name of the input array
        id    - ID of the selector div
     */
    makeMultiSelectBox: function(users, name, id) {
        var multiselect = new Element('div',
                                      {'class': 'MultiSelect',
                                       'id':    id});
        Array.each(this.data, function(user, idx) {
            var entry = new Element('input',
                                    {'type': 'checkbox',
                                     'id':   name+'_'+user.id,
                                     'name': name+'[]',
                                     'value': user.id});
            var label = new Element('label',
                                    {'for':  name+'_'+user.id,
                                     'text': user.name});
            if(users.some(function(el){ return el.id == user.id; }))
                entry.set('checked', 'checked');
            multiselect.grab(entry).grab(label);
        });
        new MultiSelect(multiselect,
                       {monitorText: ' Benutzer ausgewählt'});
        return multiselect;
    },

    /* Function: performUpdate

       Perform a server request to update the user data.  Calls any
       handlers previously registered via onUpdate().
     */
    performUpdate: function() {
        var ref = this;
        new Request.JSON({
            url: 'request.php',
            onSuccess: function(status, text) {
                if(!status['success']) {
                    gui.showNotice('error',
                                   "Konnte Benutzerdaten nicht laden.");
                    return;
                }
                ref.data = status['data'];
                ref.byID = {};
                Array.each(ref.data, function(user, idx) {
                    ref.byID[user.id] = idx;
                });
                Array.each(ref.onUpdateHandlers, function(handler) {
                    handler(status, text);
                });
            }
        }).get({'do': 'getUserList'});
    }
}

cora.userEditor = {
    initialize: function() {
        cora.users.onUpdate(this.refreshUserTable);
        cora.users.performUpdate();

        $('adminCreateUser').addEvent(
            'click', function() { this.createUser(); }.bind(this)
        );
        $('adminUsersRefresh').addEvent(
            'click', function() { cora.users.performUpdate(); }
        );
	$('editUsers').addEvent(
	    'click:relay(td)',
	    function(event, target) {
		if(target.hasClass('adminUserAdminStatusTD')) {
		    this.toggleStatus(event, 'Admin');
		}
		else if(target.hasClass('adminUserDelete')) {
		    this.deleteUser(event);
		}
	    }.bind(this)
	);
        $('editUsers').addEvent(
            'click:relay(a.adminUserPasswordButton)',
            function(event, button) {
                event.stop();
                var uid = button.getParent('tr').get('id').substr(5);
                this.changePassword(uid);
            }.bind(this)
        );
        $('editUsers').store('HtmlTable',
                             new HtmlTable($('editUsers'),
                                           {sortable: true,
                                            parsers: ['string', 'title',
                                                      'date',   'string']}));
    },

    /* Function: createUser

       Displays a dialog to create a new user entry.
    */
    createUser: function() {
        var performChecks = function(cdiv) {
            var un, pw1, pw2;
            un  = cdiv.getElements('input[name="newuser"]')[0].get('value');
            pw1 = cdiv.getElements('input[name="newpw"]')[0].get('value');
            pw2 = cdiv.getElements('input[name="newpw2"]')[0].get('value');
            if(!un) {
	        gui.showNotice('error', "Benutzername darf nicht leer sein.");
                return false;
            }
            if(!pw1) {
                gui.showNotice('error', "Passwort darf nicht leer sein.");
                return false;
            }
	    if (pw1 !== pw2) {
	        gui.showNotice('error', "Passwörter stimmen nicht überein.");
	        return false;
	    }
            return [un, pw1];
        };
        var performRequest = function(data) {
            var username = data[0];
            var password = data[1];
            cora.users.createUser(username, password, function (status, text) {
                if(status['success']) {
                    gui.showNotice('ok', 'Benutzer hinzugefügt.');
                }
                else {
	            gui.showNotice('error', 'Benutzer nicht hinzugefügt.');
                }
            });
        };
        new mBox.Modal({
            title: "Neuen Benutzer hinzufügen",
            content: $('templateCreateUser'),
            buttons: [ {title: "Abbrechen", addClass: "mform"},
                       {title: "Hinzufügen", addClass: "mform button_green",
                        event: function() {
                            var data = performChecks(this.content);
                            if(data) {
                                this.close();
                                performRequest(data);
                            }
                        }
                       }
                     ],
            onInit: function() {
                this.content.getElements('input[name="newuser"]').set('value', '');
                this.content.getElements('input[name="newpw"]').set('value', '');
                this.content.getElements('input[name="newpw2"]').set('value', '');
            }
        }).open();
    },

    /* Function: deleteUser

       Asks for confirmation to delete a user and requests the delete.
    */
    deleteUser: function(event) {
	var parentrow = event.target.getParent('tr');
	var uid = parentrow.get('id').substr(5);
        var username = parentrow.getElement('td.adminUserNameCell').get('text');
        var performDelete = function() {
            cora.users.deleteUser(uid, function(status, text) {
                if(status['success']) {
                    gui.showNotice('ok', 'Benutzer gelöscht.');
                }
                else {
                    gui.showNotice('error', 'Benutzer nicht gelöscht.');
                }
            });
        };
        gui.confirm("Benutzer '" + username + "' wirklich löschen?",
                    performDelete, true);
    },

    /* Function: toggleStatus

       Toggles a status for a user.  (Right now, only admin status exists.)
    */
    toggleStatus: function(event, statusname) {
        if(statusname!='Admin')
            return;
	var tr = event.target.getParent('tr');
        var td = tr.getElement('td.adminUserAdminStatusTD');
	var uid = tr.get('id').substr(5);

        cora.users.toggleAdmin(uid, function(status, text) {
            if(!status['success']) {
                gui.showNotice('error', 'Admin-Status nicht geändert.');
                return;
            }
            td.toggleClass('adminUserIsAdmin');
	    if(ts.hasClass('adminUserIsAdmin')) {
                td.set('title', 'Admin');
	    } else {
                td.set('title', 'Kein Admin');
	    }
        });
    },

    /* Function: changePassword

       Displays a dialog to change the password for a user.
       
       Parameters:
         uid - ID of the user
    */
    changePassword: function(uid) {
        var performChecks = function(cdiv) {
            var pw1, pw2;
            pw1 = cdiv.getElements('input[name="newchpw"]')[0].get('value');
            pw2 = cdiv.getElements('input[name="newchpw2"]')[0].get('value');
            if(!pw1) {
                gui.showNotice('error', "Passwort darf nicht leer sein.");
                return false;
            }
	    if (pw1 !== pw2) {
	        gui.showNotice('error', "Passwörter stimmen nicht überein.");
	        return false;
	    }
            return pw1;
        };
        var performRequest = function(uid, password) {
            cora.users.changePassword(uid, password, function (status, text) {
                if(status['success'])
                    gui.showNotice('ok', 'Password geändert.');
                else
                    gui.showNotice('error', 'Password nicht geändert.');
            });
        };
        new mBox.Modal({
            title: "Passwort ändern",
            content: $('templateChangePassword'),
            buttons: [ {title: "Abbrechen", addClass: "mform"},
                       {title: "Ändern", addClass: "mform button_red",
                        event: function() {
                            var pw = performChecks(this.content);
                            if(pw) {
                                this.close();
                                performRequest(uid, pw);
                            }
                        }
                       }
                     ]
        }).open();
    },
    
    /* Function: refreshUserTable
       
       Renders the table containing the user data.
     */
    refreshUserTable: function() {
        var table = $('editUsers').getElement('tbody');
        table.empty();
        Array.each(cora.users.getAll(), function(user) {
            var tr = $('templateUserInfoRow').clone();
            tr.set('id', 'User_'+user.id);
            tr.getElement('td.adminUserNameCell').set('text', user.name);
            tr.getElement('td.adminUserLastactiveCell')
                .set('text', gui.formatDateString(user.lastactive));
            if(user.opened_text) {
                var opened_text = cora.files.get(user.opened_text);
                if(opened_text) {
                    tr.getElement('td.adminUserActivityCell')
                        .appendText(cora.files.getProject(user.opened_text).name)
                        .appendText(": "+cora.files.getDisplayName(opened_text));
                }
            }
            if(user.active == "1")
                tr.addClass('userActive');
            if(user.admin == "1") {
                tr.getElement('.adminUserAdminStatusTD')
                    .addClass('adminUserIsAdmin').set('title', 'Admin');
            }
            tr.inject(table);
        });
        $('editUsers').retrieve('HtmlTable').reSort();
    }
}

// ***********************************************************************
// ********** AUTOMATIC ANNOTATION ***************************************
// ***********************************************************************
cora.annotatorEditor = {
    byID: {},
    annotators: [],
    table: null,
    editForm: null,
    flexrow: null,

    initialize: function() {
        this.table = $('editAutomaticAnnotators');
        this.editForm = $('annotatorEditForm');
        $('adminCreateAnnotator').addEvent(
            'click', function() {this.showCreateAnnotatorDialog();}.bind(this)
        );
	this.table.addEvent(
	    'click:relay(a)',
	    function(event, target) {
                var parent = target.getParent('td');
		if(target.hasClass('deletion-link')
                   && parent.hasClass("adminAnnotatorConfig")) {
		    this.deleteAnnotator(event);
		} else if(target.hasClass('adminAnnotatorEditButton')) {
                    var tid = parent.getParent('tr')
                        .getElement('td.adminAnnotatorIDCell').get('text');
                    this.showAnnotatorOptionsDialog(tid);
                }
	    }.bind(this)
	);
        this.flexrow = new FlexRowList(this.editForm.getElement('ul.flexrow-container'),
                                       $('annotatorOptEntryTemplate'));
        this.table.store('HtmlTable',
                         new HtmlTable(this.table,
                                       {sortable: true,
                                        parsers: ['number', 'string',
                                                  'string', 'title', 'string']}));

        this.performUpdate();
    },

    /* Function: performUpdate

       Sends a server request to get a list of all taggers.
     */
    performUpdate: function() {
        new Request.JSON({
            url: 'request.php',
            onSuccess: function(status, text) {
                if(!status['success']) {
                    gui.showNotice('error',
                                   "Konnte Tagger nicht laden.");
                    return;
                }
                this.annotators = status['taggers'];
                this.byID = {};
                Array.each(this.annotators, function(annotator, idx) {
                    this.byID[annotator.id] = idx;
                }.bind(this));
                this.refreshTable();
            }.bind(this)
        }).get({'do': 'adminGetAllAnnotators'});
    },

    /* Function: refreshTable

       Recreates the table listing all taggers.
     */
    refreshTable: function() {
        var table = this.table.getElement('tbody');
        table.empty();
        Array.each(this.annotators, function(tagger) {
            var tr = $('templateAnnotatorInfoRow').clone();
            var tlist = cora.tagsets.get(tagger.tagsets).map(function(ts) {
                return ts['class'];
            }).sort();
            tr.getElement('td.adminAnnotatorIDCell').set('text', tagger.id);
            tr.getElement('td.adminAnnotatorNameCell').set('text', tagger.name);
            tr.getElement('td.adminAnnotatorClassCell').set('text', tagger.class_name);
            tr.getElement('td.adminAnnotatorTrainableCell')
                .set('title', tagger.trainable ? 'Individuell trainierbar'
                                               : 'Nicht individuell trainierbar');
            tr.getElement('span.adminAnnotatorTrainableStatus')
                .setStyle('display', tagger.trainable ? 'inline-block' : 'none');
            tr.getElement('td.adminAnnotatorTagsetCell')
                .set('text', tlist.join(', '));
            tr.inject(table);
        });
    },

    /* Function: deleteAnnotator

       Asks for confirmation to delete a tagger and requests the delete.
     */
    deleteAnnotator: function(event) {
	var parentrow = event.target.getParent('tr');
	var tid  = parentrow.getElement('td.adminAnnotatorIDCell').get('text');
	var name = parentrow.getElement('td.adminAnnotatorNameCell').get('text');
        var performDelete = function() {
            new Request.JSON({
                'url': 'request.php',
                'async': false,
	        onSuccess: function(status, text) {
		    this.performUpdate();
                    if(status['success']) {
                        gui.showNotice('ok', 'Tagger gelöscht.');
                    }
                    else {
                        gui.showNotice('error', 'Tagger nicht gelöscht.');
                    }
	        }.bind(this)
	    }).get({'do': 'adminDeleteAnnotator', 'id': tid});
        };
        gui.confirm("Tagger "+tid+" '"+name+"' wirklich löschen?",
                    performDelete, true);
    },

    /* Function: createAnnotator

       Sends a server request to create a new tagger.

       Parameters:
         name - Display name of the tagger
         fn - Callback function to invoke after the server request
     */
    createAnnotator: function(name, fn) {
        var ref = this;
	new Request.JSON({
	    'url': 'request.php?do=adminCreateAnnotator',
	    'async': false,
	    'data': {'name': name, 'class': 'None'},
	    onSuccess: function(status, text) {
		ref.performUpdate();
                if(typeof(fn) == "function")
                    fn(status, text);
	    }
	}).post();
    },

    /* Function: changeAnnotatorOptionsFromDialog

       Change the options for an annotator based on the contents of a
       dialog window.  Sends a server request to perform the change
       and calls performUpdate() afterwards.

       Parameters:
         tid - ID of the annotator to be modified
         content - Content of the dialog window from where to extract
                   the changes
     */
    changeAnnotatorOptionsFromDialog: function(tid, content) {
        /* extract settings */
        var annotator = {id: tid};
        annotator.name = content.getElement('input[name=annotatorDisplayName]')
                                .get('value');
        annotator.class_name = content.getElement('input[name=annotatorClassName]')
                                      .get('value');
        annotator.trainable = content.getElement('input[name=annotatorIsTrainable]')
                                     .get('checked') ? 1 : 0;
        annotator.tagsets = [];
        content.getElements('input[name="linkannotagsets[]"]').each(function(el) {
            if(el.get('checked'))
                annotator.tagsets.push(el.get('value'));
        });
        annotator.options = {};
        content.getElements('li.flexrow-content').each(function(li) {
            var key = li.getElement('input.annotatorOptKey').get('value');
            var value = li.getElement('input.annotatorOptValue').get('value');
            if (key && key.length > 0)
                annotator.options[key] = value;
        });
        /* send request */
        new Request.JSON({
            url: 'request.php?do=adminChangeAnnotator',
            data: annotator,
            onSuccess: function(status, text) {
                if(status['success']) {
                    gui.showNotice('ok', "Optionen geändert.");
                } else {
                    gui.showNotice('error', "Optionen nicht geändert.");
                }
                this.performUpdate();
            }.bind(this)
        }).post();
    },

    /* Function: showAnnotatorOptionsDialog

       Display a dialog to edit the automatic annotator's options.

       Parameters:
         tid - ID of the annotator to be modified
    */
    showAnnotatorOptionsDialog: function(tid) {
        if(this.byID[tid] === undefined)
            return;
        var ref = this;
        var annotator = this.annotators[this.byID[tid]];
	var content   = this.editForm;
        var opt_add   = content.getElement('li.annotatorOptAddLi');
        /* name, class, tagsets */
        content.getElement('input[name=annotatorDisplayName]')
            .set('value', annotator.name);
        content.getElement('input[name=annotatorClassName]')
            .set('value', annotator.class_name);
        content.getElement('input[name=annotatorIsTrainable]')
            .set('checked', (annotator.trainable ? 'checked' : ''));
        cora.tagsets
            .makeMultiSelectBox(annotator.tagsets, 'linkannotagsets', 'LinkTagsets_AA')
            .addClass('tagsetSelectPlaceholder')
            .replaces(content.getElement('.tagsetSelectPlaceholder'));
        /* option list */
        this.flexrow.empty();
        Object.each(annotator.options, function(value, key) {
            var thisopt = this.flexrow.rowTemplate.clone();
            thisopt.getElement('input.annotatorOptKey').set('value', key);
            thisopt.getElement('input.annotatorOptValue').set('value', value);
            this.flexrow.grab(thisopt);
        }.bind(this));
        /* dialog window */
        new mBox.Modal({
            title: "Optionen für Tagger "+tid,
            content: content,
            buttons: [
                {title: 'Schließen', addClass: 'mform'},
                {title: 'Ändern', addClass: 'mform button_red',
                 event: function() {
                     ref.changeAnnotatorOptionsFromDialog(tid, this.content);
                     this.close();
                 }}
            ],
            closeOnBodyClick: false
        }).open();
    },

    /* Function: showCreateAnnotatorDialog

       Displays a dialog to create a new automatic annotator.
     */
    showCreateAnnotatorDialog: function() {
        var performRequest = function(content) {
            var name = content.getElement('input').get('value');
            if(!name || name.length < 1) {
                gui.showNotice('error', 'Name darf nicht leer sein!');
                return false;
            }
            this.createAnnotator(name, function (status) {
                if(status['success']) {
                    gui.showNotice('ok', 'Tagger hinzugefügt.');
                    this.showAnnotatorOptionsDialog(status['id']);
                }
                else {
                    gui.showNotice('error', 'Tagger nicht hinzugefügt.');
                }
            }.bind(this));
            return true;
        }.bind(this);
        new mBox.Modal({
            title: "Neuen Tagger definieren",
            content: $('annotatorCreateForm'),
            buttons: [ {title: "Abbrechen", addClass: "mform"},
                       {title: "Erstellen", addClass: "mform button_green",
                        event: function() {
                            if(performRequest(this.content))
                                this.close();
                        }
                       }
                     ]
        }).open();
    }

}

// ***********************************************************************
// ********** SERVER NOTICES *********************************************
// ***********************************************************************
cora.noticeEditor = {
    notices: [],

    initialize: function() {
        $('adminCreateNotice').addEvent(
            'click', function() { this.showCreateNoticeDialog(); }.bind(this)
        );
	$('editNotices').addEvent(
	    'click:relay(td)',
	    function(event, target) {
		if(target.hasClass('adminNoticeDelete')) {
		    this.deleteNotice(event);
		}
	    }.bind(this)
	);
        $('editNotices').store('HtmlTable',
                               new HtmlTable($('editNotices'),
                                             {sortable: true,
                                              parsers: ['number', 'string',
                                                        'string', 'date']}));
        this.performUpdate();
    },

    /* Function: performUpdate

       Sends a server request to get a list of all server notices.
     */
    performUpdate: function() {
        new Request.JSON({
            url: 'request.php',
            onSuccess: function(status, text) {
                if(!status['success']) {
                    gui.showNotice('error',
                                   "Konnte Benachrichtigungen nicht laden.");
                    return;
                }
                this.notices = status['notices'];
                this.refreshNoticeTable();
            }.bind(this)
        }).get({'do': 'adminGetAllNotices'});
    },

    /* Function: refreshNoticeTable

       Recreates the table listing all server notices.
     */
    refreshNoticeTable: function() {
        var table = $('editNotices').getElement('tbody');
        table.empty();
        Array.each(this.notices, function(notice) {
            var tr = $('templateNoticeInfoRow').clone();
            tr.getElement('td.adminNoticeIDCell').set('text', notice.id);
            tr.getElement('td.adminNoticeTextCell').set('text', notice.text);
            tr.getElement('td.adminNoticeTypeCell').set('text', notice.type);
            tr.getElement('td.adminNoticeExpiresCell')
                .set('text', gui.formatDateString(notice.expires));
            tr.inject(table);
        });
    },

    /* Function: createNotice

       Sends a server request to create a new notice.

       Parameters:
         type - Type of the notice (alert|info)
         text - Notice text
         expires - Expiry date of the notice
         fn - Callback function to invoke after the server request
     */
    createNotice: function(type, text, expires, fn) {
        var ref = this;
	new Request.JSON({
	    'url': 'request.php?do=adminCreateNotice',
	    'async': false,
	    'data': {'type': type, 'text': text, 'expires': expires},
	    onSuccess: function(status, text) {
		ref.performUpdate();
                if(typeof(fn) == "function")
                    fn(status, text);
	    }
	}).post();
    },

    /* Function: deleteNotice

       Asks for confirmation to delete a notice and requests the delete.
     */
    deleteNotice: function(event) {
	var parentrow = event.target.getParent('tr');
	var nid = parentrow.getElement('td.adminNoticeIDCell').get('text');
        var performDelete = function() {
            new Request.JSON({
                'url': 'request.php',
                'async': false,
	        onSuccess: function(status, text) {
		    this.performUpdate();
                    if(status['success']) {
                        gui.showNotice('ok', 'Benachrichtigung gelöscht.');
                    }
                    else {
                        gui.showNotice('error', 'Benachrichtigung nicht gelöscht.');
                    }
	        }.bind(this)
	    }).get({'do': 'adminDeleteNotice', 'id': nid});
        }.bind(this);
        gui.confirm("Benachrichtigung "+nid+" wirklich löschen?",
                    performDelete, true);
    },

    /* Function: showCreateNoticeDialog

       Displays a dialog to create a new server notice.
     */
    showCreateNoticeDialog: function() {
        var performRequest = function(content) {
            var type, text, expires;
            type = content.getElement('select').getSelected()[0].get('value');
            text = content.getElement('textarea').get('value');
            expires = content.getElement('input').get('value');
            if(!expires || expires.length < 1) {
                gui.showNotice('error', 'Ablauftermin muss angegeben werden!');
                return false;
            }
            if(!text || text.length < 1) {
                gui.showNotice('error', 'Benachrichtigung darf nicht leer sein!');
                return false;
            }
            this.createNotice(type, text, expires, function (status) {
                if(status['success']) {
                    gui.showNotice('ok', 'Benachrichtigung hinzugefügt.');
                }
                else {
                    gui.showNotice('error', 'Benachrichtigung nicht hinzugefügt.');
                }
            });
            return true;
        }.bind(this);
        new mBox.Modal({
            title: "Neue Server-Benachrichtigung erstellen",
            content: $('templateCreateNotice'),
            buttons: [ {title: "Abbrechen", addClass: "mform"},
                       {title: "Erstellen", addClass: "mform button_green",
                        event: function() {
                            if(performRequest(this.content))
                                this.close();
                        }
                       }
                     ],
            onInit: function() {
                this.content.getElements('textarea').set('value', '');
                new DatePicker(this.content.getElement('input[name=noticeexpires]'),
                               {timePicker: true,
                                format: 'd.m.Y, H:i',
                                inputOutputFormat: 'Y-m-d H:i:s',  // for MySQL
                                minDate: 'now'
                               });
            },
            closeOnBodyClick: false,
            closeOnEsc: true
        }).open();
    }
}

// ***********************************************************************
// ********** PROJECT MANAGEMENT *****************************************
// ***********************************************************************

cora.projectEditor = {
    initialize: function() {
	var ref = this;
        cora.projects.onUpdate(this.refreshProjectTable);
	// adding projects
	var cp_mbox = new mBox.Modal({
	    'title': 'Neues Projekt erstellen',
	    'content': 'projectCreateForm',
	    'attach': 'adminCreateProject',
            'buttons': [ {title: "Abbrechen", addClass: "mform"},
                         {title: "Erstellen", addClass: "mform button_green",
                          event: function() {
		              var pn = this.content.getElement('input').get('value');
		              var req = new Request.JSON(
		                  {url:'request.php',
		                   onSuccess: function(status) {
			               var pid = false;
			               if(status!==null && status.success && status.pid) {
			                   pid = Number.from(status.pid);
			               }
			               if(!pid || pid<1) {
                                           gui.showNotice('error', 'Projekt erstellen fehlgeschlagen.');
			               } else {
			                   $('projectCreateForm').getElement('input').set('value', '');
			                   this.close();
                                           gui.showNotice('ok', 'Projekt angelegt.');
                                           cora.projects.performUpdate(function() {
                                               ref.showProjectEditDialog(pid);
                                           });
			               }
		                   }.bind(this)
		                  });
		              req.get({'do': 'createProject', 'project_name': pn});
                          }
                         }
                       ]
	});

	// deleting projects
	$('editProjects').addEvent(
	    'click:relay(a)',
	    function(event, target) {
		if(target.hasClass("adminProjectDelete")) {
                    this.deleteProject(target);
		}
	    }.bind(this)
	);
	// editing project groups
	$('editProjects').addEvent(
	    'click:relay(a)',
	    function(event, target) {
		if(target.hasClass("adminProjectEditButton")) {
                    event.stop();
		    var pid = target.getParent('tr').get('id').substr(8);
                    ref.showProjectEditDialog(pid);
		}
	    }
	);

        $('editProjects').store('HtmlTable',
                                new HtmlTable($('editProjects'),
                                              {sortable: true,
                                               parsers: ['string', 'string',
                                                         'string',
                                                         'title',  'title']}));
    },

    /* Function: deleteProject

       Asks for confirmation to delete a project and requests the delete.
    */
    deleteProject: function(target) {
	var pid = target.getParent('tr').get('id').substr(8);
	var pn  = target.getParent('tr').getElement('td.adminProjectNameCell').get('html');
        var performDelete = function() {
            var prj = cora.projects.get(pid);
	    if (prj.files == undefined || prj.files.length == 0) {
	        var req = new Request.JSON(
		    {url:'request.php',
		     onSuccess: function(data, text) {
		         if(data.success) {
                             gui.showNotice('ok', 'Projekt gelöscht.');
                             cora.projects.performUpdate();
		         } else {
                             gui.showNotice('error', 'Projekt löschen fehlgeschlagen.');
		         }
		     }
		    }
	        );
	        req.get({'do': 'deleteProject', 'project_id': pid});
	    } else {
                setTimeout(function() {
                    gui.showMsgDialog('error', 'Projekte können nicht gelöscht werden, '
                                      + 'solange noch mindestens ein Dokument dem Projekt '
                                      + 'zugeordnet ist.');
                }, 10);
	    }
        };
        gui.confirm("Projekt "+pid+" '"+pn+"' wirklich löschen?",
                    performDelete, true);
    },

    /* Function: refreshProjectTable
       
       Renders the table containing the project data.
     */
    refreshProjectTable: function() {
        var table = $('editProjects').getElement('tbody');
        table.getElements('tr.adminProjectInfoRow').dispose();
        Array.each(cora.projects.getAll(), function(prj) {
            var ulist = prj.users.map(function(user) { return user.name; });
            var tlist = cora.tagsets.get(prj.tagsets).map(function(ts) {
                                                          return ts['class'];
            }).sort();
            var tr = $('templateProjectInfoRow').clone();
            tr.set('id', 'project_'+prj.id);
            tr.getElement('td.adminProjectNameCell').set('text', prj.name);
	    tr.getElement('td.adminProjectUsersCell')
                .set('text', ulist.join(', '));
	    tr.getElement('td.adminProjectTagsetsCell')
                .set('text', tlist.join(', '));
            if(!prj.settings.cmd_edittoken
               || prj.settings.cmd_edittoken.length === 0) {
                tr.getElement('td.adminProjectCmdEdittoken span').hide();
                tr.getElement('td.adminProjectCmdEdittoken')
                    .set('title', 'Kein Edit-Skript zugeordnet');
            }
            if(!prj.settings.cmd_import
               || prj.settings.cmd_import.length === 0) {
                tr.getElement('td.adminProjectCmdImport span').hide();
                tr.getElement('td.adminProjectCmdImport')
                    .set('title', 'Kein Import-Skript zugeordnet');
            }
            tr.inject(table);
        });
    },

    /* Function: makeProjectEditContent

       Creates a div containing the form to edit project settings,
       already filled with information for a given project.

       Should only be called internally from showProjectEditDialog.

       Parameters:
        prj - Project to take the settings from
     */
    makeProjectEditContent: function(prj) {
	var content = $('projectEditForm').clone();
        if(prj.settings.cmd_edittoken) {
            content.getElement('input[name="projectCmdEditToken"]')
                   .set('value', prj.settings.cmd_edittoken);
        }
        if(prj.settings.cmd_import) {
            content.getElement('input[name="projectCmdImport"]')
                   .set('value', prj.settings.cmd_import);
        }
        cora.users.makeMultiSelectBox(prj.users, 'linkusers', 'LinkUsers_MS')
            .replaces(content.getElement('.userSelectPlaceholder'));
        cora.tagsets.makeMultiSelectBox(prj.tagsets, 'linkprjtagsets', 'LinkTagsets_MS')
            .replaces(content.getElement('.tagsetSelectPlaceholder'));

        return content;
    },

    /* Function: showProjectEditDialog

       Opens the dialog to edit project settings.

       Parameters:
        pid - ID of the project to be edited
     */
    showProjectEditDialog: function(pid) {
        var content, mbox;
        var ref = this;
        var prj = cora.projects.get(pid);

        if(prj == undefined || prj.id != pid) {
            gui.showTextDialog('Unbekannter Fehler',
                               'Die Einstellungen für das Projekt konnten nicht geladen werden.');
            return;
        }

        content = this.makeProjectEditContent(prj);
	mbox = new mBox.Modal({
	    title: 'Einstellungen für Projekt "'+prj.name+'"',
	    content: content,
            closeOnBodyClick: false,
	    buttons: [ {title: "OK", addClass: "mform button_green",
			id: "importCloseButton", 
			event: function() {
                            var cb = function (status, text) {
                                if(status && status['success']) {
                                    gui.showNotice('ok',
                                                   "Einstellungen gespeichert.");
                                    cora.projects.performUpdate();
                                    this.close();
                                }
                                else {
                                    gui.showNotice('error',
                                                   "Speichern der Einstellungen fehlgeschlagen.");
                                }
                            }.bind(this);
                            ref.saveProjectSettings(pid, this.content, cb);
			}},
                       {title: "Abbrechen", addClass: "mform"}
                     ]
	});
        mbox.open();
    },

    /* Function: saveProjectSettings

       Sends a server request to save settings for a project.
       
       Parameters:
        pid - ID of the project
        div - Content <div> containing the settings
        fn  - Callback function after the request
     */
    saveProjectSettings: function(pid, div, fn) {
        // extract data
        var data = {'id': pid,
                    'users': [],
                    'tagsets': []
                   };
        data.cmd_edittoken = div.getElement('input[name="projectCmdEditToken"]')
                                .get('value');
        data.cmd_import    = div.getElement('input[name="projectCmdImport"]')
                                .get('value');
        div.getElements('input[name="linkusers[]"]').each(function(el) {
            if(el.get('checked'))
                data.users.push(el.get('value'));
        });
        div.getElements('input[name="linkprjtagsets[]"]').each(function(el) {
            if(el.get('checked'))
                data.tagsets.push(el.get('value'));
        });
        if(data.users.length == 0)
            data.users = "";
        if(data.tagsets.length == 0)
            data.tagsets = "";

        // send request
	new Request.JSON(
	    {'url': 'request.php?do=saveProjectSettings',
	     'async': false,
	     'data': data,
	     onSuccess: function(status, text) {
                 if(typeof(fn) == "function")
                     fn(status, text);
	     }
	}).post();
    }
}


cora.tagsetEditor = {
    initialize: function() {
	var ref = this;
	this.activateImportForm();
	this.activateTagsetViewer();
    },

    activateTagsetViewer: function() {
	var ref = this;
	var import_mbox = new mBox.Modal({
	    title: 'Tagset-Browser',
	    content: 'adminTagsetBrowser',
	    attach: 'adminViewTagset',
            closeOnBodyClick: false
	});

	$('aTBview').addEvent('click', function(e) {
	    var tagset     = $('aTBtagset').getSelected().get('value')[0];
	    var tagsetname = $('aTBtagset').getSelected().get('html');
	    var textarea   = $('aTBtextarea');
	    var spinner    = new Spinner(textarea).show(true);
            textarea.empty();
	    // fetch tag list and perform a quick and dirty analysis:
	    var request = new Request.JSON(
		{url:'request.php',
		 onSuccess: function(status, text) {
		     var output;
		     if(status['success']) {
			 var data = status['data'];
			 var postags = new Array();
			 output = tagsetname + " (ID: " + tagset + ") has ";
			 output += data.length + " tags ";
			 Array.each(data, function(tag) {
			     var pos;
			     var dot = tag['value'].indexOf('.');
			     if(dot>=0 && dot<tag['value'].length-1) {
				 pos = tag['value'].slice(0, dot);
			     } else {
				 pos = tag['value'];
			     }
			     postags.push(pos);
			 });
			 postags = postags.unique();
			 output += "in " + postags.length + " base POS categories.\n\n";
			 output += "Base POS categories are:\n";
			 output += postags.join(", ");
			 output += "\n\nAll tags:\n";
			 Array.each(data, function(tag) {
			     if(tag['needs_revision']==1) {
				 output += "^";
			     }
			     output += tag['value'] + "\n";
			 });
		     }
		     else {
			 output = "Fehler beim Laden des Tagsets.";
		     }
		     textarea.empty().appendText(output);
                     spinner.hide();
		 }
		}
	    );
	    request.get({'do': 'fetchTagset', 'tagset_id': tagset, 'limit': 'none'});
	});
    },

    activateImportForm: function() {
	var ref = this;
	var formname = 'newTagsetImportForm';
	var import_mbox = new mBox.Modal({
	    title: 'Tagset aus Textdatei importieren',
	    content: 'tagsetImportForm',
	    attach: 'adminImportTagset'
	});

	// note: these checks would be redundant if the iFrame method
	// below would be replaced by an mForm.Submit ...
	// check if a name & file has been selected
	$('newTagsetImportForm').getElement('input[type="submit"]').addEvent('click', function(e) {
	    var importname = $('newTagsetImportForm').getElement('input[name="tagset_name"]').get('value');
	    if(importname==null || importname=="") {
		$('newTagsetImportForm').getElement('input[name="tagset_name"]').addClass("input_error");
		e.stop();
	    } else {
		$('newTagsetImportForm').getElement('input[name="tagset_name"]').removeClass("input_error");
	    }
	    var importfile = $('newTagsetImportForm').getElement('input[name="txtFile"]').get('value');
	    if(importfile==null || importfile=="") {
		$$('#newTagsetImportForm p.error_text').show();
		e.stop();
	    } else {
		$$('#newTagsetImportForm p.error_text').hide();
	    }
	});

        var iFrame = new iFrameFormRequest(formname, {
            onFailure: function(xhr) {
		// never fires?
       		gui.showTextDialog("Import nicht erfolgreich",
                                   "Der Server lieferte folgende Fehlermeldung zurück:",
       		                   xhr.responseText);
       	    },
	    onRequest: function(){
		import_mbox.close();
		gui.showSpinner({message: 'Importiere Tagset...'});
	    },
	    onComplete: function(response){
		var title="", message="", textarea="", error=false;
		try{
		    response = JSON.decode(response);
		}catch(err){
		    message =  "Der Server lieferte eine ungültige Antwort zurück.";
		    textarea  = "Antwort des Servers:\n";
		    textarea += response;
		    textarea += "\n\nInterner Fehler:\n";
		    textarea += err.message;
		    error = true;
		}
		
		if(error){
		    title = "Tagset-Import fehlgeschlagen";
		}
		else if(response==null){
		    title = "Tagset-Import fehlgeschlagen";
		    message = "Beim Import des Tagsets ist ein unbekannter Fehler aufgetreten.";
		}
		else if(!response.success){
		    title = "Tagset-Import fehlgeschlagen";
		    message  = "Beim Import des Tagsets ";
		    message += response.errors.length>1 ? "sind " + response.errors.length : "ist ein";
		    message += " Fehler aufgetreten:";

		    for(var i=0;i<response.errors.length;i++){
			textarea += response.errors[i] + "\n";
		    }
		} 
		else { 
		    title = "Tagset-Import erfolgreich";
		    message = "Das Tagset wurde erfolgreich hinzugefügt.";
		    if((typeof response.warnings !== "undefined") && response.warnings.length>0) {
			message += " Das System lieferte ";
			message += response.warnings.length>1 ? response.warnings.length + " Warnungen" : "eine Warnung";
			message += " zurück:";

			for(var i=0;i<response.warnings.length;i++){
			    textarea += response.warnings[i] + "\n";
			}
		    }

		    form.reset($(formname));
		    $(formname).getElements('.error_text').hide();
                }

		if(textarea!='') {
		    $('adminImportPopup').getElement('p').empty().appendText(message);
		    $('adminImportPopup').getElement('textarea').empty().appendText(textarea);
		    message = 'adminImportPopup';
		}
		new mBox.Modal({
		    title: title,
		    content: message,
		    closeOnBodyClick: false,
		    buttons: [ {title: "OK"} ]
		}).open();

		gui.hideSpinner();
            }
	});

    }
}

// ***********************************************************************
// ********** DOMREADY BINDINGS ******************************************
// ***********************************************************************

window.addEvent('domready', function() {
    cora.noticeEditor.initialize();
    cora.projects.onInit(cora.userEditor.initialize.bind(cora.userEditor));
    cora.projectEditor.initialize();
    cora.tagsetEditor.initialize();
    cora.annotatorEditor.initialize();

    $('adminViewCollapseAll').addEvent('click',
        function(e){
            e.stop();
            $$('div#adminDiv .clappable').each(function (clappable) {
                clappable.addClass('clapp-hidden');
		clappable.getElement('div').hide();
            });
        });
    $('adminViewExpandAll').addEvent('click',
        function(e){
            e.stop();
            $$('div#adminDiv .clappable').each(function (clappable) {
                clappable.removeClass('clapp-hidden');
		clappable.getElement('div').show();
            });
        });

});


//    var mask = new Mask();
//    mask.show();
