<?php

namespace Drupal\layout_builder_kit\Plugin\Block\LBKTab;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\hook_event_dispatcher\Event\Theme\ThemeEvent;
use Drupal\hook_event_dispatcher\HookEventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class LBKTabEventSubscriber.
 *
 * @package Drupal\layout_builder_kit
 */
class LBKTabEventSubscriber implements EventSubscriberInterface {

  /**
   * Logger Factory service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * LBKTabEventSubscriber constructor.
   */
  public function __construct(LoggerChannelFactoryInterface $loggerFactory) {
    $this->loggerFactory = $loggerFactory;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = [];

    $events[HookEventDispatcherInterface::THEME][] = ['themeEvent'];
    return $events;
  }

  /**
   * Theme event.
   *
   * @param \Drupal\hook_event_dispatcher\Event\Theme\ThemeEvent $event
   *   The event.
   */
  public function themeEvent(ThemeEvent $event) {
    $modulePath = drupal_get_path('module', 'layout_builder_kit');

    $newThemes = [
      'LBKTab' => [
        'template' => 'LBKTab',
        'render element' => 'content',
        'variables' => [
          'title' => NULL,
          'display_title' => NULL,
          'tabs_default_text' => NULL,
          'classes' => NULL,
          'tabs' => [],
        ],
        'path' => $modulePath . '/src/Plugin/Block/LBKTab',
      ],
    ];

    $event->addNewThemes($newThemes);
  }

}
