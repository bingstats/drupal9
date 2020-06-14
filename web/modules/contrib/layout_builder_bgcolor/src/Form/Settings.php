<?php

namespace Drupal\layout_builder_bgcolor\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Primary configuration form for the Layout Builder BGColor module.
 */
class Settings extends ConfigFormBase {

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    /**
     * @var \Drupal\layout_builder_bgcolor\Form\Settings
     */
    $instance = parent::create($container);
    $instance->state = $container->get('state');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'layout_builder_bgcolor_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['layout_builder_bgcolor.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $settings = $this->config('layout_builder_bgcolor.settings');

    $form['colors'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Background colors'),
      '#description' => $this->t('Must be a list of colors in the format:<blockquote><code>code:Color name</code></blockquote>For example:<br /><blockquote><code>#000:White<br />blue:Blue</code></blockquote>'),
      '#default_value' => $settings->get('colors'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $settings = $this->config('layout_builder_bgcolor.settings');
    $settings->set('colors', trim($form_state->getValue('colors')));
    $settings->save();
    parent::submitForm($form, $form_state);
  }

}
