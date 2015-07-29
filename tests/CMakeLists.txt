#########################################################
## Set test-specific cache variables
#########################################################

set(CORA_TESTDB_NAME "${CORA_DB_NAME}_test"
    CACHE STRING "Name of the CorA unit test database")
set(CORA_TESTDB_ROOT "${CORA_DB_USER}_test"
    CACHE STRING "Name of the database user used by CorA unit tests")
set(CORA_TESTDB_ROOTPW "mistrustthecode"
    CACHE STRING "Password of the database user used by CorA unit tests")

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

configure_all("${CMAKE_CURRENT_BINARY_DIR}" ${PHP_TESTS} phpunit.xml DB_fixture.php)
file(COPY data DESTINATION .)

#########################################################
## Add PHPUnit tests
#########################################################

foreach(TEST ${PHP_TESTS})
  add_test(phpunit-${TEST}
    ${PHPUNIT_EXECUTABLE} ${PHPUNIT_OPTIONS} ${TEST}
    )
endforeach()
