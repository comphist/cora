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

    handleEvent: function(event, target) {
        return target.getSelected()[0].get('value');
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
         cls - Tagset class of the annotation
         value - New value of the annotation
     */
    update: function(tr, data, cls, value) {
        if (cls === "lemmapos")
            data.anno_lemmapos = value;
    }
});
