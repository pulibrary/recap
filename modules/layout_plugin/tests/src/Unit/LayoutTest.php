<?php

/**
 * @file
 * Contains \Drupal\Tests\layout_plugin\Unit\LayoutTest.
 */

namespace Drupal\Tests\layout_plugin\Unit;

use Drupal\layout_plugin\Layout;
use Drupal\layout_plugin\Plugin\Layout\LayoutPluginManagerInterface;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the LayoutPluginManager.
 *
 * @coversDefaultClass \Drupal\layout_plugin\Layout
 *
 * @group LayoutPlugin
 */
class LayoutTest extends UnitTestCase {

  /**
   * Test getting layout options.
   *
   * @covers ::getLayoutOptions
   */
  public function testGetLayoutOptions() {
    /** @var LayoutPluginManagerInterface|\PHPUnit_Framework_MockObject_MockBuilder $layout_plugin */
    $layout_plugin = $this->getMock('Drupal\layout_plugin\Plugin\Layout\LayoutPluginManagerInterface');
    $layout_plugin->method('getDefinitions')
      ->willReturn([
        'simple_layout' => [
          'label' => 'Simple layout',
          'category' => 'Test layouts',
        ],
        'complex_layout' => [
          'label' => 'Complex layout',
          'category' => 'Test layouts',
        ],
      ]);

    $options = Layout::getLayoutOptions(array(), $layout_plugin);
    $this->assertEquals([
      'simple_layout' => 'Simple layout',
      'complex_layout' => 'Complex layout',
    ], $options);

    $options = Layout::getLayoutOptions(array('group_by_category' => TRUE), $layout_plugin);
    $this->assertEquals([
      'Test layouts' => [
        'simple_layout' => 'Simple layout',
        'complex_layout' => 'Complex layout',
      ],
    ], $options);
  }

  /**
   * Tests layout theme implementations.
   *
   * @covers ::getThemeImplementations
   */
  public function testGetThemeImplementations() {
    /** @var LayoutPluginManagerInterface|\PHPUnit_Framework_MockObject_MockBuilder $layout_plugin */
    $layout_plugin = $this->getMock('Drupal\layout_plugin\Plugin\Layout\LayoutPluginManagerInterface');
    $layout_plugin->method('getDefinitions')
      ->willReturn([
        // Should get template registered automatically.
        'simple_layout' => [
          'path' => 'modules/layout_plugin_test',
          'template_path' => 'modules/layout_plugin_test/templates',
          'template' => 'simple-layout',
          'theme' => 'simple_layout',
        ],
        // Shouldn't get registered automatically.
        'complex_layout' => [
          'path' => 'modules/layout_plugin_test',
          'theme' => 'complex_layout',
        ],
      ]);

    $theme_registry = Layout::getThemeImplementations($layout_plugin);
    $this->assertEquals([
      'simple_layout' => [
        'render element' => 'content',
        'template' => 'simple-layout',
        'path' => 'modules/layout_plugin_test/templates',
      ],
    ], $theme_registry);
  }

  /**
   * Tests layout plugin library info.
   *
   * @covers ::getLibraryInfo
   */
  public function testGetLibraryInfo() {
    /** @var LayoutPluginManagerInterface|\PHPUnit_Framework_MockObject_MockBuilder $layout_plugin */
    $layout_plugin = $this->getMock('Drupal\layout_plugin\Plugin\Layout\LayoutPluginManagerInterface');
    $layout_plugin->method('getDefinitions')
      ->willReturn([
        // Should get template registered automatically.
        'simple_layout' => [
          'css' => 'modules/layout_plugin_test/layouts/simple_layout/simple-layout.css',
          'library' => 'layout_plugin/simple_layout',
        ],
        'complex_layout' => [
          'library' => 'layout_plugin_test/complex_layout',
        ],
      ]);

    $library_info = Layout::getLibraryInfo($layout_plugin);
    $this->assertEquals([
      'simple_layout' => [
        'version' => 'VERSION',
        'css' => [
          'theme' => [
            '/modules/layout_plugin_test/layouts/simple_layout/simple-layout.css' => [],
          ],
        ],
      ],
    ], $library_info);
  }

}
