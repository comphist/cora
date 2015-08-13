message(STATUS "Using database '${CORA_DB_NAME}' at '${CORA_DB_SERVER}'"
               " with user '${CORA_DB_USER}'")
set(CORA_DB_DIR "${CMAKE_CURRENT_BINARY_DIR}"
    CACHE INTERNAL "Path to database directory" FORCE)

configure_all_copyonly("${CORA_DB_DIR}"
  coradb.sql
  coradb-data.sql
  migration/README
  migration/1-to-2.sql
  migration/revert-1-to-2.sql
)
