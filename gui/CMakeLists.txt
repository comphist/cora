add_sources(
  admin.php
  edit.php
  file.php
  help.php
  login.php
  menu.php
  search.php
  settings.php
)

add_subdirectory(css)
add_subdirectory(js)

# Directories that will only ever contain pure data files
add_sources_glob(fonts/* images/*)
