<?php

namespace Drupal\layout_builder_kit\Plugin\Block\LBKTab;

use Drupal\block\Entity\Block;
use Drupal\block_content\Entity\BlockContent;
use Drupal\Core\Config\ConfigManagerInterface;
use Drupal\Core\Entity\EntityFieldManager;
use Drupal\Core\Entity\EntityTypeBundleInfo;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Plugin\DataType\ConfigEntityAdapter;
use Drupal\Core\Entity\Plugin\DataType\EntityAdapter;
use Drupal\Core\Extension\Extension;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\Context\EntityContext;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\layout_builder\Entity\LayoutBuilderEntityViewDisplay;
use Drupal\layout_builder\Plugin\SectionStorage\DefaultsSectionStorage;
use Drupal\layout_builder\Plugin\SectionStorage\OverridesSectionStorage;
use Drupal\layout_builder_kit\Plugin\Block\LBKBaseComponent;
use Drupal\node\Entity\Node;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'LBKTab' block.
 *
 * @Block(
 *  id = "lbk_tab",
 *  admin_label = @Translation("Tab (LBK)"),
 * )
 */
class LBKTab extends LBKBaseComponent implements ContainerFactoryPluginInterface {

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
   * Drupal\Core\Routing\CurrentRouteMatch class.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected $currentRouteMatch;

  /**
   * Drupal\Core\Extension\ModuleHandlerInterface class.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Drupal\Core\Entity\EntityFieldManager class.
   *
   * @var \Drupal\Core\Entity\EntityFieldManager
   */
  protected $entityFieldManager;

  /**
   * Drupal\Core\Entity\EntityTypeBundleInfo class.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfo
   */
  protected $entityTypeBundleInfo;

  /**
   * Constructs a new tab object.
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
   * @param \Drupal\Core\Routing\CurrentRouteMatch $currentRouteMatch
   *   The CurrentRouteMatch service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The ModuleHandlerInterface service.
   * @param \Drupal\Core\Entity\EntityFieldManager $entityFieldManager
   *   The EntityFieldManager service.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfo $entityTypeBundleInfo
   *   The EntityTypeBundleInfo service.
   */
  public function __construct(
    array $configuration,
    $pluginId,
    $pluginDefinition,
    EntityTypeManagerInterface $entityTypeManager,
    ConfigManagerInterface $configManager,
    CurrentRouteMatch $currentRouteMatch,
    ModuleHandlerInterface $moduleHandler,
    EntityFieldManager $entityFieldManager,
    EntityTypeBundleInfo $entityTypeBundleInfo
  ) {
    parent::__construct($configuration, $pluginId, $pluginDefinition, $currentRouteMatch, $entityTypeBundleInfo);
    $this->entityTypeManager = $entityTypeManager;
    $this->configManager = $configManager;
    $this->currentRouteMatch = $currentRouteMatch;
    $this->moduleHandler = $moduleHandler;
    $this->entityFieldManager = $entityFieldManager;
    $this->entityTypeBundleInfo = $entityTypeBundleInfo;
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
      $container->get('current_route_match'),
      $container->get('module_handler'),
      $container->get('entity_field.manager'),
      $container->get('entity_type.bundle.info')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'lbk_tab' => [
        'tab_content' => [],
      ],
      'tabs_default_text' => $this->t('No tabs.'),
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $formState) {
    $config = $this->getConfiguration();

    $form['#prefix'] = '<div class="lbk-tab--component">';
    $form['#tree'] = TRUE;

    $form['items_fieldset'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Tabs'),
      '#prefix' => '<div id="items-fieldset-wrapper">',
      '#suffix' => '</div>',
      '#weight' => 0,
    ];

    if (!$formState->has('items')) {
      $formState->set('items', $config['lbk_tab']['tab_content']);
    }

    $tabs = $formState->get('items');
    $numTabs = count($tabs);
    for ($i = 0; $i < $numTabs; $i++) {
      $tab_value = 'Tab ' . ($i + 1);
      if (isset($tabs[$i]['name_tab'])) $tab_value = $tabs[$i]['name_tab'];

      // Tab label.
      $form['items_fieldset']['items'][$i]['name_tab'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Tab @index label', ['@index' => $i + 1]),
        '#default_value' => $tab_value,
        '#maxlength' => 64,
        '#size' => 64,
        '#weight' => 0,
      ];

      // Which type of tab are we adding?
      if ($tabs[$i]['type'] === 'custom_text') {
        // Custom text tab.
        $tab_text = '';
        if (isset($tabs[$i]['text_format']['value'])) $tab_text = $tabs[$i]['text_format']['value'];

        $tab_text_format = '';
        if (isset($tabs[$i]['text_format']['format'])) $tab_text_format = $tabs[$i]['text_format']['format'];

        $form['items_fieldset']['items'][$i]['text_format'] = [
          '#type' => 'text_format',
          '#title' => $this->t('Rich text'),
          '#default_value' => $tab_text,
          '#format' => $tab_text_format,
          '#weight' => 10,
        ];

      }
      else {
        // Block tab.
        if ($tabs[$i]['type'] === 'block') {
          $tab_name = '';
          if (isset($tabs[$i]['name'])) $tab_name = $tabs[$i]['name'];

          $form['items_fieldset']['items'][$i]['name'] = [
            '#type' => 'select',
            '#title' => $this->t('Content Block'),
            '#options' => $this->getBlocks(),
            '#default_value' => $tab_name,
            '#weight' => 10,
          ];

        }
      }
    }

    // Remove last block button.
    if ($numTabs >= 1) {
      $form['items_fieldset']['items']['remove_item'] = [
        '#type' => 'submit',
        '#value' => $this->t('Remove last tab'),
        '#submit' => [[$this, 'removeLastBlock']],
        '#ajax' => [
          'callback' => [$this, 'removeLastBlockCallback'],
          'wrapper' => 'items-fieldset-wrapper',
        ],
        '#weight' => 20,
      ];
    }

    $form['items_fieldset']['actions'] = [
      '#type' => 'actions',
    ];

    // Add custom text block button.
    $form['add_custom_text'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add custom text'),
      '#submit' => [[$this, 'addCustomText']],
      '#ajax' => [
        'callback' => [$this, 'addCustomTextCallback'],
        'wrapper' => 'items-fieldset-wrapper',
      ],
      '#weight' => 10,
      '#prefix' => '<div class="lbk-tab-buttons">',
    ];

    // Add block button.
    $form['add_block_tab'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add block'),
      '#submit' => [[$this, 'addBlock']],
      '#ajax' => [
        'callback' => [$this, 'addBlockCallback'],
        'wrapper' => 'items-fieldset-wrapper',
      ],
      '#weight' => 10,
      '#suffix' => '</div>',
    ];

    $tab_default_text = '';
    if (isset($this->configuration['tabs_default_text'])) $tab_default_text = $this->configuration['tabs_default_text'];
    $form['tabs_default_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('"No tabs" text.'),
      '#default_value' => $tab_default_text,
      '#maxlength' => 64,
      '#size' => 64,
      '#weight' => 20,
    ];

    $form['#suffix'] = '</div>';
    $form['#attached']['library'] = ['layout_builder_kit/tab-styling'];

    return $form;
  }

  /**
   * Add custom text.
   *
   * @param array $form
   *   The array form.
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *   The FormStateInterface.
   */
  public function addCustomText(array &$form, FormStateInterface $formState) {
    $items = [];
    if ($formState->has('items')) {
      $items = $formState->get('items');
      $nextItem = count($items);
      $items[$nextItem]['type'] = 'custom_text';
    }
    else {
      $nextItem = count($items);
      $items[$nextItem]['type'] = 'custom_text';
    }

    $formState->set('items', $items);
    $formState->setRebuild();
  }

  /**
   * Add custom text (callback).
   *
   * @param array $form
   *   The array form.
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *   The FormStateInterface.
   *
   * @return mixed
   *   Return the form.
   */
  public function addCustomTextCallback(array &$form, FormStateInterface $formState) {
    return $form['settings']['items_fieldset'];
  }

  /**
   * Add block.
   *
   * @param array $form
   *   The array form.
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *   The FormStateInterface.
   */
  public function addBlock(array &$form, FormStateInterface $formState) {
    $items = [];
    if ($formState->has('items')) {
      $items = $formState->get('items');
      $nextItem = count($items);
      $items[$nextItem]['type'] = 'block';
    }
    else {
      $nextItem = count($items);
      $items[$nextItem]['type'] = 'block';
    }

    $formState->set('items', $items);
    $formState->setRebuild();
  }

  /**
   * Add block (callback).
   *
   * @param array $form
   *   The array form.
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *   The FormStateInterface.
   *
   * @return mixed
   *   Return the form.
   */
  public function addBlockCallback(array &$form, FormStateInterface $formState) {
    return $form['settings']['items_fieldset'];
  }

  /**
   * Remove last block.
   *
   * @param array $form
   *   The array form.
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *   The FormStateInterface.
   */
  public function removeLastBlock(array &$form, FormStateInterface $formState) {
    $items = [];
    if ($formState->has('items')) {
      $items = $formState->get('items');
      array_pop($items);
    }

    $formState->set('items', $items);
    $formState->setRebuild();
  }

  /**
   * Remove last block (callback).
   *
   * @param array $form
   *   The array form.
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *   The FormStateInterface.
   *
   * @return mixed
   *   Return the form.
   */
  public function removeLastBlockCallback(array &$form, FormStateInterface $formState) {
    return $form['settings']['items_fieldset'];
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $formState) {
    $this->configuration['tabs_default_text'] = $formState->getValue('tabs_default_text');

    // Set to empty before save all again.
    $this->configuration['lbk_tab']['tab_content'] = [];

    foreach ($formState->getValues() as $key => $value) {
      if ($key === 'items_fieldset') {
        if (isset($value['items'])) {
          $items = $value['items'];
          $tabs = $formState->get('items');
          if (is_array($tabs)) {
            for ($i = 0; $i < count($tabs); $i++) {
              if ($tabs[$i]['type'] === 'custom_text') {
                $this->configuration['lbk_tab']['tab_content'][$i]['type'] = 'custom_text';
                $this->configuration['lbk_tab']['tab_content'][$i]['name_tab'] = $items[$i]['name_tab'];
                $this->configuration['lbk_tab']['tab_content'][$i]['text_format']['value'] = $items[$i]['text_format']['value'];
                $this->configuration['lbk_tab']['tab_content'][$i]['text_format']['format'] = $items[$i]['text_format']['format'];
              }
              else {
                if ($tabs[$i]['type'] === 'block') {
                  $this->configuration['lbk_tab']['tab_content'][$i]['type'] = 'block';
                  $this->configuration['lbk_tab']['tab_content'][$i]['name_tab'] = $items[$i]['name_tab'];
                  $this->configuration['lbk_tab']['tab_content'][$i]['name'] = $items[$i]['name'];
                }
              }
            }
          }
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = parent::build();

    $tabs = [];
    $node = NULL;
    $renderedBlock = NULL;
    if (isset($this->configuration['lbk_tab']['tab_content'])) {
      for ($i = 0; $i < count($this->configuration['lbk_tab']['tab_content']); $i++) {
        if ($this->configuration['lbk_tab']['tab_content'][$i]['type'] === 'block') {
          $tabs[$i]['type'] = $this->configuration['lbk_tab']['tab_content'][$i]['type'];
          $tabs[$i]['name_tab'] = $this->configuration['lbk_tab']['tab_content'][$i]['name_tab'];

          // Render block.
          if ((int) $this->configuration['lbk_tab']['tab_content'][$i]['name'] > 0) {
            // Render block.
            $bid = $this->configuration['lbk_tab']['tab_content'][$i]['name'];
            $block = BlockContent::load($bid);
            if ($block instanceof BlockContent) {
              $renderedBlock = $this->entityTypeManager->getViewBuilder('block_content')->view($block);
            }
            else {
              if ($this->configuration['lbk_tab']['tab_content'][$i]['name'] == 'none') {
                $renderedBlock = $this->t("There is no any content block yet.");
              }
              else {
                $renderedBlock = $this->t("Sorry this Block was deleted.");
              }
            }
          }
          else {
            // Render fields.
            $field = $this->getField($this->configuration['lbk_tab']['tab_content'][$i]['name']);

            if (isset($field) && $field !== "") {
              $entityType = 'node';
              // Fields to render.
              $fields = [
                $field,
              ];

              // Get the Node entity object.
              if ($this->currentRouteMatch->getParameter('node')) {
                $node = $this->currentRouteMatch->getParameter('node');
              }
              else {
                $currentRoute = $this->currentRouteMatch;
                $sectionStorage = $currentRoute->getParameters()->get('section_storage');
                if ($sectionStorage instanceof OverridesSectionStorage) {
                  $context = $sectionStorage->getContext('entity');
                  if ($context instanceof EntityContext) {
                    $contextData = $context->getContextData();
                    if ($contextData instanceof EntityAdapter) {
                      $node = $contextData->getEntity();
                    }
                  }
                }
              }

              // Get the view for this Node entity object.
              $viewBuilder = $this->entityTypeManager->getViewBuilder($entityType);

              // Build the Node array to render.
              foreach ($fields as $field_name) {
                if ($node instanceof Node) {
                  if ($node->hasField($field_name) && $node->access('view')) {
                    $value = $node->get($field_name);
                    $renderedBlock = $viewBuilder->viewField($value, ["label" => "hidden"]);
                    $renderedBlock['#cache']['tags'] = $node->getCacheTags();
                  }
                  else {
                    $renderedBlock = $this->t('This field is not available for this content.');
                  }
                }
                else {
                  $renderedBlock = $this->t('Render this inside a node page that has this field.');
                }
              }
            }
            else {
              // Render content block.
              $blockEntity = Block::load($this->configuration['lbk_tab']['tab_content'][$i]['name']);
              $renderedBlock = $this->entityTypeManager
                ->getViewBuilder('block')
                ->view($blockEntity);
            }
          }
          $tabs[$i]['name'] = $renderedBlock;
        }
        else {
          $tabs[$i] = $this->configuration['lbk_tab']['tab_content'][$i];
        }
      }
    }

    $build['#theme'] = 'LBKTab';
    $build['#attached']['library'] = ['layout_builder_kit/tab-styling'];
    $build['#tabs_default_text']['#markup'] = $this->configuration['tabs_default_text'];
    $build['#classes'] = $this->configuration['classes'];
    $build['#tabs'] = $tabs;
    return $build;
  }

  /**
   * Get custom blocks.
   *
   * @return array
   *   Return array with custom blocks.
   */
  private function getContentBlocks() {
    $blocks = BlockContent::loadMultiple();

    $options = [];
    $labelOption = $this->t('Custom');

    if ($labelOption instanceof TranslatableMarkup) {
      foreach ($blocks as $value) {
        if ($value instanceof BlockContent) {
          $options[$labelOption->render()][$value->id()] = $value->label();
        }
      }
    }

    return $options;
  }

  /**
   * Get module blocks.
   *
   * @return array
   *   Return array with module blocks.
   */
  private function getContentPluginBlocks() {
    $blocksList = [];
    $labelOption = $this->t('Modules');

    // TODO: Use this in certain cases? If so, where do we store the block?
    //    $blockManager = \Drupal::service('plugin.manager.block');
    //    $contextRepository = \Drupal::service('context.repository');
    //    $definitions = $blockManager->getDefinitionsForContexts($contextRepository->getAvailableContexts());

    // Load all existing blocks.
    $blocks = Block::loadMultiple();

    $modules = $this->moduleHandler->getModuleList();

    if ($labelOption instanceof TranslatableMarkup) {
      foreach ($blocks as $block) {
        if ($block instanceof Block) {
          $dependenciesBlock = NULL;
          if (isset($block->getDependencies()['module'])) $dependenciesBlock = $block->getDependencies()['module'];
          if ($dependenciesBlock) {
            $system = FALSE;
            foreach ($dependenciesBlock as $dependencyBlock) {
              $module = $modules[$dependencyBlock];
              if ($module instanceof Extension) {
                if ($block->getPluginId() !== 'lbk_tab') {
                  $pathModuleArray = explode('/', $module->getPathname());
                  if (in_array('contrib', $pathModuleArray) || in_array('custom', $pathModuleArray)) {
                    $system = TRUE;
                  }
                }
              }
            }
            if ($system) {
              $blocksList[$labelOption->render()][$block->id()] = $block->label();
            }
          }
        }
      }
    }

    return $blocksList;
  }

  /**
   * Get system blocks.
   *
   * @return array
   *   Return array with system blocks.
   */
  private function getSystemPluginBlocks() {
    $blocksList = [];
    $labelOption = $this->t('System');

    $blocks = Block::loadMultiple();

    $modules = $this->moduleHandler->getModuleList();
    unset($modules['layout_builder_kit']);

    if ($labelOption instanceof TranslatableMarkup) {
      foreach ($blocks as $block) {
        if ($block instanceof Block) {
          $dependenciesBlock = NULL;
          if (isset($block->getDependencies()['module'])) $dependenciesBlock = $block->getDependencies()['module'];
          if ($dependenciesBlock) {
            $dependenciesBlock = $block->getDependencies()['module'];
            $system = FALSE;
            foreach ($dependenciesBlock as $dependencyBlock) {
              $module = $modules[$dependencyBlock];
              if ($module instanceof Extension) {
                $pathModuleArray = explode('/', $module->getPathname());
                if (in_array('core', $pathModuleArray)) {
                  $system = TRUE;
                }
              }
            }
            if ($system) {
              $blocksList[$labelOption->render()][$block->id()] = ucfirst($block->getTheme()) . ':' . $block->label();
            }
          }
        }
      }
    }

    return $blocksList;
  }

  /**
   * Get blocks of all types.
   *
   * @return array
   *   Return all blocks.
   */
  private function getBlocks() {
    $contentAndContentPluginBlocks = array_merge($this->getContentBlocks(), $this->getContentPluginBlocks());
    $allBlocks = array_merge($contentAndContentPluginBlocks, $this->getSystemPluginBlocks());

    $allFields = array_merge($allBlocks, $this->getNodeFields());

    return $allFields;
  }

  /**
   * Get node fields.
   *
   * @return array
   *   Return array of node fields.
   */
  private function getNodeFields() {
    $allFields = [];
    // Get the Current Route Match.
    $currentRoute = $this->currentRouteMatch;
    if ($currentRoute instanceof CurrentRouteMatch) {
      $sectionStorage = $currentRoute->getParameters()->get('section_storage');
      // Content Type page.
      if ($sectionStorage instanceof DefaultsSectionStorage) {
        $availableContexts = $sectionStorage->getContexts();
        $contextDisplay = $availableContexts['display'];
        if ($contextDisplay instanceof EntityContext) {
          $contextData = $contextDisplay->getContextData();
          if ($contextData instanceof ConfigEntityAdapter) {
            $contentInfo = $contextData->getEntity();
            if ($contentInfo instanceof LayoutBuilderEntityViewDisplay) {
              // Bundle 'page'.
              $bundle = $contentInfo->getTargetBundle();
              // Content Type 'node'.
              $entityTypeId = $contentInfo->getTargetEntityTypeId();

              $definitions = $this->entityFieldManager->getFieldDefinitions($entityTypeId, $bundle);
              foreach ($definitions as $fieldName => $fieldDefinition) {
                if (!empty($fieldDefinition->getTargetBundle()) && $fieldName !== 'layout_builder__layout') {
                  $allFields[$bundle][$bundle . '_' . $fieldName] = $bundle . ':' . $fieldDefinition->getLabel();
                }
              }
            }
          }
        }
      }
      else {
        // Bundle page.
        if ($sectionStorage instanceof OverridesSectionStorage) {
          $context = $sectionStorage->getContext('entity');
          if ($context instanceof EntityContext) {
            $contextData = $context->getContextData();
            if ($contextData instanceof EntityAdapter) {
              $node = $contextData->getEntity();
              if ($node instanceof Node) {
                $bundle = $node->bundle();

                $definitions = $this->entityFieldManager->getFieldDefinitions('node', $bundle);
                foreach ($definitions as $fieldName => $fieldDefinition) {
                  if (!empty($fieldDefinition->getTargetBundle()) && $fieldName !== 'layout_builder__layout') {
                    $allFields[$bundle][$bundle . '_' . $fieldName] = $bundle . ':' . $fieldDefinition->getLabel();
                  }
                }
              }
            }
          }
        }
        else {
          // Get fields for all bundles.
          $entityType = "node";

          $contentTypes = $this->entityTypeBundleInfo->getBundleInfo($entityType);

          foreach ($contentTypes as $key => $value) {
            $bundle = $key;

            $definitions = $this->entityFieldManager->getFieldDefinitions($entityType, $bundle);
            foreach ($definitions as $fieldName => $fieldDefinition) {
              if (!empty($fieldDefinition->getTargetBundle())) {
                $allFields[$value['label']][$bundle . '_' . $fieldName] = $value['label'] . ':' . $fieldDefinition->getLabel();
              }
            }
          }
        }
      }
    }

    return $allFields;
  }

}
