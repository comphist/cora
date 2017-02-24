#########################################################
## Main files belonging in the web directory
#########################################################

file(MAKE_DIRECTORY "${CORA_WEB_DIR}")
add_custom_target(web ALL)

set(CORA_PHP_SOURCES "")
add_sources(CORA_PHP_SOURCES
  content.php
  index.php
  news.php
  request.php
  )
configure_all_copyonly(${CORA_WEB_DIR} ${CORA_PHP_SOURCES})
add_subdirectory(gui)
add_subdirectory(lib)

configure_file(gui.php.in "${CORA_WEB_DIR}/gui.php" @ONLY)
configure_file(error.php.in "${CORA_WEB_DIR}/error.php" @ONLY)
add_sources(CORA_PHP_SOURCES gui.php)

#########################################################
## Configuration files
#########################################################

configure_file(config.defaults.php "${CORA_WEB_DIR}/config.defaults.php" COPYONLY)
configure_file(version.php "${CORA_WEB_DIR}/version.php" COPYONLY)
add_sources(config.defaults.php version.php)

add_subdirectory(locale)

#########################################################
## Linting
#########################################################

add_custom_target(lint-php
  COMMENT "Checking PHP sources for syntax errors")
foreach(CORA_PHP_SRC ${CORA_PHP_SOURCES})
  add_custom_command(TARGET lint-php POST_BUILD
    COMMAND ${PHP5_EXECUTABLE} -l ${CORA_PHP_SRC}
    WORKING_DIRECTORY ${CORA_WEB_DIR}
    )
endforeach()

add_custom_target(lint DEPENDS lint-php)

#########################################################
## Database
#########################################################

add_subdirectory(db)
