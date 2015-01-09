/* Class: EditorModelUndo

   Extension for EditorModel providing undo functionality.
 */
var EditorModelUndo = new Class({
    undoStack: [],
    undoLimit: 100,
    redoStack: [],
    buttonsUndo: null,
    buttonsRedo: null,

    /* Function: activateUndoButtons

       Sets up the undo/redo buttons.
     */
    activateUndoButtons: function(panels) {
        // gather buttons
        this.buttonsUndo = new Elements();
        this.buttonsRedo = new Elements();
        panels.each(function(panel) {
            var elem = $(panel).getElement('.btn-undo');
            if (elem != null)
                this.buttonsUndo.push(elem);
            elem = $(panel).getElement('.btn-redo');
            if (elem != null)
                this.buttonsRedo.push(elem);
        }.bind(this));

        // add events
        this.buttonsUndo.removeEvents('click');
        this.buttonsUndo.addEvent('click', this.performUndo.bind(this));
        this.buttonsRedo.removeEvents('click');
        this.buttonsRedo.addEvent('click', this.performRedo.bind(this));
    },

    /* Function: logUndoInformation

       Logs a change made by the user and stores it in the undo stack.
     */
    logUndoInformation: function(data, changes) {
        var before = {};
        Object.each(changes, function(value, key) {
            before[key] = data[key];
        });
        this.pushUndo({num: data.num, from: before, to: changes});
    },

    /* Function: pushUndo

       Pushes an undo operation onto the stack.
     */
    pushUndo: function(operation) {
        this.undoStack.push(operation);
        this.buttonsUndo.removeClass('start-disabled');
        this.clearRedoStack();
        while(this.undoStack.length > this.undoLimit)
            this.undoStack.shift();
    },

    /* Function: popUndo

       Pops the latest operation from the undo stack.
     */
    popUndo: function() {
        var operation;
        if(this.undoStack.length == 0)
            return null;
        operation = this.undoStack.pop();
        this.redoStack.push(operation);
        this.buttonsRedo.removeClass('start-disabled');
        if(this.undoStack.length == 0)
            this.buttonsUndo.addClass('start-disabled');
        return operation;
    },

    /* Function: popRedo

       Pops the latest operation from the redo stack.
     */
    popRedo: function() {
        var operation;
        if(this.redoStack.length == 0)
            return null;
        operation = this.redoStack.pop();
        this.undoStack.push(operation);
        this.buttonsUndo.removeClass('start-disabled');
        if(this.redoStack.length == 0)
            this.buttonsRedo.addClass('start-disabled');
        return operation;
    },

    /* Function: performUndo

       Performs an undo.
     */
    performUndo: function() {
        var operation = this.popUndo();
        if(operation === null)
            return;
        this.applyChanges(this.get(operation.num), operation.from);
    },

    /* Function: performRedo

       Performs a redo.
     */
    performRedo: function() {
        var operation = this.popRedo();
        if(operation === null)
            return;
        this.applyChanges(this.get(operation.num), operation.to);
    },

    /* Function: clearUndoStack

       Clears the undo stack.
     */
    clearUndoStack: function() {
        this.undoStack = [];
        this.buttonsUndo.addClass('start-disabled');
        return this;
    },

    /* Function: clearRedoStack

       Clears the redo stack.
     */
    clearRedoStack: function() {
        this.redoStack = [];
        this.buttonsRedo.addClass('start-disabled');
        return this;
    }
});
