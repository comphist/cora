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
     */
    makeMultiSelectBox: function(users) {
        var multiselect = new Element('div',
                                      {'class': 'MultiSelect',
                                       'id':    'LinkUsers_MS'});
        Array.each(this.data, function(user, idx) {
            var entry = new Element('input',
                                    {'type': 'checkbox',
                                     'id':   'linkusers_'+user.id,
                                     'name': 'linkusers[]',
                                     'value': user.id});
            var label = new Element('label',
                                    {'for':  'linkusers_'+user.id,
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

	var ceralink = new Element('a', {
	    "id": 'ceraCUButton',
	    "href": '#ceraCreateUser'
	}).wraps('createUser');
	ceralink.cerabox({
	    displayTitle: false,
	    group: false,
	    events: {
		onOpen: function(currentItem, collection) {
	    	    $$('button[name="submitCreateUser"]').addEvent(
			'click', cora.userEditor.createUser, cora.userEditor
		    );
		}
	    }
	});

        $('adminUsersRefresh').addEvent(
            'click', function() { cora.users.performUpdate(); }
        );
	$('editUsers').addEvent(
	    'click:relay(td)',
	    function(event, target) {
		if(target.hasClass('adminUserAdminStatus')) {
		    cora.userEditor.toggleStatus(event, 'Admin');
		}
		else if(target.hasClass('adminUserDelete')) {
		    cora.userEditor.deleteUser(event);
		}
	    }
	);
    },
    createUser: function() {
	var username  = $$('.cerabox-content input[name="newuser[un]"]')[0].get('value');
	var password  = $$('.cerabox-content input[name="newuser[pw]"]')[0].get('value');
	var controlpw = $$('.cerabox-content input[name="newuser[pw2]"]')[0].get('value');

	// perform checks
	if (username === '') {
	    alert("Fehler: 'Benutzername' darf nicht leer sein.");
	    return false;
	}
	if (password === '') {
	    alert("Fehler: 'Passwort' darf nicht leer sein.");
	    return false;
	}
	if (password != controlpw) {
	    alert("Fehler: Passwörter stimmen nicht überein.");
	    return false;
	}

	// send request
        cora.users.createUser(username, password, function (status, text) {
	    CeraBoxWindow.close();
            if(status['success']) {
                gui.showNotice('ok', 'Benutzer hinzugefügt.');
            }
            else {
	        gui.showNotice('error', 'Benutzer nicht hinzugefügt.');
            }
        });
	return true;
    },
    deleteUser: function(event) {
	var parentrow = event.target.getParent('tr');
	var uid = parentrow.get('id').substr(5);
        var username = parentrow.getElement('td.adminUserNameCell').get('text');

	var dialog = "Soll der Benutzer '" + username + "' wirklich gelöscht werden?";
	if (!confirm(dialog))
	    return;

        cora.users.deleteUser(uid, function(status, text) {
            if(status['success']) {
                gui.showNotice('ok', 'Benutzer gelöscht.');
            }
            else {
                gui.showNotice('error', 'Benutzer nicht gelöscht.');
            }
        });
    },
    toggleStatus: function(event, statusname) {
        if(statusname!='Admin')
            return;
	var parentrow = event.target.getParent('tr');
	var uid = parentrow.get('id').substr(5);

        cora.users.toggleAdmin(uid, function(status, text) {
            if(!status['success']) {
                gui.showNotice('error', 'Admin-Status nicht geändert.');
            }
	    var arrow = parentrow.getElement('img.adminUserAdminStatus');
	    if(arrow.isDisplayed()) {
		arrow.hide();
	    } else {
		arrow.show('inline');
	    }
        });
    },
    changePassword: function() {
	var uid       = $$('.cerabox-content input[name="changepw[id]"]')[0].get('value');
	var password  = $$('.cerabox-content input[name="changepw[pw]"]')[0].get('value');
	var controlpw = $$('.cerabox-content input[name="changepw[pw2]"]')[0].get('value');

	// perform checks
	if (password === '') {
	    alert("Fehler: 'Passwort' darf nicht leer sein.");
	    return false;
	}
	if (password != controlpw) {
	    alert("Fehler: Passwörter stimmen nicht überein.");
	    return false;
	}

	// send request
        cora.users.changePassword(uid, password, function (status, text) {
	    CeraBoxWindow.close();
            if(status['success']) {
                gui.showNotice('ok', 'Password geändert.');
            }
            else {
                gui.showNotice('error', 'Password nicht geändert.');
            }
        });
	return true;
    },
    
    /* Function: refreshUserTable
       
       Renders the table containing the user data.
     */
    refreshUserTable: function() {
        var table = $('editUsers');
        table.getElements('tr.adminUserInfoRow').dispose();
        Array.each(cora.users.getAll(), function(user) {
            var tr = $('templateUserInfoRow').clone();
            tr.set('id', 'User_'+user.id);
            tr.getElement('td.adminUserNameCell').set('text', user.name);
            tr.getElement('td.adminUserLastactiveCell').set('text', user.lastactive);
            if(user.active == "1")
                tr.addClass('userActive');
            if(user.admin == "0")
                tr.getElement('img.adminUserAdminStatus').hide();
            tr.inject(table);
        });

        // old legacy code here:
	$$('button.adminUserPasswordButton').each(
	    function(button) {
		var uid = button.getParent('tr').get('id').substr(5);
		var ceralink = new Element('a', {'href':'#ceraChangePassword'}).wraps(button);
		ceralink.cerabox({
		    displayTitle: false,
		    group: false,
		    events: {
			onOpen: function(currentItem, collection) {
			    $$('input[name="changepw[id]"]').set('value', uid);
			    $$('button[name="submitChangePassword"]').addEvent(
				'click', cora.userEditor.changePassword, cora.userEditor
			    );
			}
		    }
		});
	    }
	);
    },
}


// ***********************************************************************
// ********** Additional function for tagset management ******************
// ***********************************************************************

/* Function: makeMultiSelectBox

   Creates and returns a dropdown box using MultiSelect.js with all
   available tagsets as entries.

   Parameters:
    tagsets - Array of tagset IDs that should be pre-selected
*/
cora.tagsets.makeMultiSelectBox = function(tagsets) {
    var multiselect = new Element('div',
                                  {'class': 'MultiSelect',
                                   'id':    'LinkTagsets_MS'});
    Array.each(this.data, function(tagset, idx) {
        var entry = new Element('input',
                                {'type': 'checkbox',
                                 'id':   'linkprjtagsets_'+tagset.id,
                                 'name': 'linkprjtagsets[]',
                                 'value': tagset.id});
        var textr = "["+tagset['class']+"] "+tagset.longname+" (id: "+tagset.id+")";
        var label = new Element('label',
                                {'for':  'linkprjtagsets_'+tagset.id,
                                 'text': textr});
        if(tagsets.some(function(el){ return el == tagset.id; }))
            entry.set('checked', 'checked');
        multiselect.grab(entry).grab(label);
    });
    new MultiSelect(multiselect,
                    {monitorText: ' Tagset(s) ausgewählt'});
    return multiselect;
};

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
	    'attach': 'createProject'
	});
	$('projectCreateForm').getElement("button[name='submitCreateProject']").addEvent(
	    'click',
	    function(event, target) {
		var pn = $('projectCreateForm').getElement('input').get('value');
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
			     cp_mbox.close();
                             gui.showNotice('ok', 'Projekt angelegt.');
                             cora.projects.performUpdate(function() {
                                 ref.showProjectEditDialog(pid);
                             });
			 }
		     }
		    });
		req.get({'do': 'createProject', 'project_name': pn});
	    }
	);

	// deleting projects
	$('editProjects').addEvent(
	    'click:relay(a)',
	    function(event, target) {
		if(target.hasClass("adminProjectDelete")) {
		    var pid = target.getParent('tr').get('id').substr(8);
		    var pn  = target.getParent('tr').getElement('td.adminProjectNameCell').get('html');
                    var prj = cora.projects.get(pid);
		    if (prj.files == undefined || prj.files.length == 0) {
			var req =  new Request.JSON(
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
                        gui.showTextDialog('Projekt löschen: "'+pn+'"',
                                           'Projekte können nicht gelöscht werden, solange noch mindestens ein Dokument dem Projekt zugeordnet ist.');
		    }
		}
	    }
	);
	// editing project groups
	$('editProjects').addEvent(
	    'click:relay(button)',
	    function(event, target) {
		if(target.hasClass("adminProjectEditButton")) {
		    var pid = target.getParent('tr').get('id').substr(8);
                    ref.showProjectEditDialog(pid);
		}
	    }
	);
    },

    /* Function: refreshProjectTable
       
       Renders the table containing the project data.
     */
    refreshProjectTable: function() {
        var table = $('editProjects');
        table.getElements('tr.adminProjectInfoRow').dispose();
        Array.each(cora.projects.getAll(), function(prj) {
            var ulist = prj.users.map(function(user) { return user.name; });
            var tr = $('templateProjectInfoRow').clone();
            tr.set('id', 'project_'+prj.id);
            tr.getElement('td.adminProjectNameCell').set('text', prj.name);
	    tr.getElement('td.adminProjectUsersCell')
                .set('text', ulist.join());
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
        cora.users.makeMultiSelectBox(prj.users)
            .replaces(content.getElement('.userSelectPlaceholder'));
        cora.tagsets.makeMultiSelectBox(prj.tagsets)
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
                            gui.showNotice('error',
                                           "Speichern noch nicht implementiert.");
                            // save settings here
			    this.close();
			}},
                       {title: "Abbrechen", addClass: "mform"}
                     ]
	});
        mbox.open();
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
	    attach: 'viewTagset'
	});

	$('aTBview').addEvent('click', function(e) {
	    var tagset     = $('aTBtagset').getSelected().get('value')[0];
	    var tagsetname = $('aTBtagset').getSelected().get('html');
	    var textarea   = $('aTBtextarea');
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
	    attach: 'importTagset'
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
       		alert("Import nicht erfolgreich: Der Server lieferte folgende Fehlermeldung zurück:\n\n" +
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
    cora.userEditor.initialize();
    cora.projectEditor.initialize();
    cora.tagsetEditor.initialize();
});


//    var mask = new Mask();
//    mask.show();
