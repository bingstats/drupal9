<?php

namespace Drupal\layout_builder_kit\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ConfigManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ImageComponentSettingsForm.
 */
class LayoutBuilderKitSettingsForm extends ConfigFormBase {

  /**
   * Drupal\Core\Config\ConfigManagerInterface definition.
   *
   * @var \Drupal\Core\Config\ConfigManagerInterface
   */
  protected $configManager;

  /**
   * Constructs a new ImageComponentSettingsForm object.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    ConfigManagerInterface $config_manager) {

    parent::__construct($config_factory);
    $this->configManager = $config_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('config.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'layout_builder_kit.image_component',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'location_folder_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('layout_builder_kit.image_component');

    $form['image_location'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Image Location'),
      '#description' => $this->t('Set location where Image component stores files under the public folder. For example, "my_custom_folder" may store images in /sites/default/files/my_custom_folder.
'),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => $config->get('layout_builder_kit.image_location'),
    ];

    $form['image_component_extension'] = [
      '#type' => 'textfield',
      '#title' => $this->t(' Image Component Allowed File Extensions'),
      '#description' => $this->t('Add extensions separated by spaces.'),
      '#required' => TRUE,
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => $config->get('layout_builder_kit.image_extensions'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('layout_builder_kit.image_component')
      ->set('layout_builder_kit.image_location', $form_state->getValue('image_location'))
      ->set('layout_builder_kit.image_extensions', $form_state->getValue('image_component_extension'))
      ->save();
  }

}
