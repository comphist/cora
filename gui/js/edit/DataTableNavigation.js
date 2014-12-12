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
