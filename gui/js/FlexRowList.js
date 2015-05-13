/** @file
 * A list object that allows the user to dynamically add or delete rows.
 *
 * @author Marcel Bollmann
 */

/* Class: FlexRowList

   A list (e.g., <ul>) element that allows the user to dynamically add
   or delete rows.
 */
var FlexRowList = new Class({
    container: null,
    rowTemplate: null,
    entries: 0,

    /* Function: initialize

       Create a new FlexRowList object.

       Parameters:
         container - The <ul>/<ol> element to become a FlexRowList
         template - Template for a newly added row
     */
    initialize: function(container, template) {
        this.container = container.empty().addClass("flexrow");
        this.rowTemplate = template.clone().addClass("flexrow-content");
        this.rowTemplate.grab(this._makeDeleteButton());
        this.rowTemplate.grab(this._makeAddButton());
        this._addContainerEvents();
    },

    _addContainerEvents: function() {
        this.container.removeEvents('click');
        this.container.addEvent(
            'click:relay(span)',
            function(event, target) {
                if(target.hasClass("flexrow-add-btn")) {
                    this.grabNewRow();
                } else if(target.hasClass("flexrow-del-btn")) {
                    this.destroy(target.getParent('li'));
                }
            }.bind(this)
        );
        return this;
    },

    _makeAddButton: function() {
        return new Element('span',
                           {'class': "oi oi-shadow flexrow-add-btn",
                            'data-glyph': "plus",
                            'aria-hidden': "true"});
    },

    _makeDeleteButton: function() {
        return new Element('span',
                           {'class': "oi oi-shadow flexrow-del-btn",
                            'data-glyph': "minus",
                            'aria-hidden': "true"});
    },

    /* Function: grabNewRow

       Add a new row cloned from the row template to this element.
     */
    grabNewRow: function() {
        var row = this.rowTemplate.clone();
        row.inject(this.container, 'bottom');
        this.entries++;
        return row;
    },

    /* Function: grab

       Add a specific row to the bottom of this container.
     */
    grab: function(li) {
        li.addClass("flexrow-content").inject(this.container, 'bottom');
        this.entries++;
        return this;
    },

    /* Function: getAllRows

       Get all content rows from the container.
     */
    getAllRows: function() {
        return this.container.getElements('li.flexrow-content');
    },

    /* Function: destroy

       Destroys a specific row in the container.  Checks if this is the last row
       remaining, and inserts an empty row if necessary.
     */
    destroy: function(li) {
        li.destroy();
        this.entries--;
        if(this.entries < 1)
            this.grabNewRow();
    },

    /* Function: empty

       Destroys all rows in the container.
     */
    empty: function() {
        this.container.getElements('li.flexrow-content').destroy();
        this.entries = 0;
        return this;
    }
});
