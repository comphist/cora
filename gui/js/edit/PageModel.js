/* Class: PageModel

   Model representing a page in the editor window.

   Contains functions related to page navigation, calculating which line appears
   on which page, etc.
 */
var PageModel = new Class({
    parent: null,
    lineJumper: null,
    tokenSearcher: null,
    panels: [],
    maxPage: 0,
    activePage: 0,

    initialize: function(parent) {
        this.parent = parent;
        this._calculateMaxPage();
        this.lineJumper = new LineJumper(this, $('jumpToLineForm'));
        this.tokenSearcher = new TokenSearcher(this, $('searchTokenForm'));
    },

    /* Function: _calculateMaxPage

       Calculates the total number of pages with the given display
       settings.
     */
    _calculateMaxPage: function() {
        var lines_per_page = userdata.noPageLines;
        var lines_context  = userdata.contextLines;
        var x = (this.parent.lineCount - lines_context);
        var y = (lines_per_page - lines_context);
        this.maxPage = (x % y) ? Math.ceil(x/y) : (x/y);
        return this;
    },

    /* Function: update

       Recalculate the page count and change the active page to a
       valid number, if necessary.
     */
    update: function() {
        this._calculateMaxPage();
        this.set(this.activePage);
        return this;
    },

    /* Function: addPanel

       Adds a <div> toolbar element that acts as a container for this
       page panel.  Recognized elements within this <div> are attached
       events and are updated when the page panel updates.

       Parameters:
         div - A toolbar <div> to contain this page panel
     */
    addPanel: function(div) {
        this._activatePageBackForward(div)
            ._activateJumpToLine(div)
            ._activateSearch(div)
            ._activateJumpToPage(div);
        this.panels.push(div);
        return this;
    },

    /* Function: _activatePageBackForward

       Activates buttons to jump back/forward by one page.
     */
    _activatePageBackForward: function(div) {
        var elem;
        /* page back */
        elem = div.getElement('span.btn-page-back');
        if (elem != null) {
            elem.removeEvents('click');
            elem.addEvent('click', function() {
                this.set(this.activePage - 1).render();
            }.bind(this));
        }
        /* page forward */
        elem = div.getElement('span.btn-page-forward');
        if (elem != null) {
            elem.removeEvents('click');
            elem.addEvent('click', function() {
                this.set(this.activePage + 1).render();
            }.bind(this));
        }
        return this;
    },

    /* Function: _activateJumpToLine

       Activates button that allows jumping to a specific line.
     */
    _activateJumpToLine: function(div) {
        var elem = div.getElement('span.btn-jump-to');
        if (elem != null) {
            elem.removeEvents('click');
            elem.addEvent('click', function() {
                this.lineJumper.open();
            }.bind(this));
        }
        return this;
    },

    /* Function: _activateSearch

       Activates button that allows searching within the text.
     */
    _activateSearch: function(div) {
        var elem = div.getElement('span.btn-text-search');
        if (elem != null && userdata.admin) {  // HACK while this is in development
            elem.removeEvents('click');
            elem.addEvent('click', function() {
                this.tokenSearcher.open();
            }.bind(this));
        }
        return this;
    },

    /* Function: _activateJumpToPage

       Activates element that allows jumping to a specific page.
     */
    _activateJumpToPage: function(div) {
        var elem = div.getElement('span.btn-page-count');
        if (elem == null)
            return this;
        var input = elem.getElement('input.btn-page-to');
        var span  = elem.getElement('span.page-active');
        if (input != null && span != null) {
            var changePage = function(event) {
                this.set(input.get('value').toInt()).render();
                input.hide();
                span.show('inline');
            }.bind(this);
            elem.removeEvents('click');
            elem.addEvent('click', function() {
                if (span.isVisible()) {
                    span.hide();
                    input.set('value', this.activePage).show('inline').focus();
                    input.select();
                }
            }.bind(this));
            input.removeEvents();
            input.addEvents({
                keydown: function(event) {
                    if (event.key == "enter")
                        changePage();
                },
                blur: changePage,
                mousewheel: function(event) {
                    var i = event.target, v = i.get('value').toInt();
                    i.focus();
                    if (event.wheel > 0 && v < this.maxPage)
                        v++;
                    else if (event.wheel < 0 && v > 1)
                        v--;
                    i.set('value', v);
                    event.stop();
                }.bind(this)
            });
        }
        return this;
    },

    /* Function: _updatePageCounter

       Updates the element that displays current and maximum page
       numbers.
     */
    _updatePageCounter: function() {
        var elem = null;
        Array.each(this.panels, function(panel) {
            elem = panel.getElement('span.page-active');
            if (elem != null)
                elem.set('text', this.activePage);
            elem = panel.getElement('span.page-max');
            if (elem != null)
                elem.set('text', this.maxPage);
        }.bind(this));
        return this;
    },

    /* Function: set

       Sets the active page to a specific page number.
     */
    set: function(page) {
        if (page === null || page < 1) {
            this.activePage = 1;
        } else if (page > this.maxPage) {
            this.activePage = this.maxPage;
        } else {
            this.activePage = page;
        }
        this._updatePageCounter();
        return this;
    },

    /* Function: increment

       Increments the active page by one, if possible.

       Returns:
         True if the page number changed, false otherwise.
    */
    increment: function() {
        var former = this.activePage;
        this.set(this.activePage + 1);
        return (this.activePage > former);
    },

    /* Function: decrement

       Decrements the active page by one, if possible.

       Returns:
         True if the page number changed, false otherwise.
    */
    decrement: function() {
        var former = this.activePage;
        this.set(this.activePage - 1);
        return (this.activePage < former);
    },

    /* Function: getRange

       Gets the line numbers where a given page starts and ends.

       Parameters:
         page - Number of the page

       Returns:
         {from: <start>, to: <end>}, where <start> is the first
         line of the given page and <end> is the last
     */
    getRange: function(page) {
        var start, end;
	var cl = userdata.contextLines;
	var pl = userdata.noPageLines;
	if (page === null || page < 1) {
            page = 1;
        } else if (page > this.maxPage) {
            page = this.maxPage;
        }
	end   = page * (pl - cl) + cl;
	start = end - pl;
        end   = Math.min(end, this.parent.lineCount);
        return {from: start, to: end};
    },

    /* Function: getPageByLine

       Calculates the page number which contains a given line.

       Parameters:
         line - Number of the line

       Returns:
         The page number that holds the given line.
     */
    getPageByLine: function(line) {
	if (line > this.parent.lineCount) {
	    line = this.parent.lineCount;
	}
	var y = (userdata.noPageLines - userdata.contextLines);
	return (line % y) ? Math.ceil(line/y) : (line/y);
    },

    /* Function: setPageByLine

       Sets the current page to the one that contains a given line number.

       Parameters:
         line - Number of the line
     */
    setPageByLine: function(line) {
        return this.set(this.getPageByLine(line));
    },

    /* Function: isValidLine

       Checks if a given line number is valid.
     */
    isValidLine: function(line) {
        return (line > 0 && line <= this.parent.lineCount);
    },

    /* Function: render

       Makes the parent editor model (re-)render the currently active
       page.
     */
    render: function() {
        var range = this.getRange(this.activePage);
        this.parent.renderLines(range.from, range.to);
        return this;
    }
});
