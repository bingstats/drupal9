<?php

namespace Drupal\Tests\layout_bg\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Testing for layout_bg module.
 *
 * @group layout_bg
 */
class LayoutBgTest extends BrowserTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = ['layout_bg_tests'];

  /**
   * The theme to install as the default for testing.
   *
   * Defaults to the install profile's default theme, if it specifies any.
   *
   * @var string
   */
  protected $defaultTheme = 'classy';

  /**
   * Tests html output of layout_bg sections.
   */
  public function testLayoutBgMarkup() {
    $this->drupalGet('/node/1');

    // Node 1 has only a hero image, which is the fallback.
    $session = $this->assertSession();
    // One-column is first.
    $session->elementTextContains('css', '.layout-bg-section:first-child .layout-bg-row .layout-bg-content-container.center-content.set-text-color .layout__region--content', 'This is the first node');
    $session->elementTextContains('css', '.layout-bg-section:first-child .layout-bg-row .layout-bg-content-container.center-content.set-text-color .layout__region--content', 'Here is a short bit of text.');
    $session->elementExists('css', '.layout-bg-section:first-child .layout-bg-row .layout-bg-bg-container .layout-bg-bg-wrapper img[alt="Node one should have alt text."]');
    // Two-column is last.
    $session->elementTextContains('css', '.layout-bg-section:last-child .layout-bg-row .layout-bg-content-container.center-content.set-text-color .layout--twocol-section .layout__region--first', 'This is the first node');
    $session->elementTextContains('css', '.layout-bg-section:last-child .layout-bg-row .layout-bg-content-container.center-content.set-text-color .layout--twocol-section .layout__region--second', 'Here is a short bit of text.');
    $session->elementExists('css', '.layout-bg-section:last-child .layout-bg-row .layout-bg-bg-container .layout-bg-bg-wrapper img[alt="Node one should have alt text."]');

    $this->drupalGet('/node/2');
    // Node two has only a teaser image, which is first choice for background.
    $session = $this->assertSession();
    // One-column is first.
    $session->elementTextContains('css', '.layout-bg-section:first-child .layout-bg-row .layout-bg-content-container.center-content.set-text-color .layout__region--content', 'Another node please!');
    $session->elementTextContains('css', '.layout-bg-section:first-child .layout-bg-row .layout-bg-content-container.center-content.set-text-color .layout__region--content', 'Another node, another bit of text.');
    $session->elementExists('css', '.layout-bg-section:first-child .layout-bg-row .layout-bg-bg-container .layout-bg-bg-wrapper img[alt="Node two should have alt text."]');
    // Two-column is last.
    $session->elementTextContains('css', '.layout-bg-section:last-child .layout-bg-row .layout-bg-content-container.center-content.set-text-color .layout--twocol-section .layout__region--first', 'Another node please!');
    $session->elementTextContains('css', '.layout-bg-section:last-child .layout-bg-row .layout-bg-content-container.center-content.set-text-color .layout--twocol-section .layout__region--second', 'Another node, another bit of text.');
    $session->elementExists('css', '.layout-bg-section:last-child .layout-bg-row .layout-bg-bg-container .layout-bg-bg-wrapper img[alt="Node two should have alt text."]');

    $this->drupalGet('/node/3');
    // Node 3 has both images. Make sure only the teaser image renders.
    $session = $this->assertSession();
    // One-column is first.
    $session->elementTextContains('css', '.layout-bg-section:first-child .layout-bg-row .layout-bg-content-container.center-content.set-text-color .layout__region--content', 'One more!');
    $session->elementTextContains('css', '.layout-bg-section:first-child .layout-bg-row .layout-bg-content-container.center-content.set-text-color .layout__region--content', 'All for one and one for all.');
    $session->elementExists('css', '.layout-bg-section:first-child .layout-bg-row .layout-bg-bg-container .layout-bg-bg-wrapper img[alt="I want this alt text."]');
    $session->elementNotExists('css', '.layout-bg-section:first-child .layout-bg-row .layout-bg-bg-container .layout-bg-bg-wrapper img[alt="I do not want this text."]');
    // Two-column is last.
    $session->elementTextContains('css', '.layout-bg-section:last-child .layout-bg-row .layout-bg-content-container.center-content.set-text-color .layout--twocol-section .layout__region--first', 'One more!');
    $session->elementTextContains('css', '.layout-bg-section:last-child .layout-bg-row .layout-bg-content-container.center-content.set-text-color .layout--twocol-section .layout__region--second', 'All for one and one for all.');
    $session->elementExists('css', '.layout-bg-section:last-child .layout-bg-row .layout-bg-bg-container .layout-bg-bg-wrapper img[alt="I want this alt text."]');
    $session->elementNotExists('css', '.layout-bg-section:last-child .layout-bg-row .layout-bg-bg-container .layout-bg-bg-wrapper img[alt="I do not want this text."]');

    // Now let's focus on results of configuration.
    // One-column overlay and style attributes.
    $session->elementAttributeContains('css', '.layout-bg-section:first-child .layout-bg-row .layout-bg-bg-container .layout-bg-bg-wrapper', 'style', 'background-color: #aaaaaa;');
    $session->elementExists('css', '.layout-bg-section:first-child .layout-bg-row .layout-bg-bg-container .layout-bg-bg-wrapper.static-image');
    $session->elementExists('css', '.layout-bg-section:first-child .layout-bg-row .layout-bg-bg-container .layout-bg-bg-wrapper + .layout-bg-overlay');
    $session->elementAttributeContains('css', '.layout-bg-section:first-child .layout-bg-row .layout-bg-bg-container .layout-bg-bg-wrapper + .layout-bg-overlay', 'style', 'background-color: #000000; opacity: 0.3;');
    $session->elementAttributeContains('css', '.layout-bg-section:first-child .layout-bg-row .layout-bg-content-container.center-content.set-text-color', 'style', 'color: #ffffff;');
    // Two-column overlay and style attributes.
    $session->elementAttributeContains('css', '.layout-bg-section:last-child .layout-bg-row .layout-bg-bg-container .layout-bg-bg-wrapper', 'style', 'background-color: #aaaaaa;');
    $session->elementExists('css', '.layout-bg-section:last-child .layout-bg-row .layout-bg-bg-container .layout-bg-bg-wrapper.static-image');
    $session->elementExists('css', '.layout-bg-section:last-child .layout-bg-row .layout-bg-bg-container .layout-bg-bg-wrapper + .layout-bg-overlay');
    $session->elementAttributeContains('css', '.layout-bg-section:last-child .layout-bg-row .layout-bg-bg-container .layout-bg-bg-wrapper + .layout-bg-overlay', 'style', 'background-color: #000000; opacity: 0.3;');
    $session->elementAttributeContains('css', '.layout-bg-section:last-child .layout-bg-row .layout-bg-content-container', 'style', 'color: #ffffff;');

    // Update configuration and check again.
    $config = $this->config('core.entity_view_display.node.layout_bg_test_node.default');
    $sections = $config->get('third_party_settings.layout_builder.sections');
    foreach ($sections as &$section) {
      $section['layout_settings']['add_overlay'] = FALSE;
      $section['layout_settings']['set_text_color'] = FALSE;
      $section['layout_settings']['static_image'] = FALSE;
    }
    $config->set('third_party_settings.layout_builder.sections', $sections);
    $config->save();

    // Refresh with updated config and see if stuff changed.
    // Overlay and style attributes should be gone.
    // BG wrapper should have absolute-image class.
    $this->drupalGet('/node/3');
    // One-column overlay and style attributes.
    $session->elementAttributeContains('css', '.layout-bg-section:first-child .layout-bg-row .layout-bg-bg-container .layout-bg-bg-wrapper', 'style', 'background-color: #aaaaaa;');
    $session->elementExists('css', '.layout-bg-section:first-child .layout-bg-row .layout-bg-bg-container .layout-bg-bg-wrapper.absolute-image');
    $session->elementNotExists('css', '.layout-bg-section:first-child .layout-bg-row .layout-bg-bg-container .layout-bg-bg-wrapper + .layout-bg-overlay');
    $session->elementExists('css', '.layout-bg-section:first-child .layout-bg-row .layout-bg-content-container:not(.center-content):not(.set-text-color)');
    $this->assertFalse($session->elementExists('css', '.layout-bg-section:first-child .layout-bg-row .layout-bg-content-container')->hasAttribute('style'));
    // Two-column overlay and style attributes.
    $session->elementAttributeContains('css', '.layout-bg-section:last-child .layout-bg-row .layout-bg-bg-container .layout-bg-bg-wrapper', 'style', 'background-color: #aaaaaa;');
    $session->elementExists('css', '.layout-bg-section:last-child .layout-bg-row .layout-bg-bg-container .layout-bg-bg-wrapper.absolute-image');
    $session->elementNotExists('css', '.layout-bg-section:last-child .layout-bg-row .layout-bg-bg-container .layout-bg-bg-wrapper + .layout-bg-overlay');
    $session->elementExists('css', '.layout-bg-section:last-child .layout-bg-row .layout-bg-content-container:not(.center-content):not(.set-text-color)');
    $this->assertFalse($session->elementExists('css', '.layout-bg-section:last-child .layout-bg-row .layout-bg-content-container')->hasAttribute('style'));
  }

}
