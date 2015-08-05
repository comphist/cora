# This is a mess.  Which bin/ files do we want to include in the build, which
# ones should even be installed somewhere, and how should we configure their
# CorA directory?
configure_file(cora_create_user.php.in cora_create_user.php @ONLY)
configure_file(debug_memory_usage.php.in debug_memory_usage.php @ONLY)
configure_file(export_file.php.in export_file.php @ONLY)
configure_file(update_taglist.php.in update_taglist.php @ONLY)
configure_file(repair_duplicate_mod2error.php.in repair_duplicate_mod2error.php @ONLY)
configure_file(repair_duplicate_tags.php.in repair_duplicate_tags.php @ONLY)

configure_file(cli_status_bar.php cli_status_bar.php COPYONLY)
