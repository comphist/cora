#########################################################
## Configure Mocha
#########################################################
set(MOCHA_OPTIONS
  --sort
  --require global.js
)
#if(DEBUG_MODE)
#  list(APPEND MOCHA_OPTIONS --debug)
#endif()

set(FRONTEND_TESTS
  test.js
  )
set(FRONTEND_TEST_ASSETS
  global.js
  )

configure_all_copyonly("${CMAKE_CURRENT_BINARY_DIR}" ${FRONTEND_TESTS} ${FRONTEND_TEST_ASSETS})

#########################################################
## Add Mocha tests
#########################################################

# TODO: this needs to be replaced by proper package finding and installation
set(MOCHA_EXECUTABLE "/usr/bin/mocha")

foreach(TEST ${FRONTEND_TESTS})
  add_test(mocha-${TEST}
    ${MOCHA_EXECUTABLE} ${MOCHA_OPTIONS}
    ${TEST}
    )
endforeach()

#########################################################
## Add custom target for frontend-only tests
#########################################################

if (CMAKE_CONFIGURATION_TYPES)
  add_custom_target(check-frontend COMMAND ${CMAKE_CTEST_COMMAND}
      -R "^mocha"
      --force-new-ctest-process --output-on-failure
      --build-config "$<CONFIGURATION>")
else()
  add_custom_target(check-frontend COMMAND ${CMAKE_CTEST_COMMAND}
      -R "^mocha"
      --force-new-ctest-process --output-on-failure)
endif()
