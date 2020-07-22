<?php

namespace Drupal\layout_builder_kit\Plugin\Block\LBKRender;

use Drupal\Core\Config\ConfigManagerInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityDisplayRepository;
use Drupal\Core\Entity\EntityTypeBundleInfo;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\layout_builder_kit\Plugin\Block\LBKBaseComponent;
use Drupal\media\Entity\Media;
use Drupal\node\Entity\Node;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'LBKRender' block.
 *
 * @Block(
 *  id = "lbk_render",
 *  admin_label = @Translation("Render (LBK)"),
 * )
 */
class LBKRender extends LBKBaseComponent implements ContainerFactoryPluginInterface {

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
   * Drupal\Core\Database\Connection definition.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $entityTypeBundleInfo;

  /**
   * Drupal\Core\Database\Connection definition.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $entityDisplayRepository;

  /**
   * Constructs a new render object.
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
   */
  public function __construct(
    array $configuration,
    $pluginId,
    $pluginDefinition,
    EntityTypeManagerInterface $entityTypeManager,
    ConfigManagerInterface $configManager,
    Connection $database,
    CurrentRouteMatch $currentRouteMatch,
    EntityTypeBundleInfo $entityTypeBundleInfo,
    EntityDisplayRepository $entityDisplayRepository
  ) {
    parent::__construct($configuration, $pluginId, $pluginDefinition, $currentRouteMatch, $entityTypeBundleInfo);
    $this->entityTypeManager = $entityTypeManager;
    $this->configManager = $configManager;
    $this->database = $database;
    $this->entityTypeBundleInfo = $entityTypeBundleInfo;
    $this->entityDisplayRepository = $entityDisplayRepository;
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
      $container->get('entity_type.bundle.info'),
      $container->get('entity_display.repository')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
        'render_component' => [
          'render_type' => '',
          'node_id' => '',
          'media_id' => '',
          'view_mode_node' => '',
          'view_mode_media' => '',
        ],
      ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $formState) {
    $nodeId = $this->configuration['render_component']['node_id'];
    $mediaId = $this->configuration['render_component']['media_id'];
    $mediaObject = '';

    $messenger = \Drupal::messenger();
    if (!\Drupal::moduleHandler()->moduleExists('media')){
      $messenger->addMessage('Enable Media module to place this component.', 'warning');
      $form['warning'] = [
        '#type' => 'label',
        '#title' => $this->t('Media module is not enabled.'),
        '#weight' => 30,
        '#attributes' => [
          'class' => ['warning-entity']
        ]
      ];
    }
    else {
      $mediaObject = (isset($mediaId)) ? Media::load($mediaId) : '';
    }
    $nodeObject = (isset($nodeId)) ? Node::load($nodeId) : '';

    $form['render_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Render Type'),
      '#options' => [
        'node' => $this->t('Node'),
        'media' => $this->t('Media'),
      ],
      '#default_value' => $this->configuration['render_component']['render_type'],
      '#weight' => 40,
      '#prefix' => '<div id="entity-render">',
      '#suffix' => '</div>',
      '#attributes' => [
        'class' => ['render-type']
      ]
    ];

    $form['node_id'] = [
      '#type' => 'entity_autocomplete',
      '#target_type' => 'node',
      '#title' => t('Node'),
      '#description' => t('Use autocomplete to find it'),
      '#selection_handler' => 'default',
      '#selection_settings' => [
        'target_bundles' => array_keys($this->getBundles('node')),
      ],
      '#default_value' => ($nodeObject instanceof Node) ? $nodeObject : '',
      '#weight' => 50,
      '#prefix' => '<div id="entity-node">',
      '#suffix' => '</div>',
    ];

    $form['media_id'] = [
      '#type' => 'entity_autocomplete',
      '#target_type' => 'media',
      '#title' => t('Media'),
      '#description' => t('Use autocomplete to find it'),
      '#selection_handler' => 'default',
      '#selection_settings' => [
        'target_bundles' => array_keys($this->getBundles('media')),
      ],
      '#default_value' => ($mediaObject instanceof Media) ? $mediaObject : '',
      '#weight' => 50,
      '#prefix' => '<div id="entity-media">',
      '#suffix' => '</div>',
    ];

    $form['view_mode_node'] = [
      '#type' => 'select',
      '#title' => $this->t('View Mode'),
      '#options' => $this->getViewModes('node'),
      '#default_value' => $this->configuration['render_component']['view_mode_node'],
      '#weight' => 60,
      '#prefix' => '<div id="view-mode-node">',
      '#suffix' => '</div>',
    ];

    $form['view_mode_media'] = [
      '#type' => 'select',
      '#title' => $this->t('View Mode'),
      '#options' => $this->getViewModes('media'),
      '#default_value' => $this->configuration['render_component']['view_mode_media'],
      '#weight' => 60,
      '#prefix' => '<div id="view-mode-media">',
      '#suffix' => '</div>',
    ];

    $form['#attached']['library'] = ['layout_builder_kit/render-styling'];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $formState) {
    $version = '';
    if (isset($this->configuration['render_component']['version'])) {
      $version = $this->configuration['render_component']['version'];
    }
    else {
      $version = "1.0";
    }
    if ($version == "1.0") $version = "1.1";

    // Each version has its own storage structure.
    if ($version == "1.1") {
      $this->configuration['render_component']['version'] = $version;
      $this->configuration['render_component']['render_type'] = $formState->getValue('render_type');;
      $this->configuration['render_component']['node_id'] = $formState->getValue('node_id');
      $this->configuration['render_component']['media_id'] = $formState->getValue('media_id');
      $this->configuration['render_component']['view_mode_node'] = $formState->getValue('view_mode_node');
      $this->configuration['render_component']['view_mode_media'] = $formState->getValue('view_mode_media');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = parent::build();

    $build['#theme'] = 'LBKRender';
    $build['#attached']['library'] = ['layout_builder_kit/render-styling'];

    $build['#render_type'] = $this->configuration['render_component']['render_type'];
    $build['#classes'] = $this->configuration['classes'];

    $entity_type = $this->configuration['render_component']['render_type'];
    if ($entity_type === 'node') {
      // Render node.
      $nid = $this->configuration['render_component']['node_id'];
      $view_mode = $this->configuration['render_component']['view_mode_node'];

      $entity = (isset($nid)) ? $this->entityTypeManager->getStorage($entity_type)->load($nid) : '';
      if ($entity instanceof Node) {
        $toRender = $this->entityTypeManager->getViewBuilder($entity_type)->view($entity, $view_mode);
      }
      else {
        $toRender = ['#markup' => '<span>' . $this->t('Node object not present.') . '</span>'];
      }
    }
    else {
      // Render media.
      $mid = $this->configuration['render_component']['media_id'];
      $view_mode = $this->configuration['render_component']['view_mode_media'];

      if (\Drupal::moduleHandler()->moduleExists('media')){
        $testMedia = Media::load($mid);
        if ($testMedia instanceof Media) {
          $entity = (isset($mid)) ? $this->entityTypeManager->getStorage($entity_type)->load($mid) : '';
          if ($entity instanceof Media) {
            $toRender = $this->entityTypeManager->getViewBuilder($entity_type)->view($entity, $view_mode);
          }
          else {
            $toRender = ['#markup' => '<span>' . $this->t('Media object not present.') . '</span>'];
          }
        }
      }
      else {
        $toRender = ['#markup' => '<span>' . $this->t('Media module is not enabled.') . '</span>'];
      }
    }
    $output = render($toRender);

    $build['#entity'] = $output;

    return $build;
  }

  /**
   * Get Bundles for 'node' or 'media' entity.
   *
   * @param string $entity
   * @return array|mixed
   */
  protected function getBundles(string $entity) {
    return $this->entityTypeBundleInfo->getBundleInfo($entity);
  }

  /**
   * Get node's view modes.
   *
   * @param string $entity
   * @return array
   */
  protected function getViewModes(string $entity) {
    // Call the Entity Display Repository service.
    $nodeViewModes = $this->entityDisplayRepository->getViewModes($entity);
    $viewModes = [];
    foreach ($nodeViewModes as $key => $value) {
      $viewModes[$key] = $value['label'];
    }

    return $viewModes;
  }

}
