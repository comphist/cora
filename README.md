# CorA (Corpus Annotator) #

CorA is a web-based annotation tool for non-standard language data.

* Current version: **1.2**
* [CorA project website](http://www.linguistics.rub.de/comphist/resources/cora/)

**TODO:** determine a license

## Dependencies ##

### Runtime dependencies ###

* A web server, such as Apache

* PHP 5.3+ with the following extensions: (**TODO:** find out which version exactly the minimum required)
    * dom
    * json
    * libxml

* MySQL (**TODO:** which version?)

### Additional build dependencies ###

* CMake 2.8.12+

* Sass 3.2+

* Java runtime environment; for running:
    * YUICompressor (for compressing CSS; can be downloaded automatically during build)
    * Closure Compiler (for compressing JS; can be downloaded automatically during build)

### Optional build dependencies ###

* PHPUnit 3.7+ with DBUnit extension (for PHP unit tests)

* Doxygen (for API documentation of PHP files)

* Perl 5.8+; for running:
    * NaturalDocs 1.52 (for API documentation of JavaScript files; can be downloaded automatically during build)

* mkdocs 0.14+ (for user documentation)

## Installing CorA ##

For detailed information about building and installing CorA, refer to INSTALL.md.

### The short version ###

To build CorA, do:

    mkdir <build-dir> && cd <build-dir>
    cmake <cora-source-dir>
    make

If the build completes successfully, the directory `<build-dir>/www/` holds all
files that should be served by your web server.

**Important:** Database creation is not yet automated and must be performed
  manually after the build.  See INSTALL.md for instructions.
