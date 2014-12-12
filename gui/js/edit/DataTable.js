/* Class: DataTable

   Table element for displaying annotated tokens.
 */
var DataTable = new Class({
    Implements: [Events, Options,
                 DataTableDropdownMenu, DataTableProgressBar],
    options: {
        template: 'data-table-template',
        progressBar: true,
        dropdown: true
        // onUpdate: function(elem, data, cls, value) {}
        // onRender: function(data_array) {}
    },

    dataSource: null,
    tagsets: {},  // Tagsets represented in this table
    flagHandler: null,

    table: null,  // The content <table> element
    lineTemplate: null,  // Template <tr> element

    displayedStart: -1,
    displayedEnd: -1,

    initialize: function(ds, tagsets, flags, options) {
        this.setOptions(options);
        this.dataSource = ds;
        this.tagsets = tagsets;
        this.flagHandler = flags;
        // other initializers
        this.initializeTable();
        this.initializeTagsetSpecific();
        this.initializeEvents();
        if(this.options.progressBar)
            this.initializeProgressBar();
        if(this.options.dropdown)
            this.initializeDropdown();
    },

    initializeTable: function() {
        var template = $(this.options.template);
        this.table = new Element('table');
        this.lineTemplate = template.getElement('tbody')
                                    .getElement('tr').clone();
        this.table.grab(template.getElement('thead').clone());
        this.table.grab(new Element('tbody'));
    },

    initializeTagsetSpecific: function() {
        Object.each(this.tagsets, function(tagset, cls) {
            var elem = this.lineTemplate.getElement('td.editTable_'+cls);
            if(typeof(elem) !== "undefined")
                tagset.buildTemplate(elem);
            tagset.setUpdateAnnotation(this.update.bind(this));
            tagset.defineDelegatedEvents(this.table);
        }.bind(this));
        this.flagHandler.setUpdateAnnotation(this.update.bind(this));
        this.flagHandler.defineDelegatedEvents(this.table);
    },

    initializeEvents: function() {
        this.addEvent('update',
                      function(tr, data, cls, value) {
                          Object.each(this.tagsets, function(tagset) {
                              tagset.update(tr, data, cls, value);
                          });
                          this.flagHandler.update(tr, data, cls, value);
                      }.bind(this),
                      true);
    },

    /* Function: getRowNumberFromElement

       Gets the number of the row (used in '#' column and the <tr
       id=...> attribute) containing the supplied element.
     */
    getRowNumberFromElement: function(elem) {
        var id = elem.get('id');
        if(id === null || id.substr(0, 5) !== "line_") {
            id = elem.getParent('tr').get('id');
        }
        return Number.from(id.substr(5));
    },

    /* Function: getRowClassFromElement

       Gets the "editTable_*" class of the <td> cell.
     */
    getRowClassFromElement: function(td) {
        for (var i = 0; i < td.classList.length; i++) {
            var item = td.classList.item(i);
            if(item.substr(0, 10) === "editTable_")
                return item;
        }
        return "";
    },

    /* Function: setVisibility

       Sets visibility of an annotation column.

       Parameters:
         name - Column name (e.g., "pos")
         visible - Whether the column should be visible
     */
    setVisibility: function(name, visible) {
        var elems = this.table.getElements('.editTable_'+name);
        var temps = this.lineTemplate.getElements('.editTable_'+name);
        if(visible) {
            elems.show('table-cell');
            temps.show('table-cell');
        } else {
            elems.hide();
            temps.hide();
        }
    },

    /* Function: update

       Updates an annotation with a new value.

       Triggers the 'update' event.  The table content itself is not changed.

       Parameters:
         elem - The element that triggered the update
         cls - Annotation class that has changed
         value - New value of the annotation
     */
    update: function(elem, cls, value) {
        var tr = elem.getParent('tr');
        var data = this.dataSource.get(this.getRowNumberFromElement(tr));
        console.log("DataTable: "+data.num+": set '"+cls+"' to '"+value+"'");
        this.fireEvent('update', [tr, data, cls, value]);
    },

    /* Function: empty

       Empties the data table.
     */
    empty: function() {
        this.table.getElement('tbody').empty();
        return this;
    },

    /* Function: forceRedraw

       Forces a redraw of the data table.
     */
    forceRedraw: function() {
        this.empty().renderLines(this.displayedStart, this.displayedEnd);
        return this;
    },

    /* Function: renderLines

       Displays a given range of lines in the data table.

       Lines are requested from the dataSource object; since this request can
       involve an asynchronous server operation, a callback to renderData() is
       used to perform the actual rendering.

       Parameters:
         start - First line to be displayed
         end   - Last line to be displayed
     */
    renderLines: function(start, end) {
        this.dataSource.getRange(start, end, function(data) {
            this.displayedStart = start;
            this.displayedEnd   = end;
            this.renderData(data);
        }.bind(this));
    },

    /* Function: renderData

       Displays a given set of data in the data table.
     */
    renderData: function(data) {
        var rows;
        this.table.hide();
        this.setRowCount(data.length);
        rows = this.table.getElements('tbody tr');
        for (var i=0; i<data.length; ++i) {
            this._fillRow(data[i], rows[i]);
        }
        this.table.show();
        this.fireEvent('render', [data]);
    },

    /* Function: setRowCount

       Makes sure that the table has the given number of rows, adding or
       deleting rows as necessary.

       Is called internally by renderData().
     */
    setRowCount: function(num) {
        var i = 0,
            tbody = this.table.getElement('tbody'),
            rows = tbody.getElements('tr');
        while (typeof(rows[i]) !== "undefined") {
            if (i >= num)  // destroy superfluous rows
                rows[i].destroy();
            ++i;
        }
        while (i++ < num) {  // add missing rows
            tbody.adopt(this.lineTemplate.clone());
        }
    },

    /* Function: _fillRow

       Fills a row in the table with data.

       Parameters:
         data - Object containing the data
         row  - <tr> element to modify
     */
    _fillRow: function(data, row) {
        var lineinfo = data.page_name + data.page_side + data.col_name
                + "," + data.line_name;

        this._fillProgress(row, data.num);
        row.set('id', 'line_'+data.num);
        row.getElement('.editTable_tokenid').set('text', data.num);
        row.getElement('.editTable_line').set('text', lineinfo);
        row.getElement('.editTable_token').set('text', data.utf);
        row.getElement('.editTable_tok_trans').set('text', data.trans);
        Object.each(this.tagsets, function(tagset) {
            tagset.fill(row, data);
        });
        this.flagHandler.fill(row, data);
    }
});
