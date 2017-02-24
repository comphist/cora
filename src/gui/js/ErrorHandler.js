/*
 * Copyright (C) 2016 Marcel Bollmann <bollmann@linguistics.rub.de>
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

/** @file
 * General JavaScript exception handling
 *
 * Make the application indicate that an unexpected error has occured, and handle
 * it as gracefully as possible.
 *
 * @author Marcel Bollmann
 * @date January 2016
 */

var CoraHandleUnexpectedError = function (errorMsg, url, lineNumber, colNumber) {
    var details, fileOpenedOnServer, fileOpenedOnClient;

    /* Show a message to the user that something went unexpectedly wrong. */
    details = "Error in '" + url + "' (" + lineNumber + "/" + colNumber + "): " + errorMsg;
    $('unknownErrorDetails').set('text', details);
    $('unknownErrorPopup').show();

    /* If a file is currently opened, try to save it. */
    if (cora !== null && cora.editor !== null) {
        try {
            if (cora.editor.hasUnsavedChanges()) cora.editor.save();
        } catch(e) {
            console.error("An exception occured during the attempt to save"
                          + " the currently opened file.");
            console.error(e);
        }
    }

    /* If the error appears to have happened while opening a file,
       try to prevent CorA from automatically re-opening the file on the
       next page load -- otherwise, user might get stuck in an "error loop".
     */
    if (cora !== null && cora.fileManager !== null) {
        fileOpenedOnServer = (cora.fileManager.currentFileId !== null);
        fileOpenedOnClient = (cora.fileManager.openFileRunning == false);
        if (fileOpenedOnServer && !fileOpenedOnClient) {
            try {
                cora.files.close(cora.fileManager.currentFileId);
            } catch(e) {
                console.error("An exception occured during the attempt to close"
                              + " the currently opened file.");
                console.error(e);
            }
        }
    }
};

window.onerror = CoraHandleUnexpectedError;
