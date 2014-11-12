/* Array: cora.supportedTagsets

   List of all tagset classes that are recognized by the GUI.
 */
cora.supportedTagsets = ["pos", "norm", "norm_broad", "norm_type",
                         "lemma", "lemmapos", "lemma_sugg"];

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
        return tagset.needsProcessing();
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
        tagset.processTags(data.tags);
    },

    /* Function: performUpdate

       Analogous to cora.projects.performUpdate(), this function is
       supposed to update the tagset information.  Currently doesn't
       perform a server request, but reads the data from another
       PHP-generated variable.  (HACK)
     */
    performUpdate: function(){
        if(this.data.length < 1) {
            Array.each(PHP_tagsets, function(tagset) {
                this.data.push(cora.tagsetFactory.make(tagset));
            }.bind(this));
        }
        this.byID = {};
        Array.each(this.data, function(ts, idx) {
            this.byID[ts.id] = idx;
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
