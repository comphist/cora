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

/* Class: BoundaryTagset

   Class representing a (sentence) boundary tagset.
 */
var BoundaryTagset = new Class({
    Extends: Tagset,
    classname: 'Satzgrenze',
    optgroup: null,
    eventString: 'change:relay(select.et-select-boundary)',

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
        this.optgroup.grab(new Element('option', {value: "", text: ""}), 'top');
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
                this.optgroup.clone().getChildren().inject(elem);
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
        var ref = this,
            disabled = null,
            opt = null,
            select_value = "",
            pos = tr.getElement('.et-select-boundary');
        if(pos !== null) {
            if(typeof(data.flag_boundary) === "undefined"
               || data.flag_boundary == "" || data.flag_boundary == "0")
                disabled = "disabled";
            pos.set('disabled', disabled);
            if(typeof(data.anno_boundary) !== "undefined")
                select_value = data.anno_boundary;
            opt = pos.getElement("option[value='" + select_value + "']");
            if(opt !== null)
                opt.set('selected', 'selected');
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
        var elem, opt, disabled = null;
        if (cls === "boundary") {
            changes.anno_boundary = value;
        }
        else if (cls === "flag_boundary") {
            elem = tr.getElement('.et-select-boundary');
            if (elem !== null) {
                if (value != 1) {
                    disabled = 'disabled';
                    if (typeof(data.anno_boundary) !== ""
                       && data.anno_boundary !== "") {
                        changes.anno_boundary = "";
                        opt = elem.getElement("option[value='']");
                        if (opt !== null)
                            opt.set('selected', 'selected');
                    }
                }
                elem.set('disabled', disabled);
            }
        }
    }
});
