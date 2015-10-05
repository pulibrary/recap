<?php

/**
 * @file
 * Contains \Drupal\layout_plugin_example\Plugin\Layout\LayoutExampleTest.
 */

namespace Drupal\layout_plugin_example\Plugin\Layout;
use Drupal\layout_plugin\Plugin\Layout\LayoutBase;

/**
 * The plugin that handles the default layout template.
 *
 * @ingroup layout_template_plugins
 *
 * @Layout(
 *   id = "layout_example_test",
 *   label = @Translation("Test1"),
 *   description = @Translation("Test1 sample description"),
 *   type = "page",
 *   help = @Translation("Layout"),
 *   theme = "layout_example_test",
 *   regions = {
 *     "top" = {
 *       "label" = @Translation("Top Region"),
 *       "plugin_id" = "default"
 *     },
 *    "bottom" = {
 *       "label" = @Translation("Bottom Region"),
 *       "plugin_id" = "default"
 *     }
 *   }
 * )
 */
class LayoutExampleTest extends LayoutBase {
}
