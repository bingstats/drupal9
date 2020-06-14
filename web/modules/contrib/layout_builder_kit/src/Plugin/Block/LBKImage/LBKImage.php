<?php

namespace Drupal\layout_builder_kit\Plugin\Block\LBKImage;

use Drupal\Core\Config\ConfigManagerInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeBundleInfo;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\file\Entity\File;
use Drupal\layout_builder_kit\Plugin\Block\LBKBaseComponent;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'LBKImage' block.
 *
 * @Block(
 *  id = "lbk_image",
 *  admin_label = @Translation("Image (LBK)"),
 * )
 */
class LBKImage extends LBKBaseComponent implements ContainerFactoryPluginInterface {

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
   * Drupal\Core\Entity\EntityTypeBundleInfo class.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfo
   */
  protected $entityTypeBundleInfo;

  /**
   * Constructs a new image object.
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
      'image_component_fields' => [
        'title_position' => 'title_on_top',
        'image' => [],
        'image_style' => 'none',
        'image_alignment' => 'left',
        'overlay_text' => [],
        'overlay_position' => 'none',
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
    $locationFolder = $locationFolderConfig->get('layout_builder_kit.image_location');

    $title_position = $this->configuration['image_component_fields']['title_position'] ? $this->configuration['image_component_fields']['title_position'] : 'title_on_top';
    $form['title_position'] = [
      '#type' => 'select',
      '#title' => $this->t('Title position'),
      '#options' => [
        'title_on_top' => $this->t('Top'),
        'title_on_bottom' => $this->t('Bottom'),
      ],
      '#weight' => 10,
    ];
    $form['title_position']['#default_value'] = $title_position;

    $form['image'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Image'),
      '#upload_location' => ($locationFolder != NULL) ? 'public://' . $locationFolder : 'public://layout_builder_kit',
      '#multiple' => FALSE,
      '#default_value' => $this->configuration['image_component_fields']['image'],
      '#required' => TRUE,
      '#upload_validators' => [
        'file_validate_extensions' => [$locationFolderConfig->get('layout_builder_kit.image_extensions')],
      ],
      '#weight' => 20,
    ];

    $form['image_style'] = [
      '#type' => 'select',
      '#title' => $this->t('Image style'),
      '#options' => [
        'none' => $this->t('None'),
        'thumbnail' => $this->t('Thumbnail'),
        'medium' => $this->t('Medium'),
        'large' => $this->t('Large'),
      ],
      '#default_value' => $this->configuration['image_component_fields']['image_style'],
      '#weight' => 20,
    ];

    $form['image_alignment'] = [
      '#type' => 'select',
      '#title' => $this->t('Alignment'),
      '#options' => [
        'left' => $this->t('Left'),
        'center' => $this->t('Center'),
        'right' => $this->t('Right'),
      ],
      '#default_value' => $this->configuration['image_component_fields']['image_alignment'],
      '#weight' => 40,
    ];

    $format = (isset($this->configuration['image_component_fields']['overlay_text']['format'])) ? $this->configuration['image_component_fields']['overlay_text']['format'] : NULL;
    $value = (isset($this->configuration['image_component_fields']['overlay_text']['value'])) ? $this->configuration['image_component_fields']['overlay_text']['value'] : NULL;

    $form['overlay_text'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Overlay text'),
      '#weight' => 50,
    ];
    if ($format) $form['overlay_text']['#format'] = $format;
    if ($value) $form['overlay_text']['#default_value'] = $value;

    $form['overlay_position'] = [
      '#type' => 'select',
      '#title' => $this->t('Overlay text position'),
      '#options' => [
        'none' => $this->t('None'),
        'bottom' => $this->t('Bottom'),
      ],
      '#default_value' => $this->configuration['image_component_fields']['overlay_position'],
      '#weight' => 60,
    ];

    $form['#attached']['library'] = ['layout_builder_kit/image-styling'];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $formState) {
    $this->configuration['image_component_fields']['title_position'] = $formState->getValue('title_position');;
    $this->configuration['image_component_fields']['image'] = $formState->getValue('image');
    $this->configuration['image_component_fields']['image_style'] = $formState->getValue('image_style');
    $this->configuration['image_component_fields']['image_alignment'] = $formState->getValue('image_alignment');
    $this->configuration['image_component_fields']['overlay_text'] = $formState->getValue('overlay_text');
    $this->configuration['image_component_fields']['overlay_position'] = $formState->getValue('overlay_position');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = parent::build();

    $build['#theme'] = 'LBKImage';
    $build['#attached']['library'] = ['layout_builder_kit/image-styling'];
    $build['#title_position'] = $this->configuration['image_component_fields']['title_position'];

    if (!empty($this->configuration['image_component_fields']['image'])) {
      $imageFileId = implode($this->configuration['image_component_fields']['image']);
      $image = File::load($imageFileId);
      if ($image != NULL) {
        $image->setPermanent();
        $image->save();

        if ($this->configuration['image_component_fields']['image_style'] == "none") {
          $build['#image'] = [
            '#theme' => 'image',
            '#uri' => $image->getFileUri(),
          ];
        }
        else {
          $build['#image'] = [
            '#theme' => 'image_style',
            '#style_name' => $this->configuration['image_component_fields']['image_style'],
            '#uri' => $image->getFileUri(),
          ];
        }
      }
    }

    $build['#image_alignment'] = $this->configuration['image_component_fields']['image_alignment'];
    $build['#overlay_position'] = $this->configuration['image_component_fields']['overlay_position'];
    $build['#overlay_text'] = $this->configuration['image_component_fields']['overlay_text']['value'];
    $build['#overlay_text_format'] = $this->configuration['image_component_fields']['overlay_text']['format'];
    $build['#classes'] = $this->configuration['classes'];

    return $build;
  }

}
