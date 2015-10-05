<?php

/**
 * @file
 * Contains \Drupal\layout_plugin\Plugin\Layout\LayoutPluginManager.
 */

namespace Drupal\layout_plugin\Plugin\Layout;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Plugin\Discovery\YamlDiscoveryDecorator;

/**
 * Plugin type manager for all layouts.
 */
class LayoutPluginManager extends DefaultPluginManager implements LayoutPluginManagerInterface {


  /**
   * The theme handler.
   *
   * @var \Drupal\Core\Extension\ThemeHandlerInterface
   */
  protected $themeHandler;

  /**
   * Constructs a LayoutPluginManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $theme_handler
   *   The theme handle to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler, ThemeHandlerInterface $theme_handler) {
    $plugin_interface = 'Drupal\layout_plugin\Plugin\Layout\LayoutInterface';
    $plugin_definition_annotation_name = 'Drupal\layout_plugin\Annotation\Layout';
    parent::__construct("Plugin/Layout", $namespaces, $module_handler, $plugin_interface, $plugin_definition_annotation_name);
    $discovery = $this->getDiscovery();
    $this->discovery = new YamlDiscoveryDecorator($discovery, 'layouts', $module_handler->getModuleDirectories() + $theme_handler->getThemeDirectories());
    $this->themeHandler = $theme_handler;

    $this->defaults += array(
      'type' => 'page',
      // Used for plugins defined in layouts.yml that do not specify a class
      // themselves.
      'class' => 'Drupal\layout_plugin\Plugin\Layout\LayoutDefault',
    );

    $this->setCacheBackend($cache_backend, 'layout');
    $this->alterInfo('layout');
  }

  /**
   * {@inheritdoc}
   */
  protected function providerExists($provider) {
    return $this->moduleHandler->moduleExists($provider) || $this->themeHandler->themeExists($provider);
  }

  /**
   * {@inheritdoc}
   */
  public function processDefinition(&$definition, $plugin_id) {
    parent::processDefinition($definition, $plugin_id);

    // Add the module or theme path to the 'path'.
    if ($this->moduleHandler->moduleExists($definition['provider'])) {
      $base_path = $this->moduleHandler->getModule($definition['provider'])->getPath();
    }
    elseif ($this->themeHandler->themeExists($definition['provider'])) {
      $base_path = $this->themeHandler->getTheme($definition['provider'])->getPath();
    }
    else {
      $base_path = '';
    }
    $definition['path'] = !empty($definition['path']) ? $base_path . '/' . $definition['path'] : $base_path;

    // Add the path to the icon filename.
    if (!empty($definition['icon'])) {
      $definition['icon'] = $definition['path'] . '/' . $definition['icon'];
    }

    // If 'template' is set, then we'll derive 'template_path' and 'theme'.
    if (!empty($definition['template'])) {
      $template_parts = explode('/', $definition['template']);

      $definition['template'] = array_pop($template_parts);
      $definition['theme'] = strtr($definition['template'], '-', '_');
      $definition['template_path'] = $definition['path'];
      if (count($template_parts) > 0) {
        $definition['template_path'] .= '/' . implode('/', $template_parts);
      }
    }

    // If 'css' is set, then we'll derive 'library'.
    if (!empty($definition['css'])) {
      $definition['css'] = $definition['path'] . '/' . $definition['css'];
      $definition['library'] = 'layout_plugin/' . $plugin_id;
    }

    // Generate the 'region_names' key from the 'regions' key.
    $definition['region_names'] = array();
    if (!empty($definition['regions']) && is_array($definition['regions'])) {
      foreach ($definition['regions'] as $region_id => $region_definition) {
        $definition['region_names'][$region_id] = $region_definition['label'];
      }
    }
  }

}
