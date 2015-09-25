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

/* Class: DataTableNavigation

   Extension for DataTable allowing for easier (keyboard-based) navigation.
 */
var DataTableNavigation = new Class({

    initializeNavigation: function() {
	this.table.addEvent(
            'keyup:relay(input)',
            function(event, target) {
	        var id = this.getRowNumberFromElement(target),
		    cls = this.getRowClassFromElement(target.getParent('td'));

                // up/down arrow navigation
                if ((event.code == 40 || event.code == 38)
                    && (event.control || !target.hasClass("et-input-lemma"))) {
                    var dir = (event.code == 38) ? 'up' : 'down';
                    this._performKeyNavigation(dir, id, cls);
                }
	    }.bind(this)
	);
    },

    _shiftFocus: function(nr, cls) {
        if(nr == null) return;
	var new_target = nr.getElement('td.'+cls+' input');
	if(new_target != null) {
	    new_target.focus();
	}
    },

    _performKeyNavigation: function(dir, id, cls) {
        var row, change;
        (dir === 'up') ? --id : ++id;
        row = this.getRowFromNumber(id);
        if(row !== null) {
            this._shiftFocus(row, cls);
        } else {
            change = (dir === 'up') ? this.pages.decrement() : this.pages.increment();
            if(change) {
                this.addEvent('render:once', function() {
                    this._shiftFocus(this.getRowFromNumber(id), cls);
                }.bind(this));
                this.pages.render();
            }
        }
    }
});
