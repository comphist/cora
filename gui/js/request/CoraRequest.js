/* Class: CoraRequest

   Wraps all AJAX requests made to the CorA PHP backend.
 */
var CoraRequest = new Class({
    Implements: [Options, Events],
    Binds: ['_onRequestSuccess', '_onRequestFailure', '_onRequestCancel',
            '_onRequestError',   '_onRequestTimeout', '_onRequestException',
            '_onRequestStart', '_onRequestComplete',
            'send', 'retry', 'isRunning'],
    options: {
        name: "keepalive",
        async: true,
        method: 'get',
        retry: 2,  /**< a request that fails with anything other than
                        CoraRequestError.Handled is retried at least
                        this number of times before actually failing */
        noticeOnError: false,
        textDialogOnError: false
    },
    request: null,

    tries: 0,
    lastData: null,

    /* Constructor: CoraRequest

       Creates a new CoraRequest.

       Parameters:
         name - Name of the server request
         options - An options object
     */
    initialize: function(options) {
        this.setOptions(options);
        this.instantiateRequest();
        this.addDefaultEventHandlers();
    },

    /* Function: instantiateRequest

       Creates the MooTools Request object for this instance.

       Called during initialization, but can also be called manually to update
       the request object when settings have been changed.
     */
    instantiateRequest: function() {
        this.request = new Request.JSON({
            url: 'request.php?do='+this.options.name,
            async: this.options.async,
            method: this.options.method,
            secure: true,  // performs JSON syntax check
            onRequest: this._onRequestStart,
            onComplete: this._onRequestComplete,
            onSuccess: this._onRequestSuccess,
            onFailure: this._onRequestFailure,
            onError: this._onRequestError,
            onCancel: this._onRequestCancel,
            onTimeout: this._onRequestTimeout,
            onException: this._onRequestException
        });
    },

    /* Function: addDefaultEventHandlers

       Adds pre-defined event handlers to this instance depending on the flags
       set in the options.
     */
    addDefaultEventHandlers: function() {
        if(this.options.noticeOnError) {
            this.addEvent('error', function(e) {
                e.showAsNotice();
            });
        }
        if(this.options.textDialogOnError) {
            this.addEvent('error', function(e) {
                e.showAsTextDialog();
            });
        }
    },

    /* Function: send

       Sends a request.

       Parameters:
         data - (optional) Data to send
     */
    send: function(data) {
        this.lastData = data;
        this.request.send({'method': this.options.method, 'data': data});
        return this;
    },

    /* Function: get

       Equivalent to send(), but enforces the 'get' method.
     */
    get: function(data) {
        this.options.method = 'get';
        return this.send(data);
    },

    /* Function: post

       Equivalent to send(), but enforces the 'post' method.
     */
    post: function(data) {
        this.options.method = 'post';
        return this.send(data);
    },

    /* Function: retry

       Retries the last request.
     */
    retry: function() {
        this.send(this.lastData);
    },

    /* Function: isRunning

       Check if the request is currently running.
     */
    isRunning: function() {
        return this.tries > 0;
    },

    /********************** INTERNAL CALLBACK FUNCTIONS ***********************/
    /* Function: _fireError

       Fires a given error, unless the request should be retried as specified in
       the options.
     */
    _fireError: function(error) {
        if(error.name === 'Handled' || error.name === 'NotLoggedIn'
           || this.tries > this.options.retry) {
            this.fireEvent('error', error);
            this._fireComplete();
        } else {  // retry
            setTimeout(this.retry, 500);
        }
    },

    /* Function: _fireComplete

       Cleans up and fires an event when a request completes.
     */
    _fireComplete: function() {
        this.tries = 0;
        this.fireEvent('complete');
    },

    /* Function: _onRequestSuccess

       Internal function that is called whenever a request succeeds.

       Checks whether the returned JSON is an object with a true 'success'
       attribute.
     */
    _onRequestSuccess: function(json, text) {
        if(typeof(json) === "object") {
            if(json.success) {
                this.fireEvent('success', [json]);
                this._fireComplete();
            } else if(typeof(json.errors) !== "undefined") {
                this._fireError(new CoraRequestError.Handled(json.errors));
            } else if(json.errcode === -1) {
                this._fireError(new CoraRequestError.NotLoggedIn());
            } else {
                this._fireError(new CoraRequestError.Invalid(text));
            }
        } else {
            this._fireError(new CoraRequestError.Invalid(text));
        }
    },
    _onRequestError: function(text, error) {
        this._fireError(new CoraRequestError.Invalid(text));
    },
    _onRequestFailure: function(xhr) {
        if(xhr.status === 0) {
            this._fireError(new CoraRequestError.Network());
        } else {
            this._fireError(new CoraRequestError.Server(xhr));
        }
    },
    _onRequestCancel: function() {
        this._fireError(new CoraRequestError.Cancelled());
    },
    _onRequestTimeout: function() {
        this._fireError(new CoraRequestError.Network(true));
    },
    _onRequestException: function(headerName, value) {
        this._fireError(new CoraRequestError.Exception(headerName, value));
    },
    _onRequestStart: function() {
        this.tries++;
    },
    _onRequestComplete: function() {}
});
