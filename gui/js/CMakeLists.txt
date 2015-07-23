file(RELATIVE_PATH CORA_CURRENT_REL_PATH "${CMAKE_SOURCE_DIR}" "${CMAKE_CURRENT_SOURCE_DIR}")
set(CORA_CURRENT_WEB_DIR "${CORA_WEB_DIR}/${CORA_CURRENT_REL_PATH}")
file(MAKE_DIRECTORY "${CORA_CURRENT_WEB_DIR}")

# should compile to master.min.js
# TODO: can we separate the "plugin" files from CorA proper?
set(JS_SOURCES_MASTER
  request/CoraRequestErrors.js
  request/CoraRequest.js
  settings.js
  ProgressBar.js
  iFrameFormRequest.js
  Meio.Autocomplete.js
  gui.js
  tagsets.js
  tagsets/Tagset.js
  tagsets/SplitClassTagset.js
  tagsets/POS.js
  tagsets/Norm.js
  tagsets/NormBroad.js
  tagsets/NormType.js
  tagsets/LemmaAutocomplete.js
  tagsets/LemmaSugg.js
  tagsets/Lemma.js
  tagsets/LemmaPOS.js
  tagsets/Boundary.js
  tagsets/Comment.js
  tagsets/TagsetFactory.js
  file.js
  edit/DataSource.js
  edit/DataTableNavigation.js
  edit/DataTableProgressBar.js
  edit/DataTableDropdownMenu.js
  edit/DataTable.js
  edit/FlagHandler.js
  edit/LineJumper.js
  edit/TokenSearcher.js
  edit/SearchResults.js
  edit/HorizontalTextPreview.js
  edit/PageModel.js
  edit/EditorModelUndo.js
  edit/EditorModel.js
  edit.js
  MultiSelect.js
)

# should compile to admin.min.js
set(JS_SOURCES_ADMIN
  datepicker.js
  admin.js
)

# should compile to mbox.min.js
set(JS_SOURCES_MBOX
  mbox/mBox.Core.js
  mbox/mBox.Modal.js
  mbox/mBox.Notice.js
  mbox/mBox.Modal.Confirm.js
  mbox/mBox.Tooltip.js
  mbox/mForm.Core.js
  mbox/mForm.Submit.js
  mbox/mForm.Element.js
  mbox/mForm.Element.Select.js
)

if(WITH_MINIFY_JS)
  # Only use simple optimizations because our code isn't properly prepared for
  # advanced optimization (yet?)
  set(CLOSURE_COMPILATION_LEVEL "SIMPLE_OPTIMIZATIONS")
  add_custom_command(
    OUTPUT "${CORA_CURRENT_WEB_DIR}/master.min.js"
    COMMAND ${Java_JAVA_EXECUTABLE} -jar ${CLOSURE_JAR}
            --js_output_file "${CORA_CURRENT_WEB_DIR}/master.min.js"
            --language_in ECMASCRIPT5
            --compilation_level ${CLOSURE_COMPILATION_LEVEL}
            --warning_level DEFAULT
            ${JS_SOURCES_MASTER}
    WORKING_DIRECTORY "${CMAKE_CURRENT_SOURCE_DIR}"
    DEPENDS ClosureCompiler ${JS_SOURCES_MASTER}
    COMMENT "Compressing master.js"
    )
  add_custom_command(
    OUTPUT "${CORA_CURRENT_WEB_DIR}/admin.min.js"
    COMMAND ${Java_JAVA_EXECUTABLE} -jar ${CLOSURE_JAR}
            --js_output_file "${CORA_CURRENT_WEB_DIR}/admin.min.js"
            --language_in ECMASCRIPT5
            --compilation_level ${CLOSURE_COMPILATION_LEVEL}
            --warning_level DEFAULT
            ${JS_SOURCES_ADMIN}
    WORKING_DIRECTORY "${CMAKE_CURRENT_SOURCE_DIR}"
    DEPENDS ClosureCompiler ${JS_SOURCES_ADMIN}
    COMMENT "Compressing admin.js"
    )
  add_custom_command(
    OUTPUT "${CORA_CURRENT_WEB_DIR}/mbox.min.js"
    COMMAND ${Java_JAVA_EXECUTABLE} -jar ${CLOSURE_JAR}
            --js_output_file "${CORA_CURRENT_WEB_DIR}/mbox.min.js"
            --compilation_level ${CLOSURE_COMPILATION_LEVEL}
            --warning_level QUIET
            --third_party
            ${JS_SOURCES_MBOX}
    WORKING_DIRECTORY "${CMAKE_CURRENT_SOURCE_DIR}"
    DEPENDS ClosureCompiler ${JS_SOURCES_MBOX}
    COMMENT "Compressing mbox.js"
    )
  add_custom_target(
    web-minify-js
    DEPENDS "${CORA_CURRENT_WEB_DIR}/master.min.js"
            "${CORA_CURRENT_WEB_DIR}/admin.min.js"
            "${CORA_CURRENT_WEB_DIR}/mbox.min.js"
    )
  add_dependencies(web web-minify-js)
else()
  # no minification? just configure everything into the web directory
  add_sources(${JS_SOURCES_MASTER} ${JS_SOURCES_ADMIN} ${JS_SOURCES_MBOX})
endif()
