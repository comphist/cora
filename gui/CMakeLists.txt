set_current_web_dir()

configure_all_copyonly(${CORA_CURRENT_WEB_DIR}
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
file(GLOB_RECURSE CORA_FONTS RELATIVE "${CMAKE_CURRENT_SOURCE_DIR}" fonts/*)
file(GLOB_RECURSE CORA_IMAGES RELATIVE "${CMAKE_CURRENT_SOURCE_DIR}" images/*)
configure_all_copyonly(${CORA_CURRENT_WEB_DIR}
  ${CORA_FONTS}
  ${CORA_IMAGES}
  )
