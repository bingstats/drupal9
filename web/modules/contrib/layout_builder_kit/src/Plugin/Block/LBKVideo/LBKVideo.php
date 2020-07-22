<?php

namespace Drupal\layout_builder_kit\Plugin\Block\LBKVideo;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Config\ConfigManagerInterface;
use Drupal\Core\Entity\EntityFieldManager;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldItemList;
use Drupal\Core\Field\TypedData\FieldItemDataDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\Context\EntityContext;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\entity\BundleFieldDefinition;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\FieldConfigInterface;
use Drupal\file\Entity\File;
use Drupal\layout_builder_kit\Plugin\Block\LBKBaseComponent;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;
use Drupal\Tests\Core\Entity\EntityTypeBundleInfoTest;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Drupal\layout_builder\Plugin\SectionStorage\DefaultsSectionStorage;
use Drupal\layout_builder\Plugin\SectionStorage\OverridesSectionStorage;
use Drupal\Core\Entity\Plugin\DataType\ConfigEntityAdapter;
use Drupal\Core\Entity\Plugin\DataType\EntityAdapter;
use Drupal\layout_builder\Entity\LayoutBuilderEntityViewDisplay;
use Drupal\Core\Entity\EntityTypeBundleInfo;
use Drupal\Core\Field\FieldDefinition;

/**
 * Provides a 'LBKVideo' block.
 *
 * @Block(
 *  id = "lbk_video",
 *  admin_label = @Translation("Video (LBK)"),
 * )
 */
class LBKVideo extends LBKBaseComponent implements ContainerFactoryPluginInterface {

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
   * Drupal\Core\Entity\EntityFieldManager definition.
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
   * Constructs a new video object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $pluginId
   *   The plugin_id for the plugin instance.
   * @param string $pluginDefinition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The EntityTypeManagerInterface service.
   * @param \Drupal\Core\Config\ConfigManagerInterface $configManager
   *   The ConfigManagerInterface service.
   * @param \Drupal\Core\Routing\CurrentRouteMatch $currentRouteMatch
   *   The CurrentRouteMatch service.
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
      EntityFieldManager $entityFieldManager,
      EntityTypeBundleInfo $entityTypeBundleInfo
  ) {
    parent::__construct($configuration, $pluginId, $pluginDefinition, $currentRouteMatch, $entityTypeBundleInfo);
    $this->entityTypeManager = $entityTypeManager;
    $this->configManager = $configManager;
    $this->currentRouteMatch = $currentRouteMatch;
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
      $container->get('entity_field.manager'),
      $container->get('entity_type.bundle.info')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'video_component_fields' => [
        'video_url' => '',
        'video' => [],
        'video_field' => [],
        'video_radio_options' => [],
      ],
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $formState) {
    // Attach library to the Form.
    $form['#attached']['library'] = ['layout_builder_kit/video-styling'];
    $currentValue = $this->configuration['video_component_fields']['video_radio_options'];

    // Get default radio option.
    if (empty($currentValue)) $currentValue = 0;

    //$node = parent::getContextNode($this->currentRouteMatch);

    $form['video_radio_options'] = [
      '#type' => 'radios',
      '#default_value' => $currentValue,
      '#options' => [
        0 => $this->t('Video URL'),
        2 => $this->t('Video Fields'),
      ],
      '#id' => 'options_video',
      '#title_display' => $this->t('title'),
      '#weight' => 30,
      '#prefix' => '<div class="video-container"><div class="video-radios--options">',
      '#suffix' => '</div>',

    ];

    $form['video_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Video URL'),
      '#maxlength' => 255,
      '#default_value' => $this->configuration['video_component_fields']['video_url'],
      '#required' => FALSE,
      '#weight' => 40,
      '#prefix' => '<div class="video-fields--options">',
    ];

    $form['video_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Video fields available'),
      '#options' => $this->getVideosEmbedArray(),
      '#default_value' => $this->configuration['video_component_fields']['video_field'],
      '#weight' => 50,
      '#suffix' => '</div></div>',
    ];

    $form['#attached']['library'] = ['layout_builder_kit/video-styling'];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockValidate($form, FormStateInterface $form_state) {
    parent::blockValidate($form, $form_state);

    $link = $form_state->getValue('video_url');
    if (isset($link) && !empty($link)) {
      $isValid = UrlHelper::isValid($link, TRUE);
      if (!$isValid) $form_state->setErrorByName('link', $this->t('URL must be in form of http://www.url.com.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $formState) {
    $this->configuration['video_component_fields']['video_radio_options'] = $formState->getValue('video_radio_options');
    if ($formState->getValue('video_radio_options') == 0) {
      $this->configuration['video_component_fields']['video_url'] = $formState->getValue('video_url');
    }
    else {
      $this->configuration['video_component_fields']['video_field'] = $formState->getValue('video_field');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = parent::build();

    $build['#theme'] = 'LBKVideo';
    $build['#attached']['library'] = ['layout_builder_kit/video-styling'];

    // TODO: Remove this block in the future, it does nothing.
    if (!empty($this->configuration['video_component_fields']['video'])) {
      $imageFileId = implode($this->configuration['video_component_fields']['video']);
      $image = File::load($imageFileId);
      if ($image != NULL) {
        $image->setPermanent();
        $image->save();
      }
    }

    // Get bundle type.
    //$bundle = $this->getEntityType();
    $entityType = $this->getEntityType();

    //$node = NULL;
    $entity = NULL;
    $fieldOutput = [];
    // Render fields.
    // TODO: Add function for all kind of fields.
    //$field = $this->getField($this->configuration['video_component_fields']['video_field']);
    $field = $this->getBundleField($this->configuration['video_component_fields']['video_field'], $entityType);
    //$field = $this->configuration['video_component_fields']['video_field'];

    if (isset($field) && $field !== "" && $entityType !== "") {
      //$entityType = 'node';
      // Fields to render.
      $fields = [
        $field,
      ];

      // Get the Node entity object.
      if ($this->currentRouteMatch->getParameter($entityType)) {
        //$node = $this->currentRouteMatch->getParameter($entityType);
        $entity = $this->currentRouteMatch->getParameter($entityType);
      }
      else {
        $currentRoute = $this->currentRouteMatch;
        $sectionStorage = $currentRoute->getParameters()->get('section_storage');
        if ($sectionStorage instanceof OverridesSectionStorage) {
          $context = $sectionStorage->getContext('entity');
          if ($context instanceof EntityContext) {
            $contextData = $context->getContextData();
            if ($contextData instanceof EntityAdapter) {
              //$node = $contextData->getEntity();
              $entity = $contextData->getEntity();
            }
          }
        }
      }

      // Get the view for this Node entity object.
      $viewBuilder = $this->entityTypeManager->getViewBuilder($entityType);

      // Build the Node array to render.
      foreach ($fields as $field_name) {
        if ($entity instanceof Node) {
          if ($entity->hasField($field_name) && $entity->access('view')) {
            $value = $entity->get($field_name);
            if ($value instanceof FieldItemList) {
              if (!empty($value->getValue())) {
                $fieldOutput['value'] = $viewBuilder->viewField($value, ["label" => "hidden"]);
                $fieldOutput['value']['#cache']['tags'] = $entity->getCacheTags();
              } else {
                $fieldOutput['text'] = $this->t('This field is empty.');
              }
            }
          }
          else {
            $fieldOutput['text'] = $this->t('This field is not available for this content.');
          }
        }
        else {
          if ($entity instanceof Term) {
            if ($entity->hasField($field_name) && $entity->access('view')) {
              $value = $entity->get($field_name);
              if ($value instanceof FieldItemList) {
                if (!empty($value->getValue())) {
                  $fieldOutput['value'] = $viewBuilder->viewField($value, ["label" => "hidden"]);
                  $fieldOutput['value']['#cache']['tags'] = $entity->getCacheTags();
                } else {
                  $fieldOutput['text'] = $this->t('This field is empty.');
                }
              }
            }
            else {
              $fieldOutput['text'] = $this->t('This field is not available for this content.');
            }
          }
        }
      }
    } else {
      $fieldOutput['text'] = $this->t('Place this component inside a node page');
    }

    // Basic response at least handles tags.
    $build['#video_field'] = $fieldOutput;

    if (isset($build['#video_field']) && $entityType !== "") {
      //$test = $this->getField($field);
      $currentVideoFieldType = $this->getVideoFieldType($field, $entityType);
      $build['#field_type'] = $currentVideoFieldType;

      if ($entity instanceof Node) {
        switch ($currentVideoFieldType) {
          case "string":
          case "string_long":
            if (isset($fieldOutput['value'][0]['#context']['value'])) {
              $videoFieldPath = trim($fieldOutput['value'][0]['#context']['value']);
              if ($videoFieldPath) $build['#video_field']['value'] = $this->getVideoEmbedUrl($videoFieldPath);
            }
            break;

          case "text":
            if (isset($fieldOutput['value'][0]['#text'])) {
              $videoFieldPath = trim($fieldOutput['value'][0]['#text']);
              if ($videoFieldPath) $build['#video_field']['value'] = $this->getVideoEmbedUrl($videoFieldPath);
            }
            break;

          case "link":
            if (isset($fieldOutput['value'][0]['#url'])) {
              $videoFieldPath = $fieldOutput['value'][0]['#url']->getUri();
              $build['#video_field']['value'] = $this->getVideoEmbedUrl($videoFieldPath);
            }
            break;

          case "file":
            $filePath = $fieldOutput['value'][0]['#file'];
            if ($filePath) {
              $videoFieldUri = $filePath->getFileUri();
              $videoFieldPath = file_create_url($videoFieldUri);
              $build['#video_field']['value'] = $videoFieldPath;
            }
            break;

          case "video_embed_field":
            break;
        }
      }
      else {
        if ($entity instanceof Term) {
          switch ($currentVideoFieldType) {
            case "string":
            case "string_long":
              if (isset($fieldOutput['value'][0]['#context']['value'])) {
                $videoFieldPath = trim($fieldOutput['value'][0]['#context']['value']);
                if ($videoFieldPath) $build['#video_field']['value'] = $this->getVideoEmbedUrl($videoFieldPath);
              }
              break;

            case "text":
              if (isset($fieldOutput['value'][0]['#text'])) {
                $videoFieldPath = trim($fieldOutput['value'][0]['#text']);
                if ($videoFieldPath) $build['#video_field']['value'] = $this->getVideoEmbedUrl($videoFieldPath);
              }
              break;

            case "link":
              if (isset($fieldOutput['value'][0]['#url'])) {
                $videoFieldPath = $fieldOutput['value'][0]['#url']->getUri();
                $build['#video_field']['value'] = $this->getVideoEmbedUrl($videoFieldPath);
              }
              break;

            case "file":
              $filePath = $fieldOutput['value'][0]['#file'];
              if ($filePath) {
                $videoFieldUri = $filePath->getFileUri();
                $videoFieldPath = file_create_url($videoFieldUri);
                $build['#video_field']['value'] = $videoFieldPath;
              }
              break;

            case "video_embed_field":
              break;
          }
        }
      }
    }

    $build['#video_radio_options'] = $this->configuration['video_component_fields']['video_radio_options'];
    $build['#video_url'] = $this->getVideoEmbedUrl($this->configuration['video_component_fields']['video_url']);
    $build['#classes'] = $this->configuration['classes'];

    return $build;
  }

  /**
   * Get fields from content type.
   *
   * @param string $contentType
   *   The Content Type.
   *
   * @return array
   *   Return an array of fields.
   */
  public function getContentTypeFields($contentType) {
    $fields = [];

    if (!empty($contentType)) {
      $fields = array_filter(
          $this->entityFieldManager->getFieldDefinitions('node', $contentType), function ($fieldDefinition) {
            return $fieldDefinition instanceof FieldConfigInterface;
          }
      );
    }

    return $fields;
  }

  /**
   * Get videos embed array.
   *
   * @return array
   *   The video array.
   */
  public function getVideosEmbedArray() {
    $allFields = [];
    // Get the Current Route Match.
    $currentRoute = $this->currentRouteMatch;
    if ($currentRoute instanceof CurrentRouteMatch) {
      $sectionStorage = $currentRoute->getParameters()->get('section_storage');
      // For content types without bundles.
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
                  switch ($fieldDefinition->getType()) {
                    case "string":
                    case "string_long":
                    case "text":
                    case "link":
                    case "file":
                    case "video_embed_field":
                      $allFields[$bundle][$fieldName] = $fieldDefinition->getLabel();
                      break;
                  }
                }
              }
            }
          }
        }
      }
      else {
        // For bundles (Layout Builder overrides).
        if ($sectionStorage instanceof OverridesSectionStorage) {
          $context = $sectionStorage->getContext('entity');
          if ($context instanceof EntityContext) {
            $contextData = $context->getContextData();
            if ($contextData instanceof EntityAdapter) {
              $entity = $contextData->getEntity();

              $entity_type = 'taxonomy_term';
              if ($entity instanceof Node) $entity_type = 'node';
              $bundle = $entity->bundle();

              $definitions = $this->entityFieldManager->getFieldDefinitions($entity_type, $bundle);
              foreach ($definitions as $fieldName => $fieldDefinition) {
                if (!empty($fieldDefinition->getTargetBundle()) && $fieldName !== 'layout_builder__layout') {
                  switch ($fieldDefinition->getType()) {
                    case "string":
                    case "string_long":
                    case "text":
                    case "link":
                    case "file":
                    case "video_embed_field":
                      $allFields[$bundle][$fieldName] = $fieldDefinition->getLabel();
                      break;
                  }
                }
              }
            }
          }
        }
        else {
          // Get fields for all bundles(Block Layout).
          $entityType = "node";

          $contentTypes = $this->entityTypeBundleInfo->getBundleInfo($entityType);

          foreach ($contentTypes as $key => $value) {
            $bundle = $key;

            $definitions = $this->entityFieldManager->getFieldDefinitions($entityType, $bundle);
            foreach ($definitions as $fieldName => $fieldDefinition) {
              if (!empty($fieldDefinition->getTargetBundle())) {
                switch ($fieldDefinition->getType()) {
                  case "string":
                  case "string_long":
                  case "text":
                  case "link":
                  case "file":
                  case "video_embed_field":
                    $allFields[$value['label']][$bundle . '_' . $fieldName] = $fieldDefinition->getLabel();
                    break;
                }
              }
            }
          }
        }
      }
    }

    return $allFields;
  }

  /**
   * Get video embed URL.
   *
   * @param string $videoUrl
   *   The Video URL.
   *
   * @return string
   *   The URL.
   */
  public function getVideoEmbedUrl($videoUrl) {
    $finalVideoUrl = '';

    if (strpos($videoUrl, 'vimeo.com/') !== FALSE) {

      // It is Vimeo video.
      $videoId = explode("vimeo.com/", $videoUrl)[1];
      if (strpos($videoId, '&') !== FALSE) {
        $videoId = explode("&", $videoId)[0];
      }
      $finalVideoUrl .= 'https://player.vimeo.com/video/' . $videoId;
    }
    elseif (strpos($videoUrl, 'youtube.com/watch') !== FALSE) {

      // It is Youtube video.
      $videoId = explode("v=", $videoUrl)[1];
      if (strpos($videoId, '&') !== FALSE) {
        $videoId = explode("&", $videoId)[0];
      }
      $finalVideoUrl .= 'https://www.youtube.com/embed/' . $videoId;
    }
    elseif (strpos($videoUrl, 'youtu.be/') !== FALSE) {

      // It is Youtube video.
      $videoId = explode("youtu.be/", $videoUrl)[1];
      if (strpos($videoId, '&') !== FALSE) {
        $videoId = explode("&", $videoId)[0];
      }
      $finalVideoUrl .= 'https://www.youtube.com/embed/' . $videoId;
    }
    elseif (strpos($videoUrl, 'youtube.com/embed') !== FALSE) {

      // It is a valid Youtube embed video.
      $finalVideoUrl .= $videoUrl;

    }

    return $finalVideoUrl;
  }

  /**
   * Get video field type.
   *
   * @param string $fieldName
   *   The field machine name value.
   *
   * @return string
   *   The video field type.
   */
  public function getVideoFieldType($fieldName, $entityType) {
    $fieldType = '';
    if (isset($fieldName) && !empty($fieldName)) {
      $fieldConfig = FieldStorageConfig::loadByName($entityType, $fieldName);
      $fieldType = $fieldConfig->getType();
    }
    return $fieldType;
  }

  public function getEntityType() {
    $entityType = '';
    $currentRoute = $this->currentRouteMatch;
    if ($currentRoute instanceof CurrentRouteMatch) {
      $sectionStorage = $currentRoute->getParameters()->get('section_storage');
      // For content types without bundles.
      if ($sectionStorage instanceof DefaultsSectionStorage) {
        $availableContexts = $sectionStorage->getContexts();
        $contextDisplay = $availableContexts['display'];
        if ($contextDisplay instanceof EntityContext) {
          $contextData = $contextDisplay->getContextData();
          if ($contextData instanceof ConfigEntityAdapter) {
            $contentInfo = $contextData->getEntity();
            if ($contentInfo instanceof LayoutBuilderEntityViewDisplay) {
              // Bundle 'page'.
              //$entityType = $contentInfo->getEntityTypeId();
              $entityType = $contentInfo->getTargetEntityTypeId();
            }
          }
        }
      }
      else {
        // For bundles (Layout Builder overrides).
        if ($sectionStorage instanceof OverridesSectionStorage) {
          $context = $sectionStorage->getContext('entity');
          if ($context instanceof EntityContext) {
            $contextData = $context->getContextData();
            if ($contextData instanceof EntityAdapter) {
              $entity = $contextData->getEntity();

              if ($entity instanceof Node) {
                $entityType = $entity->getEntityTypeId();
              }
              else {
                if ($entity instanceof Term) {
                  $entityType = $entity->getEntityTypeId();
                }
              }
            }
          }
        }
        else {
          // Content page is built.
          if ($currentRoute->getParameters()->get('node')) {
            $entityType = $currentRoute->getParameters()->get('node')->getEntityTypeId();
          }
          else if($currentRoute->getParameters()->get('taxonomy_term')) {
            $entityType = $currentRoute->getParameters()->get('taxonomy_term')->getEntityTypeId();
          }
        }
      }
    }

    return $entityType;
  }

  public function getBundleField($fieldName, $entityType) {
    $machineName = '';
    // Get fields for all bundles.
    //$entityType = "node";
    $contentTypes = $this->entityTypeBundleInfo->getBundleInfo($entityType);

    // TODO: Refactor this for all contexts.

    foreach ($contentTypes as $key => $value) {
      if (strpos($fieldName, $key . '_') !== FALSE) {
        $machineName = str_replace($key . '_', '', $fieldName);
      }
    }
    if ($machineName === '')
      $machineName = $fieldName;

    return $machineName;
  }

}
