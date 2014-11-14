/* Class: LemmaSuggTagset

   Class for the lemma suggestion "tagset".  This class does not handle any
   annotations, but rather enables the link to an external resource.
 */
var LemmaSuggTagset = new Class({
    Extends: Tagset,
    Implements: LemmaAutocomplete,  // for splitting the external IDs

    baseURL: "http://www.woerterbuchnetz.de/DWB",
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

    /* Function: fill

       Fill the approriate elements in a <tr> with annotation from a token data
       object.

       Parameters:
         tr - Table row to fill
         data - An object possibly containing annotations ({anno_pos: ...} etc.)
     */
    fill: function(tr, data) {
        var ref = this;
        var elem = tr.getElement('.editTable_lemma_sugg div');
        if (elem !== null) {
            elem.removeEvents().addEvent('click', function() {
                var input = this.getParent('tr').getElement('.editTable_lemma input');
                if (input !== null)
                    ref.openExternalLemmaLink(input.get('value'));
            });
        }
    },

    /* Function: openExternalLemmaLink

       Opens a link to an external website.
     */
    openExternalLemmaLink: function(value) {
        var split, targetURL = this.baseURL;
        if (typeof(value) !== "undefined" && value !== null) {
            split = this._acSplitExternalId(value);
            if(split[1].length > 0)
                targetURL = targetURL + "?lemid=" + split[1];
            else
                targetURL = targetURL + "?lemma=" + split[0];
        }
        window.open(targetURL, this.windowTarget);
    }
});
