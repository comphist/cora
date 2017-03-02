cmake_minimum_required(VERSION 2.8.12)
project(CorA NONE)
set(CMAKE_PROJECT_VERSION 1.2)
set(CMAKE_MODULE_PATH ${CMAKE_MODULE_PATH} "${CMAKE_SOURCE_DIR}/CMakeModules")

include(AddSources)
include(Cat)
include(ConfigureAll)
include(SetCurrentWebDir)
include(SetWithCacheBuster)

# modelled after https://github.com/kogmbh/WebODF/blob/master/webodf/CMakeLists.txt

# Require separate build dir
if (CMAKE_SOURCE_DIR STREQUAL CMAKE_BINARY_DIR)
  message(FATAL_ERROR "Source and target directory must be different.")
endif (CMAKE_SOURCE_DIR STREQUAL CMAKE_BINARY_DIR)

#########################################################
## Options and other cache variables
#########################################################

option(DEBUG_MODE "Debug mode" OFF)
if(DEBUG_MODE)
  set(DEBUG_MODE_YESNO YES)
  set(MINIFY_CSS_DEFAULT NO)
  set(MINIFY_JS_DEFAULT NO)
  set(EXPENSIVE_TESTS_DEFAULT YES)
else()
  set(DEBUG_MODE_YESNO NO)
  set(MINIFY_CSS_DEFAULT YES)
  set(MINIFY_JS_DEFAULT YES)
  set(EXPENSIVE_TESTS_DEFAULT NO)
endif()
option(WITH_MINIFY_CSS "Minification of CSS files" ${MINIFY_CSS_DEFAULT})
option(WITH_MINIFY_JS "Minification of JavaScript files" ${MINIFY_JS_DEFAULT})
option(WITH_EXPENSIVE_TESTS "Include potentially more time-consuming tests" ${EXPENSIVE_TESTS_DEFAULT})

set(EXTERNALS_DOWNLOAD_DIR "${CMAKE_BINARY_DIR}/downloads"
    CACHE PATH "Directory where external downloads will be stored")
set(CORA_WEB_DIR "${CMAKE_CURRENT_BINARY_DIR}/www"
    CACHE PATH "Path where all web-related files will be built")

message(STATUS "Debug mode: ${DEBUG_MODE}")
message(STATUS "Minification of CSS files: ${WITH_MINIFY_CSS}")
message(STATUS "Minification of JavaScript files: ${WITH_MINIFY_JS}")
message(STATUS "External downloads will be stored/expected in: ${EXTERNALS_DOWNLOAD_DIR}")

#########################################################
## Find installed dependencies
#########################################################

find_package(PHP5 5.5 COMPONENTS Runtime REQUIRED)
find_package(MySQL 5.5 REQUIRED)
find_package(Sass 3.2 REQUIRED)

if(WITH_MINIFY_CSS OR WITH_MINIFY_JS)
  # Needed for Closure Compiler / YUICompressor
  find_package(Java COMPONENTS Runtime REQUIRED)
endif()

# For API documentation
find_package(Doxygen)
find_package(Perl 5.8 COMPONENTS Runtime)
find_package(NaturalDocs 1.51)

# For user documentation
find_package(mkdocs 0.14)

# For unit testing
find_package(PHPUnit 3.7)

#########################################################
## Download stuff that is not commonly installed/packaged
#########################################################

include (ExternalProject)

# Closure Compiler
ExternalProject_Add(
  ClosureCompiler
  DOWNLOAD_DIR ${EXTERNALS_DOWNLOAD_DIR}
  URL "http://dl.google.com/closure-compiler/compiler-20150505.tar.gz"
  URL_MD5 dea8e282c316316daeb39fcd5708d369
  LOG_DOWNLOAD 1
  CONFIGURE_COMMAND ""
  BUILD_COMMAND ""
  INSTALL_COMMAND ""
)
set_target_properties(ClosureCompiler PROPERTIES EXCLUDE_FROM_ALL TRUE)
set(CLOSURE_JAR ${CMAKE_BINARY_DIR}/ClosureCompiler-prefix/src/ClosureCompiler/compiler.jar)

# YUI Compressor
ExternalProject_Add(
  YUICompressor
  DOWNLOAD_DIR ${EXTERNALS_DOWNLOAD_DIR}
  URL "https://github.com/yui/yuicompressor/releases/download/v2.4.8/yuicompressor-2.4.8.zip"
  URL_MD5 44f20ece35d889c1c658eb5297cd20ee
  LOG_DOWNLOAD 1
  CONFIGURE_COMMAND ""
  BUILD_COMMAND ""
  INSTALL_COMMAND ""
)
set_target_properties(YUICompressor PROPERTIES EXCLUDE_FROM_ALL TRUE)
set(YUICOMP_JAR ${CMAKE_BINARY_DIR}/YUICompressor-prefix/src/YUICompressor/yuicompressor-2.4.8.jar)

if(NOT NATURALDOCS_FOUND)
  ExternalProject_Add(
    NaturalDocs
    DOWNLOAD_DIR ${EXTERNALS_DOWNLOAD_DIR}
    URL "http://downloads.sourceforge.net/project/naturaldocs/Stable%20Releases/1.52/NaturalDocs-1.52.zip"
    URL_MD5 68e3982acae57b6befdf9e75b420fd80
    LOG_DOWNLOAD 1
    CONFIGURE_COMMAND ""
    BUILD_COMMAND ""
    INSTALL_COMMAND chmod a+x "${CMAKE_BINARY_DIR}/NaturalDocs-prefix/src/NaturalDocs/NaturalDocs"
    )
  set_target_properties(NaturalDocs PROPERTIES EXCLUDE_FROM_ALL TRUE)
  set(NATURALDOCS_EXECUTABLE ${CMAKE_BINARY_DIR}/NaturalDocs-prefix/src/NaturalDocs/NaturalDocs)
endif()

#########################################################
## Main source directory
#########################################################

add_subdirectory(src)

# TODO: figure out installation step, could include sth. like
# install(DIRECTORY ${CORA_WEB_DIR} DESTINATION ???)

#########################################################
## Documentation
#########################################################

add_custom_target(docs)

if(DOXYGEN_FOUND)
  configure_file("${CMAKE_CURRENT_SOURCE_DIR}/Doxyfile.in"
                 "${CMAKE_CURRENT_BINARY_DIR}/Doxyfile"
                 @ONLY)
  add_custom_target(docs-php
    ${DOXYGEN_EXECUTABLE} ${CMAKE_CURRENT_BINARY_DIR}/Doxyfile
    WORKING_DIRECTORY ${CMAKE_CURRENT_BINARY_DIR}
    COMMENT "Generating API documentation (PHP)"
    )
  add_dependencies(docs docs-php)
else()
  message(STATUS "SKIPPING generation of API documentation for PHP (Doxygen not found)")
endif()

if(PERL_FOUND)
  set(NATURALDOCS_FLAGS
    -i "${CMAKE_CURRENT_SOURCE_DIR}/src/gui/js"
    -xi "${CMAKE_CURRENT_SOURCE_DIR}/src/gui/js/mbox"
    -o HTML "${CMAKE_CURRENT_BINARY_DIR}/docs/api-js"
    -p "${CMAKE_CURRENT_BINARY_DIR}/NaturalDocs-config"
    )
  if(NOT DEBUG_MODE)
    list(APPEND NATURALDOCS_FLAGS --quiet)
  endif()
  add_custom_target(docs-js
    ${NATURALDOCS_EXECUTABLE} ${NATURALDOCS_FLAGS}
    WORKING_DIRECTORY ${CMAKE_CURRENT_BINARY_DIR}
    COMMENT "Generating API documentation (JavaScript)"
    )
  add_dependencies(docs docs-js)
  if(NOT NATURALDOCS_FOUND)
    add_dependencies(docs-js NaturalDocs)
  endif()
  file(MAKE_DIRECTORY "${CMAKE_CURRENT_BINARY_DIR}/docs/api-js")
  file(MAKE_DIRECTORY "${CMAKE_CURRENT_BINARY_DIR}/NaturalDocs-config")
else()
  message(STATUS "SKIPPING generation of API documentation for JavaScript (Perl not found)")
endif()

if(MKDOCS_FOUND)
  configure_file(mkdocs.yml "${CMAKE_CURRENT_BINARY_DIR}/mkdocs.yml" COPYONLY)
  set(MKDOCS_FLAGS
    --site-dir "${CMAKE_CURRENT_BINARY_DIR}/docs/user/"
    --config-file "${CMAKE_CURRENT_BINARY_DIR}/mkdocs.yml"
    )
  if(DEBUG_MODE)
    list(APPEND MKDOCS_FLAGS --clean --verbose)
  endif()
  add_custom_target(docs-user
    ${MKDOCS_EXECUTABLE} build ${MKDOCS_FLAGS}
    WORKING_DIRECTORY "${CMAKE_SOURCE_DIR}/doc"
    COMMENT "Generating user documentation"
    )
  add_dependencies(docs docs-user)
else()
  message(STATUS "SKIPPING generation of user documentation (mkdocs not found)")
endif()

if(DOXYGEN_FOUND OR MKDOCS_FOUND)
  file(MAKE_DIRECTORY "${CMAKE_BINARY_DIR}/docs")
endif()

#########################################################
## Testing
#########################################################

enable_testing()
configure_file(phpunit.xml.dist.in "${CMAKE_CURRENT_BINARY_DIR}/phpunit.xml.dist" @ONLY)
file(MAKE_DIRECTORY "${CMAKE_CURRENT_BINARY_DIR}/TestCoverage")
if(PHPUNIT_FOUND)
  add_subdirectory(tests)
  if (CMAKE_CONFIGURATION_TYPES)
    add_custom_target(check COMMAND ${CMAKE_CTEST_COMMAND}
        --force-new-ctest-process --output-on-failure
        --build-config "$<CONFIGURATION>")
  else()
    add_custom_target(check COMMAND ${CMAKE_CTEST_COMMAND}
        --force-new-ctest-process --output-on-failure)
  endif()
else()
  message(STATUS "SKIPPING PHP-based tests (PHPUnit not found)")
endif()

#########################################################
## Scripts/executables
#########################################################

add_subdirectory(bin)
