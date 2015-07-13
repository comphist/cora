# Installing CorA

At this point, there is no easy way to deploy and install CorA yet.

**TODO:** Fill this section with content as soon as a proper deployment process
  exists for CorA.

### What must be done to install CorA?

+ **Create the main database structure:** this can be done by executing the
    MySQL script `coradb.sql` that is part of the repository.

+ **Create the first admin user** so he/she can log in to the web interface;
  AFAIK there is currently no way to do that outside the MySQL console!

+ **Set passwords for the database users;** currently hardcoded in `globals.php`
  and `coradb.sql`.

+ **Configure variables** such as the database server (if it's not `localhost`)
    or the directory for parameter files (currently `/var/lib/cora`).

+ **Compress JavaScript/CSS files** in a similar fashion to the current
  `bin/compress.sh` (which contains hardcoded paths and no error handling)

+ **Run unit tests.** Not strictly necessary of course, but should probably be
  part of an automatic deployment process.

+ **Copy files to a web server directory;** note that not all files should be
  copied, e.g. the `bin/` or `tests/` directories.
