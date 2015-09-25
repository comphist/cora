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

/* Class: LemmaPOSTagset

   Class representing a LemmaPOS tagset.
 */
var LemmaPOSTagset = new Class({
    Extends: Tagset,
    classname: 'Lemma-Tag',
    optgroup: null,
    eventString: 'change:relay(select.et-select-lemmapos)',

    /* Constructor: Tagset

       Instantiate a new LemmaPOSTagset.

       Parameters:
         data - A data object containing tagset information.
     */
    initialize: function(data) {
        this.parent(data);
    },

    /* Function: processTags

       Defines a list of tags for this tagset and preprocesses it, building
       <optgroup> elements for all tags.
     */
    processTags: function(tags) {
        this.parent(tags);
        this.optgroup = this.generateOptgroupFor(this.tags);
    },

    /* Function: buildTemplate

       Update an editor line template for this tagset.  Fills the <select>
       element with the pre-generated <optgroup> containing all lemmaPOS tags,
       if possible.

       Parameters:
         td - Table cell element to update
     */
    buildTemplate: function(td) {
        var elem = td.getElement('select');
        if (elem !== null) {
            elem.empty();
            if(this.processed)
                elem.grab(this.optgroup.clone());
        }
    },

    /* Function: handleEvent

       A specialization of the default handler that works with <select>
       elements.
     */
    handleEvent: function(event, target) {
        return {cls: this.class, value: target.getSelected()[0].get('value')};
    },

    /* Function: fill

       Fill the approriate elements in a <tr> with annotation from a token data
       object.

       Parameters:
         tr - Table row to fill
         data - An object possibly containing annotations ({anno_pos: ...} etc.)
     */
    fill: function(tr, data) {
        var ref = this;
        var pos = tr.getElement('.editTable_lemmapos select');
        if(pos !== null) {
            pos.getElements('.lineSuggestedTag').destroy();
            pos.grab(new Element('option', {
                text: (typeof(data.anno_lemmapos) === "undefined")
                          ? '' : data.anno_lemmapos,
                value: data.anno_lemmapos,
                selected: 'selected',
                class: 'lineSuggestedTag'
            }), 'top');
        }
    },

    /* Function: update

       Triggered method to call whenever an annotation changes.

       Parameters:
         tr - Table row where the change happened
         data - An object possibly containing annotations ({anno_pos: ...}),
                in the state *before* the update
         changes - An object containing any changed values *after* the update
         cls - Tagset class of the annotation
         value - New value of the annotation
     */
    update: function(tr, data, changes, cls, value) {
        if (cls === "lemmapos") {
            changes.anno_lemmapos = value;
        } else if (cls === "pos" && !data.anno_lemmapos) {
            // try to "pre-select" a reasonable tag
            if (this.tags.contains(value)) {
                changes.anno_lemmapos = value;
                this.fill(tr, changes);
            } else {
                for(var i=0; i<this.tags.length; i++) {
                    if (value.indexOf(this.tags[i]) === 0) {
                        changes.anno_lemmapos = this.tags[i];
                        this.fill(tr, changes);
                        break;
                    }
                }
            }
        }
    }
});
