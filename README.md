# CorA (Corpus Annotator) #

CorA is a web-based annotation tool for non-standard language data.

* Current version: **1.2**
* [CorA project website](http://www.linguistics.rub.de/comphist/resources/cora/)

**TODO:** determine a license

## Dependencies ##

To host CorA on your own machine, you need at least the following:

* A web server, such as Apache

* PHP 5.3+ with the following extensions: (**TODO:** find out which version exactly the minimum required)
    * dom
    * json
    * libxml

* MySQL (**TODO:** which version?)

If you plan to build CorA yourself, further dependencies are needed; see
INSTALL.md for details.

## Installing CorA ##

The easy way to install CorA is to simply download a zip file from
<https://bitbucket.org/mbollmann/cora/downloads> containing all the required
files. (**TODO:** there are none available yet.)  If you want to modify parts of
the source code, run unit tests, etc., you should follow the instructions in
INSTALL.md instead.

Extract the contents of that package to a local directory, then perform the
following steps:

1. Check if the configuration options in `www/config.php` are set correctly.
   This file contains the database credentials, default language, external
   directories to use, etc.; it comes with sensible default settings and
   typically includes descriptions for each of them.  If necessary, adjust this
   file before continuing.

2. Run `bin/make_database.php` from a terminal and follow the instructions on
   the screen.

3. Copy the contents of the `www/` directory to the desired location of your web
   server.

That's it!
