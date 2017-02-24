# Contributing to Localisation

All strings specific to a given locale are stored in the respective
`Locale.<locale>.json` file.

**TODO:** Maybe some sort of "standard" or "guidelines" for organizing/naming locale
  keys?  Currently we just have no idea.

## Example Usage

Let's assume we're working with this very minimalistic locale file:

    {"name": "en-US",
     "sets": {
         "Forms": {
             "buttonLogin": "Login",
             "msgUserNotFound": "User {user} could not be found!"
         },
         "Example": {
             "foo": {
                 "bar": "Hello {title} {name}!"
             }
         }
     }
    }

All localization strings must be stored in the object under the "sets" attribute.

The final value should always be a string, but intermediate keys may also map to
other objects (e.g. "Example" refers to an object that maps "foo" to an object
that maps "bar" to a string).  This can be used to group related strings
together, and to create a sort of hierarchy for localization strings.

To refer to a specific localized string in code, hierarchies are **written with
dots** like this: `"Example.foo.bar"`

Localized strings may have **arguments** that will be "filled in" at runtime.
Arguments always need to be wrapped in curly braces, as in `Hello {name}!`.

## Using localized strings in JavaScript

Refer to a localized string:

    _("Forms.buttonLogin")

Refer to a localized string with arguments:

    // returns "User foobar could not be found!"
    _("Forms.msgUserNotFound", {user: 'foobar'})

Multiple arguments work the same way:

    // returns "Hello Mr. Smith!"
    _("Example.foo.bar", {title: 'Mr.', name: 'Smith'})

(Internally, the underscore function `_` is an alias for `gui.localizeText`,
which in turn calls MooTools's own `Locale` module to fetch the string.)

## Using localized strings in PHP

For the PHP files in the `gui/` directory, things work very similarly to the
JavaScript case:

    $_("Forms.buttonLogin")
    $_("Example.foo.bar", array('title' => 'Mr.', 'name' => 'Smith'))

Unfortunately, for the PHP classes defined in `lib/` it's not quite so easy.  We
need to pass around the LocaleHandler object that gets instantiated, and refer
to it as a member variable of the class we're currently using.

I've currently set this up for `sessionHandler.php` and `requestHandler.php`
only, where you can access localized strings like this:

    $this->lh->_("Forms.buttonLogin")
    $this->lh->_("Example.foo.bar", array('title' => 'Mr.', 'name' => 'Smith'))

This is quite verbose.  If there are more than one or two localized strings in a
function, you should probably put

    $_ = $this->lh;

at the beginning of the function and then just use `$_` like in the first
example.
