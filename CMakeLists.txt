cmake_minimum_required(VERSION 2.8.12)
project(CorA NONE)
set(CMAKE_PROJECT_VERSION 1.2)
set(CMAKE_MODULE_PATH ${CMAKE_MODULE_PATH} "${CMAKE_SOURCE_DIR}/CMakeModules")
include(ConfigureAll)

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
else()
  set(DEBUG_MODE_YESNO NO)
  set(MINIFY_CSS_DEFAULT YES)
  set(MINIFY_JS_DEFAULT YES)
endif()
option(WITH_MINIFY_CSS "Minification of CSS files" ${MINIFY_CSS_DEFAULT})
option(WITH_MINIFY_JS "Minification of JavaScript files" ${MINIFY_JS_DEFAULT})

set(CORA_WEB_DIR "${CMAKE_CURRENT_BINARY_DIR}/www"
    CACHE PATH "Path where all web-related files will be built")
set(CORA_DB_NAME "cora"
    CACHE STRING "Name of the CorA database")
set(CORA_DB_SERVER "localhost"
    CACHE STRING "Database server used by CorA")
set(CORA_DB_USER "cora"
    CACHE STRING "Name of the database user used by CorA")
set(CORA_DB_PASSWORD "trustthetext"
    CACHE STRING "Password of the database user used by CorA")

#########################################################
## Find installed dependencies
#########################################################

find_package(PHP5 5.3 COMPONENTS Runtime REQUIRED)
find_package(Sass 3.4.13 REQUIRED)

# Needed for Closure Compiler / YUICompressor
find_package(Java COMPONENTS Runtime REQUIRED)

# For API documentation
find_package(Doxygen)

# For user documentation
find_package(mkdocs 0.14)

# For unit testing
find_package(PHPUnit 3.7)

#########################################################
## Download stuff that is not commonly installed/packaged
#########################################################

# allow specification of a directory with pre-downloaded
# requirements by evaluating environment variable
# $CORA_DOWNLOAD_DIR
# defaults to "./downloads" in the build directory.
if(IS_DIRECTORY $ENV{CORA_DOWNLOAD_DIR})
  set(EXTERNALS_DOWNLOAD_DIR $ENV{CORA_DOWNLOAD_DIR})
else(IS_DIRECTORY $ENV{CORA_DOWNLOAD_DIR})
  set(EXTERNALS_DOWNLOAD_DIR ${CMAKE_BINARY_DIR}/downloads)
endif(IS_DIRECTORY $ENV{CORA_DOWNLOAD_DIR})
message(STATUS "External downloads will be stored/expected in: ${EXTERNALS_DOWNLOAD_DIR}")

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
set(YUICOMP_JAR ${CMAKE_BINARY_DIR}/YUICompressor-prefix/src/YUICompressor/yuicompressor-2.4.8.jar)

#########################################################
## Files belonging in the web directory
#########################################################

file(MAKE_DIRECTORY ${CORA_WEB_DIR})
add_custom_target(web ALL)

include(AddSources)
include(SetWithCacheBuster)
add_sources(content.php index.php news.php request.php)
add_subdirectory(gui)
add_subdirectory(lib)

configure_file(gui.php.in "${CORA_WEB_DIR}/gui.php" @ONLY)

foreach(SRC ${SRCS})
  configure_file("${SRC}" "${CORA_WEB_DIR}/${SRC}" COPYONLY)
endforeach()

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
  add_custom_target(docs-api
    ${DOXYGEN_EXECUTABLE} ${CMAKE_CURRENT_BINARY_DIR}/Doxyfile
    WORKING_DIRECTORY ${CMAKE_CURRENT_BINARY_DIR}
    COMMENT "Generating API documentation..."
    )
  add_dependencies(docs docs-api)
endif()

if(MKDOCS_FOUND)
  configure_file("${CMAKE_CURRENT_SOURCE_DIR}/doc/mkdocs/mkdocs.yml.in"
                 "${CMAKE_CURRENT_BINARY_DIR}/mkdocs.yml")
  add_custom_target(docs-user
    ${MKDOCS_EXECUTABLE} build
    WORKING_DIRECTORY ${CMAKE_CURRENT_BINARY_DIR}
    COMMENT "Generating user documentation..."
    )
  add_dependencies(docs docs-user)
endif()

if(DOXYGEN_FOUND OR MKDOCS_FOUND)
  file(MAKE_DIRECTORY ${CMAKE_CURRENT_BINARY_DIR}/docs)
endif()

#########################################################
## Testing
#########################################################

enable_testing()
if(PHPUNIT_FOUND)
  add_subdirectory(tests)
endif()

#########################################################
## Database
#########################################################

message(STATUS "Using database '${CORA_DB_NAME}' at '${CORA_DB_SERVER}'"
               " with user '${CORA_DB_USER}'")
configure_file(coradb.sql.in "${CMAKE_CURRENT_BINARY_DIR}/coradb.sql")

#########################################################
## Scripts/executables
#########################################################

add_subdirectory(bin)
