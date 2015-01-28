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
        if(cls == "comment")
            return new CommentTagset(data);
        if(cls == "pos")
            return new POSTagset(data);
        if(cls == "lemmapos")
            return new LemmaPOSTagset(data);
        if(cls == "norm")
            return new NormTagset(data);
        if(cls == "norm_broad")
            return new NormBroadTagset(data);
        if(cls == "norm_type")
            return new NormTypeTagset(data);
        if(cls == "lemma")
            return new LemmaTagset(data);
        if(cls == "lemma_sugg")
            return new LemmaSuggTagset(data);
        if(cls == "boundary")
            return new BoundaryTagset(data);
        return new Tagset(data);
    }
};
