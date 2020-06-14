<?php

namespace Drupal\layout_bg\Plugin\Layout;

use Drupal\Core\Layout\LayoutDefault;
use Drupal\layout_bg\LayoutBgTrait;

/**
 * Layout with single column and bg.
 */
class LayoutBgOneCol extends LayoutDefault {
  use LayoutBgTrait;

  /**
   * The template to use to render the non-bg content.
   *
   * Most likely it's the template used by the base class of this plugin.
   *
   * @var string
   */
  protected $baseLayoutTemplate = 'layout--onecol.html.twig';

}
