<?php

namespace Drupal\layout_bg;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Template\Attribute;

/**
 * Layout with background image trait.
 */
trait LayoutBgTrait {

  /**
   * {@inheritdoc}
   */
  public function build(array $regions) {
    $build = parent::build($regions);
    $build = $this->processBackground($build);
    if (isset($this->baseLayoutTemplate)) {
      $build['#base_layout_template'] = $this->baseLayoutTemplate;
    }
    return $build;
  }

  /**
   * Ready the background region for rendering.
   *
   * This function also processes some style configuration.
   *
   * @param array $build
   *   The build array.
   *
   * @return array
   *   The build array with background region processed.
   */
  protected function processBackground(array $build) {
    // Process background region such that we dump everything after
    // the first block with content.
    // Keep the preceding blocks for their cache data.
    if (isset($build['background']) && !empty($build['background'])) {
      $bg_items = 0;
      foreach ($build['background'] as &$block) {
        $bg_items++;
        if (isset($block['content'])) {
          // Don't display label because it ruins layout.
          $block['#configuration']['label_display'] = '0';
          break;
        }
      }
      $build['processed_background'] = array_slice($build['background'], 0, $bg_items, TRUE);
    }
    $build['background']['#attributes']['style'][] = "background-color: {$this->configuration['background_color']};";
    $build['background']['#attributes']['class'][] = $this->configuration['static_image'] ? 'static-image' : 'absolute-image';

    $build['content_attributes']['#attributes']['class'] = [];
    if ($this->configuration['static_image'] && $this->configuration['center_content']) {
      $build['content_attributes']['#attributes']['class'][] = 'center-content';
    }

    if ($this->configuration['set_text_color']) {
      $build['content_attributes']['#attributes']['style'] = ["color: {$this->configuration['text_color']};"];
      $build['content_attributes']['#attributes']['class'][] = 'set-text-color';
    }

    if ($this->configuration['add_overlay']) {
      $styles = new Attribute();
      $styles->setAttribute('style', ["background-color: {$this->configuration['overlay_color']};", "opacity: {$this->configuration['overlay_opacity']};"]);
      $build['overlay_attributes']['#attributes'] = [
        'style' => [
          "background-color: {$this->configuration['overlay_color']};",
          "opacity: {$this->configuration['overlay_opacity']};",
        ],
      ];
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $configuration = parent::defaultConfiguration();

    return $configuration + [
      'background_color' => '#AAAAAA',
      'static_image' => TRUE,
      'center_content' => FALSE,
      'add_overlay' => TRUE,
      'overlay_color' => '#000000',
      'overlay_opacity' => '0.3',
      'set_text_color' => TRUE,
      'text_color' => '#FFFFFF',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['static_image'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use Static Image'),
      '#default_value' => $this->configuration['static_image'],
      '#description' => $this->t('If checked, the background image (presumably it is an image, at least!) will be positioned statically, meaning it takes up space in the DOM. If unchecked, the background is positioned absolutely and the image does not take up space. Either way, the magic of object-fit makes sure the image is centered and fully covers the background.'),
    ];
    $form['center_content'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Center Content'),
      '#default_value' => $this->configuration['center_content'],
      '#description' => $this->t('If checked, the layout will be centered horizontally and vertically over the background. This makes sense mostly for a one-column layout over a static background image.'),
      '#states' => [
        'visible' => [
          ':input[name="layout_settings[static_image]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['background_color'] = [
      '#type' => 'color',
      '#title' => $this->t('Background Color'),
      '#default_value' => $this->configuration['background_color'],
      '#description' => $this->t('Choose a background color in case the image has not yet loaded or does not load. This is especially important if you do not configure an overlay.'),
    ];
    $form['set_text_color'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Set Text Color'),
      '#default_value' => $this->configuration['set_text_color'],
      '#description' => $this->t('Set a text color (including links) using inline styles.'),
    ];
    $form['text_color'] = [
      '#type' => 'color',
      '#title' => $this->t('Text Color'),
      '#default_value' => $this->configuration['text_color'],
      '#description' => $this->t('Choose a text color. Use a light color if your overlay/image are dark, for example.'),
      '#states' => [
        'visible' => [
          ':input[name="layout_settings[set_text_color]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['add_overlay'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Add Overlay'),
      '#default_value' => $this->configuration['add_overlay'],
      '#description' => $this->t('The overlay will be placed over your background. When combined with an appropriate text color, an overlay can improve readability of your content.'),
    ];
    $form['overlay_color'] = [
      '#type' => 'color',
      '#title' => $this->t('Overlay Color'),
      '#default_value' => $this->configuration['overlay_color'],
      '#states' => [
        'visible' => [
          ':input[name="layout_settings[add_overlay]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['overlay_opacity'] = [
      '#type' => 'number',
      '#title' => $this->t('Overlay Opacity'),
      '#default_value' => $this->configuration['overlay_opacity'],
      '#description' => $this->t('0 is opaque. 1 is transparent.'),
      '#min' => 0,
      '#max' => 1,
      '#step' => 0.05,
      '#states' => [
        'visible' => [
          ':input[name="layout_settings[add_overlay]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $this->configuration['background_color'] = $form_state->getValue('background_color');
    $this->configuration['static_image'] = $form_state->getValue('static_image');
    $this->configuration['center_content'] = $form_state->getValue('center_content');
    $this->configuration['set_text_color'] = $form_state->getValue('set_text_color');
    $this->configuration['text_color'] = $form_state->getValue('text_color');
    $this->configuration['add_overlay'] = $form_state->getValue('add_overlay');
    $this->configuration['overlay_color'] = $form_state->getValue('overlay_color');
    $this->configuration['overlay_opacity'] = $form_state->getValue('overlay_opacity');
  }

}
