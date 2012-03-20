/** @file
 * Functions related to site navigation (tab changing, etc.)
 *
 * @author Marcel Bollmann
 * @date January 2012
 */

/** Perform initialization. Adds JavaScript events to interactive
 * navigation elements, e.g.\ clappable div containers, and selects
 * the default tab.
 */
function onLoad() {
    addToggleEvents();

    // default item defined in content.php, variable set in gui.php
    changeTab(default_tab);
}

function onBeforeUnload() {
    if (edit!==null && edit.editorModel!==null) {
	var chl = edit.editorModel.changedLines.length;
	if (chl>0) {
	    var zeile = (chl>1) ? "Zeilen" : "Zeile";
	    // Meldung wird von Firefox ignoriert... steht aber
	    // trotzdem hier, falls ein anderer Browser das anders
	    // macht
	    return ("Warnung: Sie sind im Begriff, diese Seite zu verlassen. Im geöffneten Dokument gibt es noch ungespeicherte Änderungen in "+chl+" "+zeile+", die verloren gehen, wenn Sie fortfahren.");
	}
   }
}

/** Select a new tab.  Shows the content @c div corresponding to the
 * selected menu item, while hiding all others and highlighting the
 * correct menu button.
 *
 * @tparam String tabName Internal name of the tab to be selected
 */
function changeTab(tabName) {
    var contentBox, tabButton, activeTab, i;

    // hide all tabs
    contentBox = $$(".content");
    for (i = 0; i < contentBox.length; i++) {
        contentBox[i].setStyle("display", "none");
    }

    // select correct tab button
    tabButton = $$(".tabButton");
    for (i = 0; i < tabButton.length; i++) {
        if (tabButton[i].id === tabName + "TabButton") {
            tabButton[i].set("active", "true");
        } else {
            tabButton[i].set("active", "false");
        }
    }

    // show active tab
    activeTab = $(tabName + "Div");
    if (activeTab === null) {
        alert(tabName + " tab not implemented!");
    }
    activeTab.setStyle("display", "block");
}

/** Enable clappable @c div containers.  Adds @em onClick events to
 * the <code>.clapp</code> element in each <code>.clappable</code>
 * container that toggle the visibility of its contents.  Also,
 * automatically hides all contents of <code>.starthidden</code>
 * containers.
 */
function addToggleEvents() {
    $$('.clappable').each(function (clappable) {
        var clapper, content;

        // add toggle event
        clapper = clappable.getElement('.clapp');
        content = clappable.getElement('div');
        if (clapper !== null) {
            clapper.addEvent('click', function () {
                content.toggle();
            });
        }
        // hide content by default, if necessary
        if (clappable.hasClass('starthidden')) {
            content.hide();
        }
    });
}
