cmake_minimum_required(VERSION 2.8.12)
project(CorA NONE)
set(CMAKE_PROJECT_VERSION 1.2)

# modelled after https://github.com/kogmbh/WebODF/blob/master/webodf/CMakeLists.txt

message(STATUS "Source: ${CorA_SOURCE_DIR}")
message(STATUS "Target: ${CorA_BINARY_DIR}")

# Require separate build dir
if (CMAKE_SOURCE_DIR STREQUAL CMAKE_BINARY_DIR)
  message(FATAL_ERROR "Source and target directory must be different.")
endif (CMAKE_SOURCE_DIR STREQUAL CMAKE_BINARY_DIR)

#########################################################
## Find installed dependencies
#########################################################

set(CMAKE_MODULE_PATH ${CMAKE_MODULE_PATH} "${CMAKE_SOURCE_DIR}/CMakeModules")

find_package(PHP5 5.3 COMPONENTS Runtime REQUIRED)

# Needed for Closure Compiler / YUICompressor
find_package(Java COMPONENTS Runtime REQUIRED)

#########################################################
## Download stuff that is not commonly installed/packaged
#########################################################

# allow specification of a directory with pre-downloaded
# requirements by evaluating environment variable
# $CorA_DOWNLOAD_DIR
# defaults to "./downloads" in the build directory.
if ( IS_DIRECTORY $ENV{CorA_DOWNLOAD_DIR} )
    SET ( EXTERNALS_DOWNLOAD_DIR $ENV{CorA_DOWNLOAD_DIR} )
else ( IS_DIRECTORY $ENV{CorA_DOWNLOAD_DIR} )
    SET ( EXTERNALS_DOWNLOAD_DIR ${CMAKE_BINARY_DIR}/downloads )
endif ( IS_DIRECTORY $ENV{CorA_DOWNLOAD_DIR} )
MESSAGE ( STATUS "External downloads will be stored/expected in: ${EXTERNALS_DOWNLOAD_DIR}" )

include (ExternalProject)

# Closure Compiler
ExternalProject_Add(
    ClosureCompiler
    DOWNLOAD_DIR ${EXTERNALS_DOWNLOAD_DIR}
    URL "http://dl.google.com/closure-compiler/compiler-20150505.tar.gz"
    URL_MD5 dea8e282c316316daeb39fcd5708d369
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
    CONFIGURE_COMMAND ""
    BUILD_COMMAND ""
    INSTALL_COMMAND ""
)
set(YUICOMP_JAR ${CMAKE_BINARY_DIR}/yuicompressor-2.4.8.jar)

#########################################################
## Documentation
#########################################################

add_custom_target(docs)

# For API documentation
find_package(Doxygen)

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

# For user documentation
find_package(mkdocs 0.14)

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
