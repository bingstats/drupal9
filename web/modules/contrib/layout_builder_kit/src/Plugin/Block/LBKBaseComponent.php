<?php

namespace Drupal\layout_builder_kit\Plugin\Block;

use Drupal\Component\Transliteration\TransliterationInterface;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Entity\EntityTypeBundleInfo;
use Drupal\Core\Entity\Plugin\DataType\ConfigEntityAdapter;
use Drupal\Core\Entity\Plugin\DataType\EntityAdapter;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\Context\EntityContext;
use Drupal\Core\Plugin\ContextAwarePluginAssignmentTrait;
use Drupal\Core\Plugin\ContextAwarePluginBase;
use Drupal\Core\Plugin\PluginWithFormsInterface;
use Drupal\Core\Plugin\PluginWithFormsTrait;
use Drupal\Core\Render\PreviewFallbackInterface;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Session\AccountInterface;
use Drupal\layout_builder\Entity\LayoutBuilderEntityViewDisplay;
use Drupal\layout_builder\Plugin\SectionStorage\DefaultsSectionStorage;
use Drupal\layout_builder\Plugin\SectionStorage\OverridesSectionStorage;
use Symfony\Component\Routing\Route;

/**
 * Defines a base block implementation that most blocks plugins will extend.
 *
 * This abstract class provides the generic block configuration form, default
 * block settings, and handling for general user-defined block visibility
 * settings.
 *
 * @ingroup block_api
 */
abstract class LBKBaseComponent extends ContextAwarePluginBase implements BlockPluginInterface, PluginWithFormsInterface, PreviewFallbackInterface, ContainerFactoryPluginInterface {

  use ContextAwarePluginAssignmentTrait;
  use MessengerTrait;
  use PluginWithFormsTrait;

  /**
   * The transliteration service.
   *
   * @var \Drupal\Component\Transliteration\TransliterationInterface
   */
  protected $transliteration;

  /**
   * Drupal\Core\Routing\CurrentRouteMatch class.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  private $currentRouteMatch;

  /**
   * Drupal\Core\Entity\EntityTypeBundleInfo class.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfo
   */
  protected $entityTypeBundleInfo;

  /**
   * {@inheritdoc}
   * @param \Drupal\Core\Routing\CurrentRouteMatch $currentRouteMatch
   *   The CurrentRouteMatch service.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfo $entityTypeBundleInfo
   *   The EntityTypeBundleInfo service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, CurrentRouteMatch $currentRouteMatch, EntityTypeBundleInfo $entityTypeBundleInfo) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->setConfiguration($configuration);
    $this->currentRouteMatch = $currentRouteMatch;
    $this->entityTypeBundleInfo = $entityTypeBundleInfo;
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    if (!empty($this->configuration['title'])) {
      return $this->configuration['title'];
    }

    $definition = $this->getPluginDefinition();
    // Cast the admin label to a string since it is an object.
    // @see \Drupal\Core\StringTranslation\TranslatableMarkup
    return (string) $definition['admin_label'];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $this->configuration = NestedArray::mergeDeep(
      $this->baseConfigurationDefaults(),
      $this->defaultConfiguration(),
      $configuration
    );
  }

  /**
   * Returns generic default configuration for block plugins.
   *
   * @return array
   *   An associative array with the default configuration.
   */
  protected function baseConfigurationDefaults() {
    return [
      'id' => $this->getPluginId(),
      // 'label' => '',
      // 'label_display' => static::BLOCK_LABEL_VISIBLE,.
      'provider' => $this->pluginDefinition['provider'],
      'title' => '',
      'display_title' => TRUE,
      'classes' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function setConfigurationValue($key, $value) {
    $this->configuration[$key] = $value;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function access(AccountInterface $account, $return_as_object = FALSE) {
    $access = $this->blockAccess($account);
    return $return_as_object ? $access : $access->isAllowed();
  }

  /**
   * Indicates whether the block should be shown.
   *
   * Blocks with specific access checking should override this method rather
   * than access(), in order to avoid repeating the handling of the
   * $return_as_object argument.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user session for which to check access.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The access result.
   *
   * @see self::access()
   */
  protected function blockAccess(AccountInterface $account) {
    // By default, the block is visible.
    return AccessResult::allowed();
  }

  /**
   * {@inheritdoc}
   *
   * Creates a generic configuration form for all block types. Individual
   * block plugins can add elements to this form by overriding
   * BlockBase::blockForm(). Most block plugins should not override this
   * method unless they need to alter the generic form elements.
   *
   * @see \Drupal\Core\Block\BlockBase::blockForm()
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $definition = $this->getPluginDefinition();
    $form['provider'] = [
      '#type' => 'value',
      '#value' => $definition['provider'],
    ];

    $form['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title'),
      '#maxlength' => 255,
      '#default_value' => $this->label(),
      '#required' => TRUE,
      '#weight' => '-2',
      '#prefix' => '<div class="lbk-header--component">',
    ];

    $form['display_title'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display title'),
      '#default_value' => $this->configuration['display_title'],
      '#weight' => '-1',
      '#suffix' => '</div>',
    ];

    $form['classes'] = [
      '#type' => 'textfield',
      '#title' => $this->t('CSS class'),
      '#description' => $this->t('A list of CSS classes added to this component separated by spaces.'),
      '#maxlength' => 64,
      '#default_value' => $this->configuration['classes'],
      '#weight' => '500',
    ];

    // Add context mapping UI form elements.
    $contexts = $form_state->getTemporaryValue('gathered_contexts') ?: [];
    $form['context_mapping'] = $this->addContextAssignmentElement($this, $contexts);
    // Add plugin-specific settings for this block type.
    $form += $this->blockForm($form, $form_state);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    return [];
  }

  /**
   * {@inheritdoc}
   *
   * Most block plugins should not override this method. To add validation
   * for a specific block type, override BlockBase::blockValidate().
   *
   * @see \Drupal\Core\Block\BlockBase::blockValidate()
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    // Remove the admin_label form item element value so it will not persist.
    $form_state->unsetValue('admin_label');

    $this->blockValidate($form, $form_state);
  }

  public function build() {
    $build['#title'] = $this->configuration['title'];

    // Checks if user is inside any layout page.
    $routeObject = $this->currentRouteMatch->getRouteObject();
    if($routeObject instanceof Route) {
      $options = $routeObject->getOptions();
    }

    if (isset($options['_layout_builder'])) {
      $layoutBuilder = $options['_layout_builder'];
      if ($layoutBuilder && !$this->configuration['display_title']) {
        $build['#display_title'] = TRUE;
      }
    }
    else {
      $build['#display_title'] = $this->configuration['display_title'];
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function blockValidate($form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   *
   * Most block plugins should not override this method. To add submission
   * handling for a specific block type, override BlockBase::blockSubmit().
   *
   * @see \Drupal\Core\Block\BlockBase::blockSubmit()
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    // Process the block's submission handling if no errors occurred only.
    if (!$form_state->getErrors()) {
      $this->configuration['title'] = $form_state->getValue('title');
      $this->configuration['display_title'] = $form_state->getValue('display_title');
      $this->configuration['classes'] = $form_state->getValue('classes');
      $this->configuration['provider'] = $form_state->getValue('provider');
      $this->blockSubmit($form, $form_state);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {}

  /**
   * {@inheritdoc}
   */
  public function getMachineNameSuggestion() {
    $definition = $this->getPluginDefinition();
    $admin_label = $definition['admin_label'];

    // @todo This is basically the same as what is done in
    //   \Drupal\system\MachineNameController::transliterate(), so it might make
    //   sense to provide a common service for the two.
    $transliterated = $this->transliteration()->transliterate($admin_label, LanguageInterface::LANGCODE_DEFAULT, '_');
    $transliterated = mb_strtolower($transliterated);

    $transliterated = preg_replace('@[^a-z0-9_.]+@', '', $transliterated);

    return $transliterated;
  }

  /**
   * {@inheritdoc}
   */
  public function getPreviewFallbackString() {
    return $this->t('"@block" block', ['@block' => $this->label()]);
  }

  /**
   * Wraps the transliteration service.
   *
   * @return \Drupal\Component\Transliteration\TransliterationInterface
   *   The transliteration service.
   */
  protected function transliteration() {
    if (!$this->transliteration) {
      $this->transliteration = \Drupal::transliteration();
    }
    return $this->transliteration;
  }

  /**
   * Sets the transliteration service.
   *
   * @param \Drupal\Component\Transliteration\TransliterationInterface $transliteration
   *   The transliteration service.
   */
  public function setTransliteration(TransliterationInterface $transliteration) {
    $this->transliteration = $transliteration;
  }

  /**
   * Get node from section (won't work in Layout Builder if not from section).
   *
   * @param $currentRouteMatch
   * @return array
   *   Return an array.
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  protected static function getContextNode($currentRouteMatch) {
    $contentContext = [];

    if ($currentRouteMatch->getParameter('node')) {
      $contentContext['node'] = $currentRouteMatch->getParameter('node');
      $contentContext['source'] = 'route';
      $contentContext['bundle'] = $contentContext['node']->getType();
    }
    else {
      // Individual layout.
      $sectionStorage = $currentRouteMatch->getParameters()->get('section_storage');
      if($sectionStorage instanceof OverridesSectionStorage) {
        $context = $sectionStorage->getContext('entity');
        if($context instanceof EntityContext) {
          $contextData = $context->getContextData();
          if($contextData instanceof EntityAdapter) {
            $contentContext['node'] = $contextData->getEntity();
            $contentContext['source'] = 'individual_layout';
            $contentContext['bundle'] = $contentContext['node']->getType();
          }
        }
      }
      else {
        // Content type layout.
        if ($sectionStorage instanceof DefaultsSectionStorage) {
          $availableContexts = $sectionStorage->getContexts();
          $contextDisplay = $availableContexts['display'];
          if ($contextDisplay instanceof EntityContext) {
            $contextData = $contextDisplay->getContextData();
            if ($contextData instanceof ConfigEntityAdapter) {
              $contentInfo = $contextData->getEntity();
              if ($contentInfo instanceof LayoutBuilderEntityViewDisplay) {
                $contentContext['source'] = 'entity_type_layout';
                $contentContext['bundle'] = $contentInfo->getTargetBundle();
              }
            }
          }
        }
      }
    }
    return $contentContext;
  }

  /**
   * Get machine name of field.
   *
   * @param string $fieldName
   *   The fieldName concatenated with bundle.
   *
   * @return mixed|string
   *   Return the machine name.
   */
  protected function getField($fieldName) {
    $machineName = '';
    // Get fields for all bundles.
    $entityType = "node";
    $contentTypes = $this->entityTypeBundleInfo->getBundleInfo($entityType);

    foreach ($contentTypes as $key => $value) {
      if (strpos($fieldName, $key . '_') !== FALSE) {
        $machineName = str_replace($key . '_', '', $fieldName);
      }
    }

    return $machineName;
  }
}
