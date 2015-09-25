/*
 * Copyright (C) 2015 Marcel Bollmann <bollmann@linguistics.rub.de>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy of
 * this software and associated documentation files (the "Software"), to deal in
 * the Software without restriction, including without limitation the rights to
 * use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of
 * the Software, and to permit persons to whom the Software is furnished to do so,
 * subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS
 * FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR
 * COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER
 * IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN
 * CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */

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
	    content: content.clone(),
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
            this.parent.setPageByLine(value, true);
            this.mbox.close();
        }
    }
});
