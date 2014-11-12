/* Class: Tagset

   Basic class that represents a tagset.  Can be extended by more specialized
   classes.
 */
var Tagset = new Class({
    id: null,
    shortname: null,
    longname: null,
    set_type: null,
    class: null,
    split_class: false,

    /* Constructor: Tagset

       Instantiate a new Tagset.

       Parameters:
         data - A data object containing tagset information.
     */
    initialize: function(data) {
        if(typeof(data.id) !== "undefined")
            this.id = data.id;
        if(typeof(data.shortname) !== "undefined")
            this.shortname = data.shortname;
        if(typeof(data.longname) !== "undefined")
            this.longname = data.longname;
        if(typeof(data.set_type) !== "undefined")
            this.set_type = data.set_type;
        if(typeof(data.class) !== "undefined")
            this.class = data.class;
    },

    /* Function: isSplitClass

       Check whether this tagset class requires splitting up the tag values
       (e.g., for POS+morph).
     */
    isSplitClass: function() {
        return this.split_class;
    }
});
