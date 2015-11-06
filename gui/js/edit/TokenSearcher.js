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
        'set': "EditorTab.dropDown.isSet",
        'nset': "EditorTab.dropDown.isNotSet",
        'eq': "EditorTab.dropDown.is",
        'neq': "EditorTab.dropDown.isNot",
        'in': "EditorTab.dropDown.contains",
        'nin': "EditorTab.dropDown.containsNot",
        'bgn': "EditorTab.dropDown.startsWith",
        'end': "EditorTab.dropDown.endsWith",
        'regex': "EditorTab.dropDown.matchesRegEx"
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
        var content, ref = this;
        this.setOptions(options);
        this.parent = parent;
        this.tagsets = tagsets;
        this.flagHandler = flags;
        this.templateListElem = $(this.options.template);

        this._initializeTemplate();
        content = $(this.options.content);
        this.flexrow = new FlexRowList(content.getElement('.flexrow-container'),
                                       this.templateListElem);
        this.reset();
        this.mbox = new mBox.Modal({
	    content: content,
	    title: _("Action.search"),
            closeOnBodyClick: false,
	    buttons: [
                {title: _("Action.reset"), addClass: 'mform button_left',
                 event: function() { ref.reset(); }
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
                                      _("EditorTab.dropDown.tokenAll"))); // cora.strings.search_condition.field['token_all']
        fieldSelector.grab(makeOption('token_trans',
                                     _("EditorTab.dropDown.tokenTrans"))); // cora.strings.search_condition.field['token_trans']
        // Annotation layers
        optgroup = new Element('optgroup', {'label': _("EditorTab.dropDown.annotationLevels")});
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
        optgroup = new Element('optgroup', {'label': _("EditorTab.dropDown.markups")});
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
        // silly agreement hack ...
        this.mbox.content.addEvent(
            'change:relay(select.editSearchOperator)',
            function(event, target) {
                var span = target.getParent('p').getElement('span.eso-det-agr');
                var selected = target.getSelected()[0].get('value');
                span.set('text', (selected === "any") ? 'dieser' : 'diese');
            }
        );
    },

    /* Function: _makeSearchOptions

       Return a list of <option> elements for search match criteria based on a
       given class of search fields.
     */
    _makeSearchOptions: function(cls) {
        var makeOption = function(a,b) {
            return new Element('option', {'value': a, 'text': b});
        };
        if(cls === 'flags') {
            return new Elements([
                makeOption('set', _("EditorTab.dropDown.isSet")), // cora.strings.search_condition.match['set']
                makeOption('nset', _("EditorTab.dropDown.isNotSet")) // cora.strings.search_condition.match['nset']
            ]);
        }
        return new Elements([
            makeOption('eq', _("EditorTab.dropDown.is")), // cora.strings.search_condition.match['eq']
            makeOption('neq', _("EditorTab.dropDown.isNot")), // cora.strings.search_condition.match['neq']
            makeOption('in', _("EditorTab.dropDown.contains")), // cora.strings.search_condition.match['in']
            makeOption('nin', _("EditorTab.dropDown.containsNot")), // cora.strings.search_condition.match['nin']
            makeOption('bgn', _("EditorTab.dropDown.startsWith")), // cora.strings.search_condition.match['bgn']
            makeOption('end', _("EditorTab.dropDown.endsWith")), // cora.strings.search_condition.match['end']
            makeOption('regex', _("EditorTab.dropDown.matchesRegEx")) // cora.strings.search_condition.match['regex']
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
