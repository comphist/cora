/* Class: LemmaPOSTagset

   Class representing a LemmaPOS tagset.
 */
var LemmaPOSTagset = new Class({
    Extends: Tagset,
    optgroup: null,

    initialize: function(data) {
        this.parent(data);
    },

    processTags: function(tags) {
        this.parent(tags);
        this.optgroup = this.generateOptgroupFor(this.tags);
    },

    buildTemplate: function(td) {
        var select = td.getElement('select');
        if(typeof(select) === "undefined")
            return this;
        select.empty();
        if(this.processed)
            select.grab(this.optgroup.clone());
        return this;
    }
});
