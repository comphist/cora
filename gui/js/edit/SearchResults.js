/* Class: SearchResults

   Wraps the results of a search request from TokenSearcher, manages the search
   results tab and navigation within the results.
 */
var SearchResults = new Class({
    Implements: [DataSource],

    parent: null,
    criteria: {},
    criteriaHtmlId: 'searchResultsCriteria',
    data: [],
    dataTable: null,
    dataTableHtmlId: 'searchTable',
    currentSearchIdx: 0,
    searchIdxByNum: {},

    panel: null,
    buttonBack: null,
    buttonForward: null,

    /* Constructor: SearchResults

       Create a new instance of SearchResults.

       Parameters:
         parent - The parent EditorModel
         criteria - Object containing the search criteria
         data - Array of lines representing the search results
         panel - Panel in which to activate back/forward buttons
     */
    initialize: function(parent, criteria, data, panel) {
        this.parent = parent;
        this.criteria = criteria;
        this.data = data;
        this.panel = panel;

        Array.each(this.data, function(line, idx) {
            this.searchIdxByNum[line.num] = idx;
        }.bind(this));

        this._initializeDataTable();
        this._initializeButtons();
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
                           onUpdate: this.parent.update.bind(parent),
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
                gui.changeTab('edit');
                this.gotoSearchResult(this.searchIdxByNum[id], id);
            }.bind(this)
        );

        this.dataTable.render();
    },

    /* Function: _initializeButtons

       Activate buttons to step backwards/forwards within the search results.
     */
    _initializeButtons: function() {
        var elem, panel = $(this.panel);
        if(panel === null || typeof(panel) === "undefined")
            return;
        elem = panel.getElement('span.btn-search-back');
        if (elem != null) {
            elem.removeEvents('click');
            elem.addEvent('click', function() {
                this.gotoSearchResult(this.currentSearchIdx - 1);
            }.bind(this));
            this.buttonBack = elem;
        }
        elem = panel.getElement('span.btn-search-forward');
        if (elem != null) {
            elem.removeEvents('click');
            elem.addEvent('click', function() {
                this.gotoSearchResult(this.currentSearchIdx + 1);
            }.bind(this));
            if (this.data.length > 1)
                elem.removeClass('start-disabled');
            this.buttonForward = elem;
        }
    },

    /* Function: gotoSearchResult

       Makes the editor table jump to a specific search entry.

       Parameters:
         idx - Index of the search results (e.g., 0 for first result)
         num - (optional) Number of the corresponding token
     */
    gotoSearchResult: function(idx, num) {
        if(num === null || typeof(num) === "undefined") {
            num = this.data[idx].num;
        }
        this.currentSearchIdx = idx;
        this.parent.dataTable.pages.setPageByLine(num + 1, true);

        if(this.buttonBack !== null) {
            if (idx == 0)
                this.buttonBack.addClass('start-disabled');
            else
                this.buttonBack.removeClass('start-disabled');
        }
        if(this.buttonForward !== null) {
            if (idx == (this.data.length - 1))
                this.buttonForward.addClass('start-disabled');
            else
                this.buttonForward.removeClass('start-disabled');
        }
    },

    /* Function: renderSearchCriteria

       Displays the search criteria in a human-readable <div>.
     */
    renderSearchCriteria: function() {
        var text, ul, div = $(this.criteriaHtmlId);
        if (div === null || typeof(div) === "undefined")
            return;
        div.getElement('.srl-count').set('text', this.data.length);
        text = (this.data.length == 1) ? _("EditorTab.Forms.result") : _("EditorTab.Forms.results");
        div.getElement('.srl-agr-count').set('text', text);

        text = (this.data.length == 1) ? _("EditorTab.Forms.resultInfoHead1") : _("EditorTab.Forms.resultInfoHead2");
        div.getElement('.srl-head-count').set('text',text);

        text = (cora.strings.search_condition.operator[this.criteria.operator] === "alle") ? _("EditorTab.Forms.allFulfilled") : _("EditorTab.Forms.oneFulfilled");
        div.getElement('.srl-operator').set('text', text);

        text = (this.criteria.operator === "all") ? _("EditorTab.Forms.ofThese2") : _("EditorTab.Forms.ofThese1");
        div.getElement('.srl-agr-operator').set('text', text);

        ul = div.getElement('.srl-condition-list');
        ul.empty();
        this.criteria.conditions.each(function(condition) {
            var li = new Element('li');
            
            text = cora.strings.search_condition.field[condition.field] + " ";

            text += _(cora.strings.search_condition.match[condition.match]) + " ";
            
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
        if (typeof(callback) === "function")
            callback(this._slice(this.data, start, end));
    },

    /* Function: applyChanges

       Apply a set of changes to a data object.
     */
    applyChanges: function(data, changes) {
        this.parent.applyChanges(data, changes, 'search');
    },

    /* Function: updateProgress

       Callback function of DataTable that is invoked whenever the progress bar
       updates.
     */
    updateProgress: function(num, changes) {
        this.parent.updateProgress(num, changes);
    },

    /* Function: destroy

       Destroys all objects associated with this instance.
     */
    destroy: function() {
        this.dataTable.empty();
        if(this.buttonBack !== null)
            this.buttonBack.addClass('start-disabled');
        if(this.buttonForward !== null)
            this.buttonForward.addClass('start-disabled');
        gui.hideTabButton('search');
    }
});
