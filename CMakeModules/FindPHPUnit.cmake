# - Find PHPUnit
# This module finds if PHPUnit is installed. This code sets the following
# variables:
#
#  PHPUNIT_EXECUTABLE       = full path to the PHPUnit binary
#
#  Copyright (c) 2015 Marcel Bollmann <bollmann@linguistics.rub.de>
#    based on FindPHP5.cmake by Mathieu Malaterre, found at
#    <https://github.com/malaterre/GDCM/>
#

find_program(PHPUNIT_EXECUTABLE phpunit)

if(PHPUNIT_EXECUTABLE)
    execute_process(COMMAND ${PHPUNIT_EXECUTABLE} --version
      RESULT_VARIABLE res
      OUTPUT_VARIABLE var
      ERROR_VARIABLE var
      OUTPUT_STRIP_TRAILING_WHITESPACE
      ERROR_STRIP_TRAILING_WHITESPACE)
    if(res)
      if(${PHPUnit_FIND_REQUIRED})
        message(FATAL_ERROR "Error executing PHPUnit --version")
      elseif(NOT PHPUnit_FIND_QUIETLY)
        message(WARNING "Warning, could not run PHPUnit --version")
      endif()
    else()
      if(var MATCHES ".*PHPUnit [0-9]+\\.[0-9]+\\.[0-9_.]+.*")
        string(REGEX REPLACE ".*PHPUnit ([0-9]+\\.[0-9]+\\.[0-9_.]+).*"
               "\\1" PHPUNIT_VERSION_STRING "${var}")
      else()
        if(NOT PHPUnit_FIND_QUIETLY)
          message(WARNING "regex not supported: {$var}.")
        endif()
      endif()

      string( REGEX REPLACE "([0-9]+).*" "\\1" PHPUNIT_VERSION_MAJOR "${PHPUNIT_VERSION_STRING}" )
      string( REGEX REPLACE "[0-9]+\\.([0-9]+).*" "\\1" PHPUNIT_VERSION_MINOR "${PHPUNIT_VERSION_STRING}" )
      string( REGEX REPLACE "[0-9]+\\.[0-9]+\\.([0-9]+).*" "\\1" PHPUNIT_VERSION_PATCH "${PHPUNIT_VERSION_STRING}" )
      set(PHPUNIT_VERSION ${PHPUNIT_VERSION_MAJOR}.${PHPUNIT_VERSION_MINOR}.${PHPUNIT_VERSION_PATCH})
    endif()
endif()

mark_as_advanced(
  PHPUNIT_EXECUTABLE
  )

include(FindPackageHandleStandardArgs)
find_package_handle_standard_args(PHPUnit
  REQUIRED_VARS PHPUNIT_EXECUTABLE
  VERSION_VAR PHPUNIT_VERSION)
