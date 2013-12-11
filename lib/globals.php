<?php

/** The database server to connect to. */
define( "DB_SERVER",   "localhost" );
/** The username for database login. */
define( "DB_USER",     "cora"      );
/** The password for database login. */
define( "DB_PASSWORD", "trustthetext" );
/** The name of the database. */
define( "MAIN_DB",     "cora"      );

/* Globals */
define( "TITLE",              "CorA"	                  );
define( "VERSION",            "1.0"			  );
define( "LONGTITLE",          "Corpus Annotator"          );
define( "DESCRIPTION",        "A corpus annotation tool." );
define( "KEYWORDS",           "annotation,corpus,POS"     );
define( "DEFAULT_LANGUAGE",   "de"                        );

// HACK
static $TRANS_IMPORT_AUTOTAG_OPTIONS = array('8' => '-t -p /usr/local/share/rftagger/lib/bonn.par',
					     '20' => '-t -p /usr/local/share/rftagger/lib/bonn-hits.par');

?>