/* Class: SplitClassTagset

   Contains functions for tagsets that split up their tags, e.g. POS+morph.
 */
var SplitClassTagset = new Class({
    split_class: true,
    tags_for: {},
    optgroup_for: {},

    /* Function: _makeSplitTaglist

       Takes a list of (combined) tags and splits them up.

       Returns:
         An object of the form {tag1: [subtag1, subtag2, ...], tag2: ...}
     */
    _makeSplitTaglist: function(taglist) {
        var tags = {};
        var splitTag = function(tag) {
            var idx = tag.indexOf('.');
            if(idx<0 || idx==(tag.length-1))
                return [tag, null];
            return [tag.substr(0, idx), tag.substr(idx+1)];
        };
        Array.each(taglist, function(tag) {
            var split = splitTag(tag);
            if(typeof(tags[split[0]]) === "undefined")
                tags[split[0]] = [];
            if(split[1] !== null)
                tags[split[0]].push(split[1]);
        });
        return tags;
    },

    /* Function: processSplitTags

       Goes over the taglist for this tagset, splits up all tags, and populates
       the 'tags_for' and 'optgroup_for' objects.
     */
    processSplitTags: function() {
        this.tags_for = this._makeSplitTaglist(this.tags);
        Object.each(this.tags_for, function(subtags, maintag) {
            this.optgroup_for[maintag] = this.generateOptgroupFor(subtags,
                                             "Alle Tags für '" + maintag + "'");
        }.bind(this));
    }
});
