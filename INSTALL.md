# Installing CorA #

Before installing CorA, make sure that all required **dependencies** are
installed (see README.md for a full list).

To build CorA, create a new directory where the build should be generated, then
call CMake to configure CorA into this directory.  If CMake finishes without
errors, you can then run the build system.

For example, on a Linux-based system you could do:

    mkdir <build-dir> && cd <build-dir>
    cmake <cora-source-dir>
    make

If the build completes successfully, the directory `<build-dir>/www/` holds all
files that should be served by your web server.

## First-time Installation ##

If you're installing CorA for the first time, you need to create the CorA
database on your MySQL server.  We're working on making this process more
convenient, but right now, this needs to be **done manually.**

To create the CorA database structure from scratch, do:

1. Run `<build-dir>/coradb.sql` and `<build-dir>/coradb-data.sql` against your
   MySQL instance (requires MySQL root permissions), for example by doing:

        cat coradb.sql coradb-data.sql | mysql -uroot -p

2. Run `php <build-dir>/bin/cora_create_user.php -a` to create a first ucer
   account with administrator rights.  You can define username and password via
   `-u username -p password`, but if you don't, the script will prompt you for
   it.

**ATTENTION!** If you're already running an (older) instance of CorA on your
  server, performing the above steps will **delete all CorA-related data in your
  database!** Only perform these steps during a first-time installation of CorA.
  Support for updating CorA when the database structure has changed will be
  added in the future.

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

Some of these tests require access to a MySQL test database, which is granted in
`<build-dir>/coradb-data.sql`.  If you run PHPUnit tests before creating the
database according to the instructions above, some of the tests will inevitably
fail.

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

* `-DCORA_DB_NAME=...` sets the name of the database used by CorA.  Default is
  'cora'.  This could potentially be changed to run multiple installations of
  CorA in parallel on the same server.

* `-DCORA_DB_SERVER=...` sets the host of the database used by CorA.  Default is
  'localhost'.

* `-DCORA_DB_USER=...` sets the name of the database user used by all requests.
  Default is 'cora'.

* `-DCORA_DB_PASSWORD=...` sets the password for the CorA database user.
  Default is 'trustthetext'.  (**TODO:** this will potentially change in the
  future.)
