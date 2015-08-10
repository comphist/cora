/* File: settings.js

   Defines the global cora.settings variable, which manages user-specific
   settings and controls the "settings" tab.
*/
cora.settings = {
    lineSettingsDiv: 'editLineSettings',
    columnVisibilityDiv: 'editorSettingsHiddenColumns',
    textPreviewDiv: 'editorSettingsTextPreview',
    inputAidsDiv: 'editorSettingsInputAids',

    initialize: function() {
        this._activateLineSettingsDiv();
        this._activateColumnVisibilityDiv();
        this._activateTextPreviewDiv();
        this._activateInputAidsDiv();
        this._activatePasswordChangeForm();
    },

    /* Function: _activateLineSettingsDiv

       Activates the form request for the line number/context lines setting in
       the settings tab.
     */
    _activateLineSettingsDiv: function() {
        var div = $(this.lineSettingsDiv);
        if (div === null || typeof(div) === "undefined")
            return;

	// validate input
	div.getElement("input[type='submit']").addEvent(
	    'click',
	    function(e) {
		var cl = div.getElement('input[name="contextLines"]').get('value').toInt();
		var pl = div.getElement('input[name="noPageLines"]').get('value').toInt();
		if (isNaN(cl) || isNaN(pl)) {
                    gui.showNotice('error', "Bitte nur Zahlen eingeben!");
		    e.stop(); return;
		}
		if (cl >= pl) {
                    gui.showNotice('error', "Anzahl überlappender Zeilen muss kleiner sein als Anzahl der Zeilen pro Seite.");
		    e.stop(); return;
		}
		if (pl > 50) {
                    // TODO: change to gui.confirm --- but doesn't work with Form.Request
		    var doit = confirm("Warnung: Eine hohe Anzahl an Zeilen pro Seite kann zur Folge haben, dass der Seitenaufbau sehr langsam wird bzw. der Browser für längere Zeit nicht mehr reagiert.");
		    if (!doit) { e.stop(); return; }
		}
	    }
	);

        // request
        new Form.Request(div, '', {
            resetForm: false,
            extraData: {'do': 'saveEditorUserSettings'},
            onSuccess: function(){
		var cl, pl, em, range;
		em = cora.editor;
                if (em !== null)
                    range = em.dataTable.pages.getRange(em.dataTable.pages.activePage);
		cl = div.getElement('input[name="contextLines"]').get('value').toInt();
		pl = div.getElement('input[name="noPageLines"]').get('value').toInt();
                this.set('contextLines', cl).set('noPageLines', pl);
		if (em !== null) {
                    em.dataTable.pages.update().setPageByLine(range.from).render();
		    gui.changeTab('edit');
		}
                gui.showNotice('ok', 'Änderungen übernommen.');
            }.bind(this),
            onFailure: function(){
                gui.showNotice('error', 'Änderungen nicht übernommen.');
            }
	});
    },

    /* Function: _activateColumnVisibilityDiv
     */
    _activateColumnVisibilityDiv: function() {
	var div = $(this.columnVisibilityDiv);
	this.get('hiddenColumns').split(",").each(function(value) {
	    div.getElements('input[value="'+value+'"]').set('checked', false);
	});
	div.addEvent(
	    'change:relay(input)',
	    function(event, target) {
		var checked = target.get('checked');
		var value = target.get('value');
                var setting = this.get('hiddenColumns');
		if(cora.editor !== null) {
		    cora.editor.setColumnVisibility(value, checked);
		}
		if (checked) {
                    this.set('hiddenColumns', setting.replace(value+",",""));
		} else {
		    this.set('hiddenColumns', setting + value + ",");
		}
		new Request({url: 'request.php'}).get(
		    {'do': 'setUserEditorSetting',
		     'name': 'columns_hidden',
		     'value': this.get('hiddenColumns')}
		);
	    }.bind(this)
	);
    },

    /* Function: _activateTextPreviewDiv
     */
    _activateTextPreviewDiv: function() {
        var elem, div = $(this.textPreviewDiv);
        elem = div.getElement('input[value="'+this.get('textPreview')+'"]');
        if(elem !== null) {
            elem.set('checked', 'yes');
        }
        div.addEvent(
            'change:relay(input)',
            function(event, target) {
                var value = div.getElement('input:checked').get('value');
                this.set('textPreview', value);
                if (cora.editor !== null) {
                    cora.editor.horizontalTextView
                        .setPreviewType(value)
                        .redraw();
                }
		new Request({url: 'request.php'}).get(
		    {'do': 'setUserEditorSetting',
		     'name': 'text_preview',
		     'value': value}
		);
            }.bind(this)
        );
    },

    /* Function: _activateInputAidsDiv
     */
    _activateInputAidsDiv: function() {
	var div = $(this.inputAidsDiv);
	div.getElement('input[name="show_error"]')
            .set('checked', this.get('showInputErrors'));
	div.addEvent(
	    'change:relay(input)',
	    function(event, target) {
		var checked = target.get('checked');
		var value = target.get('value');
                this.set(value, checked);
		if(value == "show_error") {
                    this.set('showInputErrors', checked);
		    if (cora.editor !== null)
			cora.editor.updateShowInputErrors();
		}
		new Request({url: 'request.php'}).get(
		    {'do': 'setUserEditorSetting',
		     'name': value,
		     'value': checked ? 1 : 0}
		);
	    }.bind(this)
	);
    },

    /* Function: _activatePasswordChangeForm
     */
    _activatePasswordChangeForm: function() {
        /* Change password */
        var pwch = new mBox.Modal({
	    title: 'Passwort ändern',
	    content: 'changePasswordFormDiv',
	    attach: 'changePasswordLink'
        });
        new mForm.Submit({
	    form: 'changePasswordForm',
	    ajax: true,
	    validate: true,
	    blinkErrors: true,
	    bounceSubmitButton: false,
	    onSubmit: function() {
	        var pw1 = this.form.getElement('input[name="newpw"]').get('value');
	        var pw2 = this.form.getElement('input[name="newpw2"]').get('value');
	        if (pw1=="" && pw2=="") {
		    // mForm deals with this automatically ...
		    this.form.getElements('.error_text').hide();
	        }
	        else if (pw1==pw2) {
		    this.blockSubmit = false;
		    this.form.getElements('.error_text').hide();
	        } else {
		    this.blockSubmit = true;
		    this.showErrors([
		        this.form.getElement('input[name="newpw"]'),
		        this.form.getElement('input[name="newpw2"]')
		    ]);
		    $('changePasswordErrorNew').show();
	        }
	    },
	    onComplete: function(response) {
	        response = JSON.decode(response);
	        if(response.success) {
		    pwch.close();
		    form.reset($('changePasswordForm'));
		    new mBox.Notice({
		        content: 'Passwort geändert',
		        type: 'ok',
		        position: {x: 'right'}
		    });
	        } else if (response.errcode!=null && response.errcode=="oldpwmm") {
		    $('changePasswordErrorOld').show();
		    this.showErrors(this.form.getElement('input[name="oldpw"]'));
	        }
	    }
        });
    },

    /* Function: get

       Retrieve value of a specific user setting.
     */
    get: function(name) {
        return userdata[name];
    },

    /* Function: set

       Set value of a specific user setting.
     */
    set: function(name, value) {
        userdata[name] = value;
        return this;
    },

    /* Function: isColumnVisible

       Checks whether a given column is set to be visible in the settings tab.
     */
    isColumnVisible: function(name) {
        var elem = $(this.columnVisibilityDiv).getElement('input#eshc-'+name);
        if (elem != null)
            return elem.get('checked');
        return true;
    },

    /* Function: setColumnActive

       Sets a given column to active or inactive, determining whether it is
       shown in the settings tab or not.
     */
    setColumnActive: function(name, active) {
        var div = $(this.columnVisibilityDiv);
        if(active) {
            div.getElements('input#eshc-'+name).show();
            div.getElements('label[for="eshc-'+name+'"]').show();
        } else {
            div.getElements('input#eshc-'+name).hide();
            div.getElements('label[for="eshc-'+name+'"]').hide();
        }
    }
};

cora.isAdmin = function() {
    var admin = cora.settings.get('admin');
    return (Boolean(admin) && admin !== '0');
};

// ***********************************************************************
// ********** DOMREADY BINDINGS ******************************************
// ***********************************************************************

window.addEvent('domready', function() {
    cora.settings.initialize();
});
