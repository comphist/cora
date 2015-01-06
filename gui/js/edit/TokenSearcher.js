/* Class: TokenSearcher

   GUI element to perform a search within a document.
 */
var TokenSearcher = new Class({
    Implements: [Options],

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
	    title: 'Suchen',
            closeOnBodyClick: false,
	    buttons: [
                {title: 'Zurücksetzen', addClass: 'mform button_left',
                 event: function() { ref.reset(); }
                },
		{title: 'Abbrechen', addClass: 'mform'},
		{title: 'Suchen', addClass: 'mform button_green',
		 event: function() {
		     ref.requestSearch(this);
                     this.close();
		 }
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
        var optgroup;
        var fieldSelector =
                this.templateListElem.getElement('select.editSearchField');
        var makeOption = function(a,b) {
            return new Element('option', {'value': a, 'text': b});
        };
        fieldSelector.empty();
        // Fixed elements
        fieldSelector.grab(makeOption('token_all', "Token"));
        fieldSelector.grab(makeOption('token_trans', "Token (Transkription)"));
        // Annotation layers
        optgroup = new Element('optgroup', {'label': "Annotationsebenen"});
        Object.each(this.tagsets, function(tagset) {
            if(tagset.searchable)
                optgroup.grab(makeOption(tagset.class, tagset.classname));
        });
        fieldSelector.grab(optgroup);
        // Flags
        optgroup = new Element('optgroup', {'label': "Markierungen"});
        Object.each(this.flagHandler.flags,
                    function(flag, flagname) {
                        optgroup.grab(makeOption(flagname, flag.displayname));
                    });
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
                makeOption('set', "ist gesetzt"),
                makeOption('nset', "ist nicht gesetzt")
            ]);
        }
        return new Elements([
            makeOption('eq', "ist"),
            makeOption('neq', "ist nicht"),
            makeOption('in', "enthält"),
            makeOption('nin', "enthält nicht"),
            makeOption('bgn', "beginnt mit"),
            makeOption('end', "endet auf"),
            makeOption('regex', "matcht RegEx")
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
        this.mbox.open();
    },

    /* Function: reset

       Reset the search dialog, clearing all fields etc.
     */
    reset: function() {
        this.flexrow.empty().grabNewRow();
    },

    /* Function: requestSearch

       Perform a server-side document search, using the query values in the
       search dialog.
     */
    requestSearch: function(mbox) {
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
        new Request({
            'url': 'request.php?do=search',
            'data': data,
            onSuccess: function(status, text) {
                this.parent.onSearchSuccess(data, status);
            }.bind(this)
        }).get();
    }
});
