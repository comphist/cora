add_sources(
  datepicker.css
  master.css
  Meio.Autocomplete.css
  MultiSelect.css
  open-iconic.min.css
  screen.scss
)

# TODO: the directory handling is messed up atm
add_custom_target(
  web-scss
  COMMAND ${SASS_EXECUTABLE} --scss screen.scss screen.css
  WORKING_DIRECTORY "${CORA_WEB_DIR}/gui/css"
  COMMENT "Pre-compiling SCSS stylesheet..."
)
add_dependencies(web web-scss)
