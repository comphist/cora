set_current_web_dir()

set(LOCAL_PHP_SOURCES
  byte_safe_strings.php
  cast_to_int.php
  error_polyfill.php
  random_bytes_com_dotnet.php
  random_bytes_dev_urandom.php
  random_bytes_libsodium_legacy.php
  random_bytes_libsodium.php
  random_bytes_mcrypt.php
  random_int.php
  random.php
)

configure_all_copyonly(${CORA_CURRENT_WEB_DIR} ${LOCAL_PHP_SOURCES})
# do not call add_sources() for external files
