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
