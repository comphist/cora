<?php

/** @file automaticAnnotation.php
 * Features for automatic, dynamic training and annotation of texts
 * within CorA.
 *
 * @author Marcel Bollmann
 * @date June 2013
 */

require_once( "documentModel.php" );

class AutomaticAnnotator {
  private $db; /**< A DBInterface object. */

  function __construct($db) {
    $this->db = $db;
  }


}

?>