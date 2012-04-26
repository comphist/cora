
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

        ref = this;
        var iFrame = new iFrameFormRequest(form,{
            onFailure: function(xhr) {
       	     alert(lang_strings.dialog_save_unsuccessful + " " +
       		   lang_strings.dialog_server_returned_error + "\n\n" +
       		   xhr.responseText);
       	    },
	    onRequest: function(){
		
	    },
	    onComplete: function(response){
		response = JSON.decode(response);
		if(!response.status){
		    
		    if(response.locked){
			alert("Tagsset is locked");			            
		    } else {			            
			ref.tagsetName = response.tagsetName;
                        ref.unmatchedTags = response.data;
			ref.importUnmatchedTagError();			                                    
		    }
		} 
		else { 
		    $(form).getElements('input[type!=submit]').set('value','');
		    if($$('.addDataPopup'))
			$$('.addDataPopup').destroy();
		    $$('#main > div[class!=importErrorPopup]').setStyle('opacity',1);
		    ref.listFiles();
                    // ref.addFileToList();
                }
                
            }
	});
    },
    
    copyTagset: function(){
        
    },
    
    addNewData: function(link){
          var popup = $('addDataImportForm').clone().addClass('addDataPopup');
            

          popup.getElement('input[name=fileID]').set('value',link.getParent('tr').get('id').substr(5));
          popup.getElement('input[name=textName]').set('value',link.getParents('tr td[class=filename] a')[0].get('text'));
          // popup.getElement('input[name=tagset]').set('value',link.getParents('tr td[class=tagsetName]').get('text'));
          popup.getElement('input[name=tagType]').set('value',link.get('class').replace(/addData/,''));
          popup.getElement('input[type=button]').addEvent('click',function(){
              popup.destroy();
              $$('#main > div[class!=importErrorPopup]').setStyle('opacity',1);
          })
          var fileInput = popup.getElement('input[type=file]');
          fileInput.addEvent('change',function(){
              if(fileInput.get('value').length > 0 )
                  popup.getElement('input[type=submit]').erase('disabled');
            })
          this.formSave(popup);
            
          $$('#main > div[class!=importErrorPopup]').setStyle('opacity',0.5);            
          $('main').adopt(popup);
            
        
    },
    
    getNewTagsetForm: function(){
        var ref = this;
        
        var submit = new Element('button',{
            text: 'Speichern',
            events: {click : function(){ 
                        var element = this;
                        new Request({
                            url: 'request.php?do=saveCopyTagset'
                            
                        }).post({'tags': JSON.encode(ref.unmatchedTags), 'originTagset': ref.tagsetName, 'name': element.getPrevious('input').get('value')})
                    }
            }
        });
        
        var frame = new Element('div')
                .adopt( new Element('label',{text: 'Tagset-Name: '}),
                    new Element('input',{name: 'tagsetName'}),
                    submit);
            
            
        return frame;
    },
    
    getTagsetEditForm: function(){

        var file = this;

        var frame = new Element('div',{id: 'editImportUnmatchedTagset'});
        
        var t1 = new Element('table',{id: 'editImportUnmatchedTags'})
                 .adopt(new Element('tr')
                            .adopt(new Element('th'),
                                   new Element('th',{text: lang_strings.admin_tag}),
                                   new Element('th',{'class': 'tagDesc', text: lang_strings.admin_description})//,
                                   // new Element('th',{text: lang_strings.admin_possible_attributes})
                            ),
                       new Element('tr',{'class': 'newTagRow'})
                            .adopt(new Element('td'),
                                   new Element('td').adopt(new Element('a',{text: lang_strings.admin_tag_new})),
                                   new Element('td')//,
                                   // new Element('td')
                            )
                 );
                
        // var t2 = new Element('table',{id: 'editImportUnmatchedTagsAttribs'})
        //                               .adopt(new Element('tr')
        //                                  .adopt(new Element('th'),
        //                                         new Element('th',{text: lang_strings.admin_attrib}),
        //                                         new Element('th',{text: lang_strings.admin_description}),
        //                                         new Element('th',{text: lang_strings.admin_possible_values})),
        //                                     new Element('tr',{'class': 'newAttribRow'})
        //                                  .adopt(new Element('td'),
        //                                         new Element('td').adopt('a',{text: lang_strings.admin_attrib_new}),
        //                                         new Element('td'),
        //                                         new Element('td'))
        //                               );
                 
        var saveButtons = new Element('div')
                            .adopt(new Element('button',{   id: 'saveImportUnmatchedTagsChangesButton',
                                                            type: 'button',
                                                            html: lang_strings.dialog_save_changes+'<img src=gui/images/proxal/file.ico',
                                                            events: { click: function(){if(saveTagset()){frame.getParent('.importErrorPopup').dispose();} }}
                                   }),
                                                            
                                   new Element('button',{   id: 'discardImportUnmatchedTagsChangesButton',
                                                            type: 'button',
                                                            text: lang_strings.dialog_discard_changes,
                                                            events: { click: function(){if(discardTagset()){ frame.getParent('.importErrorPopup').dispose();} }}
                                   })
                            );

        // tagset_editor = new TagsetEditor(file.unmatchedTags,{
        tagset_editor = new TagsetEditor([],{            
            elementId: frame,
            tagEditorId: t1,
            attribEditorId: null
        });
        tagset_editor.tagset = ref.tagsetName;        
        new Request({
            url: "request.php",
            onComplete: function(response){ 
                tagset_editor.highestId = response;
                file.unmatchedTags.each(function(tag){ tagset_editor.addNewTag(tag.shortname); });
                tagset_editor.render();
            }
        }).get({'do':'getHighestTagId','tagset': ref.tagsetName});
        
        return frame.adopt(t1,saveButtons);
    },
    
    importUnmatchedTagError: function(){
        
        var fileObj = this;
        var head = "<h2>Import-Fehler</h2>";//new Element('h2',{text: 'Import-Fehler'});
        var msg = new Element('p',{html: 'Nicht alle in der Datei vorkommenden Tags passend zu dem gewählten Tagset. Wollen Sie das Tagset bearbeiten?'});
        var buttons = new Element('div',{'class': 'buttons'});
        var newTagset = new Element('button',{
                            text: 'Neues Tagset anlegen',
                            events: { click: function(){
                                popup.getElement('div.importErrorMessage').hide();
                                popup.getElement('div.importErrorHead h2').set('html','neues Tagset anlegen');
                                popup.getElements('div.importErrorContent').adopt(fileObj.getNewTagsetForm());
                                back.show();
                                popup.getElement('div.buttons').hide();                                

                            }}
                        });
        var editTagset = new Element('button',{
                            text: 'gewähltes Tagset bearbeiten',
                            events: { click: function(){
                                popup.getElement('div.importErrorMessage').hide();
                                popup.getElement('div.importErrorHead h2').set('html','gewähltes Tagset bearbeiten');
                                popup.getElement('div.importErrorContent').adopt(fileObj.getTagsetEditForm());
                                back.show()
                                popup.getElement('div.buttons').hide();                                
                            }}
                        });
        var cancel = new Element('button',{
                            text: 'Abbrechen',
                            events: { click: function(){
                                this.getParent('div.importErrorPopup').dispose();
                                $$('#main div').setStyle('opacity',1);
                            }}
                        });

        var back = new Element('button',{
                            text: 'Zurück',
                            'class': 'backButton',
                            events: { click: function(){
                                popup.getElement('div.importErrorHead h2').set('html',"Import-Fehler");
                                popup.getElement('div.importErrorContent').empty();
                                popup.getElement('div.importErrorMessage').show();
                                popup.getElements('div.buttons').show();
                                this.hide();
                            }}
                        });


        var popup = new Element('div',{'class': 'importErrorPopup'})
                    .adopt(
                        new Element('div',{'class': 'importErrorHead', html: head}).adopt(back.hide()),
                        new Element('div',{'class': 'importErrorMessage'}).adopt(msg),
                        buttons.adopt(newTagset,editTagset,cancel),
                        new Element('div',{'class': 'importErrorContent'})
                    );
        $('main').adopt(popup);
        $$('#main > div[class!=importErrorPopup]').setStyle('opacity',0.5);
        
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
    
    addFileToList: function(){
        
        var ref = this;
        
        var req = new Request.JSON({
                url:'request.php',
        		onSuccess: function(filesArray, text) {        		    
        		    var file = filesArray[0];
			    $('fileList').adopt(ref.renderTableLine(file));
                }
        }).get({'do': 'getLastImportedFile'})
    },
    
    listFiles: function(){
        var ref = this;
        var files = new Request.JSON({
            url:'request.php',
    		onSuccess: function(filesArray, text) {
                $$('#fileList tr[class!=fileTableHeadLine]').dispose();

                filesArray.each(function(file){
                    $('fileList').adopt(ref.renderTableLine(file));
                    $$('#fileList span[class^=addData]').each(function(link){
                        link.removeEvents('click');
                        link.addEvent('click',function(e){
                            e.stop();
                            ref.addNewData(link);
                        })
                    });
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
        var tr = new Element('tr',{id: 'file_'+file.file_id, 'class': opened});
        if((file.byUser == userdata.name) || userdata.admin){
            var delTD = new Element('td',{ html: '<img src="gui/images/proxal/delete.ico" />', 'class': 'deleteFile' });
            delTD.addEvent('click', function(){ ref.deleteFile(file.file_id,file.file_name); } );
            tr.adopt(delTD);
        } else {
	    tr.adopt(new Element('td'));
	}
        var addData = (file.opened) ? '-' : '<span class="addData{type}">Hinzufügen</span>';
        var chkImg = '<img src="gui/images/chk_on.png" />';
        tr.adopt(new Element('td',{'class': 'filename'}).adopt(new Element('a',{ html: file.file_name }).addEvent('click',function(){ ref.openFile(file.file_id); })));
        tr.adopt(new Element('td',{ 'class': 'tagStatusPOS', html: (file.POS_tagged == 1) ? chkImg : addData.substitute({type: 'POS'}) }));
        tr.adopt(new Element('td',{ 'class': 'tagStatusMorph', html: (file.morph_tagged == 1) ? chkImg : addData.substitute({type: 'Morph'}) }));
        tr.adopt(new Element('td',{ 'class': 'tagStatusNorm', html: (file.norm == 1) ? chkImg : addData.substitute({type: 'Norm'}) }));
        tr.adopt(new Element('td',{ html: file.lastMod }));
        tr.adopt(new Element('td',{ html: file.lastModUser }));                    
        tr.adopt(new Element('td',{ html: file.created }));
        tr.adopt(new Element('td',{ html: file.byUser }));
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
    }        
};


// ***********************************************************************
// ********** DOMREADY BINDINGS ******************************************
// ***********************************************************************

window.addEvent('domready', function() {

    file.initialize();    
                    
    $$('td.deleteFile').each(function(link){
        link.addEvent('click', function(e){
            e.stop();
            var par = link.getParent('tr');
            var id = par.get('id').substr(5);            
            var name = par.getElement('td.filename a').get('text');            
            file.deleteFile(id,name);
        })
    });
    $$('a.openFile').each(function(link){
        link.addEvent('click', function(e){
            e.stop();
            file.openFile(link.getParent('tr').get('id').substr(5));
        })
    });
    $$('td.closeFile a.closeFileLink').each(function(link){        
        link.addEvent('click', function(event){ 
            event.stop();
            file.closeFile(link.getParent('tr').get('id').substr(5));
        })
    });

    $$('div.fileViewRefresh img').addEvent('click',function(e){ e.stop(); file.listFiles() });
    
    $$('#fileList span[class^=addData]').each(function(link){
        link.addEvent('click',function(e){
            e.stop();
            file.addNewData(link);
        
        })
    });
    
    if(userdata.currentFileId)
        file.openFile(userdata.currentFileId);

});
