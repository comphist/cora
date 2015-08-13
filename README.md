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

1. Copy the contents of the `www/` subdirectory to the desired location of your
   web server.

2. In your web browser, navigate to `db/configure_db.php` in the location
   where CorA is served by your web server.  If your web server is set up
   correctly, this page will guide you through the database installation or
   upgrade process.

3. Log-in to your CorA instance.  On a first-time installation, use the
   username "admin" with password "admin".

**IMPORTANT:**

+ After the database installation, make sure to **delete the**
  `db/` **subdirectory from your web server!** Anyone with access to this
  directory can potentially execute arbitrary commands on your server!

+ Make sure to **change the default password** of the "admin" account
  immediately after you login for the first time.

That's it!
