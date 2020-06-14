<?php

namespace Drupal\layout_builder_kit;

/**
 * Interface LinkProviderInterface.
 *
 * @package Drupal\layout_builder_kit
 */
interface LinkProviderInterface {

  /**
   * Render Link object from a given Url object.
   *
   * @return object
   *   Return a link object.
   */
  public function renderLink($url, $html, $text);

}
