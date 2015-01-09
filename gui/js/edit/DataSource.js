/* Class: DataSource

   Defines an interface for classes that can be used as data sources for
   DataTable.

   This class does not implement any of the core functions on its own, but
   rather just defines the interface that implementing classes must follow, to
   be more explicit about which functions DataTable requires for its data
   sources.
 */
var DataSource = new Class({
    _slice: function(x, a, b) {
        var arr = [];
        for(var j=a; j<b; ++j) {
            arr.push(x[j]);
        }
        return arr;
    },

    /* Function: get

       Retrieves a data entry by its num attribute.

       Parameters:
         num - Index of the data entry

       Returns:
         The respective data entry; if it does not exist or has not been
         fetched from the server yet, undefined is returned instead.
     */
    get: function(num) {
        return undefined;
    },

    /* Function: getRange

       Retrieves a set of data entries in a given range.

       Parameters:
         start - Index of the first data entry to be retrieved
         end   - Index right *after* the last data entry to be retrieved
         callback - Callback function to invoke, with the set of data entries
                    as the first argument

       Returns:
         False if the data does not exist or an asynchronous server call has
         to be made first; true if the data was already loaded.  Use the
         callback parameter to actually get to and process the data.
     */
    getRange: function(start, end, callback) {
        return false;
    },

    /* Function: applyChanges

       Apply a set of changes to a data object.

       Parameters:
         data - The original data object
         changes - A set of changes to apply
     */
    applyChanges: function(data, changes) {
        Object.each(changes, function(value, key) {
            console.log("DataSource: "+data.num+": set '"+key+"' to '"+value+"'");
            data[key] = value;
        });
    }
});
