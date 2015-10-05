<?php
/**
 * @file
 * Contains \Drupal\layout_plugin\Plugin\Layout\LayoutPluginManager\Layout.
 */

namespace Drupal\layout_plugin;

use Drupal\layout_plugin\Plugin\Layout\LayoutPluginManagerInterface;

/**
 * Class Layout.
 */
class Layout {
  /**
   * Returns the plugin manager for the Layout plugin type.
   *
   * @return \Drupal\layout_plugin\Plugin\Layout\LayoutPluginManagerInterface
   *   Layout manager.
   */
  public static function layoutPluginManager() {
    return \Drupal::service('plugin.manager.layout_plugin');
  }

  /**
   * Return all available layout as an options array.
   *
   * If group_by_category option/parameter passed group the options by
   * category.
   *
   * @param array $params
   *   (optional) An associative array with the following keys:
   *   - group_by_category: (bool) If set to TRUE, return an array of arrays
   *   grouped by the category name; otherwise, return a single-level
   *   associative array.
   * @param \Drupal\layout_plugin\Plugin\Layout\LayoutPluginManagerInterface $layout_manager
   *   (optional) The layout plugin manager.
   *
   * @return array
   *   Layout options, as array.
   */
  public static function getLayoutOptions(array $params = [], LayoutPluginManagerInterface $layout_manager = NULL) {
    if (empty($layout_manager)) {
      $layout_manager = static::layoutPluginManager();
    }
    $group_by_category = !empty($params['group_by_category']);
    $plugins = $layout_manager->getDefinitions();

    // Sort the plugins first by category, then by label.
    $options = array();
    foreach ($plugins as $id => $plugin) {
      if ($group_by_category) {
        $category = isset($plugin['category']) ? $plugin['category'] : 'default';
        if (!isset($options[$category])) {
          $options[$category] = array();
        }
        $options[$category][$id] = $plugin['label'];
      }
      else {
        $options[$id] = $plugin['label'];
      }
    }

    return $options;
  }

  /**
   * Return theme implementations for layouts that give only a template.
   *
   * @param \Drupal\layout_plugin\Plugin\Layout\LayoutPluginManagerInterface $layout_manager
   *   (optional) The layout plugin manager.
   *
   * @return array
   *   An associative array of the same format as returned by hook_theme().
   *
   * @see hook_theme()
   */
  public static function getThemeImplementations(LayoutPluginManagerInterface $layout_manager = NULL) {
    if (empty($layout_manager)) {
      $layout_manager = static::layoutPluginManager();
    }
    $plugins = $layout_manager->getDefinitions();

    $theme_registry = [];
    foreach ($plugins as $id => $definition) {
      if (!empty($definition['template']) && !empty($definition['theme'])) {
        $theme_registry[$definition['theme']] = [
          'render element' => 'content',
          'template' => $definition['template'],
          'path' => $definition['template_path'],
        ];
      }
    }

    return $theme_registry;
  }

  /**
   * Return library info for layouts that want to automatically register CSS.
   *
   * @param \Drupal\layout_plugin\Plugin\Layout\LayoutPluginManagerInterface $layout_manager
   *   (optional) The layout plugin manager.
   *
   * @return array
   *   An associative array of the same format as returned by
   *   hook_library_info_build().
   *
   * @see hook_library_info_build()
   */
  public static function getLibraryInfo(LayoutPluginManagerInterface $layout_manager = NULL) {
    if (empty($layout_manager)) {
      $layout_manager = static::layoutPluginManager();
    }
    $plugins = $layout_manager->getDefinitions();

    $library_info = [];
    foreach ($plugins as $id => $definition) {
      if (!empty($definition['css']) && !empty($definition['library'])) {
        list ($library_module, $library_name) = explode('/', $definition['library']);

        // Make sure the library is from layout_plugin.
        if ($library_module != 'layout_plugin') {
          continue;
        }

        $library_info[$library_name] = [
          // @todo: Should be the version of the provider module or theme.
          'version' => 'VERSION',
          'css' => [
            'theme' => [
              '/' . $definition['css'] => [],
            ],
          ],
        ];
      }
    }

    return $library_info;
  }

}
