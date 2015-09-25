file(RELATIVE_PATH CORA_REL_WEB_DIR "${CMAKE_CURRENT_BINARY_DIR}" "${CORA_WEB_DIR}")
file(RELATIVE_PATH CORA_REL_DB_DIR "${CMAKE_CURRENT_BINARY_DIR}" "${CORA_DB_DIR}")

# This is a mess.  Which bin/ files do we want to include in the build, which
# ones should even be installed somewhere, and how should we configure their
# CorA directory?
configure_all("${CMAKE_CURRENT_BINARY_DIR}"
  cloning_tools.php
  create_user.php
  debug_memory_usage.php
  export_file.php
  make_database.php
  update_taglist.php
  repair_duplicate_mod2error.php
  repair_duplicate_tags.php
)

configure_all_copyonly("${CMAKE_CURRENT_BINARY_DIR}"
  cli_status_bar.php
  clone_file.php
  clone_project.php
)
