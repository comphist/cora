# CorA (Corpus Annotator) #

CorA is a web-based annotation tool for non-standard language data.

* Current version: **1.2**
* [CorA project website](http://www.linguistics.rub.de/comphist/resources/cora/)

The source code of CorA is provided under the
[MIT license](https://opensource.org/licenses/MIT).  See `LICENSE` for details.

If you have any questions about this project, please contact Marcel Bollmann
<bollmann@linguistics.rub.de>.

## Dependencies ##

To host CorA on your own machine, you should have at least the following:

* A web server, such as Apache

* PHP 5.3+ or PHP 7+ with the following extensions:

    * dom
    * json
    * libxml

* MySQL 5.5+ or MariaDB 10.0+

If you plan to build CorA yourself, further dependencies are needed; see
`INSTALL.md` for details.

## Installation ##

The easy way to install CorA is to simply
[download an archive containing a prepared build](https://bitbucket.org/mbollmann/cora/downloads).
If you want to modify parts of the source code, run unit tests, etc., you should
follow the instructions in INSTALL.md instead.

Extract the contents of that package to a local directory, then perform the
following steps:

1. Copy the contents of the `www/` subdirectory to your web server directory.

2. Open your web browser and navigate to `<cora>/db/configure_db.php`, where
   `<cora>` is the URL of your web server directory.  If your web server is set
   up correctly, this page will guide you through the database installation or
   upgrade process.  (Alternatively, you can use the command-line script
   `<cora>/db/configure_db_cli.php`.  Call it with `-h` to see the
   available options.)

3. Log-in to your CorA instance.  On a first-time installation, use the
   username "admin" with password "admin".

**IMPORTANT:**

+ After the database installation, make sure to **delete the**
  `db/` **subdirectory from your web server!** Anyone with access to this
  directory can potentially execute arbitrary commands on your server!

+ Make sure to **change the default password** of the "admin" account
  immediately after you login for the first time.

That's it!

## Acknowledgements ##

The following people have directly contributed to the source code of this
project:

+ Marcel Bollmann <bollmann@linguistics.rub.de>
+ Florian Petran <petran@linguistics.rub.de>

Many thanks to all users who provided feedback on this software!

The development of this software was supported by
[Deutsche Forschungsgemeinschaft (DFG)](http://www.dfg.de/), Grants
Nos. 1558/1-1/2 and 1558/5-1.