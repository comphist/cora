set_current_web_dir()

configure_all_copyonly(${CORA_CURRENT_WEB_DIR}
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

configure_all_copyonly(${CORA_CURRENT_WEB_DIR}
  annotation/AutomaticAnnotator.php
  annotation/DualRFTaggerAnnotator.php
  annotation/Lemmatizer.php
  annotation/RFTaggerAnnotator.php
)

configure_all_copyonly(${CORA_CURRENT_WEB_DIR}
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
