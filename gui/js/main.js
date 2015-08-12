
var cora = {
    strings: {}
};

(function() {
    var idx, len, chain;

    var initialize = function() {
        Locale.use("de-DE");
        gui.initialize({
            startHidden: ['edit', 'search', 'admin'],
            startTab: default_tab,
            showNews: true
        });
        $$('#menu ul').setStyle('visibility', 'visible');
        $('main').show();
        $('loading').hide();
        $('menuRight').show();
        cora.fileManager.initialize();
        cora.projects.onUpdate(cora.fileManager.render.bind(cora.fileManager));
        cora.projects.performUpdate();
        cora.settings.initialize();
        cora.fileImporter.initialize();
        cora.tagsets.performUpdate();
    };

    var initialize_admin = function() {
        cora.noticeEditor.initialize();
        cora.projects.onInit(cora.userEditor.initialize.bind(cora.userEditor));
        cora.projectEditor.initialize();
        cora.tagsetEditor.initialize();
        cora.annotatorEditor.initialize();
        gui.addToggleEventCollapseAll('adminViewCollapseAll', 'div#adminDiv');
        gui.addToggleEventExpandAll('adminViewExpandAll', 'div#adminDiv');
        gui.showTabButton('admin');
    };

    $LAB.setGlobalDefaults({AlwaysPreserveOrder: true});
    chain = $LAB;
    for (idx = 0, len = _srcs.framework.length; idx < len; ++idx) {
        chain = chain.script(_srcs.framework[idx]);
    }
    for (idx = 0, len = _srcs.main.length; idx < len; ++idx) {
        chain = chain.script(_srcs.main[idx]);
    }
    chain.wait(function() {
        if (document.readyState == "complete")
            initialize();
        else
            window.addEvent('domready', initialize);
    });

    if (userdata.admin) {
        for (idx = 0, len = _srcs.admin.length; idx < len; ++idx) {
            chain = chain.script(_srcs.admin[idx]);
        }
        chain = chain.wait(function() {
            if (document.readyState == "complete")
                initialize_admin();
            else
                window.addEvent('domready', initialize_admin);
        });
    }
}());

function onBeforeUnload() {
    if (cora.editor !== null && cora.editor.hasUnsavedChanges()) {
        cora.editor.save();
	return ("Es gibt noch ungespeicherte Änderungen, die verloren gehen könnten, wenn Sie fortfahren!");
    }
}
