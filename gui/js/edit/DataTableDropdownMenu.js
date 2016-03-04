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

/* Class: DataTableDropdownMenu

   Extension for DataTable implementing a dropdown menu.
 */
var DataTableDropdownMenu = new Class({
    dropdownContent: null,
    dropdownList: null,
    dropdownCurrentId: null,

    /* Function: initializeDropdown

       Adds events to show/hide the dropdown menu.
     */
    initializeDropdown: function() {
        this.dropdownContent = new Element('div',
                                           {class: 'editTableDropdownMenu'});
        this.dropdownList = new Element('ul');
        this.dropdownContent.grab(this.dropdownList);
        $(document.body).grab(this.dropdownContent);

	this.table.addEvent(
	    'click:relay(div.editTableDropdown)',
	    function(event, target) {
                var dim, id = this.getRowNumberFromElement(target);
		if (this.dropdownContent.isVisible()) {
		    this.dropdownContent.hide();
                    if (this.dropdownCurrentId === id) {
                        this.dropdownCurrentId = null;
                        return;
                    }
                }
                dim = this.dropdownContent.getDimensions();
                this.dropdownCurrentId = id;
                this.dropdownContent.setPosition({
                    x: target.getPosition().x - dim.width + 10,
                    y: target.getPosition().y + target.getSize().y
                });
		this.dropdownContent.show();
	    }.bind(this)
	);

	$(document.body).addEvent(
	    'click',
	    function(event, target) {
		if(this.dropdownContent !== null &&
		   (!event.target || !$(event.target).hasClass("editTableDropdownIcon"))) {
		    this.dropdownContent.hide();
                    this.dropdownCurrentId = null;
		}
	    }.bind(this)
	);
    },

    /* Function: addDropdownEntry

       Add a new entry to the dropdown menu.

       Parameters:
         entry - Object containing the following fields:
           * name - Internal name of the entry
           * text - Text to show in the dropdown menu
           * action - Callback function to invoke for this menu entry;
                      this function gets the row number as first parameter.
     */
    addDropdownEntry: function(entry) {
        var li = new Element('li');
        li.grab(new Element('a', {
            class: 'editTableDdButton'+entry.name,
            text: _(entry.data_trans_id),
            'data-trans-id': entry.data_trans_id,
            events: {
                click: function(event, target) {
                    entry.action(this.dropdownCurrentId);
                }.bind(this)
            }
        }));
        this.dropdownList.grab(li);
        this.setVisibility('dropdown', true);
    },

    /* Function: addDropdownEntries

       Add a list of new entries to the dropdown menu.
     */
    addDropdownEntries: function(entries) {
        Array.each(entries, this.addDropdownEntry.bind(this));
    }
});
