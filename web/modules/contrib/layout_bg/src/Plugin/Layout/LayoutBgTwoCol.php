<?php

namespace Drupal\layout_bg\Plugin\Layout;

use Drupal\layout_bg\LayoutBgTrait;
use Drupal\layout_builder\Plugin\Layout\TwoColumnLayout;

/**
 * Layout with two columnz and bg.
 */
class LayoutBgTwoCol extends TwoColumnLayout {
  use LayoutBgTrait;

  /**
   * The template to use to render the non-bg content.
   *
   * Most likely it's the template used by the base class of this plugin.
   *
   * @var string
   */
  protected $baseLayoutTemplate = 'layout--twocol-section.html.twig';

  /**
   * {@inheritdoc}
   */
  public function build(array $regions) {
    $build = parent::build($regions);
    $build = $this->processBackground($build);
    $build['#base_layout_template'] = $this->baseLayoutTemplate;

    // This next block is the addition so that classes are correct.
    $build['#attributes']['class'] = [
      'layout',
      'layout--twocol-section',
      'layout--twocol-section--' . $this->configuration['column_widths'],
    ];
    return $build;
  }

}
