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

cora.strings.search_condition = {
    'field': {
        'token_all': "Token",
        'token_trans': "Token (Transkription)"
    },
    'operator': {
        'all': "alle",
        'any': "mindestens eine"
    },
    'match': {
        'set': "EditorTab.Forms.searchForm.isSet",
        'nset': "EditorTab.Forms.searchForm.isNotSet",
        'eq': "EditorTab.Forms.searchForm.is",
        'neq': "EditorTab.Forms.searchForm.isNot",
        'in': "EditorTab.Forms.searchForm.contains",
        'nin': "EditorTab.Forms.searchForm.containsNot",
        'bgn': "EditorTab.Forms.searchForm.startsWith",
        'end': "EditorTab.Forms.searchForm.endsWith",
        'regex': "EditorTab.Forms.searchForm.matchesRegEx"
    }
};

/* Class: TokenSearcher

   GUI element to perform a search within a document.
 */
var TokenSearcher = new Class({
    Implements: [Events, Options],

    parent: null,
    tagsets: null,
    flagHandler: null,

    mbox: null,
    flexrow: null,
    templateListElem: null,

    initialize: function(parent, tagsets, flags, options) {
        this.setOptions(options);
        this.parent = parent;
        this.tagsets = tagsets;
        this.flagHandler = flags;
        this.templateListElem = $(this.options.template);
        this._initializeDialog();
        gui.onEditorLocaleChange(this._initializeDialog.bind(this));
        if(this.options.panels) {
            Array.each(this.options.panels, this._activateSearch.bind(this));
        }
    },

    /* Function: _activateSearch

       Activates button that allows searching within the text.
     */
    _activateSearch: function(div) {
        var elem = $(div).getElement('span.btn-text-search');
        if (elem != null) {
            elem.removeEvents('click');
            elem.addEvent('click', function() {
                this.open();
            }.bind(this));
        }
    },

    /* Function: _initializeDialog

       Initializes the dialog window for initiating a search.
     */
    _initializeDialog: function() {
        var content = $(this.options.content);
        this._initializeTemplate();
        this.flexrow = new FlexRowList(content.getElement('.flexrow-container'),
                                       this.templateListElem);
        this.reset();
        this.mbox = new mBox.Modal({
	    content: content,
	    title: _("Action.search"),
            closeOnBodyClick: false,
	    buttons: [
                {title: _("Action.reset"), addClass: 'mform button_left',
                 event: function() { this.reset(); }.bind(this)
                },
		{title: _("Action.cancel"), addClass: 'mform'},
		{title: _("Action.search"), addClass: 'mform button_green',
		 event: function() {
                     var spinner = new Spinner(this.mbox.container);
                     spinner.show();
                     this.parent.whenSaved(
                         function() {
                             this.requestSearch(this.mbox, spinner);
                         }.bind(this),
                         null,
                         function() { spinner.hide(); }
                     );
                 }.bind(this)
		}
	    ]
	});
        this._initializeEvents();
    },

    _initializeTemplate: function() {
        this._initializeFieldSelector();
    },

    _initializeFieldSelector: function() {
        var optgroup, elemlist = [];
        var fieldSelector =
                this.templateListElem.getElement('select.editSearchField');
        var makeOption = function(a,b) {
            return new Element('option', {'value': a, 'text': b});
        };
        fieldSelector.empty();
        // Fixed elements
        fieldSelector.grab(makeOption('token_all',
                                      _("EditorTab.Forms.searchForm.tokenAll")));
        fieldSelector.grab(makeOption('token_trans',
                                     _("EditorTab.Forms.searchForm.tokenTrans")));
        // Annotation layers
        optgroup = new Element('optgroup', {'label': _("EditorTab.Forms.searchForm.annotationLevels")});
        Object.each(this.tagsets, function(tagset) {
            if(tagset.searchable) {
                elemlist.push(makeOption(tagset.class, _(tagset.classname)));
                cora.strings.search_condition.field[tagset.class] = _(tagset.classname);
            }
        });
        elemlist.sort(function(a,b) {
            return (a.get('text') > b.get('text')) ? -1 : 1;
        });
        elemlist.each(function(a) { optgroup.grab(a); });
        fieldSelector.grab(optgroup);
        // Flags
        optgroup = new Element('optgroup', {'label': _("EditorTab.Forms.searchForm.markups")});
        Object.each(this.flagHandler.flags,
                    function(flag, flagname) {
                        optgroup.grab(makeOption(flagname, _(flag.displayname)));
                        cora.strings.search_condition.field[flagname] = _(flag.displayname);
                    });
        // optgroup.grab(makeOption("flag_progress", "Fortschrittsbalken"));
        // cora.strings.search_condition.field["flag_progress"] = "Fortschrittsbalken";
        fieldSelector.grab(optgroup);
        // Set matcher
        this._fillSearchMatcher(fieldSelector);
    },

    _initializeEvents: function() {
        this.flexrow.container.addEvent(
            'change:relay(select)',
            function(event, target) {
                if(target.hasClass('editSearchField')) {
                    this._fillSearchMatcher(target);
                } else if(target.hasClass('editSearchMatch')) {
                    this._setInputField(target);
                }
            }.bind(this)
        );
    },

    /* Function: _makeSearchOptions

       Return a list of <option> elements for search match criteria based on a
       given class of search fields.
     */
    _makeSearchOptions: function(cls) {
        var makeOption = function(a,b) {
            return new Element('option', {'value': a, 'text': _(b), 'data-trans-id': b});
        };
        if(cls === 'flags') {
            return new Elements([
                makeOption('set', "EditorTab.Forms.searchForm.isSet"),
                makeOption('nset', "EditorTab.Forms.searchForm.isNotSet")
            ]);
        }
        return new Elements([
            makeOption('eq', "EditorTab.Forms.searchForm.is"),
            makeOption('neq', "EditorTab.Forms.searchForm.isNot"),
            makeOption('in', "EditorTab.Forms.searchForm.contains"),
            makeOption('nin', "EditorTab.Forms.searchForm.containsNot"),
            makeOption('bgn', "EditorTab.Forms.searchForm.startsWith"),
            makeOption('end', "EditorTab.Forms.searchForm.endsWith"),
            makeOption('regex', "EditorTab.Forms.searchForm.matchesRegEx")
        ]);
    },

    /* Function: _fillSearchMatcher

       Fill the search matcher dropdown box based on the currently selected
       field value.
     */
    _fillSearchMatcher: function(fieldSelector) {
        var parent = fieldSelector.getParent('li');
        var matchSelector = parent.getElement('select.editSearchMatch');
        var selected = fieldSelector.getSelected()[0].get('value');
        var matchClass = 'default';
        if (selected.substr(0, 5) == "flag_")
            matchClass = 'flags';
        if(matchSelector.retrieve('matchClass') !== matchClass) {
            this._makeSearchOptions(matchClass).inject(matchSelector.empty());
            this._setInputField(matchSelector);
            matchSelector.store('matchClass', matchClass);
        }
    },

    /* Function: _setInputField

       Shows/hides the text input depending on the selected match criterion.
     */
    _setInputField: function(matchSelector) {
        var parent = matchSelector.getParent('li');
        var textInput = parent.getElement('input.editSearchText');
        var valueless = ['set', 'nset'];
        var selected = matchSelector.getSelected()[0].get('value');
        textInput.setStyle('visibility',
                           (valueless.contains(selected)) ? 'hidden' : 'visible');
    },

    /* Function: open

       Open the search dialog.
     */
    open: function() {
        if(typeof(this.parent.save) === "function")
            this.parent.save();
        this.mbox.open();
    },

    /* Function: reset

       Reset the search dialog, clearing all fields etc.
     */
    reset: function() {
        this.flexrow.empty().grabNewRow();
        return this;
    },

    /* Function: setFromData

       Sets the search dialog to match a given data object.
     */
    setFromData: function(data) {
        var conditions = [];
        Object.each(this.tagsets, function(tagset) {
            var value = tagset.getValue(data);
            if(typeof(value) !== "undefined" && value.length > 0) {
                conditions.push({'field': tagset.class,
                                 'match': 'eq',
                                 'value': value});
            }
        });
        Object.each(this.flagHandler.flags, function(flag, key) {
            var value = data[key];
            if(typeof(value) !== "undefined" && value == 1) {
                conditions.push({'field': key, 'match': 'set'});
            }
        });
        return this.setFromConditions(conditions);
    },

    setFromConditions: function(conditions) {
        if(conditions.length < 1)
            return this.reset();
        this.flexrow.empty();
        conditions.each(function(condition) {
            var row = this.flexrow.grabNewRow();
            this._fillSearchMatcher(
                row.getElement('select.editSearchField').set('value', condition.field)
            );
            row.getElement('select.editSearchMatch').set('value', condition.match);
            if (typeof(condition.value) !== "undefined")
                row.getElement('input.editSearchText').set('value', condition.value);
        }.bind(this));
        return this;
    },

    /* Function: requestSearch

       Perform a server-side document search, using the query values in the
       search dialog.
     */
    requestSearch: function(mbox, spinner) {
        var operator = mbox.content.getElement('select.editSearchOperator')
                .getSelected()[0].get('value');
        var crits = mbox.content.getElements('.editSearchCriterion');
        var conditions = [], data = {};
        Array.each(crits, function(li) {
            conditions.push({
                'field': li.getElement('select.editSearchField')
                           .getSelected()[0].get('value'),
                'match': li.getElement('select.editSearchMatch')
                           .getSelected()[0].get('value'),
                'value': li.getElement('input.editSearchText').get('value')
            });
        });
        data = {'conditions': conditions, 'operator': operator};
        this.fireEvent('searchRequest', [data]);
        new CoraRequest({
            name: 'search',
            noticeOnError: true,
            onSuccess: function(status) {
                mbox.close();
                this.fireEvent('searchSuccess', [data, status]);
            }.bind(this),
            onComplete: function() { if(spinner) spinner.hide(); }
        }).get(data);
    }
});
