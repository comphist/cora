
// ***********************************************************************
// ********** GLOBALE VARIABLEN ******************************************
// ***********************************************************************

var debugMode = false;

var fileTagset = {
    pos: null,
    morph: null,
    posHTML: "",
    morphHTML: {}
};

// ***********************************************************************
// ********** CLASS FILE ******************************************
// ***********************************************************************

var file = {
    
    initialize: function(){
        this.formSave('newFileImportForm');
    },
    
    formSave: function(form){
        if (debugMode) { return; }

        var ref = this;
	var spinner;
        var iFrame = new iFrameFormRequest(form,{
            onFailure: function(xhr) {
		// never fires?
       		alert(lang_strings.dialog_save_unsuccessful + " " +
       		      lang_strings.dialog_server_returned_error + "\n\n" +
       		      xhr.responseText);
       	    },
	    onRequest: function(){
		$('overlay').show();
		spinner = new Spinner($('overlay'),
				      {message: "Importiere Daten..."});
		spinner.show();
	    },
	    onComplete: function(response){
		var title="", message="", textarea="";
		response = JSON.decode(response);
		
		if(response==null){
		    title = "Datei-Import fehlgeschlagen";
		    message = "Beim Hinzufügen der Datei ist ein unbekannter Fehler aufgetreten.";
		}
		else if(!response.success){
		    title = "Datei-Import fehlgeschlagen";
		    message  = "Beim Hinzufügen der Datei ";
		    message += response.errors.length>1 ? "sind " + response.errors.length : "ist ein";
		    message += " Fehler aufgetreten:";

		    for(var i=0;i<response.errors.length;i++){
			textarea += response.errors[i] + "\n";
		    }
		} 
		else { 
		    title = "Datei-Import erfolgreich";
		    message = "Die Datei wurde erfolgreich hinzugefügt.";
		    if((typeof response.warnings !== "undefined") && response.warnings.length>0) {
			message += " Das System lieferte ";
			message += response.warnings.length>1 ? response.warnings.length + " Warnungen" : "eine Warnung";
			message += " zurück:";

			for(var i=0;i<response.warnings.length;i++){
			    textarea += response.warnings[i] + "\n";
			}
		    }

		    $(form).getElements('input[type=text]').set('value','');
		    $(form).getElements('input[type=file]').set('value','');
		    ref.listFiles();
                }

		if(textarea!='') {
		    $('fileImportPopup').getElement('p').set('html', message);
		    $('fileImportPopup').getElement('textarea').set('html', textarea);
		    message = 'fileImportPopup';
		}
		new mBox.Modal({
		    title: title,
		    content: message,
		    buttons: [ {title: "OK"} ]
		}).open();

		spinner.hide();
		$('overlay').hide();                
            }
	});
    },
    
    copyTagset: function(){
        
    },
    
    /* Function: preprocessTagset

       Parses tagset data and builds HTML code for drop-down boxes.
    */
    preprocessTagset: function(data) {
	var posHTML = "";
	var tags = $H(data.tags);
	var attribs = $H(data.attribs);
	var taglist = Object.values(tags);

	taglist.sort(function(tag_a,tag_b) {
	    var a = tag_a.shortname;
	    var b = tag_b.shortname;
	    return a < b ? -1 : a > b ? 1 : 0;
	});

	fileTagset.pos = tags;
	fileTagset.attribs = attribs;
	fileTagset.morph = {};

	taglist.each(function(tag) {
	    posHTML += "<option>";
	    posHTML += tag.shortname;
	    posHTML += "</option>";

	    if (tag.link && tag.link.length > 0) {
		var combinations = null;
		// ensure the correct order of morphology tags
		tag.link.sort(function(link_a,link_b) {
		    var a = attribs[link_a].shortname;
		    var b = attribs[link_b].shortname;
		    return a < b ? -1 : a > b ? 1 : 0;
		});
		// build all valid combinations of morphology tags
		tag.link.each(function(link) {
		    if (!combinations) {
			combinations = attribs[link].val;
		    } else {
			var updated = new Array();
			attribs[link].val.each(function(val) {
			    combinations.each(function(text) {
				/* stars are replaced for the sort to
				 * put them after alphabetic entities */
				updated.push((text + "." + val).replace(/\*/g,"ZZZ@"));
			    });
			});
			combinations = updated;
		    }
		});
		
		// build HTML tags
		fileTagset.morph[tag.shortname] = new Array();
		var morphHTML = "<option>-----------</option>";
		combinations.sort();
		combinations.each(function(combi) {
		    combi = combi.replace(/ZZZ@/g, "*");
		    fileTagset.morph[tag.shortname].push(combi);
		    morphHTML += "<option>";
		    morphHTML += combi;
		    morphHTML += "</option>";
		});

		fileTagset.morphHTML[tag.shortname] = morphHTML;
	    } else {
		fileTagset.morph[tag.shortname] = new Array();
		fileTagset.morphHTML[tag.shortname] = "<option>--</option>";
	    }
	});
	fileTagset.posHTML = posHTML;
    },
    
    openFile: function(fileid) {
        var ref = this;
        
        var lock = new Request.JSON({
            url:'request.php',
    	    onSuccess: function(data, text) {
    		if(data.success) {
        	    var request = new Request.JSON({
        		url: 'request.php',
        		onComplete: function(fileData) {        		         
        		    if(fileData.success){
       				// load tagset
        		        var afterLoadTagset = function() {
				    // code that depends on the tagsets being fully loaded
				    edit.editorModel = new EditorModel(fileid, fileData.maxLinesNo, fileData.lastEditedRow, fileData.lastPage);
				    $('editPanelDiv').show();
				    default_tab = 'edit';
				    changeTab('edit');
				};
				
        		        new Request.JSON({
        		            url: "request.php",
        		            async: true,
				    method: 'get',
				    data: {'do':'fetchTagset','name':fileData.data.tagset},
        		            onComplete: function(response){
					ref.preprocessTagset(response);
					afterLoadTagset();
        		            }
        		        }).send();
				
				$('currentfile').set('text',fileData.data.file_name);
				ref.listFiles();
			    }
        		}
        	    }).get({'do': 'openFile', 'fileid': fileid});
    		} else {
    		    var msg = lang_strings.dialog_file_locked_error +
    		        ": " + data.lock.locked_by + ", " +
    			data.lock.locked_since;
    		    alert(msg);
    		}
    	    }
    	});
        lock.get({'do': 'lockFile', 'fileid': fileid});
    },
    
    closeFile: function(fileid){        
        var ref = this;
	var emf = (edit.editorModel!=null && edit.editorModel.fileId==fileid);

	if (emf) {
	    if (edit.editorModel.confirmClose()) {
		$('overlay').show();
		new Request({
		    url: "request.php",
		    onSuccess: function(data) {
			if (!data) {
			    console.log("Error closing file?");
			}
			ref.listFiles();
			edit.editorModel = null;
			default_tab = 'file';
			changeTab(default_tab);
			$('menuRight').hide();
			$('editTable').hide();
			$('editPanelDiv').hide();
			$('currentfile').set('text','');
			$('overlay').hide();
		    }
		}).get({'do': 'unlockFile', 'fileid': fileid});
	    }
	} else {
	    var doit = confirm("Sie sind dabei, eine Datei zu schließen, die aktuell nicht von Ihnen bearbeitet wird. Falls jemand anderes diese Datei zur Zeit bearbeitet, könnten Datenverluste auftreten.");
	    if (doit) {
		new Request({
		    url:"request.php",
		    onSuccess: function(data){
			if(data){
			    ref.listFiles();
			}
		    }
		}).get({'do': 'unlockFile', 'fileid': fileid});
	    }
	}
    },
    
    listFiles: function(){
        var ref = this;
        var files = new Request.JSON({
            url:'request.php',
    		onSuccess: function(filesArray, text) {
                $$('#fileList tr[class!=fileTableHeadLine]').dispose();

                filesArray.each(function(file){
                    $('fileList').adopt(ref.renderTableLine(file));
                })
            }
    	});
        files.get({'do': 'listFiles'});
        
    },
    
    renderTableLine: function(file){
	/* TODO: isn't it completely unnecessary to completely
	 * re-create the whole table each time a change occurs?
	 * couldn't this be done more efficiently? */
	var ref = this;
        var opened = file.opened ? 'opened' : '';
	var displayed_name = '';
	if(file.sigle!=null && file.sigle!=''){
	    displayed_name = '[' + file.sigle + '] ';
	}
	displayed_name += file.file_name;
        var tr = new Element('tr',{id: 'file_'+file.file_id, 'class': opened});
        if((file.byUser == userdata.name) || userdata.admin){
            var delTD = new Element('td',{ html: '<img src="gui/images/proxal/delete.ico" />', 'class': 'deleteFile' });
            delTD.addEvent('click', function(){ ref.deleteFile(file.file_id,file.file_name); } );
            tr.adopt(delTD);
        } else {
	    tr.adopt(new Element('td'));
	}
        var chkImg = '<img src="gui/images/chk_on.png" />';
        tr.adopt(new Element('td',{'class': 'filename'}).adopt(new Element('a',{ html: displayed_name }).addEvent('click',function(){ ref.openFile(file.file_id); })));
        tr.adopt(new Element('td',{ 'class': 'tagStatusPOS', html: (file.POS_tagged == 1) ? chkImg : '--' }));
        tr.adopt(new Element('td',{ 'class': 'tagStatusMorph', html: (file.morph_tagged == 1) ? chkImg : '--' }));
        tr.adopt(new Element('td',{ 'class': 'tagStatusNorm', html: (file.norm == 1) ? chkImg : '--' }));
        tr.adopt(new Element('td',{ html: file.lastMod }));
        tr.adopt(new Element('td',{ html: file.lastModUser }));                    
        tr.adopt(new Element('td',{ html: file.created }));
        tr.adopt(new Element('td',{ html: file.byUser }));
        tr.adopt(new Element('td',{'class':'exportFile'}).adopt(new Element('a',{ html: 'export', 'class': 'exportFileLink' }).addEvent('click', function(){ ref.exportFile(file.file_id); } )));
        if((file.opened == userdata.name ) || (opened && userdata.admin)){
            tr.adopt(new Element('td',{'class':'closeFile'}).adopt(new Element('a',{ html: 'close', 'class': 'closeFileLink' }).addEvent('click', function(){ ref.closeFile(file.file_id); } )));
        } else {
	    tr.adopt(new Element('td'));
	}
        return tr;
    },
    
    deleteFile: function(fileid,filename){
        var ref = this;

        var dialog = lang_strings.dialog_box_confirm_file_delete.substitute({file: filename}); 
        if(!confirm(dialog))
            return;
        
        var req = new Request(
    	    {'url': 'request.php?do=deleteFile',
    	     'async': false,
    	     'data': 'file_id='+fileid,
    	     onFailure: function(xhr) {
    		 alert(lang_strings.dialog_error_caps + ": " +
    		       lang_strings.dialog_server_returned_error + "\n\n" + xhr.responseText);
    	     },
    	     onSuccess: function(data, xml) {    	         
    		     ref.listFiles();
    	     }
    	    }
    	).post();
    },

    exportFile: function(fileid){
	window.location = 'request.php?do=exportFile&fileId='+fileid;
    }
};


// ***********************************************************************
// ********** DOMREADY BINDINGS ******************************************
// ***********************************************************************

window.addEvent('domready', function() {
    file.initialize();    
                    
    $$('div.fileViewRefresh img').addEvent('click',function(e){ e.stop(); file.listFiles() });
    
    file.listFiles();
    if(userdata.currentFileId)
        file.openFile(userdata.currentFileId);
});
