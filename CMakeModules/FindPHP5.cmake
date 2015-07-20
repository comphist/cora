# - Find PHP5
# This module finds if PHP5 is installed and determines where the include files
# and libraries are. It also determines what the name of the library is. This
# code sets the following variables:
#
#  PHP5_INCLUDE_PATH       = path to where php.h can be found
#  PHP5_EXECUTABLE         = full path to the php5 binary
#
#  Copyright (c) 2006-2011 Mathieu Malaterre <mathieu.malaterre@gmail.com>
#                     2015 Marcel Bollmann <bollmann@linguistics.rub.de>
#  (Source: https://github.com/malaterre/GDCM/)
#
#  Redistribution and use is allowed according to the terms of the New
#  BSD license.
#
#  Redistribution and use in source and binary forms, with or without
#  modification, are permitted provided that the following conditions
#  are met:
#
#  1. Redistributions of source code must retain the copyright
#     notice, this list of conditions and the following disclaimer.
#  2. Redistributions in binary form must reproduce the copyright
#     notice, this list of conditions and the following disclaimer in the
#     documentation and/or other materials provided with the distribution.
#  3. The name of the author may not be used to endorse or promote products
#     derived from this software without specific prior written permission.
#
#  THIS SOFTWARE IS PROVIDED BY THE AUTHOR ``AS IS'' AND ANY EXPRESS OR
#  IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES
#  OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED.
#  IN NO EVENT SHALL THE AUTHOR BE LIABLE FOR ANY DIRECT, INDIRECT,
#  INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT
#  NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
#  DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
#  THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
#  (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF
#  THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.


set(PHP5_POSSIBLE_INCLUDE_PATHS
  /usr/include/php5
  /usr/local/include/php5
  /usr/include/php
  /usr/local/include/php
  /usr/local/apache/php
  )

set(PHP5_POSSIBLE_LIB_PATHS
  /usr/lib
  )

find_path(PHP5_FOUND_INCLUDE_PATH main/php.h
  ${PHP5_POSSIBLE_INCLUDE_PATHS})

if(PHP5_FOUND_INCLUDE_PATH)
  set(php5_paths "${PHP5_POSSIBLE_INCLUDE_PATHS}")
  foreach(php5_path Zend main TSRM)
    set(php5_paths ${php5_paths} "${PHP5_FOUND_INCLUDE_PATH}/${php5_path}")
  endforeach()
  set(PHP5_INCLUDE_PATH "${php5_paths}" CACHE INTERNAL "PHP5 include paths")
endif()

find_program(PHP5_EXECUTABLE NAMES php5 php )

if(PHP5_EXECUTABLE)
    execute_process(COMMAND ${PHP5_EXECUTABLE} --version
      RESULT_VARIABLE res
      OUTPUT_VARIABLE var
      ERROR_VARIABLE var
      OUTPUT_STRIP_TRAILING_WHITESPACE
      ERROR_STRIP_TRAILING_WHITESPACE)
    if(res)
      if(${PHP5_FIND_REQUIRED})
        message( FATAL_ERROR "Error executing php --version" )
      else()
        message( STATUS "Warning, could not run php --version")
      endif()
    else()
      if(var MATCHES "PHP [0-9]+\\.[0-9]+\\.[0-9_.]+.*")
        string(REGEX REPLACE "PHP ([0-9]+\\.[0-9]+\\.[0-9_.]+).*"
               "\\1" PHP5_VERSION_STRING "${var}")
      else()
        if(NOT PHP5_FAIL_QUIETLY)
          message(WARNING "regex not supported: {$var}.")
        endif()
      endif()

      string( REGEX REPLACE "([0-9]+).*" "\\1" PHP5_VERSION_MAJOR "${PHP5_VERSION_STRING}" )
      string( REGEX REPLACE "[0-9]+\\.([0-9]+).*" "\\1" PHP5_VERSION_MINOR "${PHP5_VERSION_STRING}" )
      string( REGEX REPLACE "[0-9]+\\.[0-9]+\\.([0-9]+).*" "\\1" PHP5_VERSION_PATCH "${PHP5_VERSION_STRING}" )
      set(PHP5_VERSION ${PHP5_VERSION_MAJOR}.${PHP5_VERSION_MINOR}.${PHP5_VERSION_PATCH})
    endif()
endif()

mark_as_advanced(
  PHP5_EXECUTABLE
  PHP5_FOUND_INCLUDE_PATH
  )

include(FindPackageHandleStandardArgs)
if(PHP5_FIND_COMPONENTS)
  foreach(component ${PHP5_FIND_COMPONENTS})
    if(component STREQUAL "Runtime")
      find_package_handle_standard_args(PHP5
        REQUIRED_VARS PHP5_EXECUTABLE
        VERSION_VAR PHP5_VERSION)
    elseif(component STREQUAL "Development")
      find_package_handle_standard_args(PHP5
        REQUIRED_VARS PHP5_EXECUTABLE PHP5_INCLUDE_PATH
        VERSION_VAR PHP5_VERSION)
    else()
      message(FATAL_ERROR "Comp: ${component} is not handled")
    endif()
    set(PHP5_${component}_FOUND TRUE)
  endforeach()
else()
  find_package_handle_standard_args(PHP5
    REQUIRED_VARS PHP5_EXECUTABLE PHP5_INCLUDE_PATH
    VERSION_VAR PHP5_VERSION)
endif()
