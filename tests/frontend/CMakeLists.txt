#########################################################
## Ensure code coverage via Istanbul
#########################################################
set(CORA_TESTS_JS_DIR "./instrumented_js")
set(ISTANBUL_INSTRUMENT_OPTS
  instrument
  -i "${CMAKE_SOURCE_DIR}/src/gui/js"
  -o "${CMAKE_CURRENT_BINARY_DIR}/instrumented_js"
  -x "**/mbox/**"
  -x "**/mootools-*"
  --save-baseline
  )

set(ISTANBUL_DEPENDENCIES "")
foreach(_src ${CORA_TESTABLE_JS_SOURCES})
  list(APPEND ISTANBUL_DEPENDENCIES "${CMAKE_SOURCE_DIR}/src/gui/js/${_src}")
endforeach()

add_custom_command(OUTPUT "${CMAKE_CURRENT_BINARY_DIR}/instrumented_js"
  COMMAND "${NODE_MODULES_DIR}/.bin/istanbul"
  ARGS ${ISTANBUL_INSTRUMENT_OPTS}
  COMMENT "Preparing JavaScript files for code coverage report"
  DEPENDS ${ISTANBUL_DEPENDENCIES}
  )
add_custom_target(istanbul DEPENDS "${CMAKE_CURRENT_BINARY_DIR}/instrumented_js")

#########################################################
## Configure test files
#########################################################
file(RELATIVE_PATH CORA_TESTS_HREF_MOOTOOLS_CORE
  "${CMAKE_CURRENT_BINARY_DIR}"
  "${CMAKE_SOURCE_DIR}/src/${CORA_HREF_JS_MOOTOOLS_CORE}")
file(RELATIVE_PATH CORA_TESTS_HREF_MOOTOOLS_MORE
  "${CMAKE_CURRENT_BINARY_DIR}"
  "${CMAKE_SOURCE_DIR}/src/${CORA_HREF_JS_MOOTOOLS_MORE}")

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
  -k mocha-phantomjs-istanbul
  -R spec
)
set(MOCHA_EXECUTABLE "${NODE_MODULES_DIR}/.bin/mocha-phantomjs")

foreach(TEST ${FRONTEND_TESTS})
  add_test(mocha-${TEST}
    ${MOCHA_EXECUTABLE} ${MOCHA_OPTIONS}
    ${TEST}
    )
endforeach()
add_test(
  NAME mocha-istanbul-coverage-report
  COMMAND "${NODE_MODULES_DIR}/.bin/istanbul" report lcovonly
  WORKING_DIRECTORY "${CMAKE_CURRENT_BINARY_DIR}"
  )

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

add_dependencies(check-frontend istanbul)
