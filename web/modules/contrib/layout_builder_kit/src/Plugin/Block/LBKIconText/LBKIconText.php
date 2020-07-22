<?php

namespace Drupal\layout_builder_kit\Plugin\Block\LBKIconText;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Config\ConfigManagerInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeBundleInfo;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\file\Entity\File;
use Drupal\layout_builder_kit\Plugin\Block\LBKBaseComponent;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'LBKIconText' block.
 *
 * @Block(
 *  id = "lbk_icon_text",
 *  admin_label = @Translation("Icon Text (LBK)"),
 * )
 */
class LBKIconText extends LBKBaseComponent implements ContainerFactoryPluginInterface {

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Drupal\Core\Config\ConfigManagerInterface definition.
   *
   * @var \Drupal\Core\Config\ConfigManagerInterface
   */
  protected $configManager;

  /**
   * Drupal\Core\Database\Connection definition.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Drupal\Core\Routing\CurrentRouteMatch class.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected $currentRouteMatch;

  /**
   * Drupal\Core\Entity\EntityTypeBundleInfo class.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfo
   */
  protected $entityTypeBundleInfo;

  /**
   * Constructs a new LBKIconText object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $pluginId
   *   The pluginId for the plugin instance.
   * @param string $pluginDefinition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The EntityTypeManagerInterface service.
   * @param \Drupal\Core\Config\ConfigManagerInterface $configManager
   *   The ConfigManagerInterface service.
   * @param \Drupal\Core\Database\Connection $database
   *   The Database Connection service.
   * @param \Drupal\Core\Routing\CurrentRouteMatch $currentRouteMatch
   *   The CurrentRouteMatch service.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfo $entityTypeBundleInfo
   *   The EntityTypeBundleInfo service.
   */
  public function __construct(
    array $configuration,
    $pluginId,
    $pluginDefinition,
    EntityTypeManagerInterface $entityTypeManager,
    ConfigManagerInterface $configManager,
    Connection $database,
    CurrentRouteMatch $currentRouteMatch,
    EntityTypeBundleInfo $entityTypeBundleInfo
  ) {
    parent::__construct($configuration, $pluginId, $pluginDefinition, $currentRouteMatch, $entityTypeBundleInfo);
    $this->entityTypeManager = $entityTypeManager;
    $this->configManager = $configManager;
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $pluginId, $pluginDefinition) {
    return new static(
      $configuration,
      $pluginId,
      $pluginDefinition,
      $container->get('entity_type.manager'),
      $container->get('config.manager'),
      $container->get('database'),
      $container->get('current_route_match'),
      $container->get('entity_type.bundle.info')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'icon_text_component_fields' => [
        'image' => [],
        'media_position' => 'media_on_left',
        'alignment' => 'alignment_left',
        'text' => [],
        'link' => '',
      ],
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $formState) {
    // Get value from ImageComponentSettingsForm's configuration.
    $configFactory = $this->configManager->getConfigFactory();
    $locationFolderConfig = $configFactory->get('layout_builder_kit.image_component');
    $locationFolder = $locationFolderConfig->get('image_location');

    $text_format = (isset($this->configuration['icon_text_component_fields']['text']['format'])) ? $this->configuration['icon_text_component_fields']['text']['format'] : NULL;
    $text_value = (isset($this->configuration['icon_text_component_fields']['text']['value'])) ? $this->configuration['icon_text_component_fields']['text']['value'] : NULL;

    $form['image'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Image'),
      '#upload_location' => ($locationFolder != NULL) ? 'public://' . $locationFolder : 'public://layout_builder_kit',
      '#multiple' => FALSE,
      '#default_value' => $this->configuration['icon_text_component_fields']['image'],
      '#upload_validators' => [
        'file_validate_extensions' => [$locationFolderConfig->get('layout_builder_kit.image_extensions')],
      ],
      '#weight' => 30,
    ];

    $form['media_position'] = [
      '#type' => 'select',
      '#title' => $this->t('Media position'),
      '#options' => [
        'media_on_left' => $this->t('Media on Left'),
        'media_on_right' => $this->t('Media on Right'),
      ],
      '#default_value' => $this->configuration['icon_text_component_fields']['media_position'],
      '#weight' => 40,
    ];

    $form['alignment'] = [
      '#type' => 'select',
      '#title' => $this->t('Alignment'),
      '#options' => [
        'alignment_left' => $this->t('Left'),
        'alignment_center' => $this->t('Center'),
        'alignment_right' => $this->t('Right'),
      ],
      '#default_value' => $this->configuration['icon_text_component_fields']['alignment'],
      '#weight' => 50,
    ];

    $form['link'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Link URL'),
      '#description' => $this->t('URL for title and icon. For relative link please use internal:/my-url'),
      '#maxlength' => 200,
      '#default_value' => $this->configuration['icon_text_component_fields']['link'],
      '#weight' => 60,
    ];

    $form['text'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Text'),
      '#weight' => 70,
    ];

    if ($text_format) $form['text']['#format'] = $text_format;
    if ($text_value) $form['text']['#default_value'] = $text_value;


    $form['#attached']['library'] = ['layout_builder_kit/icon-text-styling'];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockValidate($form, FormStateInterface $form_state) {
    parent::blockValidate($form, $form_state);

    // For now, accept all strings as valid. When the field is converted to a Link, we will inherit its validation.
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $formState) {
    $this->configuration['icon_text_component_fields']['text'] = $formState->getValue('text');
    $this->configuration['icon_text_component_fields']['media_position'] = $formState->getValue('media_position');
    $this->configuration['icon_text_component_fields']['alignment'] = $formState->getValue('alignment');
    $this->configuration['icon_text_component_fields']['image'] = $formState->getValue('image');
    $this->configuration['icon_text_component_fields']['link'] = $formState->getValue('link');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = parent::build();

    $build['#theme'] = 'LBKIconText';
    $build['#attached']['library'] = ['layout_builder_kit/icon-text-styling'];

    if (!empty($this->configuration['icon_text_component_fields']['image'])) {
      $icon_text_image_file_id = implode($this->configuration['icon_text_component_fields']['image']);
      $icon_text_image = File::load($icon_text_image_file_id);
      if ($icon_text_image != NULL) {
        $icon_text_image->setPermanent();
        $icon_text_image->save();

        $build['#image'] = [
          '#theme' => 'image',
          '#uri' => $icon_text_image->getFileUri(),
        ];
      }
    }

    $build['#media_position'] = $this->configuration['icon_text_component_fields']['media_position'];
    $build['#alignment'] = $this->configuration['icon_text_component_fields']['alignment'];
    $build['#text'] = $this->configuration['icon_text_component_fields']['text']['value'];
    $build['#text_format'] = $this->configuration['icon_text_component_fields']['text']['format'];
    $build['#link'] = $this->configuration['icon_text_component_fields']['link'];
    $build['#classes'] = $this->configuration['classes'];

    return $build;
  }

}
