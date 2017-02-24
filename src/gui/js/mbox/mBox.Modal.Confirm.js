/*
---
description: With mBox.Modal.Confirm you can attach confirm dialogs to links or other DOM elements.

authors: Stephan Wagner

license: MIT-style

requires:
 - mBox
 - mBox.Modal
 - core/1.4.5: '*'
 - more/Element.Measure

provides: [mBox.Modal.Confirm]

documentation: http://www.htmltweaks.com/mBox/Documentation/Modal
...
*/
 
mBox.Modal.Confirm = new Class({
	
	Extends: mBox.Modal,
	
	options: {
		
		addClass: {
			wrapper: 'Confirm'
		},
		
		buttons: [
			{ addClass: 'mBoxConfirmButtonCancel' },
			{ addClass: 'button_green mBoxConfirmButtonSubmit', event: function(ev) { this.confirm(); } }
		],
		
		confirmAction: function() {}, 			// action to perform when no data-confirm-action and no href given
		
		preventDefault: true,
		
		constructOnInit: true
	},
	
	// initialize and add confirm class
	initialize: function(options) {
		this.defaultSubmitButton = 'Yes';		// default value for submit button
		this.defaultCancelButton = 'No';		// default value for cancel button
		
		// destroy mBox when finished closing
		options.onSystemCloseComplete = function() {
			this.destroy();
		};
		
		// add buttons once constructed
		options.onSystemBoxReady = function() {
			this.addButtons(this.options.buttons);
		}
		
		// set options
		this.parent(options);
	},
	
	// submit the confirm and close box
	confirm: function() {
		this.options.confirmAction();
		this.close();
	}
});
