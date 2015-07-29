file(RELATIVE_PATH CORA_CURRENT_REL_PATH "${CMAKE_SOURCE_DIR}" "${CMAKE_CURRENT_SOURCE_DIR}")
set(CORA_CURRENT_WEB_DIR "${CORA_WEB_DIR}/${CORA_CURRENT_REL_PATH}")

add_sources(
  automaticAnnotation.php
  commandHandler.php
  connect.php
  contentModel.php
  documentModel.php
  exporter.php
  requestHandler.php
  sessionHandler.php
  xmlHandler.php
)

add_sources(
  annotation/AutomaticAnnotator.php
  annotation/DualRFTaggerAnnotator.php
  annotation/Lemmatizer.php
  annotation/RFTaggerAnnotator.php
)

add_sources(
  connect/DocumentAccessor.php
  connect/DocumentCreator.php
  connect/DocumentReader.php
  connect/DocumentWriter.php
  connect/ProjectAccessor.php
  connect/SearchQuery.php
  connect/TagsetAccessor.php
  connect/TagsetCreator.php
)

configure_file(globals.php.in "${CORA_CURRENT_WEB_DIR}/globals.php" @ONLY)
