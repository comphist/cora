file(RELATIVE_PATH CORA_CURRENT_REL_PATH "${CMAKE_SOURCE_DIR}" "${CMAKE_CURRENT_SOURCE_DIR}")
set(CORA_CURRENT_WEB_DIR "${CORA_WEB_DIR}/${CORA_CURRENT_REL_PATH}")

#########################################################
## CSS files that are always loaded separately
#########################################################

configure_all(${CORA_CURRENT_WEB_DIR} datepicker.css open-iconic.min.css)

set_with_cache_buster(
  CORA_HREF_CSS_ADMIN "Relative path to admin-related CSS file"
  "${CORA_CURRENT_REL_PATH}/datepicker.css"
  datepicker.css
  )
set_with_cache_buster(
  CORA_HREF_CSS_OPENICONIC "Relative path to Open Iconic CSS file"
  "${CORA_CURRENT_REL_PATH}/open-iconic.min.css"
  open-iconic.min.css
  )

#########################################################
## CSS files that can be minified
#########################################################

set(CSS_SOURCES
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
  set(CONFIGURE_TARGET_DIR ${CMAKE_CURRENT_BINARY_DIR})
  set(CSS_MASTER_FILENAME "master.min.css")
else()
  # no minification? just configure everything into the web directory
  set(CONFIGURE_TARGET_DIR ${CORA_CURRENT_WEB_DIR})
  set(CSS_MASTER_FILENAME "master.css")
  list(APPEND CSS_SOURCES master.css)
endif()

configure_all(${CONFIGURE_TARGET_DIR} ${CSS_SOURCES})
set_with_cache_buster(
  CORA_HREF_CSS_MASTER "Relative path to master CSS file"
  "${CORA_CURRENT_REL_PATH}/${CSS_MASTER_FILENAME}"
  screen.scss ${CSS_SOURCES}
  )

#########################################################
## Run Sass to convert .scss -> .css
#########################################################

configure_file(screen.scss "${CMAKE_CURRENT_BINARY_DIR}/screen.scss" COPYONLY)
add_custom_command(
  OUTPUT "${CONFIGURE_TARGET_DIR}/screen.css"
  COMMAND ${SASS_EXECUTABLE} --scss screen.scss "${CONFIGURE_TARGET_DIR}/screen.css"
  WORKING_DIRECTORY "${CMAKE_CURRENT_BINARY_DIR}"
  DEPENDS screen.scss
  COMMENT "Pre-compiling SCSS stylesheet"
)
add_custom_target(web-scss DEPENDS "${CONFIGURE_TARGET_DIR}/screen.css")
add_dependencies(web web-scss)

#########################################################
## Minification
#########################################################

if(WITH_MINIFY_CSS)
  list(INSERT CSS_SOURCES 0 "${CONFIGURE_TARGET_DIR}/screen.css")
  add_custom_command(
    OUTPUT "${CORA_CURRENT_WEB_DIR}/master.min.css"
    COMMAND ${CMAKE_COMMAND}
            -DOUTFILE="${CMAKE_CURRENT_BINARY_DIR}/master.css"
            -DINFILES="${CSS_SOURCES}"
            -P "concatenate.cmake"
    COMMAND ${Java_JAVA_EXECUTABLE} -jar ${YUICOMP_JAR}
            --type css
            -o "${CORA_CURRENT_WEB_DIR}/master.min.css"
            "${CMAKE_CURRENT_BINARY_DIR}/master.css"
    DEPENDS web-scss YUICompressor ${CSS_SOURCES}
    WORKING_DIRECTORY "${CMAKE_CURRENT_SOURCE_DIR}"
    COMMENT "Compressing master.css"
    )
  add_custom_target(
    web-minify-css
    DEPENDS "${CORA_CURRENT_WEB_DIR}/master.min.css"
    )
  add_dependencies(web web-minify-css)
endif()
