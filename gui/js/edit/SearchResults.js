/* Class: SearchResults

   Wraps the results of a search request from TokenSearcher, manages the search
   results tab and navigation within the results.
 */
var SearchResults = new Class({
    data: null,
    dataTable: null,

    /* Constructor: SearchResults

       Create a new instance of SearchResults.

       Parameters:
         criteria - Object containing the search criteria
         status - JSON object containing the search results
     */
    initialize: function(criteria, status) {
        this.data = status['results'];
    },

    /* Function: destroy

       Destroys all objects associated with this instance.
     */
    destroy: function() {
    }
});
