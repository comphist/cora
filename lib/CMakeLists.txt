set_current_web_dir()

set(LOCAL_PHP_SOURCES
  automaticAnnotation.php
  cfg.php
  commandHandler.php
  connect.php
  contentModel.php
  documentModel.php
  exporter.php
  install.php
  localeHandler.php
  requestHandler.php
  sessionHandler.php
  xmlHandler.php
  annotation/AutomaticAnnotator.php
  annotation/DualRFTaggerAnnotator.php
  annotation/Lemmatizer.php
  annotation/RFTaggerAnnotator.php
  connect/DocumentAccessor.php
  connect/DocumentCreator.php
  connect/DocumentReader.php
  connect/DocumentWriter.php
  connect/ProjectAccessor.php
  connect/SearchQuery.php
  connect/TagsetAccessor.php
  connect/TagsetCreator.php
)

configure_all_copyonly(${CORA_CURRENT_WEB_DIR} ${LOCAL_PHP_SOURCES})

add_sources(CORA_PHP_SOURCES ${LOCAL_PHP_SOURCES})
