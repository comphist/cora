# - Find NaturalDocs
# This module finds if NaturalDocs is installed, and adds it as an external
# dependency if it isn't. This code sets the following variables:
#
#  NATURALDOCS_EXECUTABLE       = full path to the naturaldocs binary
#
#  Copyright (c) 2015 Marcel Bollmann <bollmann@linguistics.rub.de>
#    based on FindPHP5.cmake by Mathieu Malaterre, found at
#    <https://github.com/malaterre/GDCM/>
#

find_program(NATURALDOCS_EXECUTABLE naturaldocs)

if(NOT NATURALDOCS_EXECUTABLE)
  include (ExternalProject)

  message(STATUS "Couldn't find NaturalDocs, trying to download it automatically")
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
  set(NATURALDOCS_PL ${CMAKE_BINARY_DIR}/NaturalDocs-prefix/src/NaturalDocs/NaturalDocs)
  set(NATURALDOCS_EXECUTABLE "${NATURALDOCS_PL}")
endif()

mark_as_advanced(
  NATURALDOCS_EXECUTABLE
  )

include(FindPackageHandleStandardArgs)
find_package_handle_standard_args(NaturalDocs
  REQUIRED_VARS NATURALDOCS_EXECUTABLE)
