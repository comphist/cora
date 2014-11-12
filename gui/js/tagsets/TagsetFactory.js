/* Class: cora.tagsetFactory

   Selects the proper Tagset object for a given tagset class and instantiates
   it.
 */
cora.tagsetFactory = {
    /* Function: make

       Selects the proper Tagset object for the given tagset class and
       instantiates it with the provided data.

       Parameters:
         data - Data object to pass to the Tagset class's constructor
         ts_class - A tagset class; if empty, the information is taken
                    from data.class
     */
    make: function(data, ts_class) {
        var cls = ts_class || data.class;
        if(cls == "pos")
            return new POSTagset(data);
        return new Tagset(data);
    }
};
