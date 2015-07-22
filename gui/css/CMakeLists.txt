file(RELATIVE_PATH CORA_CURRENT_REL_PATH "${CMAKE_SOURCE_DIR}" "${CMAKE_CURRENT_SOURCE_DIR}")
set(CORA_CURRENT_WEB_DIR "${CORA_WEB_DIR}/${CORA_CURRENT_REL_PATH}")

# These are currently always loaded separately, so we just add them as sources:
add_sources(
  datepicker.css
  open-iconic.min.css
)

set(CSS_SOURCES "")
list(APPEND CSS_SOURCES
  mBoxCore.css
  mBoxModal.css
  mBoxNotice.css
  mBoxTooltip.css
  mForm_mod.css
  mFormElement-Select.css
  Meio.Autocomplete.css
  MultiSelect.css
)

if(WITH_MINIFY_CSS)
  # configure to binary dir
  set(SASS_TARGET_DIR ${CMAKE_CURRENT_BINARY_DIR})
  # concatenate stuff that does NOT go through to Sass
  set(NO_SASS_FILE "${CMAKE_CURRENT_BINARY_DIR}/master-no-scss.css")
  file(WRITE "${NO_SASS_FILE}.in" "")
  cat_multiple("${CMAKE_CURRENT_BINARY_DIR}/master-no-scss.css" ${CSS_SOURCES})
  configure_file("${NO_SASS_FILE}.in" "${NO_SASS_FILE}" COPYONLY)
  # the web-minify-css target takes care of actually putting stuff in the
  # web directory
else()
  # no minification? just configure everything into the web directory
  set(SASS_TARGET_DIR ${CORA_CURRENT_WEB_DIR})
  add_sources(master.css ${CSS_SOURCES})
endif()

configure_file(screen.scss "${CMAKE_CURRENT_BINARY_DIR}/screen.scss" COPYONLY)
add_custom_target(
  web-scss
  COMMAND ${SASS_EXECUTABLE} --scss screen.scss "${SASS_TARGET_DIR}/screen.css"
  WORKING_DIRECTORY "${CMAKE_CURRENT_BINARY_DIR}"
  COMMENT "Pre-compiling SCSS stylesheet"
)
add_dependencies(web web-scss)

if(WITH_MINIFY_CSS)
  add_custom_target(
    web-concatenate-css
    COMMAND ${CMAKE_COMMAND} -P "${CMAKE_CURRENT_SOURCE_DIR}/concatenate.cmake"
    DEPENDS web-scss
    WORKING_DIRECTORY "${CMAKE_CURRENT_BINARY_DIR}"
    COMMENT "Concatenating CSS files"
    )
  add_custom_target(
    web-minify-css
    COMMAND ${Java_JAVA_EXECUTABLE} -jar ${YUICOMP_JAR} --type css -o "${CORA_CURRENT_WEB_DIR}/master.min.css" "${CMAKE_CURRENT_BINARY_DIR}/master.css"
    DEPENDS web-concatenate-css YUICompressor "${CMAKE_CURRENT_BINARY_DIR}/master.css"
    WORKING_DIRECTORY "${CMAKE_CURRENT_BINARY_DIR}"
    COMMENT "Compressing CSS files"
    )
  add_dependencies(web web-minify-css)
endif()
