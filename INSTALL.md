# Installing CorA #

These instructions assume that you want to build CorA from the source tree
yourself.  In general, you only need to do this if you want to modify parts of
the source code.  For the "easy" way to install CorA, refer to README.md.

## Dependencies ##

Before installing CorA, make sure that all required **dependencies** are
installed.  Besides the runtime dependencies listed in README.md, you need:

* CMake 2.8.12+

* Sass 3.2+

* Java runtime environment; for running:
    * YUICompressor (for compressing CSS; can be downloaded automatically during build)
    * Closure Compiler (for compressing JS; can be downloaded automatically during build)

The following dependencies are **optional**:

* PHPUnit 4.8-5.7 with DBUnit (2.x) extension (for PHP unit tests); PHPUnit 6+
  is explicitly **not** supported at this time

* NodeJS (for JS unit tests) with the following components (can be installed
  automatically if npm is available):
    * chai
    * istanbul
    * mocha
    * mocha-phantomjs
    * mocha-phantomjs-istanbul

* Doxygen (for API documentation of PHP files)

* mkdocs 0.14+ (for user documentation)

* Perl 5.8+; for running:
    * NaturalDocs 1.52 (for API documentation of JavaScript files; can be downloaded automatically during build)

## Build Instructions ##

To build CorA, create a new directory where the build should be generated, then
call CMake to configure CorA into this directory.  If CMake finishes without
errors, you can then run the build system.

For example, on a Linux-based system you could do:

    mkdir <build-dir> && cd <build-dir>
    cmake <cora-source-dir>
    make

If the build completes successfully, the directory `<build-dir>/www/` holds all
files that should be served by your web server.

## Database Installation/Migration ##

In addition to setting up the web directory, you need to make sure that the
database is properly set up for the current version of CorA.  If you're
installing CorA for the first time, or you are upgrading from an older version
with a different database schema, you need to make the appropriate changes on
your MySQL server.

Usually, CorA can do this automatically for you: Just navigate to
`www/db/configure_db.php` in your web browser and follow the instructions on the
page.

Alternatively, you can run the command-line script `www/db/configure_db_cli.php`
with parameter `-a install`.  If an update is required, the same script can be
used with the option `-a upgrade` to perform the database migration.

## Generating Documentation ##

Run `make docs` to generate documentation in `<build-dir>/docs/`.

* User documentation will be generated in the `user/` subdirectory and requires
  **mkdocs** to be installed.

* API documentation for PHP files will be generated in the `api-php/`
  subdirectory and requires **Doxygen** to be installed.

* API documentation for JavaScript files will be generated in the `api-js/`
  subdirectory and requires the **Perl interpreter** to be installed, which is
  used to run NaturalDocs (which in turn will be downloaded automatically during
  build).

## Performing Tests ##

Run `make test` to perform unit tests.

Currently, only PHPUnit tests will be executed, which requires that **PHPUnit**
is installed.

Some of these tests require access to a MySQL test database, which is also
created by the database installer (see above).  You should have run the database
installation at least once before calling the PHPUnit tests, otherwise a lot of
them will fail.

## Configuration Options ##

You can use the following switches for CMake to influence the build process:

* `-DDEBUG_MODE=ON` enables debug mode; it potentially shows more warnings
  during the build process, and also turns off all minification by default.

* `-DWITH_MINIFY_CSS={ON|OFF}` turns minification of CSS files on/off.
  Minification reduces the number of files that need to be served to the web
  browser, and also compresses the data using YUICompressor.  **Strongly
  recommended** for productive use.  Default is ON unless building in debug
  mode.

* `-DWITH_MINIFY_JS={ON|OFF}` turns minification of JavaScript files on/off.
  Minification *drastically* reduces the number of files that need to be served
  to the web browser, and also compresses the data using Closure Compiler.
  **Strongly recommended** for productive use.  Default is ON unless building in
  debug mode.
