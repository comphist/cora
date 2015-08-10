
var cora = {
    strings: {}
};
var gui = {changeTab: function() {}};

(function() {
    var idx, len, chain;

    $LAB.setGlobalDefaults({AlwaysPreserveOrder: true});
    chain = $LAB;
    for (idx = 0, len = _srcs.framework.length; idx < len; ++idx) {
        chain = chain.queueScript(_srcs.framework[idx]);
    }
    chain.queueWait(function() {
        $('loginTabButton').set('active', 'true');
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
    });

    chain = chain.runQueue();
    // pre-load the rest anyway, but without the initializing stuff
    for (idx = 0, len = _srcs.main.length; idx < len; ++idx) {
        chain = chain.script(_srcs.main[idx]);
    }
}());
