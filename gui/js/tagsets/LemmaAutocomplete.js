/* Class: LemmaAutocomplete

   Provides auto-complete functionality to an <input> field.
 */
var LemmaAutocomplete = new Class({
    _acSplitRe: new RegExp("^(.*) \\[(.*)\\]$"),

    /* Function: makeNewAutocomplete

       Enables auto-completion for a given input field.

       Parameters:
         elem - The <input> element to receive auto-completion
         query - The action type for the query string (?do=...)
         data - An object containing annotations
     */
    makeNewAutocomplete: function(elem, query, data) {
        var meio = new Meio.Autocomplete(elem, 'request.php?do='+query,
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
        meio.addEvent('select', this._acOnSelect.bind({
            callback: this.updateAnnotation, num: data.num
        }));
    },

    _acFilter: function(text, data) {
        if (data.t == "s" || data.t == "c")
            return true;
        return text ?
            data.v.standardize().test(
                new RegExp("^" + text.standardize().escapeRegExp(), 'i')
            )
            : true;
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
        var flag = (v.t == "c") ? 1 : 0;
        this.callback(e.field.node, this.num, 'lemma', text);
        this.callback(e.field.node, this.num, 'flag_lemma_verified', flag);
    }
});
