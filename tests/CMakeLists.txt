#########################################################
## Set test-specific cache variables
#########################################################

set(CORA_TESTDB_NAME "${CORA_DB_NAME}_test"
    CACHE INTERNAL "Name of the CorA unit test database" FORCE)
set(CORA_TESTDB_ROOT "${CORA_DB_USER}_test"
    CACHE INTERNAL "Name of the database user used by CorA unit tests" FORCE)
set(CORA_TESTDB_ROOTPW "${CORA_DB_PASSWORD}_test"
    CACHE INTERNAL "Password of the database user used by CorA unit tests" FORCE)

#########################################################
## Configure PHPUnit
#########################################################
set(PHPUNIT_OPTIONS
  --configuration phpunit.xml
)
if(DEBUG_MODE)
  list(APPEND PHPUNIT_OPTIONS --strict --verbose)
endif()

set(PHP_TESTS
  AutomaticAnnotator_test.php
  Cfg_test.php
  CoraDocument_test.php
  DBInterface_test.php
  DocumentAccessor_test.php
  DocumentCreator_test.php
  Exporter_test.php
  Locale_test.php
  Menu_test.php
  TagsetAccessor_test.php
  XMLHandler_test.php
)
if(WITH_EXPENSIVE_TESTS)
  list(APPEND PHP_TESTS DBInterface_Expensive_test.php)
endif()

configure_file(phpunit.xml.in "${CMAKE_CURRENT_BINARY_DIR}/phpunit.xml" @ONLY)
configure_all_copyonly("${CMAKE_CURRENT_BINARY_DIR}" ${PHP_TESTS} DB_fixture.php DocumentAccessor_mocks.php LocaleHandler_mock.php)
file(COPY data DESTINATION .)

#########################################################
## Add PHPUnit tests
#########################################################

foreach(TEST ${PHP_TESTS})
  add_test(phpunit-${TEST}
    ${PHPUNIT_EXECUTABLE} ${PHPUNIT_OPTIONS} ${TEST}
    )
endforeach()
