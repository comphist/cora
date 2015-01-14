/* Class: DataTable

   Table element for displaying annotated tokens.
 */
var DataTable = new Class({
    Implements: [Events, Options,
                 DataTableNavigation,
                 DataTableDropdownMenu, DataTableProgressBar],
    options: {
        lineCount: 0,
        template: 'data-table-template',  /**< Line template */
        progressBar: true,  /**< Use ProgressBar extension? */
        progressMarker: 0,
        dropdown: true,  /**< Use DropdownMenu extension? */
        pageModel: {
            panels: [],
            startPage: null
        },
        clickableText: false  /**< Make line number & tokens clickable links */
        // onFocus: function(target, id) {}
        // onRender: function(data_array) {}
        // onUpdate: function(elem, data, cls, value) {}
        // onUpdateProgress: function(num) {}
    },

    dataSource: null,
    tagsets: {},  // Tagsets represented in this table
    flagHandler: null,

    table: null,  // The content <table> element
    lineTemplate: null,  // Template <tr> element
    pages: null,  // A PageModel object

    lineCount: null,
    displayedStart: -1,
    displayedEnd: -1,
    rowsByNum: {},

    /* Constructor: DataTable

       Parameters:
         ds - Data source for the table content; this must be an object
              supporting .get() and .getRange() functions to retrieve
              the data
         tagsets - An object mapping tagset classes to tagset objects;
                   this parameter represents which tagset classes will
                   be shown in the table
         flags - A flag handler object that handles flags within the table
         options - An array containing further options
     */
    initialize: function(ds, tagsets, flags, options) {
        this.setOptions(options);
        this.dataSource = ds;
        this.tagsets = tagsets;
        this.flagHandler = flags;
        this.lineCount = this.options.lineCount;
        // other initializers
        this.initializeTable();
        this.initializeTagsetSpecific();
        this.initializeEvents();
        // extensions
        if(this.options.progressBar)
            this.initializeProgressBar();
        if(this.options.dropdown)
            this.initializeDropdown();
        this.initializePageModel();
        this.initializeNavigation();
    },

    /* Function: initializeTable

       Initializes the table & line template objects.
     */
    initializeTable: function() {
        var template = $(this.options.template);
        this.table = new Element('table', {class: 'editTable'});
        this.lineTemplate = template.getElement('tbody')
                                    .getElement('tr').clone();
        if(this.options.clickableText) {
            this.lineTemplate.getElements('.editTable_tokenid').addClass('data-table-clickable');
            this.lineTemplate.getElements('.editTable_token').addClass('data-table-clickable');
            this.lineTemplate.getElements('.editTable_tok_trans').addClass('data-table-clickable');
        }
        this.table.grab(template.getElement('thead').clone());
        this.table.grab(new Element('tbody'));
    },

    /* Function: initializeTagsetSpecific
     */
    initializeTagsetSpecific: function() {
        Object.each(this.tagsets, function(tagset, cls) {
            var event_data,
                elem = this.lineTemplate.getElement('td.editTable_'+cls);
            if(typeof(elem) !== "undefined")
                tagset.buildTemplate(elem);
            event_data = tagset.getEventData();
            event_data.each(function(ev) {
                this.table.addEvent(ev.type, this.makeUpdateHandler(ev.handler));
            }.bind(this));
        }.bind(this));
        Array.each(this.flagHandler.getEventData(), function(ev) {
            this.table.addEvent(ev.type, this.makeUpdateHandler(ev.handler));
        }.bind(this));
    },

    /* Function: initializePageModel

       Initializes the page model, which takes care of page navigation,
       navigation toolbar, etc.
     */
    initializePageModel: function() {
        this.pages = new PageModel(this);
        Array.each(this.options.pageModel.panels, function(panel) {
            this.pages.addPanel($(panel));
        }.bind(this));
        if (this.options.pageModel.startPage !== null) {
            this.pages.set(this.options.pageModel.startPage);
        }
    },

    /* Function: initializeEvents

       Add events that are generally required.
     */
    initializeEvents: function() {
        // Expose a few <table> events:
        this.table.addEvent(
            'focus:relay(input,select)',
            function(event, target) {
                var id = this.getRowNumberFromElement(target);
                this.fireEvent('focus', [target, id]);
            }.bind(this)
        );
        this.table.addEvent(
            'dblclick:relay(td)',
            function(event, target) {
                var id = this.getRowNumberFromElement(target);
                this.fireEvent('dblclick', [target, id]);
            }.bind(this)
        );
        if(this.options.clickableText) {
            this.table.addEvent(
                'click:relay(td.data-table-clickable)',
                function(event, target) {
                    var id = this.getRowNumberFromElement(target);
                    this.fireEvent('click', [target, id]);
                }.bind(this)
            );
        }
    },

    /* Function: makeUpdateHandler

       Wraps an event handler in a function that also informs all other tagsets
       about the triggered event (i.e., annotation update).

       Parameters:
         tagset_handler - Event handler function to invoke.

       Returns:
         An event handler that calls the specified event handler to extract
         annotation data, then calls each tagset in this class with the
         extracted data.
     */
    makeUpdateHandler: function(tagset_handler) {
        var handler = function(event, target) {
            var parsed = tagset_handler(event, target);
            if(parsed)
                this.update(target, parsed.cls, parsed.value);
        };
        return handler.bind(this);
    },

    /* Function: getRowNumberFromElement

       Gets the number of the row containing the supplied element.
     */
    getRowNumberFromElement: function(elem) {
        var num = elem.retrieve('num');
        if(num === null) {
            num = elem.getParent('tr').retrieve('num');
        }
        return num;
    },

    /* Function: getRowFromNumber

       Gets the <tr> element corresponding to a given row number.
     */
    getRowFromNumber: function(num) {
        if(num in this.rowsByNum)
            return this.rowsByNum[num];
        else
            return null;
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

    /* Function: setLineCount

       Sets the total number of lines in the data source.
     */
    setLineCount: function(num) {
        this.lineCount = num;
        this.pages.update();
        return this;
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
        var changes = {};
        Object.each(this.tagsets, function(tagset) {
            tagset.update(tr, data, changes, cls, value);
        });
        this.flagHandler.update(tr, data, changes, cls, value);
        this.fireEvent('update', [tr, data, changes, cls, value]);
        this.dataSource.applyChanges(data, changes);
    },

    /* Function: empty

       Empties the data table.
     */
    empty: function() {
        this.table.getElement('tbody').empty();
        this.rowsByNum = {};
        return this;
    },

    /* Function: show

       Un-hides the data table.
     */
    show: function() {
        this.table.show();
        return this;
    },

    /* Function: hide

       Hides the data table.
     */
    hide: function() {
        this.table.hide();
        return this;
    },

    /* Function: render

       Displays the current page or range of lines.
     */
    render: function() {
        this.pages.render();
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
        this.rowsByNum = {};
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

    /* Function: redrawRow

       Re-renders a single row.

       If the respective row is not currently displayed, nothing happens.

       Parameters:
         num - Number of the row to redraw
         data - Data object for this row
     */
    redrawRow: function(num, data) {
        var row = this.getRowFromNumber(num);
        if(row === null)
            return;
        this._fillRow(data, row);
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
        this.rowsByNum[data.num] = row;
        row.store('num', Number.from(data.num));
        row.getElement('.editTable_tokenid').set('text', data.num + 1);
        row.getElement('.editTable_line').set('text', lineinfo);
        row.getElement('.editTable_token').set('text', data.utf);
        row.getElement('.editTable_tok_trans').set('text', data.trans);
        Object.each(this.tagsets, function(tagset) {
            tagset.fill(row, data);
        });
        this.flagHandler.fill(row, data);
    },

    /* Function: highlightRow

       Visually highlights a certain row in the editor table, and
       positions it in the middle of the screen if possible.

       Parameters:
         number - Number of the row to highlight
     */
    highlightRow: function(number) {
        var row = this.getRowFromNumber(number);
        if (row != null) {
            /* scroll to element */
            window.scrollTo(0, (row.getTop() - (window.getHeight() / 2)));
            /* tween background color */
            row.setStyle('background-color', '#999');
            setTimeout(function() {
                new Fx.Tween(row, {
                    duration: 'long',
                    property: 'background-color'
                }).start('#999', '#f8f8f8');
            }, 200);
        }
    }
});
