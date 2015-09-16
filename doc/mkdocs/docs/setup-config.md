General configuration options of CorA are stored in PHP files in the web
directory.  Be aware that these configuration files **contain sensitive
information** such as the database password used by CorA.  Setting restrictive
permissions on these files is therefore recommended.

`config.defaults.php` contains a list of all possible configuration options
along with their default settings.  **Do not modify** this file directly!
Instead, create or modify `config.php`, which may contain a list of user-defined
options that override any of the default settings.

## List of configuration options

dbinfo
:   An array with information required for connecting to the database, namely:

      + `HOST`: hostname of the DB server, defaults to `127.0.0.1`
      + `USER`: name of the MySQL user, defaults to `cora`
      + `PASSWORD`: password of the MySQL user, defaults to `trustthetext`
      + `DBNAME`: name of the MySQL database, defaults to `cora`

default_language
:   The default language of the web interface for new users and for the login
    page; this feature is not implemented yet.

description
:   Value of the meta description HTML tag on the web page.

external_param_dir
:   Sets the local directory that CorA uses to store data files; currently, that
    means parameter files for automatic annotators.  Currently defaults to
    `/var/lib/cora/` regardless of host system (i.e., if you're running CorA on
    Windows, you definitely need to change this to something sensible).

keywords
:   Value of the meta keywords HTML tag on the web page.

longtitle
:   Long name of this CorA instance, currently only used in the
    HTML title tag.

password_cost
:   The [algorithmic cost of PHP's bcrypt algorithm for hashing passwords](http://php.net/manual/en/password.constants.php).

session_name
:   PHP session name to use; affects the name of the browser cookie set by CorA.
    Change this to something unique when running more than one CorA instance on
    the same server.

test_suffix
:   A string appended to database credentials when running unit tests; e.g., if
    this is set to "test" and the CorA database is "cora", unit tests will try
    to run on a database "cora_test".

title
:   Name of this CorA instance, used in the HTML title tag and displayed at the
    top of each page.
