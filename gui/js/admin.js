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
    */
    createUser: function(name, pw) {
        gui.lock();
        new CoraRequest({
            name: 'createUser',
            textDialogOnError: true,
            onSuccess: function(status) {
                this.performUpdate();
                gui.showNotice('ok', _("Banner.userAdded"));
            }.bind(this),
            onComplete: function() { gui.unlock(); }
        }).post({'username': name, 'password': pw});
    },

    /* Function: deleteUser

       Sends a server request to delete a user.

       Parameters:
        id - ID of the user to delete
     */
    deleteUser: function(id) {
        gui.lock();
        new CoraRequest({
            name: 'deleteUser',
            textDialogOnError: true,
            onSuccess: function(status) {
                this.performUpdate();
                gui.showNotice('ok', _("Banner.userDeleted"));
            }.bind(this),
            onComplete: function() { gui.unlock(); }
        }).post({'id': id});
    },

    /* Function: toggleAdmin

       Sends a server request to toggle admin status of a user.  Does
       not trigger functions in onUpdateHandler.

       Parameters:
        id - ID of the user
        fn - Callback function to invoke after the request
     */
    toggleAdmin: function(id, fn) {
        new CoraRequest({
            name: 'toggleAdmin',
            textDialogOnError: true,
            onSuccess: function(status) {
                var value = this.data[this.byID[id]].admin=="1" ? "0" : "1";
                this.data[this.byID[id]].admin = value;
                if(typeof(fn) === "function")
                    fn(status);
            }.bind(this)
        }).post({'id': id});
    },

    /* Function: changePassword

       Sends a server request to change the password of a user.

       Parameters:
        id - ID of the user
        pw - New password
    */
    changePassword: function(id, pw) {
        gui.lock();
        new CoraRequest({
            name: 'changePassword',
            textDialogOnError: true,
            onSuccess: function(status) {
                gui.showNotice('ok', _("Banner.passwordChanged"));
            }.bind(this),
            onComplete: function() { gui.unlock(); }
        }).post({'id': id, 'password': pw});
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
                       {monitorText: ' '+_("AdminTab.Forms.projectOptionsForm.usersSelected")});
        return multiselect;
    },

    /* Function: performUpdate

       Perform a server request to update the user data.  Calls any
       handlers previously registered via onUpdate().
     */
    performUpdate: function() {
        new CoraRequest({
            name: 'getUserList',
            textDialogOnError: true,
            onSuccess: function(status) {
                this.data = status.data;
                this.byID = {};
                Array.each(this.data, function(user, idx) {
                    this.byID[user.id] = idx;
                }.bind(this));
                Array.each(this.onUpdateHandlers, function(handler) {
                    handler(status);
                });
            }.bind(this)
        }).get();
    }
};

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
                    this.toggleStatus(event, "Admin");
                }
                else if(target.hasClass('adminUserDelete')) {
                    this.deleteUser(event);
                }
            }.bind(this)
        );
        $('editUsers').addEvent(
            'click:relay(a)',
            function(event, button) {
                event.stop();
                var uid = button.getParent('tr').get('id').substr(5);
                if(button.hasClass("adminUserEditButton"))
                    this.showUserEditDialog(uid);
            }.bind(this)
        );
        $('editUsers').store('HtmlTable',
                             new HtmlTable($('editUsers'),
                                           {sortable: true,
                                            parsers: ['string', 'title',
                                                      'date',   'string',
                                                      'string', 'string']}));
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
                gui.showNotice('error', _("Banner.userNameEmpty"));
                return false;
            }
            if(!pw1) {
                gui.showNotice('error', _("Banner.passwordEmpty"));
                return false;
            }
            if (pw1 !== pw2) {
                gui.showNotice('error', _("Banner.passwordNoMatch"));
                return false;
            }
            return [un, pw1];
        };
        var performRequest = function(data) {
            var username = data[0];
            var password = data[1];
            cora.users.createUser(username, password);
        };
        new mBox.Modal({
            title: _("AdminTab.Forms.addUser.addUserTitle"),
            content: $('templateCreateUser'),
            buttons: [ {title: _("Action.cancel"), addClass: "mform"},
                       {title: _("Action.addUserBtn"), addClass: "mform button_green",
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
        var performDelete = function() { cora.users.deleteUser(uid); };
        gui.confirm(_("AdminTab.userAdministration.deleteUserConfirm", {user:username}),
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

        cora.users.toggleAdmin(uid, function(status) {
            td.toggleClass('adminUserIsAdmin');
            if(td.hasClass('adminUserIsAdmin')) {
                td.set('title', _('AdminTab.userAdministration.isAdminTitle'));

            } else {
                td.set('title', _('AdminTab.userAdministration.isNotAdminTitle'));
            }
        });
    },

    /* Function: showUserEditDialog

       Opens the dialog to edit user settings.

       Parameters:
         uid - ID of the user to be edited
     */
    showUserEditDialog: function(uid) {
        var content, mbox;
        var ref = this;
        var user = cora.users.get(uid);

        if(user == undefined || user.id != uid) {
            gui.showMsgDialog('error',
                              _("AdminTab.userAdministration.couldNotLoadUserData"));
            return;
        }

        content = $('userEditForm').clone();
        content.getElement('input[name=adminUserEmail]').set('value', user.email);
        content.getElement('input[name=adminUserComment]').set('value', user.comment);
        mbox = new mBox.Modal({
            title: _("AdminTab.Forms.userOptionsForm.settingsForUser", {user: user.name}),
            content: content,
            closeOnBodyClick: false,
            buttons: [ {title: _("Action.changePassword"), addClass: "mform button_left",
                        event: function() {
                            this.addEvent('closeComplete', function() {
                                ref.changePassword(uid);
                            });
                            this.close();
                        }
                       },
                       {title: _("Action.cancel"), addClass: "mform"},
                       {title: _("Action.save"), addClass: "mform button_green",
                        event: function() {
                            ref.saveUserSettings(uid, this.content, function() {
                                this.close();
                            }.bind(this));
                        }}
                     ]
        });
        mbox.open();
    },

    /* Function: saveUserSettings

       Sends a server request to save settings for a user.

       Parameters:
        uid - ID of the user
        div - Content <div> containing the settings
        fn  - Callback function after the request
     */
    saveUserSettings: function(uid, div, fn) {
        // extract data
        var data = {'id': uid};
        data.email = div.getElement('input[name=adminUserEmail]').get('value');
        data.comment = div.getElement('input[name=adminUserComment]').get('value');

        // send request
        gui.lock();
        new CoraRequest({
            name: 'saveUserSettings',
            textDialogOnError: true,
            onSuccess: function(status) {
                cora.users.performUpdate();
                gui.showNotice('ok', _("Banner.settingsSaved"));
                if(typeof(fn) === "function")
                    fn(status);
            },
            onComplete: function() { gui.unlock(); }
        }).post(data);
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
                gui.showNotice('error', _("Banner.passwordEmpty"));
                return false;
            }
            if (pw1 !== pw2) {
                gui.showNotice('error', _("Banner.passwordNoMatch"));
                return false;
            }
            return pw1;
        };
        new mBox.Modal({
            title: _("AdminTab.Forms.changePasswordForm.changePassword"),
            content: $('templateChangePassword'),
            buttons: [ {title: _("Action.cancel"), addClass: "mform"},
                       {title: _("Action.change"), addClass: "mform button_red",
                        event: function() {
                            var pw = performChecks(this.content);
                            if(pw) {
                                this.close();
                                cora.users.changePassword(uid, pw);
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
                    var activity_text = cora.files.getProject(user.opened_text).name
                        + ": " + cora.files.getDisplayName(opened_text);
                    tr.getElement('td.adminUserActivityCell')
                        .set('text', activity_text)
                        .set('title', activity_text);
                }
            }
            if(user.active == "1")
                tr.addClass('userActive');
            if(user.admin == "1") {
                tr.getElement('.adminUserAdminStatusTD')
                    .addClass('adminUserIsAdmin').set('title', 'Admin');
            }
            tr.getElement('td.adminUserEmailCell').set('text', user.email);
            tr.getElement('td.adminUserCommentCell')
                .set('text', user.comment).set('title', user.comment);
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
    performUpdate: function(fn) {
        new CoraRequest({
            name: 'adminGetAllAnnotators',
            textDialogOnError: true,
            onSuccess: function(status) {
                this.annotators = status['taggers'];
                this.byID = {};
                Array.each(this.annotators, function(annotator, idx) {
                    this.byID[annotator.id] = idx;
                }.bind(this));
                this.refreshTable();
                if(typeof(fn) === "function")
                    fn(status);
            }.bind(this)
        }).get();
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
                .set('title', tagger.trainable ? _("AdminTab.autoAnnotation.individuallyTrainable")
                                               : _("AdminTab.autoAnnotation.notIndividuallyTrainable"));
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
            gui.lock();
            new CoraRequest({
                name: 'adminDeleteAnnotator',
                textDialogOnError: true,
                onSuccess: function(status) {
                    this.performUpdate();
                    gui.showNotice('ok', _("Banner.taggerDeleted"));
                }.bind(this),
                onComplete: function() { gui.unlock(); }
            }).get({'id': tid});
        }.bind(this);
        gui.confirm(_("AdminTab.autoAnnotation.taggerDeleteConfirm", {taggerNum: tid, taggerName: name}),
                    performDelete, true);
    },

    /* Function: createAnnotator

       Sends a server request to create a new tagger.

       Parameters:
         name - Display name of the tagger
         fn - Callback function to invoke after the server request
     */
    createAnnotator: function(name, fn) {
        gui.lock();
        new CoraRequest({
            name: 'adminCreateAnnotator',
            textDialogOnError: true,
            onSuccess: function(status) {
                this.performUpdate(function() {
                    this.showAnnotatorOptionsDialog(status.id);
                }.bind(this));
                gui.showNotice('ok', _("Banner.taggerCreated"));
            }.bind(this),
            onComplete: function() { gui.unlock(); }
        }).post({'name': name, 'class': 'None'});
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
        gui.lock();
        new CoraRequest({
            name: 'adminChangeAnnotator',
            textDialogOnError: true,
            onSuccess: function(status) {
                this.performUpdate();
                gui.showNotice('ok', _("Banner.taggerOptionsUpdated"));
            }.bind(this),
            onComplete: function() { gui.unlock(); }
        }).post(annotator);
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
        if(this.flexrow.entries < 1)
            this.flexrow.grabNewRow();
        /* dialog window */
        new mBox.Modal({
            title: _("AdminTab.Forms.taggerOptionsForm.optionsForTagger", {taggerId: tid}),
            content: content,
            buttons: [
                {title: _("Action.close"), addClass: 'mform'},
                {title: _("Action.change"), addClass: 'mform button_red',
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
                gui.showNotice('error', _("Banner.enterTaggerName"));
                return false;
            }
            this.createAnnotator(name);
            return true;
        }.bind(this);
        new mBox.Modal({
            title: _("AdminTab.Forms.addTaggerForm.addTagger"),
            content: $('annotatorCreateForm'),
            buttons: [ {title: _("Action.cancel"), addClass: "mform"},
                       {title: _("Action.createTagger"), addClass: "mform button_green",
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
        new CoraRequest({
            name: 'adminGetAllNotices',
            textDialogOnError: true,
            onSuccess: function(status) {
                this.notices = status['notices'];
                this.refreshNoticeTable();
            }.bind(this)
        }).get();
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
     */
    createNotice: function(type, text, expires) {
        gui.lock();
        new CoraRequest({
            name: 'adminCreateNotice',
            textDialogOnError: true,
            onSuccess: function(status) {
                this.performUpdate();
                gui.showNotice('ok', _("Banner.msgAdded"));
            }.bind(this),
            onComplete: function() { gui.unlock(); }
        }).post({'type': type, 'text': text, 'expires': expires});
    },

    /* Function: deleteNotice

       Asks for confirmation to delete a notice and requests the delete.
     */
    deleteNotice: function(event) {
        var parentrow = event.target.getParent('tr');
        var nid = parentrow.getElement('td.adminNoticeIDCell').get('text');
        var performDelete = function() {
            gui.lock();
            new CoraRequest({
                name: 'adminDeleteNotice',
                textDialogOnError: true,
                onSuccess: function(status) {
                    this.performUpdate();
                    gui.showNotice('ok', _("Banner.msgDeleted"));
                }.bind(this),
                onComplete: function() { gui.unlock(); }
            }).get({'id': nid});
        }.bind(this);
        gui.confirm(_("AdminTab.serverMessages.msgDeleteConfirm", {msg: nid}),
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
                gui.showNotice('error', _("Banner.enterExpireDate"));
                return false;
            }
            if(!text || text.length < 1) {
                gui.showNotice('error', _("Banner.enterMsg"));
                return false;
            }
            this.createNotice(type, text, expires);
            return true;
        }.bind(this);
        new mBox.Modal({
            title: _("AdminTab.Forms.addServerMsg.newServerMsg"),
            content: $('templateCreateNotice'),
            buttons: [ {title: _("Action.preview"), addClass: "mform button_left",
                        event: function() {
                            var stype = this.content
                                            .getElement('select[name=noticetype]')
                                            .getSelected()[0].get('value');
                            gui.showNotice(gui.mapServerNoticeType(stype),
                                           this.content
                                               .getElement('textarea').get('value')
                                          );
                        }},
                       {title: _("Action.cancel"), addClass: "mform"},
                       {title: _("Action.createMsg"), addClass: "mform button_green",
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
};

// ***********************************************************************
// ********** PROJECT MANAGEMENT *****************************************
// ***********************************************************************

cora.projectEditor = {
    initialize: function() {
        var ref = this;
        cora.projects.onUpdate(this.refreshProjectTable);

        // adding projects
        $('adminCreateProject').addEvent(
            'click', function() { this.showCreateProjectDialog(); }.bind(this)
        );

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

    /* Function: showCreateProjectDialog

       Displays a dialog to create a new project.
     */
    showCreateProjectDialog: function() {
        var performRequest = function(mbox) {
            var pn = mbox.content.getElement('input').get('value');
            gui.lock();
            new CoraRequest({
                name: 'createProject',
                textDialogOnError: true,
                onSuccess: function(status) {
                    var pid = Number.from(status.pid);
                    if(typeof(pid) === "undefined" || pid < 1) {
                        gui.showMsgDialog('error', _("AdminTab.projectAdministration.noValidProjectId", {projectId:pid}));
                        return;
                    }
                    $('projectCreateForm').getElement('input').set('value', '');
                    mbox.close();
                    gui.showNotice('ok', _("Banner.projectCreated"));
                    cora.projects.performUpdate(function() {
                        this.showProjectEditDialog(pid);
                    }.bind(this));
                }.bind(this),
                onComplete: function() { gui.unlock(); }
            }).get({'project_name': pn});
        }.bind(this);
        new mBox.Modal({
            'title': _("AdminTab.Forms.addProjectForm.addProject"),
            'content': 'projectCreateForm',
            'buttons': [ {title: _("Action.cancel"), addClass: "mform"},
                         {title: _("Action.createProject"), addClass: "mform button_green",
                          event: function() { performRequest(this); }
                         }
                       ]
        }).open();
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
                gui.lock();
                new CoraRequest({
                    name: 'deleteProject',
                    textDialogOnError: true,
                    onSuccess: function(status) {
                        cora.projects.performUpdate();
                        gui.showNotice('ok', _("Banner.projectDeleted"));
                    }.bind(this),
                    onComplete: function() { gui.unlock(); }
                }).get({'project_id': pid});
            } else {
                setTimeout(function() {
                    gui.showMsgDialog('error', _("AdminTab.projectAdministration.deleteProjectInfo"));
                }, 10);
            }
        };
        gui.confirm(_("AdminTab.projectAdministration.deleteProjectConfirm", {projectName: pn, projectId: pid}),
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
                    .set('title', _("AdminTab.projectAdministration.noEditScript"));
            }
            if(!prj.settings.cmd_import
               || prj.settings.cmd_import.length === 0) {
                tr.getElement('td.adminProjectCmdImport span').hide();
                tr.getElement('td.adminProjectCmdImport')
                    .set('title', _("AdminTab.projectAdministration.noImportScript"));
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
            gui.showMsgDialog('error',
                              _("AdminTab.projectAdministration.couldNotLoadSettings"));
            return;
        }

        content = this.makeProjectEditContent(prj);
        mbox = new mBox.Modal({
            title: _("AdminTab.Forms.projectOptionsForm.projectOptions", {project: prj.name}),
            content: content,
            closeOnBodyClick: false,
            buttons: [ {title: _("Action.cancel"), addClass: "mform"},
                       {title: _("Action.save"), addClass: "mform button_green",
                        event: function() {
                            ref.saveProjectSettings(pid, this.content, this.close.bind(this));
                        }}
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
        gui.lock();
        new CoraRequest({
            name: 'saveProjectSettings',
            textDialogOnError: true,
            onSuccess: function(status) {
                cora.projects.performUpdate();
                gui.showNotice('ok', _("Banner.settingsSaved"));
                if(typeof(fn) === "function")
                    fn();
            }.bind(this),
            onComplete: function() { gui.unlock(); }
        }).post(data);
    }
};

cora.tagsetEditor = {
    initialize: function() {
        var ref = this;
        this.activateImportForm();
        this.activateTagsetViewer();
    },

    activateTagsetViewer: function() {
        var ref = this;
        var import_mbox = new mBox.Modal({
            title: 'adminTagsetBrowser_title',
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
            new CoraRequest({
                name: 'fetchTagset',
                textDialogOnError: true,
                onSuccess: function(status) {
                     var data = status['data'],
                         postags = [],
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
                    textarea.empty().appendText(output);
                },
                onComplete: function() { spinner.hide(); }
            }).get({'tagset_id': tagset});
        });
    },

    activateImportForm: function() {
        var ref = this;
        var formname = 'newTagsetImportForm';
        var class_selector = $(formname).getElement('select[name="tagset_class"]');
        var import_mbox = new mBox.Modal({
            title: 'tagsetImportForm_title',
            content: 'tagsetImportForm',
            attach: 'adminImportTagset'
        });

        cora.importableTagsets.each(function(cls) {
            class_selector.grab(new Element('option', {
                text: cls,
                value: cls
            }));
        });

        // note: these checks would be redundant if the iFrame method
        // below would be replaced by an mForm.Submit ...
        // check if a name & file has been selected
        $(formname).getElement('input[type="submit"]').addEvent('click', function(e) {
            var importname = $(formname).getElement('input[name="tagset_name"]').get('value');
            if(importname==null || importname=="") {
                $(formname).getElement('input[name="tagset_name"]').addClass("input_error");
                e.stop();
            } else {
                $(formname).getElement('input[name="tagset_name"]').removeClass("input_error");
            }
            var importfile = $(formname).getElement('input[name="txtFile"]').get('value');
            if(importfile==null || importfile=="") {
                gui.showNotice('error', _("Banner.noImportFileSelected"));
                e.stop();
            }
        });

        var iFrame = new iFrameFormRequest(formname, {
            onRequest: function(){
                import_mbox.close();
                gui.showSpinner({message: _("AdminTab.Forms.importTagsetForm.importingTagset")});
            },
            onFailure: function(xhr) {
                // never fires?
                gui.showTextDialog(_("AdminTab.Forms.importTagsetForm.importNoSuccess"),
                                   _("AdminTab.Forms.importTagsetForm.serverResponseInfo"),
                                   xhr.responseText);
            },
            onComplete: function(response){
                var title="", message="", textarea="", error=false, tmp;
                try {
                    tmp = new Element('div');
                    tmp.innerHTML = response;
                    response = JSON.decode(tmp.getElement('pre.json').get('text'));
                } catch(err) {
                    message = _("AdminTab.Forms.importTagsetForm.invalidServerResponse");
                    textarea = _("AdminTab.Forms.importTagsetForm.serverResponse") + ":\n" + response + "\n\n";
                    textarea += _("AdminTab.Forms.importTagsetForm.internalError") + ":\n" + err.message;
                    error = true;
                }
                if (!error) {
                    if (response == null) {
                        error = true;
                        message = _("AdminTab.Forms.importTagsetForm.taggerImportError");
                    }
                    else if (!response.success) {
                        error = true;
                        textarea = response.errors;
                        message = response.errors.length>1 ?
                            _("AdminTab.Forms.importTagsetForm.taggerImportErrorInfo2", {nE: response.errors.length}) :
                            _("AdminTab.Forms.importTagsetForm.taggerImportErrorInfo1");
                    }
                }
                if (error) {
                    title = _("AdminTab.Forms.importTagsetForm.tagsetImportFailed");
                    gui.showTextDialog(title, message, textarea);
                }
                else {
                    form.reset($(formname));
                    gui.showMsgDialog('ok', _("AdminTab.Forms.importTagsetForm.tagsetSuccessfullyAdded"));
                }
                gui.hideSpinner();
            }
        });

    }
};

cora.initAdminLogging = function(editor) {
    editor.saveRequest.addEvent('processed', function(success, details) {
        console.log("Save" + (success ? " " : " NOT ") + "successful.");
        if(!success) {
            console.log(details.name + ": " + details.message);
            console.log(details.details);
        }
    });
    editor.addEvent('applyChanges', function(data, changes, caller) {
        var num = (data && typeof(data.num) !== "undefined") ? data.num : "--";
        Object.each(changes, function(value, key) {
            console.log("EditorModel: "+num+": set '"+key+"' to '"+value+"'");
        });
    });
    editor.dataTable.addEvent('update', function(tr, data, changes, cls, value) {
        console.log("DataTable: "+data.num+": user changed '"+cls+"' to '"+value+"'");
    });
    editor.dataTable.addEvent('updateProgress', function(num, changes) {
        console.log("DataTable: user set progress marker to '"+num+"'");
    });
};
