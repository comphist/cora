// ***********************************************************************
// ********** DOMREADY BINDINGS ******************************************
// ***********************************************************************

window.addEvent('domready', function() {
    var ref;
    var eus = $('editUserSettings');

    if(eus!==null && typeof edit!="undefined") {
	ref = edit;

	/* Initialize editor settings tab */
	new Form.Request($('editUserSettings'),'',{
            resetForm: false,
            extraData: {'do': 'saveEditorUserSettings'},
            onSuccess: function(){
		var cl, pl, dls, em, np;
		cl = eus.getElement('input[name="contextLines"]').get('value').toInt();
		pl = eus.getElement('input[name="noPageLines"]').get('value').toInt();
		userdata.contextLines = cl;
		userdata.noPageLines = pl;
		em = ref.editorModel;
		if (em !== null) {
		    /* re-render page navigation panel, because page
		     * numbers will likely have changed; also,
		     * calculate page which contains the line that was
		     * the first displayed line before the change,
		     * then navigate to that one */
		    dls = em.displayedLinesStart;
		    np = ((dls+1) / (pl-cl)).ceil();
		    em.renderPagesPanel(np);
                    em.displayPage(np);
		    gui.changeTab('edit');
		}
		new mBox.Notice({
		    type: 'ok',
		    content: 'Änderungen übernommen.',
		    position: { x: 'right' }
		});
            },
            onFailure: function(){
		alert('Error occured while saving');
            }
	});
	
	
	// validate input -- change to MooTools's Form.Validator some day?
	eus.getElement("input[type='submit']").addEvent(
	    'click',
	    function(e) {
		var new_cl = Number.from(eus.getElement('input[name="contextLines"]').get('value'));
		var new_pl = Number.from(eus.getElement('input[name="noPageLines"]').get('value'))
		if (new_cl==null || new_pl==null) {
		    alert("Fehler: Es dürfen nur Zahlen eingegeben werden.");
		    e.stop();
		    return;
		}
		if (new_cl>=new_pl) {
		    alert("Fehler: Anzahl überlappender Zeilen muss kleiner sein als Anzahl der Zeilen pro Seite.");
		    e.stop();
		    return;
		}
		if (new_pl>50) {
		    var doit = confirm("Warnung: Eine hohe Anzahl an Zeilen pro Seite kann zur Folge haben, dass der Seitenaufbau sehr langsam wird bzw. der Browser für längere Zeit nicht mehr reagiert.");
		    if (!doit) { e.stop(); return; }
		}
	    }
	);
    
	/* Hiding columns */
	var eshc = $('editorSettingsHiddenColumns');
	userdata.hiddenColumns.split(",").each(function(value){
	    eshc.getElements('input[value="'+value+'"]').set('checked', false);
	    $('editTable').getElements(".editTable_"+value).hide();
	});
	
	eshc.addEvent(
	    'change:relay(input)',
	    function(event, target) {
		var checked = target.get('checked');
		var value = target.get('value');
		if (checked) {
		    $('editTable').getElements(".editTable_"+value).show();
		    if(edit!=null && edit.editorModel!=null && edit.editorModel!=undefined) {
			edit.editorModel.forcePageRedraw();
		    }
		    userdata.hiddenColumns = userdata.hiddenColumns.replace(value+",","");
		} else {
		    $('editTable').getElements(".editTable_"+value).hide();
		    userdata.hiddenColumns += value + ",";
		}
		new Request({url: 'request.php'}).get(
		    {'do': 'setUserEditorSetting',
		     'name': 'columns_hidden',
		     'value': userdata.hiddenColumns}
		);
	    }
	);

	var esia = $('editorSettingsInputAids');
	esia.getElement('input[name="show_error"]').set('checked', userdata.showInputErrors);
	esia.addEvent(
	    'change:relay(input)',
	    function(event, target) {
		var checked = target.get('checked');
		var value = target.get('value');
		userdata[value] = checked;
		if(value=="show_error") {
		    userdata.showInputErrors = checked;
		    if (ref.editorModel!=null) {
			ref.editorModel.updateShowInputErrors();
		    }
		}
		new Request({url: 'request.php'}).get(
		    {'do': 'setUserEditorSetting',
		     'name': value,
		     'value': checked ? 1 : 0}
		);
	    }
	);
    }

    /* Showing tooltips */
    var general = $('generalSettings');
    general.getElement('input[name="showTooltips"]').set('checked', userdata.showTooltips);

    general.addEvent(
	'change:relay(input)',
	function(event, target) {
	    var checked = target.get('checked');
	    var value = target.get('value');
	    userdata[value] = checked;
	    new Request({url: 'request.php'}).get(
		{'do': 'setUserEditorSetting',
		 'name': value,
		 'value': checked ? 1 : 0}
	    );
	}
    );

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

});
