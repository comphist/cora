function(cat IN_FILE OUT_FILE)
  file(READ ${IN_FILE} CONTENTS)
  file(APPEND ${OUT_FILE} "${CONTENTS}")
endfunction()

function(cat_multiple OUT_FILE) # args...
  file(WRITE ${OUT_FILE} "")
  foreach(IN_FILE ${ARGN})
    cat(${IN_FILE} ${OUT_FILE})
  endforeach()
endfunction()
