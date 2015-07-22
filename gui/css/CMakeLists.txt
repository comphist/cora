add_sources(
  datepicker.css
  master.css
  mBoxCore.css
  mBoxModal.css
  mBoxNotice.css
  mBoxTooltip.css
  mForm_mod.css
  mFormElement-Select.css
  Meio.Autocomplete.css
  MultiSelect.css
  open-iconic.min.css
)

configure_file(screen.scss "${CMAKE_CURRENT_BINARY_DIR}/screen.scss" COPYONLY)
file(RELATIVE_PATH _relPath "${CMAKE_SOURCE_DIR}" "${CMAKE_CURRENT_SOURCE_DIR}")
set(CORA_CURRENT_WEB_DIR "${CORA_WEB_DIR}/${_relPath}")
add_custom_target(
  web-scss
  COMMAND ${SASS_EXECUTABLE} --scss screen.scss "${CORA_CURRENT_WEB_DIR}/screen.css"
  WORKING_DIRECTORY "${CMAKE_CURRENT_BINARY_DIR}"
  COMMENT "Pre-compiling SCSS stylesheet..."
)
add_dependencies(web web-scss)
