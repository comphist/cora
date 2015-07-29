# - Find MySQL
# This module finds if MySQL is installed (executables only).
# This code sets the following variables:
#
#  MYSQL_EXECUTABLE       = full path to the MySQL client program
#
#  Copyright (c) 2015 Marcel Bollmann <bollmann@linguistics.rub.de>
#    based on FindPHP5.cmake by Mathieu Malaterre, found at
#    <https://github.com/malaterre/GDCM/>
#

find_program(MYSQL_EXECUTABLE mysql)

if(MYSQL_EXECUTABLE)
    execute_process(COMMAND ${MYSQL_EXECUTABLE} --version
      RESULT_VARIABLE res
      OUTPUT_VARIABLE var
      ERROR_VARIABLE var
      OUTPUT_STRIP_TRAILING_WHITESPACE
      ERROR_STRIP_TRAILING_WHITESPACE)
    if(res)
      if(${MySQL_FIND_REQUIRED})
        message(FATAL_ERROR "Error executing mysql --version")
      elseif(NOT MySQL_FIND_QUIETLY)
        message(WARNING "Warning, could not run mysql --version")
      endif()
    else()
      if(var MATCHES "${MYSQL_EXECUTABLE} .*Distrib [0-9]+\\.[0-9]+\\.[0-9]+.*")
        string(REGEX REPLACE "${MYSQL_EXECUTABLE} .*Distrib ([0-9]+\\.[0-9]+\\.[0-9]+).*"
               "\\1" MYSQL_VERSION_STRING "${var}")
      else()
        if(NOT MySQL_FIND_QUIETLY)
          message(WARNING "regex not supported: {$var}.")
        endif()
      endif()

      string( REGEX REPLACE "([0-9]+).*" "\\1" MYSQL_VERSION_MAJOR "${MYSQL_VERSION_STRING}" )
      string( REGEX REPLACE "[0-9]+\\.([0-9]+).*" "\\1" MYSQL_VERSION_MINOR "${MYSQL_VERSION_STRING}" )
      string( REGEX REPLACE "[0-9]+\\.[0-9]+\\.([0-9]+).*" "\\1" MYSQL_VERSION_PATCH "${MYSQL_VERSION_STRING}" )
      set(MYSQL_VERSION ${MYSQL_VERSION_MAJOR}.${MYSQL_VERSION_MINOR}.${MYSQL_VERSION_PATCH})
    endif()
endif()

mark_as_advanced(
  MYSQL_EXECUTABLE
  )

include(FindPackageHandleStandardArgs)
find_package_handle_standard_args(MySQL
  REQUIRED_VARS MYSQL_EXECUTABLE
  VERSION_VAR MYSQL_VERSION)
