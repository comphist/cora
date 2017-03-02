To install and run your own instance of CorA on a server, you need:

* A web server, such as [Apache](http://httpd.apache.org/)
* [PHP 5.3](http://www.php.net/) or newer, with the following extensions:
    * dom
    * json
    * libxml
* [MySQL 5.5](http://www.mysql.com/) or newer

## The easy way

The easy (and recommended) way to install CorA is to simply
[download a prepared build][download_url].  Extract the contents of the archive
to a local directory, then perform the following steps:

1. Copy the contents of the `www/` subdirectory to your web server directory.

2. Open your web browser and navigate to `<cora>/db/configure_db.php`, where
   `<cora>` is the URL of your web server directory.  If your web server is set
   up correctly, this page will guide you through the database installation.
   (Alternatively, you can use the command-line script
   `<cora>/db/configure_db_cli.php`.  Call it with `-h` to see the available
   options.)

3. If the database installation succeeded, you can now login to your CorA
   instance.  On a first-time installation, use the username *"admin"* with
   password *"admin"* to login, but **make sure to change this password** when
   you login for the first time.

You can follow the same process when updating to a newer version of CorA.
Copying the files from an archive will *not* reset any configuration options
you've set, and the `db/configure_db.php` page is capable of upgrading your
database to a newer version, if needed.

!!! danger "Danger"
    You should make absolutely sure that no-one except you can access the `db/`
    subdirectory.  Anyone with access to this directory can potentially
    **execute arbitrary commands** on your server!  We recommend setting very
    restrictive access permissions in your web server while you install CorA,
    and deleting the `db/` directory afterwards since it is no longer needed.

## The hard way

If you'd like to modify any part of the CorA source code, run the unit tests, or
build the API documentation, you need to setup CorA's build chain on your
machine.  CorA uses [CMake](http://www.cmake.org/) and a variety of other
external tools for this purpose.  First, [clone the git repository][git_repo] on
your local machine, then follow the instructions in the `INSTALL.md` file.


[git_repo]: https://bitbucket.org/mbollmann/cora/
[download_url]: https://bitbucket.org/mbollmann/cora/downloads
