# This function sets the variable VARNAME (as a global cache variable with
# description DESC) to a string containing PATH with an appended cache buster
# string, i.e., a non-functional parameter that should cause the browser to
# re-request the file iff the contents of HASHFILE have been changed.  Multiple
# files can be given.
#
# The reason for PATH and HASHFILE being separate is as follows: PATH is what
# the web browser should request, and can potentially refer to a file that
# hasn't been generated yet at configure time; in these cases, HASHFILE can be
# used to point to one or more files that actually serve as the basis for
# generation of PATH.
#
# HASHFILE(s) should be relative to CMAKE_CURRENT_SOURCE_DIR.

function(set_with_cache_buster VARNAME DESC PATH HASHFILE)
  file(MD5 "${CMAKE_CURRENT_SOURCE_DIR}/${HASHFILE}" HASH)
  if(ARGN)
    set(HASHES "${HASH}")
    foreach(_HASHFILE ${ARGN})
      file(MD5 "${CMAKE_CURRENT_SOURCE_DIR}/${_HASHFILE}" CURRENT_HASH)
      list(APPEND HASHES "${CURRENT_HASH}")
    endforeach()
    string(MD5 HASH "${HASHES}")
  endif()
  set(${VARNAME} "${PATH}?${HASH}" CACHE INTERNAL "${DESC}" FORCE)
endfunction()

function(set_list_with_cache_buster VARNAME DESC RELPATH)
  set(SRCS_WITH_CB "")
  foreach(_HASHFILE ${ARGN})
    file(MD5 "${CMAKE_CURRENT_SOURCE_DIR}/${_HASHFILE}" CURRENT_HASH)
    list(APPEND SRCS_WITH_CB "${RELPATH}/${_HASHFILE}?${CURRENT_HASH}")
  endforeach()
  string(REPLACE ";" "','" SRCS "${SRCS_WITH_CB}")
  set(${VARNAME} "${SRCS}" CACHE INTERNAL "${DESC}" FORCE)
endfunction()
