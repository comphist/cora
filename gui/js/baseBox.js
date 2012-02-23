/*
---
name: baseBox
description: a MooTools 1.3 modal box class, powered by CSS and with scale transitions
version: 1.15
authors:
  - Dimitar Christoff

requires:

  - Core/Class.Extras
  - Core/Element.Event
  - Core/Element.Style
  - Core/Element.FX
  - Core/Element.Morph
  - More/Element.Delegation
  - More/Drag.move

license: MIT-style license

provides: [baseBox]

...
*/
(function() {
    // internals to do with allowing mootools to parse and modify transform2d scale

    Element.Styles.MozTransform = "rotate(@deg) scale(@)";
    Element.Styles.MsTransform = "rotate(@deg) scale(@)";
    Element.Styles.OTransform = "rotate(@deg) scale(@)";
    Element.Styles.WebkitTransform = "rotate(@deg) scale(@)";

    Object.append(Fx.CSS.Parsers, {

        TransformScale: {
            parse: function(value) {
                return ((value = value.match(/^scale\((([0-9]*\.)?[0-9]+)\)$/i))) ? parseFloat(value[1]) : false;
            },
            compute: function(from, to, delta) {
                return Fx.compute(from, to, delta);
            },
            serve: function(value) {
                return 'scale(' + value + ')';
            }
        }

    });

    // class baseBox start
    this.baseBox = new Class({

        Implements: [Options, Events],

        options: {
            // element: document.body       // required!
            boxTitle: "baseBoxTitle",       // css title class
            warpClass: "baseBox",           // main box wrap css class
            boxBodyOuter: "",               // an outer body content css class
            boxBody: "",                    // inner body content css class
            shadowClass: "farShadow",       // drop shadow class - applied to warpClass el
            closeClass: "baseBoxClose",     // cssing the close class
            outerClose: false,              // can position a close outside the box
            scroll: "hidden",               // by default, no scrolling. can be hidden|auto
            movable: true,                  // use Drag.Move with title as handle
            centered: true,                 // try to center the box...
            offsets: {                      // ... or offset it by:
                x: 0,
                y: 0
            },                              // ... from parent element.
            autoHeight: false,              // try to work out height based upon BLOCK content
            borderRadius: false,            // should be deprecated, use CSS classes instead
            // css based transfrom properties.
            transforms: {
                computed: ['transformProperty', 'WebkitTransform', 'MozTransform', 'OTransform', 'msTransform'],
                raw: ['transform', '-webkit-transform', '-moz-transform', '-o-transform', 'msTransform']
            },
            modal: {                        // options to do with modal div.
                enabled: true,              // use or not.
                background: "#fff",         // colour or eg, #fff url(spinner.gif) no-repeat center center
                // default colour for modal tint
                zIndex: 100000000,          // z-index for modal
                opacity: ".7",              // default opacity
                events: Function.from()     // for example, onclick you may want to close all.
            }
        },
        initialize: function(options) {
            // constructor. nothing much happens by default (due to extend). use .doBox
            this.setOptions(options);
            this.detectTransforms();
            this.element = this.options.element || $(document.body);
        },
        detectTransforms: function() {
            // do some feature detection to detrmine what scale transform methods are available
            var testEl = new Element("div"),
                self = this;
            this.scaleTransform = this.options.transforms.computed.some(function(el, index) {
                var test = el in testEl.style;
                if (test) {
                    self.prop = self.options.transforms.raw[index];
                }

                return test;
            });

            if (!this.prop) {
                this.prop = "opacity";
            }
        },
        doBox: function(title, what) {
            // where the magic happens. creates the box and shows it on-screen.

            var self = this,
                coords;

            if (!this.options.height || this.options.autoHeight) {
                this.options.autoHeight = true;
            }

            // compute / make sure we have a height set
            this.getHeight(title, what);

            if (this.options.centered) {
                coords = this.centerBox(this.options.width, this.options.height);

                if (typeOf(coords.x) == "number") {
                    coords.x += this.options.offsets.x;
                }
                else {
                    coords.x = this.options.offsets.x;
                }

                if (typeOf(coords.y) == "number") {
                    coords.y += this.options.offsets.y;
                }
                else {
                    coords.y = this.options.offsets.y;
                }

            }
            else {
                coords = this.options.offsets;
            }

            var safeTitle = this.options.id || "popup" + this.element.uid;
            if (document.id(safeTitle)) {
                document.id(safeTitle).retrieve("instance").closeBox();
                return;
            }

            // fade out out of focus ones.
            this.element.getElements("div.wrapy").setStyles({
                zIndex: 1000000,
                opacity: ".9"
            });

            if (this.options.modal.enabled) {
                this.overlay();
            }

            var radiusObj = this.options.borderRadius ? {
                "border-radius": this.options.borderRadius,
                "-moz-border-radius": this.options.borderRadius,
                "-webkit-border-radius": this.options.borderRadius
            } : {};


            this.wrap = new Element("div", {
                id: safeTitle,
                "class": [this.options.shadowClass, this.options.warpClass].join(" "),
                styles: Object.merge(radiusObj, {
                    position: "absolute",
                    width: this.options.width,
                    height: this.options.height,
                    zIndex: this.options.modal.zIndex + 1,
                    marginLeft: coords.x,
                    marginTop: coords.y,
                    padding: 0,
                    opacity: 0,
                    display: "none"
                }),
                events: {
                    "click:relay(.closeThis)": this.closeBox.bind(this),
                    click: function() {
                        this.element.getElements("div.wrapy").setStyles({
                            zIndex: this.options.modal.zIndex - 1,
                            opacity: ".9"
                        });
                        this.wrap.setStyles({
                            zIndex: this.options.modal.zIndex + 1,
                            opacity: 1
                        });
                    }.bind(this)
                }
            });

            this.box = new Element("div", {
                styles: {
                    height: this.options.height
                },
                "class": this.options.boxBodyOuter
            }).inject(this.wrap);

            this.body = new Element("div", {
                "class": this.options.boxBody
            }).inject(this.box);

            this.title = new Element("div", {
                "class": this.options.boxTitle,
                styles: {
                    zIndex: this.options.modal.zIndex + 2
                }
            }).inject(this.box, "top");

            this.setTitle(title);

            this.setHTML(what);

            if (this.options.outerClose === false) {
                new Element("div", {
                    "class": "right closeThis cur " + this.options.closeClass,
                    position: "absolute",
                    html: "<img src='http://cdn1.iconfinder.com/data/icons/Boolean/Signage/Close.png' />"
                }).inject(this.wrap, "top");
            }
            else {
                var cwrap = new Element("div", {
                    "class": ["right closeThis cur", this.options.closeClass, this.options.shadowClass].join(" "),
                    styles: {
                        // position: "absolute",
                        marginTop: -16,
                        marginRight: -14,
                        width: 22,
                        height: 22,
                        "-moz-border-radius": "14px",
                        "border-radius": "14px",
                        border: "2px solid #fff"
                    },
                    events: {
                        mouseenter: function() {
                            this.setStyle(self.prop, "scale(1)");
                        },
                        mouseleave: function() {
                            this.setStyle(self.prop, "scale(.9)");
                        }
                    }

                }).inject(this.wrap, "top").setStyle(self.prop, "scale(.9)");

                new Element("div", {
                    "class": ["right closeThis cur", this.options.closeClass].join(" "),
                    styles: {
                        // position: "absolute",
                        width: 22,
                        height: 22,
                        margin: 0,
                        "text-align": "center",
                        "-moz-border-radius": "14px",
                        "border-radius": "14px",
                        "background-image": "-moz-linear-gradient(34deg, #400,#a00)",
                        "-moz-box-shadow": "inset 2px 2px 5px #000"
                    },
                    html: "<img src='http://youli.st/images/fileclose2.png' />"
                }).inject(cwrap);
            }


            this.wrap.inject(this.element, "top");

            if (this.options.movable) {
                this.wrap.makeDraggable({
                    modifiers: {
                        x: "marginLeft",
                        y: "marginTop"
                    },
                    handle: this.title,
                    onStart: function() {
                        this.element.set("opacity", ".6");
                        self.fireEvent("moving", this.element);
                    },
                    onComplete: function() {
                        this.element.set("opacity", 1);
                        self.fireEvent("moved", this.element);
                    }
                });
            }

            this.wrap.setStyle("display", "block");

            this.body.setStyles({
                "overflow-y": this.options.scroll,
                "overflow-x": "hidden"
            });

            this.showBox();
            this.wrap.store("instance", this);
        },
        getHeight: function(title, what) {
            // returns the height of the content given the existing title and content
            // width needs to be set properly
            var height = this.options.height || 0;
            if (this.options.autoHeight) {
                var tester = new Element("div", {
                    html: "<div class='" + this.options.boxBody + "'>" + what + "</div>",
                    "class": this.options.warpClass,
                    styles: {
                        width: this.options.width,
                        visibility: "hidden"
                    }
                }).inject(document.body);

                var testerTitle = new Element("div", {
                    html: title,
                    "class": this.options.boxTitle,
                    styles: {
                        width: this.options.width,
                        visibility: "hidden"
                    }
                }).inject(document.body);

                height = tester.getSize().y + testerTitle.getSize().y;

                tester.destroy();
                testerTitle.destroy();
            }

            this.options.height = height;
        },
        resizeBox: function(newWidth, newHeight, complete) {
            // a method that can resize an open modal box and repositions
            // the complete argument is a callback but you can also use
            // the onResize event instead.

            var self = this, titleHeight = this.title.getSize().y;

            // margins.
            var coords = (this.options.centered)
                ? this.centerBox(newWidth, newHeight + titleHeight)
                : this.options.offsets;

            var oldDuration = this.wrap.get("morph").options.duration;
           
            this.wrap.set("morph", {
                duration: 200,
                onStart: function() {
                    self.body.setStyle("display", "none");
                },
                onComplete: function() {
                    self.setOptions({
                        width: newWidth,
                        height: newHeight + titleHeight
                    });

                    self.body.setStyles({
                        "height": newHeight,
                        "display": "block"
                    });

                    if (typeOf(complete) == "function") {
                        complete.apply(this);
                    }

                    this.removeEvents("complete").setOptions({
                        duration: oldDuration
                    });
                    self.fireEvent("resize");
                }
            }).morph({
                width: coords.width,
                height: coords.height,
                marginLeft: coords.x,
                marginTop: coords.y
            });
        },
        setHTML: function(what) {
            this.wrap.setStyle("height", this.options.height);
            this.body.set("html", what);
            this.fireEvent("contentLoaded");
        },
        setTitle: function(title) {
            this.title.set("html", title);
        },
        showBox: function() {
            // open the instance via morphing scale if possible or just fade
            var self = this,
                obj = {
                    opacity: [0, 1]
                };

            if (this.scaleTransform) {
                obj[self.prop] = ["scale(0)", "scale(1)"];
            }

            this.wrap.set("morph", {
                onComplete: function() {
                    this.removeEvents("complete");
                    self.fireEvent("open");
                }
            }).morph(obj);

        },
        closeBox: function(e) {
            // close the instance and clean up.
            if (e && e.stopPropagation) {
                e.stopPropagation();
            }
            var self = this,
                obj = {
                    opacity: [1, 0]
                };

            if (this.scaleTransform) {
                obj[self.prop] = ["scale(1)", "scale(0)"];
            }

            this.fireEvent("beforeClose");
            this.wrap.set("morph", {
                onComplete: function() {
                    this.element.destroy();
                    self.fireEvent("close");
                    if (self.options.modal.enabled && self.modal) {
                        self.overlay();
                    }
                }
            }).morph(obj);
        },
        centerBox: function(width, height) {
            // returns a coordinates object for positioning of a box in the centre of an element / browser
            var winSize = this.element.getSize();
            var winScroll = this.element.getScroll();
            var windowHeight = this.element.getScrollHeight() - 1;

            var coords = {
                y: winScroll.y + (winSize.y / 2) - (height / 2),
                x: winSize.x / 2 - width / 2,
                width: width,
                height: height
            };

            if (coords.y + height + 20 > windowHeight) {
                coords.y = windowHeight - 20 - height;
            }

            if (coords.y <= 0) {
                coords.y = 20;
            }

            return coords;
        },
        overlay: function() {
            // modal view for the whole screen
            if (this.modal) {
                this.modal.destroy();
                delete this.modal;
                return false;
            }

            this.modal = new Element("div", {
                id: "modal",
                styles: {
                    position: "absolute",
                    top: 0,
                    left: 0,
                    width: "100%",
                    height: window.getScrollHeight(),
                    background: this.options.modal.background,
                    zIndex: this.options.modal.zIndex
                },
                opacity: this.options.modal.opacity,
                events: this.options.modal.events
            }).inject(this.element);

            return this.modal.store("instance", this); // can chain into it.
        }

    }); // end baseBox class
})(); // end closure
