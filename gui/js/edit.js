var edit = {
    
    data: {
        lastEditedRow: 0
    },
    
    initialize: function(){
        
        this.lastEditLine = 0;
        var ref = this;
        
        new Form.Request($('editUserSettings'),'',{
            resetForm: false,
            extraData: {'do': 'saveEditorUserSettings'},
            onSuccess: function(){                
                ref.renderPagesPanel();
                ref.getLines()
            },
            onFailure: function(){
                alert('Error occured while saving');
            }
        })
    },
    
    renderPagesPanel: function(activePage){
        $$('pagePanel a').dispose();        

        new Request({
            url: 'request.php',
            onComplete: function(maxPage){
                // $('pagePanel').adopt(new Element('a',{href: 'back', text: '<'}));
                for (var i=1; i <= maxPage; i++) {
                    $('pagePanel').adopt(new Element('a',{href: i, text: i,
                    events: {
                        click: function(e){
                            e.stop();
                            $$('#pagePanel a[class=currentPage]').removeClass('currentPage');
                            this.addClass('currentPage');
                            edit.getLines(this.get('href'));} }}));
                };
                // $('pagePanel').adopt(new Element('a',{href: 'forward', text: '>'}));
                
                if(activePage != null){
                    $$('#pagePanel a[href='+activePage+']').addClass('currentPage');
                }
            }            
            
        }).get({'do': 'getMaxLinesNo'})

    },
    
    getLines: function(page){
        var ref = this;
        var lines = new Request.JSON({
            url:'request.php',
    		onSuccess: function(lineArray, text) {
    		    
                $$('#editTable tr[class!=editHeadLine]').destroy();

                lineArray.each(function(line){
                    var tr = new Element('tr',{id: 'line_'+line.line_id});
                    var progressChk = (parseInt(line.line_id) <= ref.data.lastEditedRow) ? true : false;
                    tr.adopt(new Element('td',{'class': 'editTable_progress'}).adopt( ref.renderCheckbox('Progress',progressChk )));
                    tr.adopt(new Element('td',{'class': 'editTable_error'}).adopt( ref.renderCheckbox('Error',(line.errorChk != 0)) ));
                    tr.adopt(new Element('td',{'class': 'editTable_token', html: line.token}));
                    tr.adopt(new Element('td',{'class': 'editTable_Norm'}).adopt( ref.renderInputField(line.tag_norm,'tag_norm') ));                    
                    tr.adopt(new Element('td',{'class': 'editTable_POS'}).adopt( ref.renderDropDownMenu(line.tag_POS,line.suggestions_pos,'tag_POS') ));
                    tr.adopt(new Element('td',{'class': 'editTable_Morph'}).adopt( ref.renderDropDownMenu(line.tag_morph,line.suggestions_morph,'tag_morph') ));
                    tr.adopt(new Element('td',{'class': 'editTable_Lemma'}).adopt( ref.renderInputField(line.lemma,'lemma') ));
                    tr.adopt(new Element('td',{'class': 'editTable_Comment'}).adopt( ref.renderInputField(line.comment,'comment') ));

                    $('editTable').adopt(tr);
                });                
                
            }
    	});
        lines.get({'do': 'getLines','page': page});
    },
    
    renderCheckbox: function(type,chked){
        var req = new Request({
            url: 'request.php'
        }); 
                
        var div = new Element('div',{
            'class': 'editTable'+type,
            events: { click: function(){
                if(type=="Error"){
                    if(this.retrieve('checked')){
                        req.get({'do':'unhighlightError','line': this.getParent('tr').get('id').substr(5)});                        
                        this.setStyle('background-color','white');
                        this.store('checked',false);
                    } else {
                        req.get({'do':'highlightError','line': this.getParent('tr').get('id').substr(5)});
                        this.setStyle('background-color','red');
                        this.store('checked',true);
                    }
                } else {
                    req.get({'do':'markLastPosition','line': this.getParent('tr').get('id').substr(5)});
                }
            }},
            styles: {
                'background-color': (chked) ? ( (type=="Error") ? 'red' : 'green' ) : 'white'
            }
        });
        
        div.store('type',type);
        div.store('checked',chked);
        
        return div;
    },
    
    renderInputField: function(val,type) {
                
        return new Element('input',{
            type: 'text',
            size: '10',
            value: val,
            events: {
                blur: function(){
                    var el = this;
                    var req = new Request({
                        url: 'request.php?do=saveTag',
                        onComplete: function(){
                            if(type != 'comment')
                                edit.markProgress(el);
                        },
                        onError: function(){
                            var msg = lang_strings.dialog_file_locked_error +
                                         ": " + data.lock.locked_by + ", " +
                                      data.lock.locked_since;
                                      alert(msg);
                        }
                    
                    }).post({'tag_name': type, 'tag_value': el.get('value'), 'lineid': this.getParent('tr').get('id').substr(5)});
                }
            } })
    },
            
    
    renderDropDownMenu: function(fav,data,type){
        var menu = new Element('select',{events: { change: function(){

            var el= this;                          

            var lineId = this.getParent('tr').get('id').substr(5);
            
            var req = new Request({
                url: 'request.php?do=saveTag',
                onComplete: function(){
                    edit.markProgress(el);
                },
                onError: function(){
                    var msg = lang_strings.dialog_file_locked_error +
                                 ": " + data.lock.locked_by + ", " +
                              data.lock.locked_since;
                              alert(msg);
                }
                
            }).post({'tag_name': type, 'tag_value': menu.getSelected()[0].get('html').replace(/[\s|\d]+/g,""), 'lineid': lineId});
        } }});
        
        if(type=='tag_morph'){
            menu.set('html',fileTagset.morph);            
        } else {
            menu.set('html',fileTagset.pos);
        }

        
        menu.grab(new Element('option',{html: "-----------"}),'top');
        data.reverse().each(function(opt){ 
            menu.grab(new Element('option',{html: opt.tag_name+"\t"+opt.tag_probability}),'top');
        });
        menu.grab(new Element('option',{html: fav, selected: 'selected'}),'top');

        
        
        // if(type=='Morph') tagset = 'STTS.morph';
        // else tagset = 'STTS.pos'
        // 
        
        // $each(tagset,function(tag){                   
        //         console.log(tag)
        //         menu.adopt(new Element('option',{html: tag}));
        //     });
        
        return menu;
    },
    
    markProgress: function(el){

        var id = parseInt(el.getParent('tr').get('id').substr(5));
        if(id>this.data.lastEditedRow){
            this.data.lastEditedRow = id;
            [el.getParents('tr ^ td ^'),el.getParents('tr !~ ^ td ^')].each(function(ele){
                ele.store('checked',true);
                ele.setStyle('background-color','green');
            })
            
            var req = new Request({
                url: 'request.php'
            }).get({'do':'markLastPosition','line': id});
        }
    },
    
    undoLastProgress: function(){
        // var req = new Request({
        //     url: 'request.php',
        //     onComplete: function(newLastEdit){
        //         console.log(referenz);
        //         referenz.data.lastEditedRow = parseInt(newLastEdit);
        //         $$('editTable tr').each(function(el){
        //             if(parseInt(el.get('id')) >= newLastEdit){
        //                 var td = el.getElement('td');
        //                 td.store('checked',false);
        //                 td.setStyle('background-color','white');
        //             }
        //         });                
        //     }
        // }).get({'do':'undoLastEdit'});
    }
}

window.addEvent('domready',function(){
    edit.initialize();
    $$('#pagePanel a').each(function(link){
        link.addEvent('click',function(e){
            e.stop();
            $$('#pagePanel a[class=currentPage]').removeClass('currentPage');
            link.addClass('currentPage');
            edit.getLines(this.get('href'));
        });
    })
    

    
    $$('table#editTable select').addEvent('change',function(e){
        e.stop();
        edit.markProgress();        
    })

    // $('undoEditBtn').addEvent('click',edit.undoLastProgress );
    
})
