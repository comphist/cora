message(STATUS "Using database '${CORA_DB_NAME}' at '${CORA_DB_SERVER}'"
               " with user '${CORA_DB_USER}'")
set(CORA_DB_DIR "${CMAKE_CURRENT_BINARY_DIR}"
    CACHE INTERNAL "Path to database directory" FORCE)

configure_file(coradb.sql.in "${CORA_DB_DIR}/coradb.sql")
configure_all_copyonly("${CORA_DB_DIR}"
  coradb-data.sql
  migration/README
  migration/revert-1-to-2.sql
)

configure_all("${CORA_DB_DIR}"
  migration/1-to-2.sql
)
