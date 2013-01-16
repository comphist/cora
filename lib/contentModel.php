<?php

/** @file contentModel.php
 * Define classes related to site content.
 *
 * @author Marcel Bollmann
 * @date January 2012
 */

/** The main menu of the application.
 *
 * This class holds a list of items which should appear in the menu
 * bar of the application. It also connects each of these items with a
 * PHP source file.
 */
class Menu {
  private $menu_items;   /**< Array of all menu items. */
  private $menu_files;   /**< Array linking menu items to source files. */
  private $menu_js_files;
  private $default_item; /**< Name of the default menu item. */
  private $menu_captions;
  private $menu_tooltips;

  /** Create a new Menu. */
  function __construct() {
    $this->menu_items = array();
    $this->menu_files = array();
    $this->menu_js_files = array();
    $this->menu_captions = array();
    $this->menu_tooltips = array();
  }

  /** Add an item to the menu.
   *
   * @param string $id   Name of the menu item
   * @param string $file Filename of the corresponding PHP source
   */
  public function addMenuItem( $id, $file, $js="", $caption="", $tooltip="" ) {
    if ( empty($this->menu_items) ) {
      $this->default_item = $id;
    }
    $this->menu_items[] = $id;
    $this->menu_files[$id] = $file;
    $this->menu_js_files[$id] = $js;
    $this->menu_captions[$id] = $caption;
    $this->menu_tooltips[$id] = $tooltip;
  }

  /** Get a list of all menu items. */
  public function getItems() {
    return $this->menu_items;
  }

  /** Get the filename for a given menu item.
   *
   * @param string $item Name of the menu item
   *
   * @return A @em string with the filename of the PHP source file
   * corresponding to the menu item.
   */
  public function getItemFile( $item ) {
    return $this->menu_files[$item];
  }

  /** Get the JS filepath for a given menu item.
   *
   * @param string $item Name of the menu item
   *
   * @return A @em string with the filename of the JS source file
   * corresponding to the menu item.
   */
  public function getItemJSFile( $item ) {
    return $this->menu_js_files[$item];
  }


  /** Get the caption for a given menu item. */
  public function getItemCaption( $item ) {
    return $this->menu_captions[$item];
  }

  /** Get the tooltip for a given menu item. */
  public function getItemTooltip( $item ) {
    return $this->menu_tooltips[$item];
  }

  /** Set the menu item selected by default. */
  public function setDefaultItem( $item ) {
    if (array_key_exists($item, $this->menu_items)) {
      $this->default_item = $item;
    }
  }

  /** Get the menu item selected by default. */
  public function getDefaultItem() {
    return $this->default_item;
  }

}


?>