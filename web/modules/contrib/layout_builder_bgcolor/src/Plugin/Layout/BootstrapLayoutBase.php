<?php

namespace Drupal\layout_builder_bgcolor\Plugin\Layout;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformStateInterface;
use Drupal\bootstrap_layouts\Plugin\Layout\BootstrapLayoutsBase;

/**
 * Provides a class for custom and core Layout plugins.
 */
class BootstrapLayoutBase extends BootstrapLayoutsBase {

  /**
   * {@inheritdoc}
   */
 public function defaultConfiguration() {
   $configuration = parent::defaultConfiguration();
   $configuration['layout'] += [
     'background_color' => 'none',
   ];
   return $configuration;
 }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    // This can potentially be invoked within a subform instead of a normal
    // form. There is an ongoing discussion around this which could result in
    // the passed form state going back to a full form state. In order to
    // prevent BC breaks, check which type of FormStateInterface has been
    // passed and act accordingly.
    // @see https://www.drupal.org/node/2868254
    // @todo Re-evaluate once https://www.drupal.org/node/2798261 makes it in.
    $complete_form_state = $form_state instanceof SubformStateInterface ? $form_state->getCompleteFormState() : $form_state;

    $form['layout']['background_color'] = [
      '#type' => 'select',
      '#options' => [
        'none' => $this->t('None'),
      ],
      '#required' => TRUE,
      '#title' => $this->t('Background color'),
      '#description' => $this->t('Choose a background color from the list.'),
      '#default_value' => $complete_form_state->getValue(['layout', 'background_color'], $this->configuration['layout']['background_color']),
      '#empty_value' => 'none',
    ];

    // Add each of the colors added to the settings form.
    $config = \Drupal::config('layout_builder_bgcolor.settings');
    foreach (explode("\n", $config->get('colors')) as $color) {
      $color = trim($color);
      if (!empty($color)) {
        [$color_code, $label] = explode(':', $color);
        $form['layout']['background_color']['#options'][Xss::filter($color_code)] = Xss::filter($label);
      }
    }

    return $form;
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
