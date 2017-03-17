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

find_program(NPM_EXECUTABLE npm)
find_program(NODE_EXECUTABLE node)

if(NODE_EXECUTABLE)
    execute_process(COMMAND ${NODE_EXECUTABLE} --version
      RESULT_VARIABLE res
      OUTPUT_VARIABLE var
      ERROR_VARIABLE var
      OUTPUT_STRIP_TRAILING_WHITESPACE
      ERROR_STRIP_TRAILING_WHITESPACE)
    if(res)
      if(${NodeJS_FIND_REQUIRED})
        message(FATAL_ERROR "Error executing node --version")
      elseif(NOT NodeJS_FIND_QUIETLY)
        message(WARNING "Warning, could not run node --version")
      endif()
    else()
      if(var MATCHES "v[0-9]+\\.[0-9]+\\.[0-9]+")
        string( REGEX REPLACE "v([0-9]+).*" "\\1" NODE_VERSION_MAJOR "${var}" )
        string( REGEX REPLACE "v[0-9]+\\.([0-9]+).*" "\\1" NODE_VERSION_MINOR "${var}" )
        string( REGEX REPLACE "v[0-9]+\\.[0-9]+\\.([0-9]+).*" "\\1" NODE_VERSION_PATCH "${var}" )
        set(NODE_VERSION ${NODE_VERSION_MAJOR}.${NODE_VERSION_MINOR}.${NODE_VERSION_PATCH})
      elseif(NOT NodeJS_FIND_QUIETLY)
        message(WARNING "regex not supported: {$var}")
        set(NODE_VERSION 0)
      endif()
    endif()
endif()

mark_as_advanced(
  NODE_EXECUTABLE
  NPM_EXECUTABLE
  )

if(NodeJS_FIND_COMPONENTS)
  if(NOT NPM_EXECUTABLE)
    message(FATAL_ERROR "Cannot install required components: npm not found")
  else()
    foreach(component ${NodeJS_FIND_COMPONENTS})
      add_custom_command(OUTPUT "${CMAKE_BINARY_DIR}/node_modules/${component}"
        COMMAND ${NPM_EXECUTABLE} install ${component}
        WORKING_DIRECTORY ${CMAKE_BINARY_DIR}
        COMMENT "Installing NodeJS component: ${component}"
        )
      add_custom_target(npm_${component} ALL
        DEPENDS "${CMAKE_BINARY_DIR}/node_modules/${component}")
    endif()
  endforeach()
endif()

include(FindPackageHandleStandardArgs)
find_package_handle_standard_args(NodeJS
  REQUIRED_VARS NODE_EXECUTABLE NPM_EXECUTABLE
  VERSION_VAR NODE_VERSION)
