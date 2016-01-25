/*
 * Copyright (C) 2015 Marcel Bollmann <bollmann@linguistics.rub.de>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy of
 * this software and associated documentation files (the "Software"), to deal in
 * the Software without restriction, including without limitation the rights to
 * use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of
 * the Software, and to permit persons to whom the Software is furnished to do so,
 * subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS
 * FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR
 * COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER
 * IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN
 * CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */

/* Array: cora.supportedTagsets

   List of all tagset classes that are recognized by the GUI.
 */
cora.supportedTagsets = ["pos", "morph", "norm", "norm_broad",
                         "norm_type", "lemma", "lemmapos", "lemma_sugg",
                         "comment", "sec_comment", "boundary"];

/* Array: cora.importableTagsets

   List of all tagset classes that can be used for tagset import.
 */
cora.importableTagsets = ["pos", "boundary", "lemmapos", "lemma_sugg",
                          "norm_type"];

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
        else if(pid instanceof Tagset) {
            return pid;
        } else {
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
                        {monitorText: ' '+_("AdminTab.Forms.projectOptionsForm.tagsetsSelected")});
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
