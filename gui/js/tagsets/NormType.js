/* Class: NormTypeTagset

   Class representing a NormType tagset.
 */
var NormTypeTagset = new Class({
    Extends: Tagset,
    classname: 'Modernisierungs-Typ',
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
         cls - Tagset class of the annotation
         value - New value of the annotation
     */
    update: function(tr, data, cls, value) {
        var elem, opt, disabled = null;
        if (cls === "norm_type") {
            data.anno_norm_type = value;
            this.updateInputErrorAlt(tr, data, value);
        }
        if (cls === "norm_broad") {
            elem = tr.getElement('.editTable_norm_type select');
            if(elem !== null) {
                if (value.length < 1) {
                    disabled = 'disabled';
                    if(data.anno_norm_type != "") {
                        data.anno_norm_type = "";
                        opt = elem.getElement("option[value='']");
                        if(opt !== null)
                            opt.set('selected', 'selected');
                    }
                }
                elem.set('disabled', disabled);
                this.updateInputError(elem, disabled, data.anno_norm_type);
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
    updateInputErrorAlt: function(tr, data, value) {
        if(!this.showInputErrors)
            return;
        var disabled, elem = tr.getElement('.editTable_norm_type select');
        if(typeof(data.anno_norm_broad) === "undefined"
           || data.anno_norm_broad.length < 1)
            disabled = 'disabled';
        this.updateInputError(elem, disabled, value);
    }
});
