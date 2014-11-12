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
    tags: [],
    processed: false,

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
    },

    /* Function: needsProcessing

       Check whether this tagset class needs a call to processTags() with the
       full taglist in order to work correctly.

       Returns false if the tagset class doesn't need processing, or if
       processing was already performed for this tagset instance.
     */
    needsProcessing: function() {
        if(this.set_type == "open")
            return false;
        return !this.processed;
    },

    /* Function: processTags

       Defines a list of tags for this tagset and preprocesses it, e.g.,
       generating HTML elements for the tags, splitting them up, etc. -- the
       implementation of this method is up to the individual subclasses.
     */
    processTags: function(tags) {
        this.tags = Array.map(
            Array.filter(tags,
                         function(e){return (e.needs_revision == 0);}),
            function(e) {return e.value;}
        );
        this.processed = true;
    },

    /* Function: generateOptgroupFor

       Generates and returns an <optgroup> element containing all tags supplied
       to this method.

       Parameters:
         tags - List of tags the <optgroup> should contain
         label - Label for the <optgroup>, defaults to "Alle Tags"
     */
    generateOptgroupFor: function(tags, label) {
        label = label || "Alle Tags";
        var optgroup = new Element('optgroup', {label: label});
        if(tags.length > 0) {
            Array.each(tags, function(tag) {
                optgroup.grab(new Element('option', {text: tag, value: tag}));
            });
        } else {
            optgroup.grab(new Element('option', {text: '--', value: '--'}));
        }
        return optgroup;
    },

    /* Function: buildTemplate

       Update an editor line template for this tagset.

       Parameters:
         td - Table cell element to update
     */
    buildTemplate: function(td) {
        return;
    }
});
