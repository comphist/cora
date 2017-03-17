#########################################################
## Configure test files
#########################################################
file(RELATIVE_PATH CORA_TESTS_HREF_MOOTOOLS_CORE
  "${CMAKE_CURRENT_BINARY_DIR}"
  "${CMAKE_SOURCE_DIR}/src/${CORA_HREF_JS_MOOTOOLS_CORE}")
file(RELATIVE_PATH CORA_TESTS_HREF_MOOTOOLS_MORE
  "${CMAKE_CURRENT_BINARY_DIR}"
  "${CMAKE_SOURCE_DIR}/src/${CORA_HREF_JS_MOOTOOLS_MORE}")
file(RELATIVE_PATH CORA_TESTS_JS_DIR
  "${CMAKE_CURRENT_BINARY_DIR}"
  "${CMAKE_SOURCE_DIR}/src/gui/js")

set(FRONTEND_TESTS
  test_tagsets.html
  )
set(FRONTEND_TEST_ASSETS
  test_tagsets.js
  )

configure_all("${CMAKE_CURRENT_BINARY_DIR}" ${FRONTEND_TESTS})
configure_all_copyonly("${CMAKE_CURRENT_BINARY_DIR}" ${FRONTEND_TEST_ASSETS})

#########################################################
## Add Mocha tests
#########################################################
set(MOCHA_OPTIONS
)
set(MOCHA_EXECUTABLE "${NODE_MODULES_DIR}/.bin/mocha-phantomjs")

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
