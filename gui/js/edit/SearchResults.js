/* Class: SearchResults

   Wraps the results of a search request from TokenSearcher, manages the search
   results tab and navigation within the results.
 */
var SearchResults = new Class({
    parent: null,
    criteria: {},
    dataTable: null,

    /* Constructor: SearchResults

       Create a new instance of SearchResults.

       Parameters:
         parent - The parent EditorModel
         criteria - Object containing the search criteria
         data - Array of lines representing the search results
     */
    initialize: function(parent, criteria, data) {
        this.parent = parent;
        this.criteria = criteria;
        this.data = data;

        this.dataTable =
            new DataTable(this,
                          cora.current().tagsets, parent.flagHandler,
                          {lineCount: this.data.length,
                           progressMarker: parent.lastEditedRow,
                           pageModel: {
                               panels: ['searchPagePanel', 'searchPagePanelBottom'],
                               startPage: 1
                           },
                           onUpdate: this.update.bind(this),
                           onUpdateProgress: function(n){
                               this.lastEditedRow = n;
                           }.bind(parent)
                          }
                         );
	Object.each(parent.visibility, function(visible, value) {
            this.dataTable.setVisibility(value, visible);
        }.bind(this));
        this.dataTable.table.replaces($('searchTable'));
        this.dataTable.table.set('id', 'searchTable');
        this.dataTable.render();

        gui.showTabButton('search');
    },

    /* Function: get

       Retrieves a data entry by its num attribute.
     */
    get: function(num) {
        return this.parent.get(num);
    },

    /* Function: getRange

       Retrieves a set of data entries in a given range.
     */
    getRange: function(start, end, callback) {
        var slice = function(x, a, b) {
            var arr = [];
            for(var j=a; j<b; ++j) {
                arr.push(x[j]);
            }
            return arr;
        };

        if (typeof(callback) === "function")
            callback(slice(this.data, start, end));
    },

    /* Function: update

       Callback function of DataTable that is invoked whenever an annotation
       changes.
     */
    update: function(elem, data, cls, value) {
        this.parent.update(elem, data, cls, value, true);
        this.parent.dataTable.render();
    },

    /* Function: destroy

       Destroys all objects associated with this instance.
     */
    destroy: function() {
        gui.hideTabButton('search');
    }
});
