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

/* Class: NormTypeTagset

   Class representing a NormType tagset.
 */
var NormTypeTagset = new Class({
    Extends: Tagset,
    classname: "Columns.modType", //'Modernisierungstyp'
    eventString: 'change:relay(select.et-select-norm_type)',
    optgroup: null,

    /* Constructor: Tagset

       Instantiate a new NormTypeTagset.

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
        this.optgroup = this.generateOptgroupFor(this.tags, "");
        this.optgroup.grab(new Element('option', {value: "", text: ""}), 'top');
    },

    /* Function: buildTemplate

       Update an editor line template for this tagset.

       Parameters:
         td - Table cell element to update
     */
    buildTemplate: function(td) {
        var elem = td.getElement('select');
        if (elem !== null) {
            elem.empty();
            if(this.processed) {
                this.optgroup.clone().getChildren().inject(elem);
            }
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
        var elem = tr.getElement('.et-select-norm_type'),
            select_value = '',
            disabled, opt,
            ref = this;
        if(elem !== null) {
            disabled = (typeof(data.anno_norm_broad) === "undefined"
                        || data.anno_norm_broad == '') ? 'disabled' : null;
            elem.set('disabled', disabled);
            if(typeof(data.anno_norm_type) !== "undefined")
                select_value = data.anno_norm_type;
            opt = elem.getElement("option[value='" + select_value + "']");
            if(opt !== null)
                opt.set('selected', 'selected');
            this.updateInputError(elem, disabled, select_value);
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
        var elem, opt, disabled = null, normtype = null;
        if (cls === "norm_type") {
            changes.anno_norm_type = value;
            this.updateInputErrorAlt(tr, data.anno_norm_broad, value);
        }
        else if (cls === "norm_broad") {
            elem = tr.getElement('.editTable_norm_type select');
            if(elem !== null) {
                normtype = data.anno_norm_type;
                if (value.length < 1) {
                    disabled = 'disabled';
                    if(normtype != "") {
                        normtype = "";
                        changes.anno_norm_type = "";
                        opt = elem.getElement("option[value='']");
                        if(opt !== null)
                            opt.set('selected', 'selected');
                    }
                }
                elem.set('disabled', disabled);
                this.updateInputError(elem, disabled, normtype);
            }
        }
    },

    /* Function: updateInputError

       Set the input error class when NormBroad is set, but NormType is empty.
     */
    updateInputError: function(elem, norm_broad_empty, norm_type) {
        if(!this.showInputErrors)
            return;
        var norm_type_empty = (typeof(norm_type) === "undefined"
                               || norm_type.length < 1);
        if(!norm_broad_empty && norm_type_empty)
            elem.addClass(this.inputErrorClass);
        else
            elem.removeClass(this.inputErrorClass);
    },

    /* Function: updateInputErrorAlt

       Alternative version of updateInputError, called when 'elem' and
       'norm_broad_empty' have not yet been retrieved.
     */
    updateInputErrorAlt: function(tr, norm_broad, value) {
        if(!this.showInputErrors)
            return;
        var disabled, elem = tr.getElement('.editTable_norm_type select');
        if(typeof(norm_broad) === "undefined"
           || norm_broad.length < 1)
            disabled = 'disabled';
        this.updateInputError(elem, disabled, value);
    }
});
