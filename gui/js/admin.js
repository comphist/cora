/** @file
 * Functions related to the administrator tab.
 *
 * Provides the user editor and tagset editor.
 *
 * @author Marcel Bollmann
 * @date January 2012
 * 
 * @warning This file is a mess in many parts. The code design was not
 * planned out in advance, but rather adjusted on-the-fly as the
 * author discovered new techniques and learned more about JavaScript
 * and PHP/JS interaction.
 *
 * @todo This is currently not strictly object-oriented, as the global
 * variable tagset_editor is referenced by the classes. Could this be
 * changed to an object property?
 */

// ***********************************************************************
// ********** USER MANAGEMENT ********************************************
// ***********************************************************************

var user_editor = {
    initialize: function() {
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
			'click', user_editor.createUser, user_editor
		    );
		}
	    }
	});


	$('editUsers').addEvent(
	    'click:relay(td)',
	    function(event, target) {
		if(target.hasClass('adminUserAdminStatus')) {
		    user_editor.toggleStatus(event, 'Admin');
		}
		else if(target.hasClass('adminUserNormStatus')) {
		    user_editor.toggleStatus(event, 'Norm');
		}
		else if(target.hasClass('adminUserDelete')) {
		    user_editor.deleteUser(event);
		}
	    }
	);
	$$('button.adminUserPasswordButton').each(
	    function(button) {
		var username = button.getParent('tr').get('id').substr(5);
		var ceralink = new Element('a', {'href':'#ceraChangePassword'}).wraps(button);
		ceralink.cerabox({
		    displayTitle: false,
		    group: false,
		    events: {
			onOpen: function(currentItem, collection) {
			    $$('input[name="changepw[un]"]').set('value', username);
			    $$('button[name="submitChangePassword"]').addEvent(
				'click', user_editor.changePassword, user_editor
			    );
			}
		    }
		});
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
	var request = new Request(
	    {'url': 'request.php?do=createUser',
	     'async': false,
	     'data': 'username='+username+'&password='+password,
	     onFailure: function(xhr) {
		 alert("Fehler: Der Server lieferte folgende Fehlermeldung zurück:\n\n" + xhr.responseText);
	     },
	     onSuccess: function(data, xml) {
		 var row = $$('.adminUserInfoRow')[0].clone();
		 row.set('id', 'User_'+username);
		 row.getElement('td.adminUserNameCell').set('text', username);
		 row.getElement('img.adminUserAdminStatus').hide();
		 // row.getElement('img.adminUserNormStatus').show('inline');
		 var pwbutton = row.getElement('button.adminUserPasswordButton');
		 new Element('a', {'href':'#ceraChangePassword'}).wraps(pwbutton).cerabox({
		     displayTitle: false,
		     group: false,
		     events: {
			 onOpen: function(currentItem, collection) {
			     $$('input[name="changepw[un]"]').set('value', username);
			     $$('button[name="submitChangePassword"]').addEvent(
				 'click', user_editor.changePassword, user_editor
			     );
			 }
		     }
		 });
		 $('editUsers').grab(row);
		 
		 CeraBoxWindow.close();
	     }
	    }
	);
	request.post();

	return true;
    },
    deleteUser: function(event) {
	var parentrow = event.target.getParent('tr');
	var username = parentrow.get('id').substr(5);
	var dialog = "Soll der Benutzer '" + username + "' wirklich gelöscht werden?";

	if (!confirm(dialog))
	    return;

	var request = new Request(
	    {'url': 'request.php?do=deleteUser',
	     'async': false,
	     'data': 'username='+username,
	     onFailure: function(xhr) {
		 alert("Fehler: Der Server lieferte folgende Fehlermeldung zurück:\n\n" + xhr.responseText);
	     },
	     onSuccess: function(data, xml) {
		 parentrow.dispose();
	     }
	    }
	);
	request.post();	
    },
    toggleStatus: function(event, statusname) {
	var parentrow = event.target.getParent('tr');
	var username = parentrow.get('id').substr(5);
	var request = new Request(
	    {'url': 'request.php?do=toggle'+statusname,
	     'async': false,
	     'data': 'username='+username,
	     onFailure: function(xhr) {
		 alert("Fehler: Der Server lieferte folgende Fehlermeldung zurück:\n\n" + xhr.responseText);
	     },
	     onSuccess: function(data, xml) {
		 var arrow = parentrow.getElement('img.adminUser'+statusname+'Status');
		 if(arrow.isDisplayed()) {
		     arrow.hide();
		 } else {
		     arrow.show('inline');
		 }
	     }
	    }
	);
	request.post();	
    },
    changePassword: function() {
	var username  = $$('.cerabox-content input[name="changepw[un]"]')[0].get('value');
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
	var request = new Request(
	    {'url': 'request.php?do=changePassword',
	     'async': false,
	     'data': 'username='+username+'&password='+password,
	     onFailure: function(xhr) {
		 alert("Fehler: Der Server lieferte folgende Fehlermeldung zurück:\n\n" + xhr.responseText);
	     },
	     onSuccess: function(data, xml) {
		 alert("Passwort erfolgreich geändert.");
		 CeraBoxWindow.close();
	     }
	    }
	);
	request.post();

	return true;
    },
}


var project_editor = {
    initialize: function() {
	var ref = this;
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
			     new mBox.Notice({
				 content: 'Projekt erstellen fehlgeschlagen',
				 type: 'error',
				 position: {x: 'right'}
			     });
			 } else {
			     pid = String.from(pid);
			     var new_row = $('editProjects').getElement('tr.adminProjectInfoRow').clone();
			     new_row.set('id', 'project_'+pid);
			     new_row.getElement('a.adminProjectDelete').set('id', 'projectdelete_'+pid);
			     new_row.getElement('td.adminProjectNameCell').set('html', pn);
			     new_row.getElement('td.adminProjectUsersCell').set('html', '');
			     new_row.getElement('button.adminProjectUsersButton').set('id', 'projectbutton_'+pid);
			     $('editProjects').adopt(new_row);
			     
			     $('projectCreateForm').getElement('input').set('value', '');
			     cp_mbox.close();
			     new mBox.Notice({
				 content: 'Projekt erfolgreich angelegt',
				 type: 'ok',
				 position: {x: 'right'}
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
		    var pid = target.get('id').substr(14);
		    var pn  = target.getParent('tr').getElement('td.adminProjectNameCell').get('html');
		    if(file.fileHash == undefined) {
			file.listFiles();
		    }
		    if (file.fileHash[pid] == undefined
		      || Object.getLength(file.fileHash[pid]) == 0) {
			var req =  new Request.JSON(
			    {url:'request.php',
			     onSuccess: function(data, text) {
				 if(data.success) {
				     $('project_'+pid).dispose();
				     new mBox.Notice({
					 content: 'Projekt gelöscht',
					 type: 'ok',
					 position: {x: 'right'}
				     });
				 } else {
				     new mBox.Notice({
					 content: 'Projekt löschen fehlgeschlagen',
					 type: 'error',
					 position: {x: 'right'}
				     });
				 }
			     }
			    }
			);
			req.get({'do': 'deleteProject', 'project_id': pid});
	   
		    } else {
			new mBox.Modal({
			    'title': 'Projekt löschen: "'+pn+'"',
			    'content': 'Projekte können nicht gelöscht werden, solange noch mindestens ein Dokument dem Projekt zugeordnet ist.',
			    'buttons': [ {'title': 'OK'} ]
			}).open();
		    }
		}
	    }
	);
	// editing project groups
	$('editProjects').addEvent(
	    'click:relay(button)',
	    function(event, target) {
		if(target.hasClass("adminProjectUsersButton")) {
		    var mbox_content = $('projectUserChangeForm').clone();
		    var pid = target.get('id').substr(14);
		    var pn  = target.getParent('tr').getElement('td.adminProjectNameCell').get('html');
		    if (ref.project_users[pid] != undefined) {
			ref.project_users[pid].each(function(un, idx) {
			    mbox_content.getElement("input[value='"+un+"']").set('checked', 'checked');
			});
		    }
		    mbox_content.getElement("input[name='project_id']").set('value', pid);
		    var mbox = new mBox.Modal({
			title: 'Benutzergruppe für Projekt "'+pn+'" bearbeiten',
			content: mbox_content,
		    });
		    new mForm.Submit({
			form: mbox_content.getElement('form'),
			timer: 0,
			showLoader: true,
			onComplete: function(response) {
			    response = JSON.decode(response);
			    if(response.success) {
				mbox.close();

				ref.project_users[pid] = mbox_content.
				    getElements("input[type='checkbox']:checked").map(
				    function(checkbox, idx) {
					return checkbox.get('value');
				    }
				);
				ref.updateProjectUserInfo();
				
				new mBox.Notice({
				    content: 'Benutzergruppe geändert',
				    type: 'ok',
				    position: {x: 'right'}
				});
			    } else {
				new mBox.Modal({
				    'title': 'Änderungen speichern nicht erfolgreich',
				    content: 'Ein unerwarteter Fehler ist aufgetreten. Bitte kontaktieren Sie einen Administrator.'
				}).open();
			    }
			}
		    });
		    mbox.open();
		}
	    }
	);
    },

    updateProjectUserInfo: function() {
	Object.each(this.project_users, function(ulist, pid) {
	    var tr = $('project_'+pid);
	    if(tr != undefined) {
		tr.getElement('td.adminProjectUsersCell').set('html', ulist.join());
	    }
	});
    }
}


var tagset_editor = {
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
	    textarea.set('html', '');
	    // fetch tag list and perform a quick and dirty analysis:
	    var request = new Request.JSON(
		{url:'request.php',
		 onSuccess: function(data, text) {
		     var postags = new Array();
		     var output = tagsetname + " (ID: " + tagset + ") has ";
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
		     textarea.set('html', output);
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
		nav.showSpinner({message: 'Importiere Tagset...'});
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
		    $('adminImportPopup').getElement('p').set('html', message);
		    $('adminImportPopup').getElement('textarea').set('html', textarea);
		    message = 'adminImportPopup';
		}
		new mBox.Modal({
		    title: title,
		    content: message,
		    closeOnBodyClick: false,
		    buttons: [ {title: "OK"} ]
		}).open();

		nav.hideSpinner();
            }
	});

    }
}

// ***********************************************************************
// ********** DOMREADY BINDINGS ******************************************
// ***********************************************************************

window.addEvent('domready', function() {
    user_editor.initialize();
    project_editor.initialize();
    tagset_editor.initialize();
});


//    var mask = new Mask();
//    mask.show();
