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
                var id = this.getRowNumberFromElement(target);
		if (this.dropdownContent.isVisible()) {
		    this.dropdownContent.hide();
                    if (this.dropdownCurrentId === id) {
                        this.dropdownCurrentId = null;
                        return;
                    }
                }
                this.dropdownCurrentId = id;
                this.dropdownContent.setPosition({
                    x: target.getPosition().x,
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
            text: entry.text,
            events: {
                click: function(event, target) {
                    entry.action(this.dropdownCurrentId);
                }.bind(this)
            }
        }));
        this.dropdownList.grab(li);
    },

    /* Function: addDropdownEntries

       Add a list of new entries to the dropdown menu.
     */
    addDropdownEntries: function(entries) {
        Array.each(entries, this.addDropdownEntry.bind(this));
    }
});
