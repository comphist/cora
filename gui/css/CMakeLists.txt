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
else()
  # no minification? just configure everything into the web directory
  set(SASS_TARGET_DIR ${CORA_CURRENT_WEB_DIR})
  add_sources(master.css ${CSS_SOURCES})
endif()

configure_file(screen.scss "${CMAKE_CURRENT_BINARY_DIR}/screen.scss" COPYONLY)
add_custom_command(
  OUTPUT "${SASS_TARGET_DIR}/screen.css"
  COMMAND ${SASS_EXECUTABLE} --scss screen.scss "${SASS_TARGET_DIR}/screen.css"
  WORKING_DIRECTORY "${CMAKE_CURRENT_BINARY_DIR}"
  DEPENDS screen.scss
  COMMENT "Pre-compiling SCSS stylesheet"
)
add_custom_target(web-scss DEPENDS "${SASS_TARGET_DIR}/screen.css")
add_dependencies(web web-scss)

if(WITH_MINIFY_CSS)
  list(INSERT CSS_SOURCES 0 "${SASS_TARGET_DIR}/screen.css")
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
