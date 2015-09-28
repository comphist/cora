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

/* Class: DataTableProgressBar

   Extension for DataTable implementing progress bar functionality.
 */
var DataTableProgressBar = new Class({
    progressMarker: -1,

    /* Function: initializeProgressBar

       Adds events to the progress bar elements.
     */
    initializeProgressBar: function() {
        this.progressMarker = this.options.progressMarker;
        this.table.addEvent(
            'click:relay(div.editTableProgress)',
            function(event, target) {
                var checked = !(target.hasClass('editTableProgressChecked')),
                    id = this.getRowNumberFromElement(target);
                if (!checked)
                    --id;
                this.updateProgressBar(id);
            }.bind(this)
        );
    },

    _fillProgress: function(row, num) {
        var progress = row.getElement('div.editTableProgress');
        if (this.progressMarker >= Number.from(num))
            progress.addClass('editTableProgressChecked');
        else
            progress.removeClass('editTableProgressChecked');
    },

    /* Function: updateProgressBar

       Sets the progress marker to a specific number.

       All data points with a "num" attribute lesser or equal than the progress
       marker will be shown with an activated progress bar.

       HACK: Progress should really be a feature of the data itself,
       as this function now requires access to stuff it should have no
       business accessing.

       Parameters:
         num - Last row number with activated progress bar
     */
    updateProgressBar: function(num) {
        var changes = {};
        if (num == this.progressMarker)
            return;
        this.redrawProgressMarker(num);
        this.fireEvent('updateProgress', [num, changes]);
        this.dataSource.applyChanges({}, changes);
    },

    /* Function: redrawProgressMarker

       Re-renders the progress marker for all rows.
     */
    redrawProgressMarker: function(num) {
        this.progressMarker = num;
        var rows = this.table.getElements('tbody tr');
        rows.each(function (row) {
            var rownum = this.getRowNumberFromElement(row);
            this._fillProgress(row, rownum);
        }.bind(this));
    }
});
