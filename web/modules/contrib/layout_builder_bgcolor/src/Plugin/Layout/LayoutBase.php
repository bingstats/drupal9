<?php

namespace Drupal\layout_builder_bgcolor\Plugin\Layout;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Layout\LayoutDefault;

/**
 * Provides a class for custom and core Layout plugins.
 */
class LayoutBase extends LayoutDefault {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return parent::defaultConfiguration() + [
      'background_color' => 'none',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $default = $this->configuration['background_color'];
    if (!is_string($default)) {
      $default = 'none';
    }
    $form['background_color'] = [
      '#type' => 'select',
      '#options' => [
        'none' => $this->t('None'),
      ],
      '#title' => $this->t('Background color'),
      '#description' => $this->t('Choose a background color from the list.'),
      '#default_value' => $default,
      '#empty_value' => 'none',
      '#required' => TRUE,
    ];

    // Add each of the colors added to the settings form.
    $config = \Drupal::config('layout_builder_bgcolor.settings');
    foreach (explode("\n", $config->get('colors')) as $color) {
      $color = trim($color);
      if (!empty($color)) {
        [$color_code, $label] = explode(':', $color);
        $form['background_color']['#options'][Xss::filter($color_code)] = Xss::filter($label);
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['background_color'] = $form_state->getValue('background_color');
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function build(array $regions) {
    $build = parent::build($regions);

    $background_color = $this->configuration['background_color'] ?? NULL;
    if (!empty($background_color) && $background_color !== 'none') {
      $build['#attributes']['style'] = 'background-color: ' . Xss::filter($background_color);
    }

    return $build;
  }

}
