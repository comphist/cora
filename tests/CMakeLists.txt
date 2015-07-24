# Set options
set(PHPUNIT_OPTIONS
  --configuration phpunit.xml
)
if(DEBUG_MODE)
  list(APPEND PHPUNIT_OPTIONS --strict --verbose)
endif()

set(PHP_TESTS
  AutomaticAnnotator_test.php
  CoraDocument_test.php
  DBInterface_test.php
  Exporter_test.php
  Menu_test.php
  TagsetAccessor_test.php
  XMLHandler_test.php
)
#if(DEBUG_MODE)
#  list(APPEND PHP_TESTS DBInterface_Expensive_test.php)
#endif()

configure_all("${CMAKE_CURRENT_BINARY_DIR}" ${PHP_TESTS})
configure_all_copyonly("${CMAKE_CURRENT_BINARY_DIR}"
  DB_fixture.php
  phpunit.xml
)
file(COPY data DESTINATION .)

foreach(TEST ${PHP_TESTS})
  add_test(phpunit-${TEST}
    ${PHPUNIT_EXECUTABLE} ${PHPUNIT_OPTIONS} ${TEST}
    )
endforeach()
