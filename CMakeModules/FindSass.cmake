# - Find Sass
# This module finds if Sass is installed. This code sets the following
# variables:
#
#  SASS_EXECUTABLE       = full path to the Sass binary
#
#  Copyright (c) 2015 Marcel Bollmann <bollmann@linguistics.rub.de>
#    based on FindPHP5.cmake by Mathieu Malaterre, found at
#    <https://github.com/malaterre/GDCM/>
#

find_program(SASS_EXECUTABLE sass)

if(SASS_EXECUTABLE)
    execute_process(COMMAND ${SASS_EXECUTABLE} --version
      RESULT_VARIABLE res
      OUTPUT_VARIABLE var
      ERROR_VARIABLE var
      OUTPUT_STRIP_TRAILING_WHITESPACE
      ERROR_STRIP_TRAILING_WHITESPACE)
    if(res)
      if(${Sass_FIND_REQUIRED})
        message(FATAL_ERROR "Error executing sass --version")
      elseif(NOT Sass_FIND_QUIETLY)
        message(WARNING "Warning, could not run sass --version")
      endif()
    else()
      if(var MATCHES "Sass [0-9]+\\.[0-9]+\\.[0-9_.]+.*")
        string(REGEX REPLACE "Sass ([0-9]+\\.[0-9]+\\.[0-9_.]+).*"
               "\\1" SASS_VERSION_STRING "${var}")
      else()
        if(NOT Sass_FIND_QUIETLY)
          message(WARNING "regex not supported: {$var}.")
        endif()
      endif()

      string( REGEX REPLACE "([0-9]+).*" "\\1" SASS_VERSION_MAJOR "${SASS_VERSION_STRING}" )
      string( REGEX REPLACE "[0-9]+\\.([0-9]+).*" "\\1" SASS_VERSION_MINOR "${SASS_VERSION_STRING}" )
      string( REGEX REPLACE "[0-9]+\\.[0-9]+\\.([0-9]+).*" "\\1" SASS_VERSION_PATCH "${SASS_VERSION_STRING}" )
      set(SASS_VERSION ${SASS_VERSION_MAJOR}.${SASS_VERSION_MINOR}.${SASS_VERSION_PATCH})
    endif()
endif()

mark_as_advanced(
  SASS_EXECUTABLE
  )

include(FindPackageHandleStandardArgs)
find_package_handle_standard_args(Sass
  REQUIRED_VARS SASS_EXECUTABLE
  VERSION_VAR SASS_VERSION)
