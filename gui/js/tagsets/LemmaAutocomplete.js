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

/* Class: LemmaAutocomplete

   Provides auto-complete functionality to an <input> field.
 */
var LemmaAutocomplete = new Class({
    _acSplitRe: new RegExp("^(.*) \\[(.*)\\]$"),
    acEventString: "LemmaAutocompleteSelect",

    /* Function: makeNewAutocomplete

       Enables auto-completion for a given input field.

       Parameters:
         elem - The <input> element to receive auto-completion
         query - The action type for the query string (?do=...)
         data - An object containing annotations
     */
    makeNewAutocomplete: function(elem, query, data) {
        var meio = elem.retrieve('meio');
        if (meio != null)
            meio.destroy();
        meio = new Meio.Autocomplete(elem, 'request.php?do='+query,
                       {
                           delay: 100,
                           urlOptions: {
                               extraParams: [
                                   {name: 'linenum', value: data.id}
                               ]
                           },
                           filter: {
                               filter: this._acFilter.bind(this),
                               formatMatch: this._acFormatMatch.bind(this),
                               formatItem: this._acFormatItem.bind(this)
                           }
                       });
        meio.addEvent('select', this._acOnSelect.bind(this));
        elem.store('meio', meio);
    },

    /* Function: getEventData

       As lemma autocompletion does not trigger 'input' or 'change' events on
       the lemma field, we use an artificial 'LemmaAutocompleteSelect' event to
       trigger and handle these changes.  Therefore we need to return two
       separate events for lemma tagsets implementing LemmaAutocomplete.
     */
    getEventData: function() {
        return [{type: this.eventString, handler: this.handleEvent.bind(this)},
                {type: this.acEventString, handler: this.acHandleEvent.bind(this)}];
    },

    acHandleEvent: function(event, target) {
        return {cls: 'lemma_ac', value: event};
    },

    _acFilter: function(text, data) {
        // Input was already filtered by PHP/MySQL, so we trust it -- prevents
        // problems with certain Unicode characters being handled differently in
        // PHP/MySQL and here
        return true;
    },

    _acFormatMatch: function(text, data, i) {
        return data.v;
    },

    _acFormatItem: function(text, data) {
        var sdata = this._acSplitExternalId(data.v);
        var item = (text && data.t == "q") ?
                ('<strong>' + sdata[0].substr(0, text.length) + '</strong>'
                 + sdata[0].substr(text.length))
                : sdata[0];
        if (sdata[1] != "")
            item = item + " <span class='ma-greyed'>[" + sdata[1] + "]</span>";
        if (data.t == "c")
            item = "<span class='ma-confirmed'>" + item + "</span>";
        if (data.t == "s" || data.t == "c")
            item = "<div class='ma-sugg'>" + item + "</div>";
        return item;
    },

    _acSplitExternalId: function(value) {
        var match = this._acSplitRe.exec(value);
        return (match == null) ? [value, ""] : [match[1], match[2]];
    },

    _acOnSelect: function(e, v, text, index) {
        var flag = (v.t == "c") ? 1 : 0,
            flagDiv = e.field.node.getParent('tr').getElement('div.editTableLemma');
        e.field.node.getParent('table')
            .fireEvent(this.acEventString,
                       [{lemma: text, lemma_verified: flag}, e.field.node]);
        //this.callback(e.field.node, 'lemma', text);
        //this.callback(e.field.node, 'flag_lemma_verified', flag);
    }
});
