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
  CACHE INTERNAL "Relative path to MooTools core library" FORCE
  )
set(
  CORA_HREF_JS_MOOTOOLS_MORE
  "${CORA_CURRENT_REL_PATH}/mootools-more-1.4.0.1-min.js"
  CACHE INTERNAL "Relative path to MooTools-More library" FORCE
  )

#########################################################
## Core JavaScript files
#########################################################

configure_all_copyonly(
  "${CONFIGURE_TARGET_DIR}"
  LAB.js
  main.js
  main.login.js
  )
set(JS_SOURCES_MAIN       LAB.js main.js)
set(JS_SOURCES_MAIN_LOGIN LAB.js main.login.js)
if(NOT WITH_MINIFY_JS)
  cat_multiple("${CORA_CURRENT_WEB_DIR}/main.js"       ${JS_SOURCES_MAIN})
  cat_multiple("${CORA_CURRENT_WEB_DIR}/main.login.js" ${JS_SOURCES_MAIN_LOGIN})
endif()

# TODO: can we separate the "plugin" files from CorA proper?
set(JS_SOURCES_MASTER
  request/CoraRequestErrors.js
  request/CoraRequest.js
  settings.js
  ProgressBar.js
  iFrameFormRequest.js
  Meio.Autocomplete.js
  FlexRowList.js
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

if(WITH_MINIFY_JS)
  set_with_cache_buster(
    CORA_HREF_JS_MASTER "Relative path to main JS file(s)"
    "${CORA_CURRENT_REL_PATH}/master.min.js" ${JS_SOURCES_MASTER}
    )
else()
  set_list_with_cache_buster(
    CORA_HREF_JS_MASTER "Relative path to main JS file(s)"
    "${CORA_CURRENT_REL_PATH}" ${JS_SOURCES_MASTER}
    )
endif()

configure_all_copyonly(${CONFIGURE_TARGET_DIR} ${JS_SOURCES_MASTER})

#########################################################
## JavaScript files only needed for admin functionality
#########################################################

# should compile to admin.min.js
set(JS_SOURCES_ADMIN
  datepicker.js
  admin.js
)

if(WITH_MINIFY_JS)
  set_with_cache_buster(
    CORA_HREF_JS_ADMIN "Relative path to admin JS file(s)"
    "${CORA_CURRENT_REL_PATH}/admin.min.js" ${JS_SOURCES_ADMIN}
    )
else()
  set_list_with_cache_buster(
    CORA_HREF_JS_ADMIN "Relative path to admin JS file(s)"
    "${CORA_CURRENT_REL_PATH}" ${JS_SOURCES_ADMIN}
    )
endif()

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

if(WITH_MINIFY_JS)
  set_with_cache_buster(
    CORA_HREF_JS_MBOX "Relative path to mBox JS file(s)"
    "${CORA_CURRENT_REL_PATH}/mbox.min.js" ${JS_SOURCES_MBOX}
    )
else()
  set_list_with_cache_buster(
    CORA_HREF_JS_MBOX "Relative path to mBox JS file(s)"
    "${CORA_CURRENT_REL_PATH}" ${JS_SOURCES_MBOX}
    )
endif()

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
  add_custom_command(
    OUTPUT "${CORA_CURRENT_WEB_DIR}/main.js"
    COMMAND ${Java_JAVA_EXECUTABLE} -jar ${CLOSURE_JAR}
            --js_output_file "${CORA_CURRENT_WEB_DIR}/main.js"
            --compilation_level ${CLOSURE_COMPILATION_LEVEL}
            --warning_level QUIET
            --third_party
            ${JS_SOURCES_MAIN}
    WORKING_DIRECTORY "${CMAKE_CURRENT_BINARY_DIR}"
    DEPENDS ClosureCompiler ${JS_SOURCES_MAIN}
    COMMENT "Compressing main.js"
    )
  add_custom_command(
    OUTPUT "${CORA_CURRENT_WEB_DIR}/main.login.js"
    COMMAND ${Java_JAVA_EXECUTABLE} -jar ${CLOSURE_JAR}
            --js_output_file "${CORA_CURRENT_WEB_DIR}/main.login.js"
            --compilation_level ${CLOSURE_COMPILATION_LEVEL}
            --warning_level QUIET
            --third_party
            ${JS_SOURCES_MAIN_LOGIN}
    WORKING_DIRECTORY "${CMAKE_CURRENT_BINARY_DIR}"
    DEPENDS ClosureCompiler ${JS_SOURCES_MAIN_LOGIN}
    COMMENT "Compressing main.login.js"
    )
  add_custom_target(
    web-minify-js
    DEPENDS "${CORA_CURRENT_WEB_DIR}/master.min.js"
            "${CORA_CURRENT_WEB_DIR}/admin.min.js"
            "${CORA_CURRENT_WEB_DIR}/mbox.min.js"
            "${CORA_CURRENT_WEB_DIR}/main.js"
            "${CORA_CURRENT_WEB_DIR}/main.login.js"
    )
  add_dependencies(web web-minify-js)
endif()
