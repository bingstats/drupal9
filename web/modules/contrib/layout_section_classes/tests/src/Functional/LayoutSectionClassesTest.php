<?php

namespace Drupal\Tests\layout_section_classes\Functional;

use Drupal\layout_builder\Plugin\SectionStorage\OverridesSectionStorage;
use Drupal\layout_builder\Section;
use Drupal\Tests\layout_builder\Kernel\LayoutBuilderCompatibilityTestBase;

/**
 * Test layout section classes.
 *
 * @group layout_section_classes
 */
class LayoutSectionClassesTest extends LayoutBuilderCompatibilityTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'layout_builder',
    'layout_section_classes',
    'layout_section_classes_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installLayoutBuilder();
    $this->enableOverrides();
  }

  /**
   * Test the layout section classes.
   */
  public function testLayoutSectionClasses() {
    $sections = $this->entity->get(OverridesSectionStorage::FIELD_NAME);
    $sections->appendSection(new Section('test_layout', [
      'additional' => [
        'classes' => [
          'style' => 'background--wave-dark background--primary-light',
        ],
      ],
    ]));
    $this->entity->save();

    $this->assertContains('background--wave-dark background--primary-light', $this->renderEntity());
  }

  /**
   * Render the test entity.
   *
   * @return string
   *   The test entity.
   */
  protected function renderEntity() {
    $view_builder = $this->container->get('entity_type.manager')->getViewBuilder($this->entity->getEntityTypeId());
    $build = $view_builder->view($this->entity);
    return $this->render($build);
  }

}
