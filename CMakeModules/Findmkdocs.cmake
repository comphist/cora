# - Find mkdocs
# This module finds if mkdocs is installed. This code sets the following
# variables:
#
#  MKDOCS_EXECUTABLE       = full path to the mkdocs binary
#
#  Copyright (c) 2015 Marcel Bollmann <bollmann@linguistics.rub.de>
#    based on FindPHP5.cmake by Mathieu Malaterre, found at
#    <https://github.com/malaterre/GDCM/>
#

find_program(MKDOCS_EXECUTABLE mkdocs)

if(MKDOCS_EXECUTABLE)
    execute_process(COMMAND ${MKDOCS_EXECUTABLE} --version
      RESULT_VARIABLE res
      OUTPUT_VARIABLE var
      ERROR_VARIABLE var
      OUTPUT_STRIP_TRAILING_WHITESPACE
      ERROR_STRIP_TRAILING_WHITESPACE)
    if(res)
      if(${mkdocs_FIND_REQUIRED})
        message(FATAL_ERROR "Error executing mkdocs --version")
      elseif(NOT mkdocs_FIND_QUIETLY)
        message(WARNING "Warning, could not run mkdocs --version")
      endif()
    else()
      if(var MATCHES "mkdocs, version [0-9]+\\.[0-9]+\\.[0-9_.]+.*")
        string(REGEX REPLACE "mkdocs, version ([0-9]+\\.[0-9]+\\.[0-9_.]+).*"
               "\\1" MKDOCS_VERSION_STRING "${var}")
      else()
        if(NOT mkdocs_FIND_QUIETLY)
          message(WARNING "regex not supported: {$var}.")
        endif()
      endif()

      string( REGEX REPLACE "([0-9]+).*" "\\1" MKDOCS_VERSION_MAJOR "${MKDOCS_VERSION_STRING}" )
      string( REGEX REPLACE "[0-9]+\\.([0-9]+).*" "\\1" MKDOCS_VERSION_MINOR "${MKDOCS_VERSION_STRING}" )
      string( REGEX REPLACE "[0-9]+\\.[0-9]+\\.([0-9]+).*" "\\1" MKDOCS_VERSION_PATCH "${MKDOCS_VERSION_STRING}" )
      set(MKDOCS_VERSION ${MKDOCS_VERSION_MAJOR}.${MKDOCS_VERSION_MINOR}.${MKDOCS_VERSION_PATCH})
    endif()
endif()

mark_as_advanced(
  MKDOCS_EXECUTABLE
  )

include(FindPackageHandleStandardArgs)
find_package_handle_standard_args(mkdocs
  REQUIRED_VARS MKDOCS_EXECUTABLE
  VERSION_VAR MKDOCS_VERSION)
