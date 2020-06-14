<?php

namespace Drupal\layout_builder_kit\Plugin\Block\LBKRichText;

use Drupal\Core\Entity\EntityTypeBundleInfo;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\layout_builder_kit\Plugin\Block\LBKBaseComponent;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'LBKRichText' block.
 *
 * @Block(
 *  id = "lbk_rich_text",
 *  admin_label = @Translation("Rich Text (LBK)"),
 * )
 */
class LBKRichText extends LBKBaseComponent implements ContainerFactoryPluginInterface {

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Drupal\Core\Entity\EntityTypeBundleInfo class.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfo
   */
  protected $entityTypeBundleInfo;

  /**
   * Constructs a new rich text object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $pluginId
   *   The plugin_id for the plugin instance.
   * @param string $pluginDefinition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The EntityTypeManagerInterface service.
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
    CurrentRouteMatch $currentRouteMatch,
    EntityTypeBundleInfo $entityTypeBundleInfo
  ) {
    parent::__construct($configuration, $pluginId, $pluginDefinition, $currentRouteMatch, $entityTypeBundleInfo);
    $this->entityTypeManager = $entityTypeManager;
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
      $container->get('current_route_match'),
      $container->get('entity_type.bundle.info')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'rich_text_component_fields' => [
        'text_format' => [],
      ],
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $formState) {
    $format = (isset($this->configuration['rich_text_component_fields']['text_format']['format'])) ? $this->configuration['rich_text_component_fields']['text_format']['format'] : NULL;
    $value = (isset($this->configuration['rich_text_component_fields']['text_format']['value'])) ? $this->configuration['rich_text_component_fields']['text_format']['value'] : NULL;

    $form['text_format'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Rich text'),
      '#weight' => 30,
    ];

    if ($format) $form['text_format']['#format'] = $format;
    if ($value) $form['text_format']['#default_value'] = $value;

    $form['#attached']['library'] = ['layout_builder_kit/rich-text-styling'];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $formState) {
    $this->configuration['rich_text_component_fields']['text_format'] = $formState->getValue('text_format');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = parent::build();

    $build['#theme'] = 'LBKRichText';
    $build['#attached']['library'] = ['layout_builder_kit/rich-text-styling'];
    $build['#rich_text'] = $this->configuration['rich_text_component_fields']['text_format']['value'];
    $build['#rich_text_format'] = $this->configuration['rich_text_component_fields']['text_format']['format'];
    $build['#classes'] = $this->configuration['classes'];

    return $build;
  }

}
