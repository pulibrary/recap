<?php

/**
 * @file
 * Preprocessors and helper functions to make theming easier.
 */

use Drupal\Core\Template\Attribute;

/**
 * Prepares variables for views jssor rows templates.
 *
 * Default template: jssor.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - view: The view object.
 *   - rows: An array of row items. Each row is an array of content.
 */
function template_preprocess_jssor(&$variables) {
  $view = $variables['view'];
  $rows = $variables['rows'];

  $style = $view->style_plugin;

  // View's options.
  $options = $style->options;

  // Attach library.
  $view->element['#attached']['library'][] = 'jssor/jquery.jssor.slider';


  $transitions = array();
  $transition = array(
    '$Duration' => 2000,
    'y' => -1,
    '$Delay' => 60,
    '$Cols' => 16,
    '$SlideOut' => true,
    '$Formation' => '$JssorSlideshowFormations$.$FormationStraight',
    '$Easing' => '$JssorEasing$.$EaseOutJump',
    '$Round' => array(
      '$Top' => 1.5,
    ),
  );
  $transitions[] = $transition;

  // Attach settings.
  $view->element['#attached']['drupalSettings']['views']['jssorViews']['views_dom_id:' . $view->dom_id] = array(
    //'view_name' => $view->storage->id(),
    //'view_display_id' => $view->current_display,
    //'view_base_path' => $view->getPath(),
    'view_dom_id' => $view->dom_id,
    'transition' => $options['global']['transition'],
    '$AutoPlay' => ($options['global']['autoplay']) ? true : false,
    '$AutoPlayInterval' => (int) $options['global']['autoplayinterval'],
    '$AutoPlaySteps' => (int) $options['global']['autoplaysteps'],
    '$PauseOnHover' => (int) $options['global']['pauseonhover'],
    '$SlideshowOptions' => array(
      '$Class' => '$JssorSlideshowRunner',
      '$TransitionsOrder' => 1,
      '$ShowLink' => true,
    ),
    '$ArrowNavigatorOptions' => array(
      '$Class' => '$JssorArrowNavigator$',
      '$AutoCenter' => (int) $options['arrownavigator']['autocenter'],
      '$ChanceToShow' => (int) $options['arrownavigator']['chancetoshow'],
    ),
    '$BulletNavigatorOptions' => array(
      '$Class' => '$JssorBulletNavigator$',
      '$AutoCenter' =>  (int) $options['bulletnavigator']['autocenter'],
      '$ChanceToShow' => (int) $options['bulletnavigator']['chancetoshow'],
      '$Class' => '$JssorBulletNavigator$',
      '$Steps' => (int) $options['bulletnavigator']['steps'],
      '$Lanes' => (int) $options['bulletnavigator']['lanes'],
      '$SpacingX' => (int) $options['bulletnavigator']['spacingx'],
      '$SpacingY' => (int) $options['bulletnavigator']['spacingy'],
      '$Orientation' => (int) $options['bulletnavigator']['orientation'],
      '$Scale' => false,
    ),
  );

  // Create a unique slider ID.
  $variables['slider_id'] = 'slider-dom-id-' . $view->dom_id;

  // Enable arrow navigator.
  $variables['arrownavigator'] = $options['global']['arrownavigator'];

  // Enable bullet navigator.
  $variables['bulletnavigator'] = $options['global']['bulletnavigator'];

  // Skins.
  $variables['arrowskin'] = $options['arrownavigator']['arrowskin'];
  $variables['bulletskin'] = $options['bulletnavigator']['bulletskin'];

  $default_row_class = isset($options['default_row_class']) ? $options['default_row_class'] : FALSE;
  foreach ($rows as $id => $row) {
    $variables['rows'][$id] = array();
    $variables['rows'][$id]['content'] = $row;
    $variables['rows'][$id]['attributes'] = array();
    if ($default_row_class) {
      $variables['rows'][$id]['attributes']['class'][] = 'views-row';
    }
    if ($row_class = $view->style_plugin->getRowClass($id)) {
      $variables['rows'][$id]['attributes']['class'][] = $row_class;
    }
    $variables['rows'][$id]['attributes'] = new Attribute($variables['rows'][$id]['attributes']);
  }
}
