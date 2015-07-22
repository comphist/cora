# CorA (Corpus Annotator) #

CorA is a web-based annotation tool for non-standard language data.

* Current version: **1.2**
* [CorA project website](http://www.linguistics.rub.de/comphist/resources/cora/)

**TODO:** determine a license

### Dependencies ###

This list is probably still incomplete.

Runtime dependencies:

* PHP 5.3+ with the following extensions: (**TODO:** find out which version exactly the minimum required)
    * dom
    * json
    * libxml

* MySQL server (**TODO:** which version?)

Additional build dependencies:

* Sass 3.4.13+

* Java runtime environment; for running:
    * YUICompressor (for compressing CSS -- can be downloaded automatically during build)
    * Closure Compiler (for compressing JS -- can be downloaded automatically during build)

Optional build dependencies:

* PHPUnit (for PHP unit tests)

* Doxygen (for API documentation)

* mkdocs 0.14+ (for user documentation)

### Installing/configuring CorA ###

**TODO:** there is much work to do before CorA can be deployed elsewhere without losing your sanity

PHP unit tests can be run via `phpunit` from the `tests/` subdirectory, though the test coverage is still pretty miserable at this point.
