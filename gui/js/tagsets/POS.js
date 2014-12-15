/* Class: POSTagset

   Class representing a POS tagset.
 */
var POSTagset = new Class({
    Extends: Tagset,
    Implements: SplitClassTagset,
    classname: 'POS+Morphologie-Tag',
    optgroup: null,
    eventStringPOS: 'change:relay(select.et-select-pos-main)',
    eventStringMorph: 'change:relay(select.et-select-morph)',

    emptyElement: null,

    /* Constructor: Tagset

       Instantiate a new POSTagset.

       Parameters:
         data - A data object containing tagset information.
     */
    initialize: function(data) {
        this.parent(data);
        this.emptyElement = new Element('option', {
            text: "--", value: "",
            selected: 'selected',
            class: 'lineSuggestedTag'
        });
    },

    /* Function: processTags

       Defines a list of tags for this tagset and preprocesses it, splitting up
       the tags provided in 'data' and building <optgroup> elements for both POS
       and morph tags.
     */
    processTags: function(tags) {
        this.parent(tags);
        this.processSplitTags();
        this.optgroup = this.generateOptgroupFor(Object.keys(this.tags_for));
    },

    /* Function: buildTemplate

       Update an editor line template for this tagset.  Fills the <select>
       element with the pre-generated <optgroup> containing all POS tags, if
       possible.

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

    /* Function: getEventData

       Return event types and event handlers for this tagset.
     */
    getEventData: function() {
        return [{type: this.eventStringPOS,
                 handler: this.handleEventPOS.bind(this)},
                {type: this.eventStringMorph,
                 handler: this.handleEventMorph.bind(this)}];
    },

    /* Function: handleEventPOS

       A specialization of the default handler that works with <select>
       elements.
     */
    handleEventPOS: function(event, target) {
        return {cls: 'pos', value: target.getSelected()[0].get('value')};
    },

    /* Function: handleEventMorph

       A specialization of the default handler that works with <select>
       elements.
     */
    handleEventMorph: function(event, target) {
        return {cls: 'morph', value: target.getSelected()[0].get('value')};
    },

    /* Function: fill

       Fill the approriate elements in a <tr> with annotation from a token data
       object.

       Parameters:
         tr - Table row to fill
         data - An object possibly containing annotations ({anno_pos: ...} etc.)
     */
    fill: function(tr, data) {
        var split = this.splitTag(data.anno_pos);
        this.fillPOS(tr, split, data);
        this.fillMorph(tr, split, data);
        this.updateInputError(tr, split);
    },

    /* Function: update

       Triggered method to call whenever an annotation changes.

       Checks if the currently selected POS/morph combination is valid, and
       changes it if it's not.  If POS updates, displays the appropriate tag
       selection for morph.

       Parameters:
         tr - Table row where the change happened
         data - An object possibly containing annotations ({anno_pos: ...}),
                in the state *before* the update
         cls - Tagset class of the annotation
         value - New value of the annotation
     */
    update: function(tr, data, cls, value) {
        var pos, morph, current_split;
        if (cls === "pos") {
            current_split = this.splitTag(data.anno_pos);
            pos = value, morph = current_split[1];
            if (!this.isValidTag(pos, morph))
                morph = this.getValidMorph(pos, morph);
            data.anno_pos = this.joinTag(pos, morph);
            this.fillMorph(tr, [pos, morph], data);
            this.updateInputError(tr, [pos, morph]);
        } else if (cls === "morph") {
            current_split = this.splitTag(data.anno_pos);
            pos = current_split[0], morph = value;
            data.anno_pos = this.joinTag(pos, morph);
            this.updateInputError(tr, [pos, morph]);
        }
    },

    /**********************************************************************/
    /********************** CLASS-SPECIFIC FUNCTIONS **********************/
    /**********************************************************************/

    /* Function: getValidMorph

       Return the best valid morphology tag for a given tag combination.
     */
    getValidMorph: function(pos, morph) {
        var tags = this.tags_for[pos];
        if (typeof(tags) === "undefined" || tags == null || tags.length == 0)
            return "";
        return tags[0];
    },

    /* Function: updateInputError

       Set the input error class for invalid tag combinations.
     */
    updateInputError: function(tr, split) {
        if(!this.showInputErrors)
            return;
        var pos = tr.getElement('.editTable_pos select'),
            morph = tr.getElement('.editTable_morph select');
        if(pos !== null) {
            if (typeof(this.tags_for[split[0]]) === "undefined")
                pos.addClass(this.inputErrorClass);
            else
                pos.removeClass(this.inputErrorClass);
        }
        if(morph !== null) {
            if (!this.isValidTag(split[0], split[1]))
                morph.addClass(this.inputErrorClass);
            else
                morph.removeClass(this.inputErrorClass);
        }
    },

    /* Function: fillPOS

       Fill the POS <select> box.
     */
    fillPOS: function(tr, split, data) {
        var select = tr.getElement('.et-select-pos-main'),
            ref = this;
        if(select === null)
            return;
        select.getElements('.lineSuggestedTag').destroy();
        this._grabSuggestionOptgroup(select, data.suggestions, split, 'pos');
        select.grab(new Element('option', {
            text: (split[0] === null) ? '' : split[0],
            value: split[0],
            selected: 'selected',
            class: 'lineSuggestedTag'
        }), 'top');
    },

    /* Function: fillMorph

       Fill the morphology <select> box.
     */
    fillMorph: function(tr, split, data) {
        var opt,
            select = tr.getElement('.et-select-morph'),
            ref = this;
        if(select === null)
            return;
        select.empty();
        opt = this.optgroup_for[split[0]];
        // If there are no morph tags and the current morph value is empty,
        // disable the dropdown box and return
        // ---if the current morph value is not empty here, it could still
        //    be a legal tag marked as 'needs_revision', so we continue!
        if (opt === null && (split[1] === null || split[1] === '')) {
            select.grab(this.emptyElement.clone());
            select.set('disabled', 'disabled');
            return;
        }
        select.set('disabled', null);
        this._grabSuggestionOptgroup(select, data.suggestions, split, 'morph');
        if(typeof(opt) !== "undefined" && opt !== null)
            select.grab(opt.clone());
        select.grab(new Element('option', {
            text: (split[1] === null) ? '' : split[1],
            value: split[1],
            selected: 'selected',
            class: 'lineSuggestedTag'
        }), 'top');
    },

    /* Function: _suggForDisplay

       Processes a list of suggested tags and formats them for display.

       Parameters:
         tags - List of tags
         cls - Tagset class to use (pos|morph)
     */
    _suggForDisplay: function(tags, current_split, cls) {
        var split, result = [];
        if(typeof(tags) === "undefined")
            return result;
        Array.each(tags, function(tag) {
            split = this.splitTag(tag.value);
            if (cls === "pos") {
                result.push({text: split[0]+" ("+tag.score+")", value: split[0]});
            } else if (cls === "morph" && split[0] === current_split[0]) {
                result.push({text: split[1]+" ("+tag.score+")", value: split[1]});
            }
        }.bind(this));
        return result;
    },

    /* Function: _grabSuggestionOptgroup
     */
    _grabSuggestionOptgroup: function(select, suggestions, split, cls) {
        var opt, applicable = this._suggForDisplay(suggestions, split, cls);
        if(applicable.length > 0) {
            opt = this.generateOptgroupFor(applicable,
                                           "Vorgeschlagene Tags",
                                           'lineSuggestedTag');
            if(opt !== null)
                select.grab(opt, 'top');
        }
    }
});
