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
        this.buttonsUndo.addClass('start-disabled');
        this.buttonsUndo.removeEvents('click');
        this.buttonsUndo.addEvent('click', this.performUndo.bind(this));
        this.buttonsRedo.addClass('start-disabled');
        this.buttonsRedo.removeEvents('click');
        this.buttonsRedo.addEvent('click', this.performRedo.bind(this));
    },

    /* Function: logUndoInformation

       Logs a change made by the user and stores it in the undo stack.
     */
    logUndoInformation: function(data, changes, ler) {
        var before = {}, this_undo = {};
        Object.each(changes, function(value, key) {
            before[key] = (key === 'lastEditedRow') ? ler : data[key];
        });
        this_undo = {num: data.num, from: before, to: changes};
        if(!this.mergeWithLastUndo(this_undo))
            this.pushUndo(this_undo);
    },

    /* Function: mergeWithLastUndo

       Tests if an undo operation can be merged with the last undo operation on
       the stack, and performs the merge if possible.

       Parameters:
         operation - The new undo operation

       Returns:
         True if the operations were merged, false otherwise
     */
    mergeWithLastUndo: function(operation) {
        var merged, last = this.undoStack.getLast();
        if (last !== null && last.num === operation.num
            && JSON.encode(last.to) === JSON.encode(operation.from)) {
            this.undoStack.pop();
            this.undoStack.push({num: last.num, from: last.from, to: operation.to});
            return true;
        }
        return false;
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
        this.applyChanges(this.get(operation.num), operation.from, 'undo');
    },

    /* Function: performRedo

       Performs a redo.
     */
    performRedo: function() {
        var operation = this.popRedo();
        if(operation === null)
            return;
        this.applyChanges(this.get(operation.num), operation.to, 'redo');
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
