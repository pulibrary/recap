<?php

/**
 * @file
 * Contains \Drupal\Tests\layout_plugin\Unit\PluginManagerTest.
 */

namespace Drupal\Tests\layout_plugin\Unit;

use Drupal\layout_plugin\Plugin\Layout\LayoutPluginManager;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the LayoutPluginManager.
 *
 * @coversDefaultClass \Drupal\layout_plugin\Plugin\Layout\LayoutPluginManager
 *
 * @group LayoutPlugin
 */
class PluginManagerTest extends UnitTestCase {

  /**
   * Test processDefinition.
   *
   * @covers ::processDefinition
   */
  public function testProcessDefinition() {
    $namespaces = new \ArrayObject();
    $namespaces['Drupal\layout_plugin_test'] = $this->root . '/modules/layout_plugin_test/src';

    $cache_backend = $this->getMock('Drupal\Core\Cache\CacheBackendInterface');

    $module_handler = $this->getMock('Drupal\Core\Extension\ModuleHandlerInterface');
    $module_handler->method('getModuleDirectories')->willReturn(array());
    $module_handler->method('moduleExists')->willReturn(TRUE);
    $extension = $this->getMockBuilder('Drupal\Core\Extension\Extension')
      ->disableOriginalConstructor()
      ->getMock();
    $extension->method('getPath')->willReturn('modules/layout_plugin_test');
    $module_handler->method('getModule')->willReturn($extension);

    $theme_handler = $this->getMock('Drupal\Core\Extension\ThemeHandlerInterface');
    $theme_handler->method('getThemeDirectories')->willReturn(array());

    $plugin_manager = new LayoutPluginManager($namespaces, $cache_backend, $module_handler, $theme_handler);

    // A simple definition with only the required keys.
    $definition = [
      'label' => 'Simple layout',
      'category' => 'Test layouts',
      'theme' => 'simple_layout',
      'provider' => 'layout_plugin_test',
      'regions' => [
        'first' => ['label' => 'First region'],
        'second' => ['label' => 'Second region'],
      ],
    ];
    $plugin_manager->processDefinition($definition, 'simple_layout');
    $this->assertEquals('modules/layout_plugin_test', $definition['path']);
    $this->assertEquals([
      'first' => 'First region',
      'second' => 'Second region'
    ], $definition['region_names']);

    // A more complex definition.
    $definition = [
      'label' => 'Complex layout',
      'category' => 'Test layouts',
      'template' => 'complex-layout',
      'provider' => 'layout_plugin_test',
      'path' => 'layout/complex',
      'icon' => 'complex-layout.png',
      'regions' => [
        'first' => ['label' => 'First region'],
        'second' => ['label' => 'Second region'],
      ],
    ];
    $plugin_manager->processDefinition($definition, 'complex_layout');
    $this->assertEquals('modules/layout_plugin_test/layout/complex', $definition['path']);
    $this->assertEquals('modules/layout_plugin_test/layout/complex', $definition['template_path']);
    $this->assertEquals('modules/layout_plugin_test/layout/complex/complex-layout.png', $definition['icon']);
    $this->assertEquals('complex_layout', $definition['theme']);

    // A layout with a template path.
    $definition = [
      'label' => 'Split layout',
      'category' => 'Test layouts',
      'template' => 'templates/split-layout',
      'provider' => 'layout_plugin_test',
      'path' => 'layouts',
      'icon' => 'images/split-layout.png',
      'regions' => [
        'first' => ['label' => 'First region'],
        'second' => ['label' => 'Second region'],
      ],
    ];
    $plugin_manager->processDefinition($definition, 'split_layout');
    $this->assertEquals('modules/layout_plugin_test/layouts', $definition['path']);
    $this->assertEquals('modules/layout_plugin_test/layouts/templates', $definition['template_path']);
    $this->assertEquals('modules/layout_plugin_test/layouts/images/split-layout.png', $definition['icon']);
    $this->assertEquals('split_layout', $definition['theme']);

    // A layout with an auto-registered library.
    $definition = [
      'label' => 'Auto library',
      'category' => 'Test layouts',
      'theme' => 'auto_library',
      'provider' => 'layout_plugin_test',
      'path' => 'layouts/auto_library',
      'css' => 'css/auto-library.css',
      'regions' => [
        'first' => ['label' => 'First region'],
        'second' => ['label' => 'Second region'],
      ],
    ];
    $plugin_manager->processDefinition($definition, 'auto_library');
    $this->assertEquals('modules/layout_plugin_test/layouts/auto_library/css/auto-library.css', $definition['css']);
    $this->assertEquals('layout_plugin/auto_library', $definition['library']);
  }

}
