set_current_web_dir()
file(MAKE_DIRECTORY "${CORA_CURRENT_WEB_DIR}")

if(WITH_MINIFY_JS)
  set(CONFIGURE_TARGET_DIR ${CMAKE_CURRENT_BINARY_DIR})
else()
  set(CONFIGURE_TARGET_DIR ${CORA_CURRENT_WEB_DIR})
endif()

#########################################################
## MooTools files, always included separately
#########################################################

configure_all_copyonly(
  "${CORA_CURRENT_WEB_DIR}"
  mootools-core-1.4.5-full-compat-yc.js
  mootools-more-1.4.0.1-min.js
  )

set(
  CORA_HREF_JS_MOOTOOLS_CORE
  "${CORA_CURRENT_REL_PATH}/mootools-core-1.4.5-full-compat-yc.js"
  CACHE STRING "Relative path to MooTools core library" FORCE
  )
set(
  CORA_HREF_JS_MOOTOOLS_MORE
  "${CORA_CURRENT_REL_PATH}/mootools-more-1.4.0.1-min.js"
  CACHE STRING "Relative path to MooTools-More library" FORCE
  )

#########################################################
## Core JavaScript files
#########################################################

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

configure_all_copyonly(${CONFIGURE_TARGET_DIR} ${JS_SOURCES_MASTER})

#########################################################
## JavaScript files only needed for admin functionality
#########################################################

# should compile to admin.min.js
set(JS_SOURCES_ADMIN
  datepicker.js
  admin.js
)

configure_all_copyonly(${CONFIGURE_TARGET_DIR} ${JS_SOURCES_ADMIN})

#########################################################
## mBox/mForm MooTools plugin
#########################################################

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

configure_all_copyonly(${CONFIGURE_TARGET_DIR} ${JS_SOURCES_MBOX})

#########################################################
## Minification
#########################################################

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
    WORKING_DIRECTORY "${CMAKE_CURRENT_BINARY_DIR}"
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
    WORKING_DIRECTORY "${CMAKE_CURRENT_BINARY_DIR}"
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
    WORKING_DIRECTORY "${CMAKE_CURRENT_BINARY_DIR}"
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
endif()
