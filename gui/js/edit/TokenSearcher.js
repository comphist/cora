/* Class: TokenSearcher

   GUI element to perform a search within a document.
 */
var TokenSearcher = new Class({
    parent: null,
    mbox: null,
    flexrow: null,
    templateListElem: null,

    initialize: function(parent, content) {
        var ref = this;
        this.parent = parent;
        this.templateListElem = $('editSearchCriterionTemplate');
        this._initializeTemplate();
        this.flexrow = new FlexRowList(content.getElement('.flexrow-container'),
                                       this.templateListElem);
        this.reset();
        this._initializeEvents();
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
        Object.each(cora.current().tagsets, function(tagset) {
            if(tagset.searchable)
                optgroup.grab(makeOption(tagset.class, tagset.classname));
        });
        fieldSelector.grab(optgroup);
        // Flags
        optgroup = new Element('optgroup', {'label': "Markierungen"});
        Object.each(this.parent.parent.flagHandler.flags,
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
            return new Element('option', {'value': a, 'text': b});
        };
        if(cls === 'flags') {
            return new Elements([
                makeOption('isset', "ist gesetzt"),
                makeOption('isnotset', "ist nicht gesetzt")
            ]);
        }
        return new Elements([
            makeOption('eq', "ist gleich"),
            makeOption('bgn', "beginnt mit"),
            makeOption('end', "endet auf"),
            makeOption('in', "enthält")
        ]);
    },

    /* Function: _fillSearchMatcher

       Fill the search matcher dropdown box based on the currently selected
       field value.
     */
    _fillSearchMatcher: function(fieldSelector) {
        var parent = fieldSelector.getParent('li');
        var matchSelector = parent.getElement('select.editSearchMatch');
        var textInput = parent.getElement('input.editSearchText');
        var selected = fieldSelector.getSelected()[0].get('value');
        var matchClass = 'default';
        if (selected.substr(0, 5) == "flag_")
            matchClass = 'flags';
        if(matchSelector.retrieve('matchClass') !== matchClass) {
            this._makeSearchOptions(matchClass).inject(matchSelector.empty());
            matchSelector.store('matchClass', matchClass);
            textInput.setStyle('visibility',
                               (matchClass === 'flags') ? 'hidden' : 'visible');
        }
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
        var data = [];
        Array.each(crits, function(li) {
            data.push({
                'field': li.getElement('select.editSearchField')
                           .getSelected()[0].get('value'),
                'match': li.getElement('select.editSearchMatch')
                           .getSelected()[0].get('value'),
                'value': li.getElement('input.editSearchText').get('value')
            });
        });
        new Request.JSON({
            'url': 'request.php?do=search',
            'data': {'conditions': data, 'operator': operator},
            onSuccess: function(status, text) {
                // console.log(status);
            }
        }).get();
    }
});
