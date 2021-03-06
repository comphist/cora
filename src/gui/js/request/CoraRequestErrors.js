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

/* Class: CoraRequestError

   Wraps an error state for a CoraRequest.
 */
var CoraRequestError = new Class({
    name: "",
    message: "",
    details: [],
    showAsNotice: function() {
        gui.showNotice('error', _(this.message));
    },
    showAsDialog: function() {
        gui.showMsgDialog('error', _(this.message));
    },
    showAsTextDialog: function() {
        if(typeof(this.details) === "undefined" || this.details === null
           || this.details.length === undefined || this.details.length === 0) {
            this.showAsDialog();
        } else {
            gui.showTextDialog(_("RequestErrors.actionFailed"), _(this.message), this.details);
        }
    }
});

/* Class: CoraRequestError.Handled

   The server has sent a valid response indicating that one or more errors have
   occured, by giving a list of error messages.
 */
CoraRequestError.Handled = new Class({
    Extends: CoraRequestError,
    name: 'Handled',
    message: "RequestErrors.couldNotPerformAction",
    status: {},
    initialize: function(status) {
        this.status  = status;
        if(typeof(status.errors) !== "undefined")
            this.details = status.errors;
    }
});

/* Class: CoraRequestError.NotLoggedIn

   A special type of error indicating that the user is no longer logged in on
   the server.
 */
CoraRequestError.NotLoggedIn = new Class({
    Extends: CoraRequestError,
    name: 'NotLoggedIn',
    message: "RequestErrors.notLoggedIn"
});

/* Class: CoraRequestError.Invalid

   The server replied with 200 (OK), but has sent a semantically invalid
   response; i.e. either:
     - nothing (empty string),
     - an invalid JSON string,
     - a JSON string that is not an object, or
     - a JSON object with 'success' being false or undefined, but no
       'errors' attribute.
 */
CoraRequestError.Invalid = new Class({
    Extends: CoraRequestError,
    name: 'InvalidResponse',
    message: "RequestErrors.invalidServerResponseInfo",
    initialize: function(response) {
        this.details = [response];
    }
});

/* Class: CoraRequestError.Server

   A server error has occured; either the server sent a status code other than
   200 (OK), or it encountered an uncaught exception.
 */
CoraRequestError.Server = new Class({
    Extends: CoraRequestError,
    name: 'ServerError',
    message: "RequestErrors.internalError",
    xhr: null,
    initialize: function(xhr) {
        if (xhr !== undefined) {
            this.xhr = xhr;
            this.details = [xhr.statusText, xhr.responseText];
        }
    }
});

/* Class: CoraRequestError.Cancelled

   The request was cancelled.
 */
CoraRequestError.Cancelled = new Class({
    Extends: CoraRequestError,
    name: 'Cancelled',
    message: "RequestErrors.requestCancelled"
});

/* Class: CoraRequestError.Network

   The request timed out or there was some other error with sending the request.
 */
CoraRequestError.Network = new Class({
    Extends: CoraRequestError,
    name: 'Network',
    timeout: false,
    message: "RequestErrors.serverNotAvailable",
    initialize: function(to) {
        if(to) this.timeout = true;
    }
});

/* Class: CoraRequestError.Exception

   There was a problem setting a request header; the request couldn't be sent.
   (This should probably never happen as we're not modifying any unusual request
   headers, but we want to catch every possible error condition.)
 */
CoraRequestError.Exception = new Class({
    Extends: CoraRequestError,
    name: 'Exception',
    message: "RequestErrors.internalScriptError",
    initialize: function(headerName, value) {
        this.details = [_("RequestErrors.internalScriptErrorInfo", {hName: headerName, val: value})];
    }
});
