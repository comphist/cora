/* Class: LemmaSuggTagset

   Class for the lemma suggestion "tagset".  This class does not handle any
   annotations, but rather enables the link to an external resource.
 */
var LemmaSuggTagset = new Class({
    Extends: Tagset,
    Implements: LemmaAutocomplete,  // for splitting the external IDs

    classname: 'Lemma-Vorschlag',
    searchable: false,
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

    /* Function: defineDelegatedEvents

       Define events on the appropriate elements to react to user input.

       Parameters:
         elem - Parent element to add events to
     */
    defineDelegatedEvents: function(elem) {
        elem.addEvent('click:relay(div.editTableLemmaLink)', function(e, t) {
            var input = t.getParent('tr').getElement('input.et-input-lemma');
            if (input !== null)
                this.openExternalLemmaLink(input.get('value'));
        }.bind(this));
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
