/* Class: SearchResults

   Wraps the results of a search request from TokenSearcher, manages the search
   results tab and navigation within the results.
 */
var SearchResults = new Class({
    parent: null,
    criteria: {},
    criteriaHtmlId: 'searchResultsCriteria',
    data: [],
    dataTable: null,
    dataTableHtmlId: 'searchTable',

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
        this._initializeDataTable();
        this.renderSearchCriteria();
        gui.showTabButton('search');
    },

    /* Function: _initializeDataTable

       All initialization stuff related to the data table.
     */
    _initializeDataTable: function() {
        var parent = this.parent;
        this.dataTable =
            new DataTable(this,
                          cora.current().tagsets, parent.flagHandler,
                          {lineCount: this.data.length,
                           progressMarker: parent.lastEditedRow,
                           dropdown: false,
                           clickableText: true,
                           pageModel: {
                               panels: ['searchPagePanel', 'searchPagePanelBottom'],
                               startPage: 1
                           },
                           onUpdate: this.update.bind(this),
                           onUpdateProgress: this.updateProgress.bind(this)
                          }
                         );
	Object.each(parent.visibility, function(visible, value) {
            this.dataTable.setVisibility(value, visible);
        }.bind(this));
        this.dataTable.table.replaces($(this.dataTableHtmlId));
        this.dataTable.table.set('id', this.dataTableHtmlId);
        this.dataTable.addEvent(
            'click',  // triggers on line number & token <td> elements
            function(target, id) {
                this.dataTable.pages.setPageByLine(id + 1, true);
                gui.changeTab('edit');
            }.bind(parent)  //!! this is the main editTable, not the searchTable
        );

        this.dataTable.render();
    },

    /* Function: renderSearchCriteria

       Displays the search criteria in a human-readable <div>.
     */
    renderSearchCriteria: function() {
        var text, ul, div = $(this.criteriaHtmlId);
        if (div === null || typeof(div) === "undefined")
            return;
        div.getElement('.srl-count').set('text', this.data.length);
        text = (this.data.length == 1) ? "Ergebnis" : "Ergebnisse";
        div.getElement('.srl-agr-count').set('text', text);
        text = cora.strings.search_condition.operator[this.criteria.operator];
        div.getElement('.srl-operator').set('text', text);
        text = (this.criteria.operator === "all") ? "diese" : "dieser";
        div.getElement('.srl-agr-operator').set('text', text);
        ul = div.getElement('.srl-condition-list');
        ul.empty();
        this.criteria.conditions.each(function(condition) {
            var li = new Element('li');
            text  = cora.strings.search_condition.field[condition.field] + " ";
            text += cora.strings.search_condition.match[condition.match] + " ";
            li.set('text', text);
            if (condition.match !== "set" && condition.match !== "nset") {
                if (condition.value === "") {
                    li.set('text', text + " leer");
                } else {
                    li.grab(new Element('span', {
                        'class': 'srl-condition-value',
                        'text': condition.value
                    }));
                }
            }
            ul.grab(li);
        });
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

    /* Function: updateProgress

       Callback function of DataTable that is invoked whenever the progress bar
       updates.
     */
    updateProgress: function(num) {
        this.parent.updateProgress(num, true);
        this.parent.dataTable.progressMarker = num;
        this.parent.dataTable.render();
    },

    /* Function: destroy

       Destroys all objects associated with this instance.
     */
    destroy: function() {
        this.dataTable.empty();
        gui.hideTabButton('search');
    }
});
