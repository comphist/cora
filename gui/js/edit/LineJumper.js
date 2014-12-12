/* Class: LineJumper

   GUI dialog box to jump to a specific line within a document.
 */
var LineJumper = new Class({
    parent: null,
    parentTable: null,
    mbox: null,

    /* Constructor: LineJumper

       Make a new LineJumper element.

       Parameters:
         parent - The parent PageModel, used to perform the jumps
         content - Content of the dialog window
     */
    initialize: function(parent, content) {
        var ref = this;
        this.parent = parent;
        this.parentTable = parent.parent;
        this.mbox = new mBox.Modal({
	    content: content,
	    title: 'Springe zu Zeile',
	    buttons: [
		{title: 'Abbrechen', addClass: 'mform'},
		{title: 'OK', addClass: 'mform button_green',
		 event: function() {
		     ref.jump();
		 }
		}
	    ],
	    onOpenComplete: function() {
                var box = this.content.getElement('input[name="jumpTo"]');
                box.removeEvents('keydown');
                box.addEvent('keydown', function(event) {
                    if(event.key == "enter")
                        ref.jump();
                });
		box.focus();
		box.select();
	    }
	});
    },

    /* Function: open

       Open the LineJumper dialog window.
     */
    open: function() {
        this.mbox.open();
    },

    /* Function: jump

       Jump to the line entered by the user in the LineJumper dialog window.
     */
    jump: function() {
        var value = Number.from(this.mbox.content
                                .getElement('input[name="jumpTo"]').value);
        if (value == null) {
	    gui.showNotice('error', 'Bitte eine Zahl eingeben.');
        } else if (!this.parent.isValidLine(value)) {
	    gui.showNotice('error', 'Zeilennummer existiert nicht.');
        } else {
            this.parentTable.addEvent(
                'render:once',
                function() {
                    this.highlightRow(value - 1);
                }.bind(this.parentTable)
            );
            this.parent.set(this.parent.getPageByLine(value)).render();
            this.mbox.close();
        }
    }
});
