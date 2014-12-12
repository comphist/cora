/* Class: DataTableProgressBar

   Extension for DataTable implementing progress bar functionality.
 */
var DataTableProgressBar = new Class({
    progressMarker: -1,

    /* Function: initializeProgressBar

       Adds events to the progress bar elements.
     */
    initializeProgressBar: function() {
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
        if (num == this.progressMarker)
            return;
        this.progressMarker = num;
        var rows = this.table.getElements('tbody tr');
        rows.each(function (row) {
            var rownum = row.getElement('.editTable_tokenid').get('text');
            this._fillProgress(row, rownum);
        }.bind(this));
        console.log("DataTable: progressMarker set to '"+num+"'");
        this.fireEvent('updateProgress', [num]);
    }
});
