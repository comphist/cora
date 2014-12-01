/* Class: CommentTagset

   Class representing comments.  Comments are not yet technically tagsets, but
   they can be treated in the same way.
 */
var CommentTagset = new Class({
    Extends: Tagset,
    classname: 'Kommentar',

    /* Constructor: Tagset

       Instantiate a new CommentTagset.

       Parameters:
         data - A data object containing tagset information.
     */
    initialize: function(data) {
        this.parent(data);
    },

    /* Function: getValues

       Gets the tagset-specific annotation values from a token data object.

       Parameters:
         data - An object possibly containing annotations ({anno_pos: ...} etc.)
     */
    getValues: function(data) {
        var result = {};
        var key = "comment";
        result[key] = data[key];
        return result;
    },

    /* Function: buildTemplate

       Update an editor line template for this tagset.

       Parameters:
         td - Table cell element to update
     */
    buildTemplate: function(td) {
    },

    /* Function: defineDelegatedEvents

       Define events on the appropriate elements to react to user input.

       Parameters:
         elem - Parent element to add events to
     */
    defineDelegatedEvents: function(elem) {
        elem.addEvent('input:relay(input.et-input-comment)', function(e, t) {
            this.updateAnnotation(t, 'comment', t.get('value'));
        }.bind(this));
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
        var elem = tr.getElement('.editTable_comment input');
        if (elem !== null) {
            elem.set('value', data.comment);
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
        if (cls === "comment")
            data.comment = value;
    }
});
