<?php

namespace Drupal\layout_builder_kit\Plugin\Block\LBKBookNavigation;

use Drupal\book\BookOutlineStorageInterface;
use Drupal\Core\Config\ConfigManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfo;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Url;
use Drupal\layout_builder_kit\LinkProviderInterface;
use Drupal\layout_builder_kit\Plugin\Block\LBKBaseComponent;
use Drupal\node\Entity\Node;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'LBKBookNavigation' block.
 *
 * @Block(
 *  id = "lbk_book_navigation",
 *  admin_label = @Translation("Book Navigation (LBK)"),
 * )
 */
class LBKBookNavigation extends LBKBaseComponent implements ContainerFactoryPluginInterface {

  /**
   * Drupal\Core\Routing\CurrentRouteMatch class.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  private $currentRouteMatch;

  /**
   * Drupal\book\BookOutlineStorageInterface definition.
   *
   * @var \Drupal\book\BookOutlineStorageInterface
   */
  private $bookOutlineStorage;

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entityTypeManager;

  /**
   * Drupal\Core\Config\ConfigManagerInterface definition.
   *
   * @var \Drupal\Core\Config\ConfigManagerInterface
   */
  protected $configManager;

  /**
   * Drupal\layout_builder_kit\LinkProviderInterface definition.
   *
   * @var \Drupal\layout_builder_kit\LinkProviderInterface
   */
  private $linkProvider;

  /**
   * Drupal\Core\Entity\EntityTypeBundleInfo class.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfo
   */
  protected $entityTypeBundleInfo;

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
        $container->get('current_route_match'),
        $container->get("book.outline_storage"),
        $container->get("layout_builder_kit.link_provider"),
        $container->get('entity_type.bundle.info')
    );
  }

  /**
   * Constructs a new LBKBookNavigationComponent object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $pluginId
   *   The plugin_id for the plugin instance.
   * @param mixed $pluginDefinition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The EntityTypeManagerInterface service.
   * @param \Drupal\Core\Config\ConfigManagerInterface $configManager
   *   The ConfigManagerInterface service.
   * @param \Drupal\Core\Routing\CurrentRouteMatch $currentRouteMatch
   *   The CurrentRouteMatch service.
   * @param \Drupal\book\BookOutlineStorageInterface $bookOutlineStorage
   *   The BookOutlineStorageInterface service.
   * @param \Drupal\layout_builder_kit\LinkProviderInterface $linkProvider
   *   The LinkProviderInterface service.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfo $entityTypeBundleInfo
   *   The EntityTypeBundleInfo service.
   */
  public function __construct(array $configuration,
                              $pluginId,
                              $pluginDefinition,
                              EntityTypeManagerInterface $entityTypeManager,
                              ConfigManagerInterface $configManager,
                              CurrentRouteMatch $currentRouteMatch,
                              BookOutlineStorageInterface $bookOutlineStorage,
                              LinkProviderInterface $linkProvider,
                              EntityTypeBundleInfo $entityTypeBundleInfo
  ) {
    parent::__construct($configuration, $pluginId, $pluginDefinition, $currentRouteMatch, $entityTypeBundleInfo);
    $this->entityTypeManager = $entityTypeManager;
    $this->currentRouteMatch = $currentRouteMatch;
    $this->configManager = $configManager;
    $this->bookOutlineStorage = $bookOutlineStorage;
    $this->entityTypeManager = $entityTypeManager;
    $this->linkProvider = $linkProvider;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'book_navigation_component_fields' => [
        'toc_url' => '',
      ],
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $formState) {
    $form['#attached']['library'] = ['layout_builder_kit/book-navigation-styling'];

    $form['toc_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Table of Contents URL'),
      '#maxlength' => 200,
      '#default_value' => $this->configuration['book_navigation_component_fields']['toc_url'],
      '#required' => FALSE,
      '#weight' => 40,
      '#prefix' => '<div class="toc_url--options">',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $formState) {
    $this->configuration['book_navigation_component_fields']['toc_url'] = $formState->getValue('toc_url');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = parent::build();

    $content = $this->navigationMenu();

    if ($content) {
      $build['#theme'] = 'LBKBookNavigation';
      $build['#attached']['library'] = ['layout_builder_kit/book-navigation-styling'];
      $build['#classes'] = $this->configuration['classes'];
      $build['#book_description'] = $this->getBookDescription();
      $build['#content'] = $content;
    }
    return $build;
  }

  /**
   * Return children at this level of the book.
   *
   * @return array
   *   Return array with Content Books List.
   */
  public function navigationMenu() {
    $entity = parent::getContextNode($this->currentRouteMatch);
    $node = $entity['node'];

    $content = [];
    $message = $this->t("Place Book Navigation on individual Book node layout or Book content type layout.");

    if(isset($node)) {
      if ($node instanceof Node) {
        // Check if Node is 'book' type.
        if ($node->getType() == 'book') {
          // Determine the book.
          $nid = $node->id();
          $book = $this->bookOutlineStorage->getBooks([$nid]);
          $book = $this->bookOutlineStorage
            ->loadBookChildren($book[0]);

          // Identify the active parent.
          $nidParentActive = 0;
          foreach ($book as $keyParent => $data) {
            $bookChild = $this->bookOutlineStorage
              ->loadBookChildren($keyParent);
            if ($keyParent == $nid) {
              $nidParentActive = $keyParent;
            }
            foreach ($bookChild as $key => $child) {
              if ($key == $nid) {
                $nidParentActive = $keyParent;
              }
            }
          }

          // Determine the section we are in.
          $node_parent = $this->entityTypeManager
            ->getStorage('node')
            ->load($nidParentActive);

          $titleKeyParent = '';
          if ($node_parent) {
            $titleKeyParent = $node_parent->title->value;
          }

          $content['parent_title'] = $titleKeyParent;

          // List book children.
          $bookChild = $this->bookOutlineStorage
            ->loadBookChildren($nidParentActive);
          $x = 0;
          foreach ($bookChild as $key => $child) {

            if ($key == $node->id()) {
              $content['class'][$x] = 'active';
            }
            $childNode = $this->entityTypeManager
              ->getStorage('node')
              ->load($key);
            $content['child_link'][$x] = $childNode->toLink($childNode->get('title')->value);
            $x++;
          }

          // Next section.
          $keys = array_keys($book);
          $nextSection = $keys[array_search($nidParentActive, $keys) + 1];
          $nextSectionNode = $this->entityTypeManager
            ->getStorage('node')
            ->load($nextSection);

          $link = trim($this->configuration['book_navigation_component_fields']['toc_url']);
          if ($link) {
            $url = Url::fromUri($link);
            $content['table_of_contents'] = $this->linkProvider->renderLink($url, '','Table of Contents');
          }

          if (isset($nextSectionNode)) {
            $content['next_link'] = $nextSectionNode->toLink($nextSectionNode->get('title')->value);
          }
        }
        else {
          if ($entity['source'] == 'individual_layout') {
            $content['lbk_book_navigation_block'] = $message;
          }
        }
      }
      else {
        if ($entity['bundle'] != 'book') {
          $content['lbk_book_navigation_block'] = $message;
        }
      }
    }

    return $content;
  }

  /**
   * Return Book's field_book_description field.
   *
   * @return string
   *   Return text with Book's field_book_description field value.
   */
  public function getBookDescription () {
    $entity = parent::getContextNode($this->currentRouteMatch);
    $node = $entity['node'];
    $bookDescription = '';
    if ($node instanceof Node) {
      if ($node->getType() == 'book') {
        if($node->hasField('field_book_description')) {
          $descriptionValue = $node->get('field_book_description')->getValue();
          $bookDescription = $descriptionValue[0]['value'];
        }
      }
    }
    return $bookDescription;
  }

}
