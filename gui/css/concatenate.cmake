# If WITH_MINIFY_CSS is on, this script is executed for the web-minify-css
# target to concatenate all files before they are minified.  This cannot be done
# during the configure step because it depends on the web-scss target to
# preprocess .scss files.
function(cat IN_FILE OUT_FILE)
  message(STATUS "Appending ${IN_FILE} to ${OUT_FILE}")
  file(READ ${IN_FILE} CONTENTS)
  file(APPEND ${OUT_FILE} "${CONTENTS}")
endfunction()

if(NOT OUTFILE)
  message(ERROR "Need to define output file in OUTFILE")
endif()
if(NOT INFILES)
  message(ERROR "Need to define input files in INFILES")
endif()

file(WRITE ${OUTFILE} "")

separate_arguments(INFILES)
foreach(INFILE ${INFILES})
  cat(${INFILE} ${OUTFILE})
endforeach()
