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
 * @note Depends on global variable @c lang_strings.
 *
 * @todo This is currently not strictly object-oriented, as the global
 * variable tagset_editor is referenced by the classes. Could this be
 * changed to an object property?
 */

var tagset_editor = null;

// Input forms could make use of MooTools Form.Validator;
// currently hand-coded

var CoreTag = new Class({
    id: null,
    shortname: null,
    description: null,
    tagBox: null,
    descBox: null,
    element: null,
    modified: false,
    newlyCreated: false,
    initialize: function(id, shortname, description) {
	this.id = id;
	this.shortname = shortname;
	this.description = description;
	this.element   = new Element( "tr", {
	    "class": "editRow",
	    id: "editRow:" + this.id
	});
    },
    onDescChange: function() {
	this.description = this.descBox.get('value');
	this.modified = true;
    },
    onTagBlur: function() {
	if (this.tagBox.get('value') === "") {
	    this.tagBox.addClass("error");
	} else {
	    this.tagBox.removeClass("error");
	}
    },
    render: function() {
	this.element.empty();

	var delLink = new Element("a", {
	    "id": this.id + "Del",
	    "html": '<img src="gui/images/proxal/delete.ico" />'
	});
	delLink.addEvent('click', function() {
	    tagset_editor.deleteTag(this);
	    return false;
	}.bind(this));

	this.tagBox    = new Element( "input", {
	    "type": "text",
	    "name": this.id + "Id",
	    "value": this.shortname,
	    "size": 10,
	    "maxlength": 255
	});
	this.tagBox.addEvent('change', this.onTagChange.bind(this));
	this.tagBox.addEvent('blur', this.onTagBlur.bind(this));

	this.descBox   = new Element( "input", {
	    "type": "text",
	    "name": this.id + "Desc",
	    "value": this.description,
	    "size": 50
	});
	this.descBox.addEvent('change', this.onDescChange.bind(this));

	this.element.grab(new Element("td").grab( delLink ));
	this.element.grab(new Element("td").grab( this.tagBox ));
	this.element.grab(new Element("td").grab( this.descBox ));
    },
});

var Tag = new Class({    
    Extends: CoreTag,
    links: [],
    linkForm: null,
    initialize: function(id, shortname, description) {

	this.parent(id, shortname, description);
	this.linkForm = new Element("td");
    },
    addLink: function(attrib) {
	this.links.push( attrib );
    },
    onTagChange: function() {
	var newname = this.tagBox.get('value');
	var no_collision = tagset_editor.tags.every( function(item, index) {
	    return (item.shortname !== newname);
	});

	if (no_collision) {
	    this.shortname = newname;
	    this.modified = true;
	}
	else {
	    var errmsg = lang_strings.dialog_error_caps + ": "
		+ newname + ": "
		+ lang_strings.dialog_tag_duplicate + "!";
	    alert(errmsg);
	    this.tagBox.set('value', this.shortname);
	}
    },
    onLinkChange: function(checkbox) {
	checkbox.checked ?
	    this.links.push(checkbox.value) :
	    this.links.erase(checkbox.value);
	this.modified = true;
    },
    toJSON: function() {
	var arr = {
	    id: this.id,
	    shortname: this.shortname,
	    desc: this.description,
	    link: this.links,
	    type: 'tag'
	};
	return arr;
    },
    render: function() {
	    this.parent();

	    // create checkboxes for all attributes
	    this.linkForm.empty();
	    tagset_editor.attribs.each( function(item, index) {
	        var checkbox = new Element("input", {
		        "type": "checkbox",
		        "name": this.id + "Links",
		        "value": item.id
	        });
	        checkbox.addEvent('click', function() {this.onLinkChange(checkbox);}.bind(this));
	        this.linkForm.grab(checkbox);
	        this.linkForm.appendText(" " + item.shortname + " ");
	    }, this);

	// select the applicable attributes
	this.links.each( function(item, index) {
	    var checkbox = this.linkForm.getElement('input[value="'+item+'"]');
	    if (checkbox !== null) {
		checkbox.checked = true;
	    }
	}, this);

	this.element.grab( this.linkForm );
    }
});

var Attribute = new Class({
    Extends: CoreTag,
    values: [],
    valueBox: null,
    initialize: function(id, shortname, description) {
	this.parent(id, shortname, description);
    },
    addValue: function(value) {
	this.values.push( value );
    },
    onTagChange: function() {
	var newname = this.tagBox.get('value');
	var no_collision = tagset_editor.attribs.every( function(item, index) {
	    return (item.shortname !== newname);
	});

	if (no_collision) {
	    this.shortname = newname;
	    this.modified = true;
	    tagset_editor.refreshTags();
	}
	else {
	    var errmsg = lang_strings.dialog_error_caps + ": "
		+ newname + ": "
		+ lang_strings.dialog_attrib_duplicate + "!";
	    alert(errmsg);
	    this.tagBox.set('value', this.shortname);
	}
    },
    onValueChange: function() {
	this.values = this.valueBox.get('value').split(',');
	this.modified = true;
    },
    toJSON: function() {
	var arr = {
	    id: this.id,
	    shortname: this.shortname,
	    desc: this.description,
	    val: this.values,
	    type: 'attrib'
	};
	return arr;
    },
    render: function() {
	this.parent();

	this.valueBox  = new Element( "input", {
	    "type": "text",
	    "name": this.id + "Val",
	    "value": this.values.toString(),
	    "size": 50
	});
	this.valueBox.addEvent('change', this.onValueChange.bind(this));

	this.element.grab(new Element("td").grab(this.valueBox));
    }
});

/*
  Class: TagsetEditor
  The tagset editor.
*/
var TagsetEditor = new Class({
    
    Implements: [Options],
    
    tags: [],
    attribs: [],
    highestId: 0,
    deleted: [],
    tagset: null,
    element: null,
    tagEditor: null,
    attribEditor: null,
    disposed: false,
    
    options:{
        elementId: $('editTagset'),
        tagEditorId: $('editTags'),
        attribEditorId: $('editAttribs')
    },
    /*
      Constructor: TagsetEditor
      Initializes the tagset editor.
    */
    initialize: function(data,options) {
    
    this.setOptions(options);
        
    this.element = this.options.elementId;
    this.tagEditor = this.options.tagEditorId;
    this.attribEditor = this.options.attribEditorId;

    if(!this.element) this.element = $('editTagset');
    if(!this.tagEditor) this.tagEditor = $('editTags');
    if(!this.attribEditor) this.attribEditor = $('editAttribs');

    
    // this.element = $('editTagset');
    // this.tagEditor = $('editTags');
    // this.attribEditor = $('editAttribs');
	$H(data.tags).each(function (tag, id) {
	    var tagobj = new Tag(id, tag.shortname, tag.desc);
	    if(tag.link){
	        tag.link.each(function (link) {
		    tagobj.addLink(link);
	        });
            }
	    this.tags.push(tagobj);
	    id = Number.from(id);
	    if (id > this.highestId) {
		this.highestId = id;
	    }
	}.bind(this));
	$H(data.attribs).each(function (attrib, id) {
	    var attobj = new Attribute(id, attrib.shortname, attrib.desc);
	    if(attrib.val) {
		attrib.val.each(function (value) {
		    attobj.addValue(value);
		});
	    }
	    this.attribs.push(attobj);
	    id = Number.from(id);
	    if (id > this.highestId) {
		this.highestId = id;
	    }
	}.bind(this));
	var shortname_sort = function(tag_a,tag_b) {
	    var a = tag_a.shortname;
	    var b = tag_b.shortname;
	    return a < b ? -1 : a > b ? 1 : 0;
	};
	this.tags.sort(shortname_sort);
	this.attribs.sort(shortname_sort);
    },
    /*
      Function: refreshTags
      Refreshes tags.
     */
    refreshTags: function() {
	this.tags.each(function (tag) {
	    tag.render();
	});
    },
    newTag: function() {
	this.highestId++;
	var tagobj = new Tag(this.highestId, "", "");
	tagobj.newlyCreated = true;
	this.tags.push(tagobj);
	tagobj.render();
	this.tagEditor.getElement('.newTagRow').grab(tagobj.element, 'before');
	tagobj.tagBox.focus();
	return false;
    },
    addNewTag: function(shortname){
    	this.highestId++;
    	var tagobj = new Tag(this.highestId, shortname, "");
    	tagobj.newlyCreated = true;
    	this.tags.push(tagobj);
    	tagobj.render();
    	this.tagEditor.getElement('.newTagRow').grab(tagobj.element, 'before');
    	tagobj.tagBox.focus();
    	return false;        
    },
    newAttrib: function() {
	this.highestId++;
	var attobj = new Attribute(this.highestId, "", "");
	attobj.newlyCreated = true;
	this.attribs.push(attobj);
	attobj.render();
	this.attribEditor.getElement('.newAttribRow').grab(attobj.element, 'before');
	attobj.tagBox.focus();
	return false;
    },
    deleteTag: function(tag) {
	if (this.tags.contains(tag)) {
	    this.tags.erase(tag);
	} else {
	    this.attribs.erase(tag);
	    this.tags.each(function (t) {
		t.links.erase(tag.id);
		t.modified = true;
	    });
	    this.refreshTags();
	}
	// tag needs to be deleted from database, but only if it
	// hasn't been newly created (so it doesn't yet exist there)
	if (!tag.newlyCreated) {
	    this.deleted.push(tag.id);
	}
	tag.element.dispose();
    },
    render: function() {

	    this.tags.each(function (tag) {
	        tag.render();
	        this.tagEditor.getElement('.newTagRow').grab(tag.element, 'before');
	    }.bind(this));
	    this.attribs.each(function (attrib) {
	        attrib.render();
	        this.attribEditor.getElement('.newAttribRow').grab(attrib.element, 'before');
	    }.bind(this));
    
        if(newTag = this.tagEditor.getElement('.newTagRow a'))
    	    newTag.addEvent('click', this.newTag.bind(this));
    	if(newAttrib = this.attribEditor.getElement('.newAttribRow a'))
    	    newAttrib.addEvent('click', this.newAttrib.bind(this));

	    this.element.show();
    },
    
    dispose: function() {
	this.tags.each(function (tag) {
	    tag.element.dispose();
	});
	this.tags.empty();
	this.attribs.each(function (attrib) {
	    attrib.element.dispose();
	});
	this.attribs.empty();
	this.tagEditor.getElement('.newTagRow a').removeEvents();
	this.attribEditor.getElement('.newAttribRow a').removeEvents();

	this.element.hide();
	this.disposed = true;
    }
});


/* Global functions */

var save_in_progress = false;

function tagsetOnChange() {
    var tagset = $('tagset-select').getSelected().getProperty('value').toString();
    $('tagsetLastModified').set('text', $('tagsetLastModified_'+tagset).get('text'));
}

function editTagset() {
    var tagset = $('tagset-select').getSelected().getProperty('value').toString();
    var fullname = $('tagset-select').getSelected().get('text');
    var lock = new Request.JSON(
	{url:'request.php',
	 onSuccess: function(data, text) {
	     if (data.success) {
		 var request = new Request.JSON(
		     {url:'request.php',
		      onSuccess: function(tagsetArray, text) {
			      $('chooseTagset').hide();
			      $('tagsetName').set('text', fullname);
			      $('tagsetName').show();
			      if (tagset_editor !== null) {
			          tagset_editor.dispose();
			      }
			      tagset_editor = new TagsetEditor(tagsetArray);
			      tagset_editor.tagset = tagset;
 			      tagset_editor.render();
		      }
		     });
		 request.get({'do': 'fetchTagset', 'name': tagset});
	     } else {
		 var msg = lang_strings.dialog_file_locked_error +
		     ": " + data.lock.locked_by + ", " +
		     data.lock.locked_since;
		 alert(msg);
	     }
	 }
	});
    lock.get({'do': 'lockTagset', 'name': tagset});
}

function saveTagset() {
    if ($$('input.error').length > 0) {
	    alert(lang_strings.dialog_field_empty_save_error);
	return false;
    }    

    if(save_in_progress) return false;
    save_in_progress = true;

    var data = {
	    tagset: tagset_editor.tagset,
	    created: [],
	    modified: [],
	    deleted: tagset_editor.deleted
    };
    tagset_editor.tags.each(function (tag) {
    	if (tag.newlyCreated) {
    	    data.created.push(tag.toJSON());
    	}
    	else if (tag.modified) {
    	    data.modified.push(tag.toJSON());
    	}
    });
    tagset_editor.attribs.each(function (attrib) {
    	if (attrib.newlyCreated) {
    	    data.created.push(attrib.toJSON());
    	}
    	else if (attrib.modified) {
    	    data.modified.push(attrib.toJSON());
    	}
    });

    var request = new Request({
        url:'request.php?do=saveTagset',
	    async: 'false',
	    useSpinner: false, // fx can't be turned off ...
	    update: document.body,
	    spinnerOptions: {
	        message: lang_strings.dialog_save_in_progress,
	        fxOptions: {noFx: true}
	    },
	    onFailure: function(xhr) {
            alert(  lang_strings.dialog_save_unsuccessful + " " +
		            lang_strings.dialog_server_returned_error + "\n\n" +
		            xhr.responseText);
	    },
	    onSuccess: function(data, xml) {
            closeTagset();
            return true;
	    }
	});

    request.post(JSON.encode(data));

    save_in_progress = false;
    return false;
}

function discardTagset() {
    var close = confirm(lang_strings.dialog_box_confirm_discard);
    if (close) {
	    closeTagset();
	    return true;
    }
    return false;
}

function closeTagset() {
    if (tagset_editor===null || tagset_editor.disposed) { return; }

    var request = new Request({url:"request.php",async:false});
    request.get({'do': 'unlockTagset', 'name': tagset_editor.tagset});

    $('tagsetName').hide();
    tagset_editor.dispose();
    $('chooseTagset').show();
    return;
}

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
	    alert(lang_strings.dialog_error_caps + ": " +
		  lang_strings.login_un + ": " +
		  lang_strings.dialog_field_empty_error);
	    return false;
	}
	if (password === '') {
	    alert(lang_strings.dialog_error_caps + ": " +
		  lang_strings.login_pw + ": " +
		  lang_strings.dialog_field_empty_error);
	    return false;
	}
	if (password != controlpw) {
	    alert(lang_strings.dialog_error_caps + ": " +
		  lang_strings.dialog_password_mismatch);
	    return false;
	}

	// send request
	var request = new Request(
	    {'url': 'request.php?do=createUser',
	     'async': false,
	     'data': 'username='+username+'&password='+password,
	     onFailure: function(xhr) {
		 alert(lang_strings.dialog_error_caps + ": " +
		       lang_strings.dialog_server_returned_error + "\n\n" + xhr.responseText);
	     },
	     onSuccess: function(data, xml) {
		 var row = $$('.adminUserInfoRow')[0].clone();
		 row.set('id', 'User_'+username);
		 row.getElement('td.adminUserNameCell').set('text', username);
		 row.getElement('img.adminUserAdminStatus').hide();
		 row.getElement('img.adminUserNormStatus').show('inline');
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
	var dialog = lang_strings.dialog_box_confirm_delete_user.substitute({user: username});
	if (!confirm(dialog))
	    return;

	var request = new Request(
	    {'url': 'request.php?do=deleteUser',
	     'async': false,
	     'data': 'username='+username,
	     onFailure: function(xhr) {
		 alert(lang_strings.dialog_error_caps + ": " +
		       lang_strings.dialog_server_returned_error + "\n\n" + xhr.responseText);
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
		 alert(lang_strings.dialog_error_caps + ": " +
		       lang_strings.dialog_server_returned_error + "\n\n" + xhr.responseText);
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
	    alert(lang_strings.dialog_error_caps + ": " +
		  lang_strings.login_pw + ": " +
		  lang_strings.dialog_field_empty_error);
	    return false;
	}
	if (password != controlpw) {
	    alert(lang_strings.dialog_error_caps + ": " +
		  lang_strings.dialog_password_mismatch);
	    return false;
	}

	// send request
	var request = new Request(
	    {'url': 'request.php?do=changePassword',
	     'async': false,
	     'data': 'username='+username+'&password='+password,
	     onFailure: function(xhr) {
		 alert(lang_strings.dialog_error_caps + ": " +
		       lang_strings.dialog_server_returned_error + "\n\n" + xhr.responseText);
	     },
	     onSuccess: function(data, xml) {
		 alert(lang_strings.dialog_change_password_successful);
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
		     onSuccess: function(data, text) {
			 var pid = Number.from(text);
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

// ***********************************************************************
// ********** DOMREADY BINDINGS ******************************************
// ***********************************************************************

window.addEvent('domready', function() {
    $('saveChangesButton').addEvent('click', saveTagset);
    $('discardChangesButton').addEvent('click', discardTagset);
    $('editTagsetButton').addEvent('click', editTagset);
    user_editor.initialize();
    project_editor.initialize();
    $('logoutLink').addEvent('click', closeTagset);
});


//    var mask = new Mask();
//    mask.show();
