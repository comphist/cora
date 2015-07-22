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

* CMake 2.8.12+

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

To build CorA, do:

    mkdir <build-dir> && cd <build-dir>
    cmake <cora-source-dir>
    make

If this build completes successfully, the directory `<build-dir>/www/` holds all
files that should be served by your web server.

You can use a few switches for CMake to influence the build process:

* `-DDEBUG_MODE=ON` enables debug mode; it potentially shows more warnings
  during the build process, and also turns off all minification by default.

* `-DWITH_MINIFY_CSS={ON|OFF}` turns minification of CSS files on/off.
  Minification reduces the number of files that need to be served to the web
  browser, and also compresses the data using YUICompressor.  Default is 'on'
  unless building in debug mode.

* `-DWITH_MINIFY_JS={ON|OFF}` turns minification of JavaScript files on/off.
  Minification *drastically* reduces the number of files that need to be served
  to the web browser, and also compresses the data using Closure Compiler.
  Default is 'on' unless building in debug mode.

Run `make docs` to generate documentation in `<build-dir>/docs/api/` and
`<build-dir>/docs/user/`, respectively.  If one of the dependencies for
generating documentation is missing (see above), the respective documentation is
silently skipped.

**TODO:** there are no targets to run tests or to install/configure CorA yet.

PHP unit tests can be run via `phpunit` from the `tests/` subdirectory, though
the test coverage is still pretty miserable at this point.
