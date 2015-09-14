# Installation

Before installation, please make sure your system fulfills the
[requirements for running CorA](index.md#requirements).

## The easy way

The easy (and recommended) way to install CorA is to simply
[download a prepared build][download_url].  Extract the contents of the archive
to a local directory, then perform the following steps:

1. Copy the contents of the `www/` subdirectory to your web server directory.

2. Open your web browser and navigate to `<cora>/db/configure_db.php`, where
   `<cora>` is the URL of your web server directory.  If your web server is set
   up correctly, this page will guide you through the database installation.

3. If the database installation succeeded, you can now login to your CorA
   instance.  On a first-time installation, use the username *"admin"* with
   password *"admin"* to login, but **make sure to change this password** when
   you login for the first time.

You can follow the same process when updating to a newer version of CorA.
Copying the files from an archive will *not* reset any configuration options
you've set, and the `db/configure_db.php` page is capable of upgrading your
database to a newer version, if needed.

**IMPORTANT:** You should make absolutely sure that no-one except you can access
  the `db/` subdirectory.  Anyone with access to this directory can potentially
  **execute arbitrary commands** on your server!  We recommend setting very
  restrictive access permissions in your web server while you install CorA, and
  deleting the `db/` directory afterwards since it is no longer needed.

## The hard way

If you'd like to modify any part of the CorA source code, run the unit tests, or
build the API documentation, you need to
[clone the git repository][git_repo] on your local
machine.  CorA uses [CMake](http://www.cmake.org/) to automate the necessary
tasks.  This process is described in more detail in the `INSTALL.md` file in the
repository.


# Configuration

General configuration options of CorA are stored in PHP files in the web
directory.  Be aware that these configuration files **contain sensitive
information** such as the database password used by CorA.  Setting restrictive
permissions on these files is therefore recommended.

`config.defaults.php` contains a list of all possible configuration options
along with their default settings.  **Do not modify** this file directly!
Instead, create or modify `config.php`, which may contain a list of user-defined
options that override any of the default settings.

This is a list of configuration options you can currently use:

+ `dbinfo` should return an array with information required for connecting to
  the database, namely:

    + `HOST`: hostname of the DB server, defaults to `127.0.0.1`
    + `USER`: name of the MySQL user, defaults to `cora`
    + `PASSWORD`: password of the MySQL user, defaults to `trustthetext`
    + `DBNAME`: name of the MySQL database, defaults to `cora`

+ `default_language` sets the default language of the web interface for new
  users and for the login page; this feature is not implemented yet.

+ `description` is the value of the meta description HTML tag on the web page.

+ `external_param_dir` sets the local directory that CorA uses to store data
  files; currently, that means parameter files for automatic annotators.
  Currently defaults to `/var/lib/cora/` regardless of host system (i.e., if
  you're running CorA on Windows, you definitely need to change this to
  something sensible).

+ `keywords` is the value of the meta keywords HTML tag on the web page.

+ `longtitle` is a long name of this CorA instance, currently only used in the
  HTML title tag.

+ `password_cost` is the
  [algorithmic cost of PHP's bcrypt algorithm for hashing passwords](http://php.net/manual/en/password.constants.php).

+ `session_name` is the PHP session name to use, which affects the name of the
  browser cookie set by CorA.  Change this to something unique when running more
  than one CorA instance on the same server.

+ `test_suffix` is a string appended to database credentials when running unit
  tests; e.g., if this is set to "test" and the CorA database is "cora", unit
  tests will try to run on a database "cora_test".

+ `title` is the name of this CorA instance, used in the HTML title tag and
  displayed at the top of each page.



[git_repo]: https://bitbucket.org/mbollmann/cora/
[download_url]: https://bitbucket.org/mbollmann/cora/downloads
