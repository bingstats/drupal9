<?php

namespace Drupal\layout_builder_kit;

use Drupal\Core\Link;
use Drupal\Core\Render\Markup;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Class LinkProvider.
 *
 * @package Drupal\layout_builder_kit
 */
class LinkProvider implements LinkProviderInterface {

  use StringTranslationTrait;

  /**
   * Get a Link object from a given Url object.
   *
   * @return \Drupal\Core\Link
   *   Return a link object.
   */
  public function renderLink($url, $html, $text) {

    $link = Link::fromTextAndUrl(
        Markup::create($html . $this->t('@text', ['@text' => $text])),
        $url
    );

    return $link;
  }

}
