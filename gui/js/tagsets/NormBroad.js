/* Class: NormBroadTagset

   Class representing a NormBroad tagset.
 */
var NormBroadTagset = new Class({
    Extends: Tagset,

    /* Constructor: Tagset

       Instantiate a new NormBroadTagset.

       Parameters:
         data - A data object containing tagset information.
     */
    initialize: function(data) {
        this.parent(data);
    },

    /* Function: buildTemplate

       Update an editor line template for this tagset.

       Parameters:
         td - Table cell element to update
     */
    buildTemplate: function(td) {
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
        var elem = tr.getElement('.editTable_norm_broad input');
        if(elem !== null) {
            elem.set('value', data.anno_norm_broad);
            elem.set('placeholder', data.anno_norm);
            elem.removeEvents().addEvent('input', function() {
                ref.updateAnnotation(this, data.num, 'norm_broad', this.get('value'));
            });
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
        var elem;
        if (cls === "norm_broad")
            data.anno_norm_broad = value;
        if (cls === "norm") {
            elem = tr.getElement('.editTable_norm_broad input');
            if(elem !== null)
                elem.set('placeholder', value);
        }
    }
});
