// ***********************************************************************
// ********** Tagset Information *****************************************
// ***********************************************************************

/* Class: cora.tagsets

   Acts as a wrapper for an array containing all tagset information.
*/
cora.tagsets = {
    initialized: false,
    data: [],
    byID: {},
    onInitHandlers: [],

    /* Function: get

       Return a tagset by ID.

       Parameters:
        pid - ID(s) of the tagset(s) to be returned
     */
    get: function(pid) {
        if(pid instanceof Array) {
            var data = [];
            pid.each(function(p) {
                var idx = this.byID[p];
                if(idx != undefined)
                    data.push(this.data[idx]);
            }.bind(this));
            return data;
        }
        else {
            var idx = this.byID[pid];
            if(idx == undefined)
                return Object();
            return this.data[idx];
        }
    },

    /* Function: getAll

       Return an array containing all projects.
    */
    getAll: function() {
        return this.data;
    },

    /* Function: isSplitClass

       Check whether the supplied tagset class requires splitting up
       the tag values (e.g., for POS+morph).

       Parameters:
        cls - Tagset class
    */
    isSplitClass: function(cls) {
        if (cls == "pos")
            return true;
        return false;
    },

    /* Function: isProcessed

       Check whether a given tagset has already been processed, or
       does not need any processing.
       
       Parameters:
        tid - Tagset ID
     */
    isProcessed: function(tid) {
        var tagset = this.get(tid);
        if(!tagset)
            return false;
        if(tagset.set_type == "closed" && tagset['class'] != "lemma_sugg")
            return (typeof(tagset.tags) !== "undefined");
        return true;
    },

    /* Function: onInit

       Add a callback function to be called after the project list has
       been first initialized (or immediately if it already has).

       Parameters:
        fn - function to be called
     */
    onInit: function(fn) {
        if(typeof(fn) == "function") {
            if(this.initialized)
                fn();
            else
                this.onInitHandlers.push(fn);
        }
        return this;
    },

    /* Function: makeMultiSelectBox
       
       Creates and returns a dropdown box using MultiSelect.js with all
       available tagsets as entries.
       
       Parameters:
        tagsets - Array of tagset IDs that should be pre-selected
        name    - Name of the input array
        ID      - ID of the selector div
    */
    makeMultiSelectBox: function(tagsets, name, id) {
        var multiselect = new Element('div',
                                      {'class': 'MultiSelect',
                                       'id':    id});
        Array.each(this.data, function(tagset, idx) {
            var entry = new Element('input',
                                    {'type': 'checkbox',
                                     'id':   name+'_'+tagset.id,
                                     'name': name+'[]',
                                     'value': tagset.id});
            var textr = "["+tagset['class']+"] "+tagset.longname+" (id: "+tagset.id+")";
            var label = new Element('label',
                                    {'for':  name+'_'+tagset.id,
                                     'text': textr});
            if(tagsets.some(function(el){ return el == tagset.id; }))
                entry.set('checked', 'checked');
            multiselect.grab(entry).grab(label);
        });
        new MultiSelect(multiselect,
                        {monitorText: ' Tagset(s) ausgewählt'});
        return multiselect;
    },

    /* Function: preprocess

       Preprocesses tagset data, storing all tags in an array and
       creating HTML elements containing these tags.
       
       Parameters:
       data - Tagset data object as returned by the
              'fetchTagsetsForFile' request
     */
    preprocess: function(data) {
        if(typeof(data.tags) === "undefined" || data.tags.length < 1)
            return;
        var tagset = this.get(data.id);
        tagset.tags = this._extractValidTags(data.tags);
        if(this.isSplitClass(tagset['class'])) {
            tagset.tags_for = this._makeSplitTaglist(tagset.tags);
            tagset.optgroup = this.generateOptgroup(Object.keys(tagset.tags_for));
            tagset.optgroup_for = {};
            Object.each(tagset.tags_for, function(subtags, tag) {
                tagset.optgroup_for[tag] = this.generateOptgroup(subtags);
                tagset.optgroup_for[tag].label = "Alle Tags für '" + tag + "'";
            }.bind(this));
        } else {
            tagset.optgroup = this.generateOptgroup(tagset.tags);
        }
    },

    /* Function: _extractValidTags
     */
    _extractValidTags: function(taglist) {
        return Array.map(
            Array.filter(taglist,
                         function(e){return (e.needs_revision == 0);}),
            function(e) {return e.value;}
        );
    },

    /* Function: _makeSplitTaglist
     */
    _makeSplitTaglist: function(taglist) {
        var tags = {};
        var splitTag = function(tag) {
            var idx = tag.indexOf('.');
            if(idx<0 || idx==(tag.length-1))
                return [tag, null];
            return [tag.substr(0, idx), tag.substr(idx+1)];
        };
        Array.each(taglist, function(tag) {
            var split = splitTag(tag);
            if(typeof(tags[split[0]]) === "undefined")
                tags[split[0]] = [];
            if(split[1] !== null)
                tags[split[0]].push(split[1]);
        });
        return tags;
    },

    /* Function: generateOptgroup
     */
    generateOptgroup: function(taglist) {
        var optgroup = new Element('optgroup', {label: "Alle Tags"});
        if(taglist.length > 0) {
            Array.each(taglist, function(tag) {
                optgroup.grab(new Element('option', {text: tag, value: tag}));
            });
        } else {
            optgroup.grab(new Element('option', {text: '--', value: '--'}));
        }
        return optgroup;
    },

    /* Function: performUpdate
       
       Analogous to cora.projects.performUpdate(), this function is
       supposed to update the tagset information.  Currently doesn't
       perform a server request, but reads the data from another
       PHP-generated variable.  (HACK)
     */
    performUpdate: function(){
        if(this.data.length < 1)
            this.data = PHP_tagsets;
        this.byID = {};
        Array.each(this.data, function(prj, idx) {
            this.byID[prj.id] = idx;
        }.bind(this));
        if(!this.initialized) {
            this.initialized = true;
            Array.each(this.onInitHandlers, function(handler) {
                handler();
            });
        }
        return this;
    }
};

// ***********************************************************************
// ********** DOMREADY BINDINGS ******************************************
// ***********************************************************************

window.addEvent('domready', function() {
    cora.tagsets.performUpdate();
});
