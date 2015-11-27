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


var cora = {
    strings: {}
};
var gui = {changeTab: function() {}};

(function() {
    var idx, len, chain;

    var initialize = function() {
        $('loginTabButton').set('active', 'true');
        $('loading').hide();
        $('main').show();
        $$('#menu ul').setStyle('visibility', 'visible');

        var uri = new URI();
        if(uri.parsed && uri.parsed.query) {
            var fid = uri.parsed.query.parseQueryString()["f"];
            var form = document.getElement('#loginDiv form');
            if(fid && form) {
                form.set('action', form.get('action') + "?f=" + fid);
            } else {
                history.replaceState({}, "", "./");
            }
        }
    };

    $LAB.setGlobalDefaults({AlwaysPreserveOrder: true});
    chain = $LAB;
    for (idx = 0, len = _srcs.framework.length; idx < len; ++idx) {
        chain = chain.script(_srcs.framework[idx]);
    }
    chain.wait(function() {
        if (document.readyState == "complete")
            initialize();
        else
            window.addEvent('domready', initialize);
    });

    // pre-load the rest anyway, but without the initializing stuff
    for (idx = 0, len = _srcs.main.length; idx < len; ++idx) {
        chain = chain.script(_srcs.main[idx]);
    }
}());
