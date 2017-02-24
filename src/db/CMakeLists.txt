set(CORA_DB_DIR "${CORA_WEB_DIR}/db"
    CACHE INTERNAL "Path to database directory" FORCE)

configure_file(configure_db.php.in "${CORA_DB_DIR}/configure_db.php")
configure_all_copyonly("${CORA_DB_DIR}"
  common.php
  configure_db_cli.php
  coradb.sql
  coradb-data.sql
  migration/README
  migration/1-to-2.sql
  migration/2-to-3.sql
  migration/3-to-4.sql
  migration/revert-1-to-2.sql
  migration/revert-2-to-3.sql
  migration/revert-3-to-4.sql
)
