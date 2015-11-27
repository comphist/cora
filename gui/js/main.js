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

(function() {
    var idx, len, chain;

    var initialize = function() {
        Locale.use(cora.settings.get('locale'));
        gui.initialize({
            startHidden: ['edit', 'search', 'admin'],
            startTab: default_tab,
            showNews: true,
            locale: cora.settings.get('locale')
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
