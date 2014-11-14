/* Class: FlagHandler

   An addition to the tagset-specific classes, this class handles all annotation
   changes to status flags ("error" annotation, verified lemma, etc.).

   Behaves similarly to Tagset, but is separate for now since flags are global
   to all texts without needing to be linked to them.
 */
var FlagHandler = new Class({
    updateAnnotation: null,
    flags: {
        'flag_general_error': {
            elem: 'div.editTableError',
            class: 'editTableErrorChecked'
        },
        'flag_lemma_verified': {
            elem: 'div.editTableLemma',
            class: 'editTableLemmaChecked'
        }
    },

    /* Function: initialize
     */
    initialize: function(fn) {
        this.setUpdateAnnotation(fn);
    },

    /* Function: setUpdateAnnotation

       Sets the callback function to invoke whenever an annotation changes.
     */
    setUpdateAnnotation: function(fn) {
        if(typeof(fn) === "function")
            this.updateAnnotation = fn;
    },

    /* Function: getValues

       Gets the flag annotation values from a token data object.

       Parameters:
         data - An object possibly containing annotations ({anno_pos: ...} etc.)
     */
    getValues: function(data) {
        return Object.map(this.flags, function(options, flag) {
            return (data[flag] == 1) ? 1 : 0;
        });
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
        Object.each(this.flags, function(options, flag) {
            var elem = tr.getElement(options.elem);
            if (elem !== null) {
                ref._setFlag(elem, options.class, data[flag]);
                elem.removeEvents().addEvent('click', function() {
                    var value = this.hasClass(options.class) ? 0 : 1;
                    ref.updateAnnotation(this, data.num, flag, value);
                });
            }
        });
    },

    /* Function: update

       Triggered method to call whenever an annotation changes.  Allows the
       FlagHandler to react to a change and/or store the result.

       Parameters:
         tr - Table row where the change happened
         data - An object possibly containing annotations ({anno_pos: ...}),
                in the state *before* the update
         cls - Tagset class of the annotation
         value - New value of the annotation
     */
    update: function(tr, data, cls, value) {
        var ref = this;
        Object.each(this.flags, function(options, flag) {
            var elem;
            if (cls === flag) {
                data[flag] = value;
                elem = tr.getElement(options.elem);
                if (elem !== null)
                    ref._setFlag(elem, options.class, value);
            }
        });
    },

    _setFlag: function(elem, css_class, value) {
        if (value == 1)
            elem.addClass(css_class);
        else
            elem.removeClass(css_class);
    }
});
