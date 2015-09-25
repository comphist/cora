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
        if(cls == "sec_comment")
            return new SecondaryCommentTagset(data);
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
