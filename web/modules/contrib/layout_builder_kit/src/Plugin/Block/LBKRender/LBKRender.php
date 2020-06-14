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
use Drupal\file\Entity\File;
use Drupal\layout_builder_kit\Plugin\Block\LBKBaseComponent;
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
          'view_mode' => '',
        ],
      ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $formState) {
    $form['render_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Render Type'),
      '#options' => [
        'node' => $this->t('Node'),
      ],
      '#default_value' => $this->configuration['render_component']['render_type'],
      '#weight' => 40,
    ];

    $form['node_id'] = [
      '#type' => 'entity_autocomplete',
      '#target_type' => 'node',
      '#title' => t('Node'),
      '#description' => t('Use autocomplete to find it'),
      '#selection_handler' => 'default',
      '#selection_settings' => [
        'target_bundles' => array_keys($this->getBundles()),
      ],
      '#default_value' => Node::load($this->configuration['render_component']['node_id']),
      '#weight' => 50,
    ];

    $form['view_mode'] = [
      '#type' => 'select',
      '#title' => $this->t('View Mode'),
      '#options' => $this->getViewModes(),
      '#default_value' => $this->configuration['render_component']['view_mode'],
      '#weight' => 60,
    ];

    $form['#attached']['library'] = ['layout_builder_kit/render-styling'];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $formState) {
    $this->configuration['render_component']['render_type'] = $formState->getValue('render_type');;
    $this->configuration['render_component']['node_id'] = $formState->getValue('node_id');
    $this->configuration['render_component']['view_mode'] = $formState->getValue('view_mode');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = parent::build();

    $build['#theme'] = 'LBKRender';
    $build['#attached']['library'] = ['layout_builder_kit/render-styling'];

    $build['#render_type'] = $this->configuration['render_component']['render_type'];
    $build['#view_mode'] = $this->configuration['render_component']['view_mode'];
    $build['#classes'] = $this->configuration['classes'];


    // Render any node.
    $nid = $this->configuration['render_component']['node_id'];
    $entity_type = $this->configuration['render_component']['render_type'];
    $view_mode = $this->configuration['render_component']['view_mode'];

    $node = $this->entityTypeManager->getStorage($entity_type)->load($nid);
    $toRender = $this->entityTypeManager->getViewBuilder($entity_type)->view($node, $view_mode);
    $output = render($toRender);

    $build['#entity'] = $output;

    return $build;
  }

  /**
   * Get Bundles for 'node' entity.
   *
   * @return array|mixed
   */
  protected function getBundles() {
    return $this->entityTypeBundleInfo->getBundleInfo('node');
  }

  /**
   * Get node's view modes.
   *
   * @return array
   */
  protected function getViewModes() {
    // Call the Entity Display Repository service.
    $nodeViewModes = $this->entityDisplayRepository->getViewModes('node');

    $viewModes = [];
    foreach ($nodeViewModes as $key => $value) {
      $viewModes[$key] = $value['label'];
    }

    return $viewModes;
  }

}
