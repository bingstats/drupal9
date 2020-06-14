<?php

namespace Drupal\layout_bg_bricks\Plugin\Layout;

use Drupal\Core\Layout\LayoutDefault;
use Drupal\layout_bg\LayoutBgTrait;

/**
 * Adding background region to twocol_bricks.
 */
class LayoutBgBricks extends LayoutDefault {

  use LayoutBgTrait;

  /**
   * The template to use to render the non-bg content.
   *
   * We are (kind of) extending the twocol_bricks layout defined in
   * layout_discovery. This is the template set for that layout
   * in layout_discovery.layouts.yml.
   *
   * @var string
   */
  protected $baseLayoutTemplate = 'layout--twocol-bricks.html.twig';

}
