/* Class: Tagset

   Basic class that represents a tagset.  Should typically be extended by more
   specialized classes.

   Specialized classes will minimally want to implement fill() and update(),
   possibly buildTemplate(), and maybe even processTags() if they need to do
   some special preprocessing with their list of tags.

   Right now, the process is:
     - fill() fills the appropriate input fields according to the given data,
           **and** adds the appropriate events for when the input changes;
           these events should call this.updateAnnotation(), which is set
           by the editor
     - updateAnnotation() then (among other things) calls update() for each
           registered tagset; here, tagsets can react to a change being made,
           and store them (after possible modifications) in the provided
           data object

   CAVEAT: Right now, updateAnnotation() is not guaranteed to be called for
           every annotation change, but only for changes triggered by the
           user.  If a tagset's update() routine makes further changes to
           the data, there is no corresponding updateAnnotation() call.

           As a consequence, tagsets cannot react to changes that have been
           made by other tagset classes as part of their update() call.
           This is not required ATM, but for clarity's sake, this process
           should probably be refactored to make **every** change go via
           updateAnnotation().
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

    updateAnnotation: null,  // callback function
    showInputErrors: false,
    inputErrorClass: 'input_error',

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

    /* Function: setShowInputErrors

       Sets whether input errors should be shown for this tagset.
     */
    setShowInputErrors: function(value) {
        this.showInputErrors = value;
    },

    /* Function: setUpdateAnnotation

       Sets the callback function to invoke whenever an annotation changes.
     */
    setUpdateAnnotation: function(fn) {
        if(typeof(fn) === "function")
            this.updateAnnotation = fn;
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

    /* Function: getValues

       Gets the tagset-specific annotation values from a token data object.

       Parameters:
         data - An object possibly containing annotations ({anno_pos: ...} etc.)
     */
    getValues: function(data) {
        var result = {};
        var key = "anno_"+this.class;
        result[key] = data[key];
        return result;
    },

    /* Function: generateOptgroupFor

       Generates and returns an <optgroup> element containing all tags supplied
       to this method.

       Parameters:
         tags - List of tags the <optgroup> should contain
         label - Label for the <optgroup>, defaults to "Alle Tags"
         cls - CSS class for the <optgroup> and all contained elements
     */
    generateOptgroupFor: function(tags, label, cls) {
        if(tags.length < 1)
            return null;
        label = label || "Alle Tags";
        var optgroup = new Element('optgroup', {label: label, class: cls});
        var opt;
        Array.each(tags, function(tag) {
            if(typeof(tag) === "string")
                opt = {text: tag, value: tag, class: cls};
            else
                opt = {text: tag.text, value: tag.value, class: cls};
            optgroup.grab(new Element('option', opt));
        });
        return optgroup;
    },

    /* Function: buildTemplate

       Update an editor line template for this tagset.

       Parameters:
         td - Table cell element to update
     */
    buildTemplate: function(td) {
        return;
    },

    /* Function: defineDelegatedEvents

       Define events on the appropriate elements to react to user input.

       Parameters:
         elem - Parent element to add events to
     */
    defineDelegatedEvents: function(elem) {
        return;
    },

    /* Function: fill

       Fill the approriate elements in a <tr> with annotation from a token data
       object.

       Parameters:
         tr - Table row to fill
         data - An object possibly containing annotations ({anno_pos: ...} etc.)
     */
    fill: function(tr, data) {
        return;
    },

    /* Function: update

       Triggered method to call whenever an annotation changes.  Allows the
       Tagset class to react to a change and store the result.

       Parameters:
         tr - Table row where the change happened
         data - An object possibly containing annotations ({anno_pos: ...}),
                in the state *before* the update
         cls - Tagset class of the annotation
         value - New value of the annotation
     */
    update: function(tr, data, cls, value) {
        return;
    }
});
