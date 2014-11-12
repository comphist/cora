/* Class: POSTagset

   Class representing a POS tagset.
 */
var POSTagset = new Class({
    Extends: Tagset,

    initialize: function(data) {
        this.parent(data);
        this.split_class = true;
    }
});
