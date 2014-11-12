/* Class: POSTagset

   Class representing a POS tagset.
 */
var POSTagset = new Class({
    Extends: Tagset,
    Implements: SplitClassTagset,
    optgroup: null,

    initialize: function(data) {
        this.parent(data);
    },

    processTags: function(tags) {
        this.parent(tags);
        this.processSplitTags();
        this.optgroup = this.generateOptgroupFor(Object.keys(this.tags_for));
    }
});
