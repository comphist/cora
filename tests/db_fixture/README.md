# Database fixture for PHPUnit/DbUnit tests

This directory contains files for a simple database fixture to use with
PHPUnit/DbUnit tests.  Specifically:

+ `coratest_data.*` contains the table data of the test fixture (see below).
+ `fixture.php` provides the DB fixture and should be included by all tests that
  rely on it.
+ `sources/` contains data files (CorA XML documents, tagsets, etc.) that were
  used for creating the test fixture; it can be used for reference when writing
  tests or as an aid to manually re-create the test database in case something
  goes horribly wrong.

**Important:** The actual database schema is **NOT** included in this directory.
Instead, `fixture.php` will derive it from the main schema file stored in
`<repo-root>/db/coradb.sql`.  This ensures that tests are always run against the
latest database schema, but also requires special care in making sure the data
files are kept up-to-date! (See "Updating the data files" below.)

## Creating the data files

Data files (`coratest_data.*`) are created by dumping an existing CorA test
instance:

    mysqldump -uroot -p --no-create-info --replace -c cora_testdev > coratest_data.sql
    mysqldump -uroot -p --no-create-info --xml cora_testdev > coratest_data.xml

Both SQL and XML versions are kept because

+ the XML version is used by the PHPUnit tests, while
+ the SQL version can be used to recreate the database in a CorA instance
  (e.g., for modifying it).

## Updating the data files

The data files need to be updated if

+ the database schema changed significantly (e.g., renamed or deleted
  tables/columns, newly added columns that are crucial for the tested
  components, etc.); or
+ new test cases should be added that require new entries in the DB.

Ideally, updating the data files should be done by creating an actual CorA
instance from them, performing the changes within CorA (if at all possible), and
dumping the database again.

To do so, build CorA from a commit with the same database schema used to create
the test data, then call

    php <build-dir>/www/db/configure_db_cli.php -d cora_testdev -a install -P <mysql-root-password>
    mysql -uroot -p<mysql-root-password> cora_testdev < coratest_data.sql

If the database schema has changed, checkout the latest version now, then
perform a database upgrade:

    php <build-dir>/www/db/configure_db_cli.php -d cora_testdev -a upgrade -P <mysql-root-password>

Make any changes to the test database you want, then dump it (see "Creating the
data files" above), and don't forget to drop your test database from MySQL
afterwards.
