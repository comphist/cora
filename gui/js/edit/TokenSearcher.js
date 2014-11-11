/* Class: TokenSearcher

   GUI element to perform a search within a document.
 */
var TokenSearcher = new Class({
    parent: null,
    mbox: null,
    flexrow: null,

    initialize: function(parent, content) {
        this.parent = parent;
        this.flexrow = new FlexRowList(content.getElement('.flexrow-container'),
                                       $('editSearchCriterionTemplate'));
        this.flexrow.grabNewRow();
        this.mbox = new mBox.Modal({
	    content: content,
	    title: 'Suchen',
	    buttons: [
		{title: 'Abbrechen', addClass: 'mform'},
		{title: 'Suchen', addClass: 'mform button_green',
		 event: function() {
		     // TODO
		 }
		}
	    ]
	});
    },

    open: function() {
        this.mbox.open();
    }
});
