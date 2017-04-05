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

/* Class: LemmaSuggTagset

   Class for the lemma suggestion "tagset".  This class does not handle any
   annotations, but rather enables the link to an external resource.
 */
var LemmaSuggTagset = new Class({
    Extends: Tagset,
    Implements: LemmaAutocomplete,  // for splitting the external IDs

    classname: 'Columns.lemmaSuggestion',
    eventString: 'click:relay(div.editTableLemmaLink)',
    searchable: false,
    exportable: false,
    windowTarget: "coraExternalLemmaLink",

    /* Constructor: Tagset

       Instantiate a new LemmaSuggTagset.

       Parameters:
         data - A data object containing tagset information.
     */
    initialize: function(data) {
        this.parent(data);
    },

    /* Function: buildTemplate

       Update an editor line template for this tagset.

       Parameters:
         td - Table cell element to update
     */
    buildTemplate: function(td) {
    },

    /* Function: getEventData

       This is a copy of the default event handler in Tagset.js; it is
       re-implemented here to override the new getEventData() provided by
       LemmaAutocomplete, which is not wanted here.
     */
    getEventData: function() {
        return [{type: this.eventString, handler: this.handleEvent.bind(this)}];
    },

    /* Function: handleEvent

       Extracts the lemma value and opens an external link.

       Returns:
         Null.  The action of opening the external link should not modify
         any annotations.
     */
    handleEvent: function(event, target) {
        var input = target.getParent('tr').getElement('input.et-input-lemma');
        if (input !== null)
            this.openExternalLemmaLink(input.get('value'));
        return null;
    },

    /* Function: fill

       Fill the approriate elements in a <tr> with annotation from a token data
       object.

       Parameters:
         tr - Table row to fill
         data - An object possibly containing annotations ({anno_pos: ...} etc.)
     */
    fill: function(tr, data) {
    },

    /* Function: openExternalLemmaLink

       Opens a link to an external website.
     */
    openExternalLemmaLink: function(value) {
        var split, targetURL = null;
        if (this.settings && typeof(value) !== "undefined" && value !== null) {
            split = this._acSplitExternalId(value);
            if(split[1].length > 0) {
                if(this.settings['url_with_id'])
                    targetURL = this.settings['url_with_id'].substitute({value: split[1]});
            }
            else {
                if(this.settings['url_with_lemma'])
                    targetURL = this.settings['url_with_lemma'].substitute({value: split[0]});
            }
        }
        if(targetURL) {
            window.open(targetURL, this.windowTarget);
        } else {
            gui.showNotice('error', _("Banner.tagsetLemmaSuggNoURL"));
        }
    }
});
