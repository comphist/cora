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

/* Class: HorizontalTextPreview

   Shows a preview of the text at the bottom of the page, highlighting the word
   that is currently being edited.
 */
var HorizontalTextPreview = new Class({
    container: null,
    view: null,
    spinner: null,
    previewType: "utf",
    hidden: false,
    currentStart: -1,
    currentEnd: -1,
    maxContextLength: 30,
    useTerminators: true,
    terminators: ['(.)', '(!)', '(?)'],

    /* Constructor: HorizontalTextPreview

       Creates a new text preview in the given container.

       Parameters:
         parent - The parent EditorModel object
         container - <div> to contain the text preview
    */
    initialize: function(parent, container) {
        this.parent = parent;
        this.container = container;
        this.view = this.container.getElement('div');
	this.view.empty().set('text', _("EditorTab.general.loadingPreview"));
        //this.spinner = new Spinner(this.container);
        this.setPreviewType(cora.settings.get('textPreview'));
    },

    /* Function: setPreviewType

       Set the type of token to use for the preview.

       For legacy reasons, this function accepts "off" as a parameter to turn
       the preview pane off, but you should rather use hide() instead.

       Parameters:
         ptype - The new preview type, one of {utf|trans|off}
     */
    setPreviewType: function(ptype) {
        if(ptype == "off") {
            this.hide();
            return this;
        }
        this.show();
        if(ptype == "utf") {
            this.view.addClass("text-preview-utf");
        } else {
            this.view.removeClass("text-preview-utf");
        }
        this.previewType = ptype;
        return this;
    },

    /* Function: hide

       Hides the preview pane.
    */
    hide: function() {
        this.container.hide();
        this.hidden = true;
        this.redraw();
        return this;
    },

    /* Function: show

       Shows the preview pane.
    */
    show: function() {
        this.container.show();
        this.hidden = false;
        this.redraw();
        return this;
    },

    /* Function: adjustPageMargin

       Sets the page margin so the preview pane doesn't hide content.
    */
    adjustPageMargin: function() {
        if(this.hidden) {
            $('editPanelDiv').setStyle('margin-bottom', 0);
        } else {
            if(this.container.isVisible()) {
                setTimeout(function(){
                    $('editPanelDiv').setStyle('margin-bottom',
                                               this.container.getHeight());
                }.bind(this), 100);
            } else {
                this.container.measure(function() {
                    $('editPanelDiv').setStyle('margin-bottom', this.getHeight());
                });
            }
        }
        return this;
    },

    /* Function: update

       Updates the preview to cover at least the given range of lines (tokens).

       Makes sure all necessary lines are in memory, determines the range of
       tokens to show, assembles them into the preview pane, and sets window
       borders to ensure the pane doesn't hide any content.  In short: Still
       does too much at once (refactor!).

       Parameters:
         start, end - Range of tokens that must minimally be included
    */
    update: function(start, end) {
        var data, start_bound, end_bound;

        if(this.hidden) {
            this.currentStart = start;
            this.currentEnd   = end;
            return this;
        }

        // fetch lines
	start_bound = Math.max(0, start - this.maxContextLength);
	end_bound   = Math.min(this.parent.dataTable.lineCount,
                               end + this.maxContextLength);
        if(!this.parent.isRangeLoaded(start_bound, end_bound)) {
            //if(this.spinner.hidden)
            //    this.spinner.show();
            this.parent.requestLines(start_bound, end_bound,
                                     function(){ this.update(start, end); }.bind(this),
                                     function(){});
            return this;
        } else {
            data = this.parent.data;  // TODO: make this at least a call to parent
        }

	// find nearest sentence boundaries
        if(this.useTerminators) {
	    for (; start >= start_bound; start--) {
	        if(typeof(data[start]) !== "undefined"
                   && this.terminators.contains(data[start].trans))
		    break;
	    }
	    start++;
	    for (; end < end_bound; end++) {
	        if(typeof(data[end]) !== "undefined"
                   && this.terminators.contains(data[end].trans)) {
	            end++;
		    break;
                }
	    }
        } else {
            start = start_bound;
            end   = end_bound;
        }

        this.currentStart = start;
        this.currentEnd   = end;
        this.redraw();
        //this.spinner.hide();
        return this;
    },

    /* Function: redraw

       (Re-)draws the preview pane.  Will fail if the lines aren't properly
       loaded yet; use update() in this case.
    */
    redraw: function() {
        var data = this.parent.data;
        var line, currenttok = -1;
        if(this.hidden)
            return this.adjustPageMargin();
        if(!this.parent.isRangeLoaded(this.currentStart, this.currentEnd))
            return this;

	this.view.empty();
	for (var i = this.currentStart; i < this.currentEnd; i++) {
	    line = data[i];
	    if(line.tok_id != currenttok) {
		this.view.appendText(" ");
		currenttok = line.tok_id;
	    }
	    this.view.adopt(new Element('span', {'id': 'htvl_'+line.num,
					         'text': line[this.previewType]}));
	}

        this.adjustPageMargin();
        return this;
    },

    /* Function: highlight

       Highlight a particular token in the preview pane.

       Parameters:
         lineid - Line ID of the token to highlight
    */
    highlight: function(lineid) {
	var span;
        if(this.hidden)
            return this;
	this.view.getElements('.highlighted').removeClass('highlighted');
	span = this.view.getElement('#htvl_'+lineid);
	if(span != null) {
	    span.addClass('highlighted');
	}
        return this;
    }
});
