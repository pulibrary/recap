<?php

namespace Drupal\juicebox;

use Drupal\Core\Messenger\MessengerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Utility\Html;
use Drupal\file\FileInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Security\TrustedCallbackInterface;
use Drupal\Core\Render\Element;

/**
 * Class to define a Drupal service with common formatter methods.
 */
class JuiceboxFormatter implements JuiceboxFormatterInterface, TrustedCallbackInterface {
  use StringTranslationTrait;

  /**
   * A Drupal configuration factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * A Drupal URL generator service.
   *
   * @var \Drupal\Core\Routing\UrlGeneratorInterface
   */
  protected $urlGenerator;

  /**
   * A Drupal module manager service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleManager;

  /**
   * A Drupal current path service.
   *
   * @var \Drupal\Core\Path\CurrentPathStack
   */
  protected $currentPathStack;

  /**
   * A Symfony request object for the current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * Storage of library details as defined by Libraries API.
   *
   * @var array
   */
  static protected $library = [];

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * A Drupal entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The Drupal config factory that can be used to derive global Juicebox
   *   settings.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   A string translation service.
   * @param \Drupal\Core\Routing\UrlGeneratorInterface $url_generator
   *   A URL generator service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_manager
   *   A module manager service.
   * @param \Drupal\Core\Path\CurrentPathStack $currentPathStack
   *   A current path service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The Symfony request stack from which to extract the current request.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger_interface
   *   The messenger interface.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   */
  public function __construct(ConfigFactoryInterface $config_factory,
  TranslationInterface $string_translation,
  UrlGeneratorInterface $url_generator,
  ModuleHandlerInterface $module_manager,
  CurrentPathStack $currentPathStack,
  RequestStack $request_stack,
  MessengerInterface $messenger_interface,
  EntityTypeManagerInterface $entity_type_manager) {
    $this->configFactory = $config_factory;
    $this->stringTranslation = $string_translation;
    $this->urlGenerator = $url_generator;
    $this->moduleManager = $module_manager;
    $this->currentPathStack = $currentPathStack;
    $this->request = $request_stack->getCurrentRequest();
    $this->messenger = $messenger_interface;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks() {
    return [
      'preRenderFieldsets',
    ];
  }

  /**
   * Form pre-render callback.
   *
   * Visually render fieldsets without affecting tree-based variable storage.
   *
   * This technique/code is taken almost directly from the D7 Views module in
   * views_ui_pre_render_add_fieldset_markup()
   *
   * @param $form
   */
  public static function preRenderFieldsets($form) {
    foreach (Element::children($form) as $key) {
      $element = $form[$key];
      // In our form builder functions, we added an arbitrary #jb_fieldset
      // property to any element that belongs in a fieldset. If this form element
      // has that property, move it into its fieldset.
      if (isset($element['#jb_fieldset']) && isset($form[$element['#jb_fieldset']])) {
        $form[$element['#jb_fieldset']][$key] = $element;
        // Remove the original element this duplicates.
        unset($form[$key]);
      }
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function newGallery(array $id_args) {
    // Calculate the gallery ID.
    $id = '';
    foreach ($id_args as $arg) {
      // Drop special characters in individual args and delimit by double-dash.
      $arg = preg_replace('/[^0-9a-zA-Z-]/', '-', $arg);
      $id .= $arg . '--';
    }
    $id = trim($id, '- ');
    // Get the library data. We do this early (before instantiating) as the lib
    // details should be allowed to impact which classes are used.
    $library = $this->getLibrary();
    // Calculate the class that needs to be instantiated allowing modules to
    // alter the result.
    $class = 'Drupal\juicebox\JuiceboxGallery';
    $this->moduleManager->alter('juicebox_gallery_class', $class, $library);
    // Instantiate the Juicebox gallery objects.
    $object_settings = [
      'filter_markup' => $this->configFactory->get('juicebox.settings')->get('apply_markup_filter'),
      'process_attributes' => FALSE,
    ];
    $gallery = new $class($id, $object_settings);
    if ($gallery instanceof JuiceboxGalleryInterface) {
      return $gallery;
    }
    throw new \Exception('Could not instantiate Juicebox gallery.');
  }

  /**
   * {@inheritdoc}
   */
  public function getGlobalSettings() {
    return $this->configFactory->get('juicebox.settings')->get();
  }

  /**
   * {@inheritdoc}
   */
  public function getLibrary($force_local = FALSE, $reset = FALSE) {
    // We "default" to sites/all/libraries and that will override
    // anything in /libraries. Rationale is that sites/all/libraries
    // was the original location for these files theoretically,
    // we could check the versions of both and pick the one
    // with the highest version not sure the juice(box) is worth the squeeze.
    if (file_exists(DRUPAL_ROOT . '/' . 'sites/all/libraries/juicebox/juicebox.js')) {
      $librarypath = '/sites/all/libraries/juicebox/juicebox.js';
    }
    elseif (file_exists(DRUPAL_ROOT . '/' . 'libraries/juicebox/juicebox.js')) {
      $librarypath = '/libraries/juicebox/juicebox.js';
    }
    if (isset($librarypath)) {
      juicebox_build_library_array($librarypath, $library);
    }
    else {
      $notification_top = $this->t('The Juicebox Javascript library does not appear to be installed. Please download and install the most recent version of the Juicebox library.');
      $this->messenger->addError($notification_top);
    }
    return $library;
  }

  /**
   * {@inheritdoc}
   */
  public function runCommonBuild(JuiceboxGalleryInterface $gallery, array $settings, $data = NULL) {
    $global_settings = $this->getGlobalSettings();
    // Add all gallery options directly from the settings.
    $this->setGalleryOptions($gallery, $settings);
    // Also attempt to translate interface strings.
    if ($global_settings['translate_interface']) {
      $base_string = $global_settings['base_languagelist'];
      if (!empty($base_string)) {
        $base_string = Html::escape($base_string);
        $gallery->addOption('languagelist', $base_string, FALSE);
      }
    }
    // Allow other modules to alter the built gallery data before it's
    // rendered. After this point the gallery should no longer change.
    $this->moduleManager->alter('juicebox_gallery', $gallery, $data);
  }

  /**
   * {@inheritdoc}
   */
  public function buildEmbed(JuiceboxGalleryInterface $gallery, array $settings, array $xml_route_info, $add_js = TRUE, $add_xml = FALSE, array $contextual = []) {
    // Merge all settings.
    $settings = $settings + $this->getGlobalSettings();
    // Set some defaults for the route info.
    $xml_route_info += [
      'route_name' => '',
      'route_parameters' => [],
      'options' => [],
    ];
    // Prep the ids that may be used.
    $embed_id = $gallery->getId();
    $embed_xml_id = 'xml--' . $embed_id;
    // Construct the base render array for the gallery.
    $output = [
      '#gallery' => $gallery,
      '#theme' => 'juicebox_embed_markup',
      '#settings' => $settings,
      '#attached' => [],
      '#contextual_links' => $contextual + [
        'juicebox_conf_global' => [
          'route_parameters' => [],
        ],
      ],
      '#cache' => [
        'tags' => ['juicebox_gallery'],
      ],
      '#suffix' => '',
    ];
    // Process JS additions.
    if ($add_js) {
      // If we are also embedding the XML we want to set some query string
      // values on the XML URL that will allow the XML build methods to fetch
      // it later.
      $embed_query_additions = [];
      if ($add_xml) {
        $embed_query_additions['xml-source-path'] = trim($this->currentPathStack->getPath(), '/');
        $embed_query_additions['xml-source-id'] = $embed_xml_id;
      }
      // Add some query params that apply to all types of Juicebox galleries and
      // generate the final XML URL.
      $xml_query_additions = array_merge(['checksum' => $gallery->getChecksum()], $embed_query_additions);
      $xml_options = array_merge_recursive(['query' => $xml_query_additions], $xml_route_info['options']);
      $xml_url = $this->urlGenerator->generateFromRoute($xml_route_info['route_name'], $xml_route_info['route_parameters'], $xml_options);
      // Add the main library.
      if (file_exists(DRUPAL_ROOT . '/' . 'sites/all/libraries/juicebox/juicebox.js')) {
        $output['#attached']['library'][] = 'juicebox/juicebox.sites';
      }
      elseif (file_exists(DRUPAL_ROOT . '/' . 'libraries/juicebox/juicebox.js')) {
        $output['#attached']['library'][] = 'juicebox/juicebox';
      }
      else {
        $notification_top = $this->t('The Juicebox Javascript library does not appear to be installed. Please download and install the most recent version of the Juicebox library.');
        $this->messenger->addError($notification_top);
      }
      // Add the JS gallery details as Drupal.settings.
      $output['#attached']['drupalSettings']['juicebox'] = [$embed_id => $gallery->getJavascriptVars($xml_url)];
      // Add some local JS (implementing Drupal.behaviors) that will process
      // the Drupal.settings above into a new client-side juicebox object.
      $output['#attached']['library'][] = 'juicebox/juicebox.local';
    }
    if ($add_xml) {
      $output['#suffix'] .= $gallery->renderXml($embed_xml_id);
    }
    // Ensure that our suffix is not further sanitized.
    $output['#suffix'] = new FormattableMarkup($output['#suffix'], []);
    return $output;
  }

  /**
   * {@inheritdoc}
   */
  public function styleImageSrcData(FileInterface $image_file, $image_style, FileInterface $thumb_file, $thumb_style, array $settings) {
    $check_incompatible = (!empty($settings['incompatible_file_action']));
    $src_data = [];
    // Style the main item.
    $src_data = $this->styleImage($image_file, $image_style, $check_incompatible);
    // Set thumb data and add it to the source info.
    $src_data['thumbURL'] = '';
    if (!$src_data['juicebox_compatible'] && $image_file->id() == $thumb_file->id()) {
      $src_data['thumbURL'] = $src_data['imageURL'];
    }
    else {
      $thumb_image_data = $this->styleImage($thumb_file, $thumb_style, $check_incompatible);
      $src_data['thumbURL'] = $thumb_image_data['imageURL'];
    }
    // Check if the linkURL should be customized based on settings.
    $src_data['linkURL'] = $src_data['unstyled_src'];
    if ($src_data['juicebox_compatible'] && !empty($settings['linkurl_source']) && $settings['linkurl_source'] == 'image_styled') {
      $src_data['linkURL'] = $src_data['imageURL'];
    }
    // Set the link target directly from the gallery settings.
    $src_data['linkTarget'] = !empty($settings['linkurl_target']) ? $settings['linkurl_target'] : '_blank';
    return $src_data;
  }

  /**
   * Utility to style an individual file entity for use in a Juicebox gallery.
   *
   * This method can detect if the passed file is incompatible with Juicebox.
   * If so it styles the output as a mimetype image icon representing the file
   * type. Otherwise the item is styled normally with the passed image style.
   *
   * @param \Drupal\file\FileInterface $file
   *   A file entity containing the image data to append Juicebox styled image
   *   data to.
   * @param string $style
   *   The Drupal image style to apply to the item.
   * @param bool $check_compatible
   *   Whether-or-not to detect if the item is compatible with Juicebox, and if
   *   so, substitute a mimetype icon for its output.
   *
   * @return array
   *   The styled image data.
   */
  protected function styleImage(FileInterface $file, $style, $check_compatible = TRUE) {
    $global_settings = $this->getGlobalSettings();
    $library = $this->getLibrary();
    $mimetype = $file->getMimeType();
    $image_data = [];
    $image_data['juicebox_compatible'] = TRUE;
    // Set the normal, unstyled, url for reference.
    $image_data['unstyled_src'] = file_create_url($file->getFileUri());
    // Check compatibility if configured and if the library info contains
    // mimetype compatibitly information.
    if ($check_compatible && !empty($library['compatible_mimetypes']) && !in_array($mimetype, $library['compatible_mimetypes'])) {
      // If the item is not compatible, find the substitute mimetype icon.
      $image_data['juicebox_compatible'] = FALSE;
      $icon_dir = drupal_get_path('module', 'juicebox') . '/images/mimetypes';
      // We only have icons for each major type, so simplify accordingly.
      // file_icon_class() could also be useful here but would require
      // supporting icons for more package types.
      $type_parts = explode('/', $mimetype);
      $icon_path = $icon_dir . '/' . reset($type_parts) . '.png';
      if (file_exists($icon_path)) {
        $image_data['imageURL'] = file_create_url($icon_path);
      }
      else {
        $image_data['imageURL'] = file_create_url($icon_dir . '/general.png');
      }
    }
    // If the item is compatible, style it.
    else {
      $sizes = ['imageURL' => $style];
      // The "juicebox_multisize" style is special, and actually consists of 3
      // individual styles configured globally.
      if ($style == 'juicebox_multisize') {
        $sizes = [
          'smallImageURL' => $global_settings['juicebox_multisize_small'],
          'imageURL' => $global_settings['juicebox_multisize_medium'],
          'largeImageURL' => $global_settings['juicebox_multisize_large'],
        ];
      }
      foreach ($sizes as $size => $style_each) {
        if (!empty($style_each)) {
          $style_obj = $this->entityTypeManager->getStorage('image_style')->load($style_each);
          if ($style_obj) {
            $image_data[$size] = $style_obj->buildUrl($file->getFileUri());
          }
        }
        else {
          $image_data[$size] = $image_data['unstyled_src'];
        }
      }
    }
    return $image_data;
  }

  /**
   * Utility to extract Juicebox options from common Drupal display settings.
   *
   * And add them to the gallery.
   *
   * Some common Juicebox configuration options are set via a GUI and others
   * are set as manual strings. This method fetches all of these values from
   * drupal settings data and merges them into the gallery. Note that this only
   * accounts for common settings.
   *
   * @param Drupal\juicebox\JuiceboxGalleryInterface $gallery
   *   An initialized Juicebox gallery object.
   * @param array $settings
   *   An associative array of gallery-specific settings.
   */
  protected function setGalleryOptions(JuiceboxGalleryInterface $gallery, array $settings) {
    // Get the string options set via the GUI.
    foreach (['jlib_galleryWidth', 'jlib_galleryHeight',
      'jlib_backgroundColor', 'jlib_textColor',
      'jlib_thumbFrameColor',
    ] as $name) {
      if (isset($settings[$name])) {
        $name_real = str_replace('jlib_', '', $name);
        $gallery->addOption(mb_strtolower($name_real), trim(Html::escape($settings[$name])));
      }
    }
    // Get the bool options set via the GUI.
    foreach (['jlib_showOpenButton', 'jlib_showExpandButton',
      'jlib_showThumbsButton', 'jlib_useThumbDots', 'jlib_useFullscreenExpand',
    ] as $name) {
      if (isset($settings[$name])) {
        $name_real = str_replace('jlib_', '', $name);
        $gallery->addOption(mb_strtolower($name_real), (!empty($settings[$name]) ? 'TRUE' : 'FALSE'));
      }
    }
    // Merge-in the manually assigned options making sure they take priority
    // over any conflicting GUI options.
    if (!empty($settings['manual_config'])) {
      $manual_options = explode("\n", $settings['manual_config']);
      foreach ($manual_options as $option) {
        $option = trim($option);
        if (!empty($option)) {
          // Each manual option has only been validated (on input) to be in the
          // form optionName="optionValue". Now we need split and sanitize the
          // values.
          $matches = [];
          preg_match('/^([A-Za-z0-9]+?)="([^"]+?)"$/u', $option, $matches);
          list(, $name, $value) = $matches;
          $gallery->addOption(mb_strtolower($name), Html::escape($value));
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function confBaseOptions() {
    return [
      'jlib_galleryWidth' => '100%',
      'jlib_galleryHeight' => '100%',
      'jlib_backgroundColor' => '#222222',
      'jlib_textColor' => 'rgba(255,255,255,1)',
      'jlib_thumbFrameColor' => 'rgba(255,255,255,.5)',
      'jlib_showOpenButton' => 1,
      'jlib_showExpandButton' => 1,
      'jlib_showThumbsButton' => 1,
      'jlib_useThumbDots' => 0,
      'jlib_useFullscreenExpand' => 0,
      'manual_config' => '',
      'custom_parent_classes' => '',
      'apply_markup_filter' => 1,
      'linkurl_source' => '',
      'linkurl_target' => '_blank',
      'incompatible_file_action' => 'show_icon_and_link',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function confBaseForm(array $form, array $settings) {
    // Get locally installed library details.
    $library = $this->getLibrary();
    $disallowed_conf = [];
    if (!empty($library) && empty($library['error'])) {
      // If we don't have a known version of the Juicebox library, just show a
      // generic warning.
      if (empty($library['version'])) {
        $notification_top = $this->t('<strong>Notice:</strong> Your Juicebox Library version could not be detected. Some options below may not function correctly.');
      }
      // If this version does not support some LITE optins, show a message.
      elseif (!empty($library['disallowed_conf'])) {
        $disallowed_conf = $library['disallowed_conf'];
        $notification_top = $this->t('<strong>Notice:</strong> You are currently using Juicebox library version <strong>@version</strong> which is not compatible with some of the options listed below. These options will appear disabled until you upgrade to the most recent Juicebox library version.', ['@version' => $library['version']]);
        $notification_label = $this->t('&nbsp;(not available in @version)', ['@version' => $library['version']]);
      }
    }
    // If the library itself is not installed, display formal error message.
    else {
      $notification_top = $this->t('The Juicebox Javascript library does not appear to be installed. Please download and install the most recent version of the Juicebox library.');
      $this->messenger->addError($notification_top);
      $form['#pre_render'] = [static::class . '::preRenderFieldsets'];
      return $form;
    }
    $form['juicebox_config'] = [
      '#type' => 'details',
      '#title' => $this->t('Juicebox Library - Lite Config'),
      '#open' => FALSE,
      '#description' => !empty($notification_top) ? '<p>' . $notification_top . '</p>' : '',
      '#weight' => 10,
    ];
    $form['jlib_galleryWidth'] = [
      '#jb_fieldset' => 'juicebox_config',
      '#type' => 'textfield',
      '#title' => $this->t('Gallery Width'),
      '#description' => $this->t('Set the gallery width in a standard numeric format (such as 100% or 300px).'),
      '#element_validate' => ['juicebox_element_validate_dimension'],
    ];
    $form['jlib_galleryHeight'] = [
      '#jb_fieldset' => 'juicebox_config',
      '#type' => 'textfield',
      '#title' => $this->t('Gallery Height'),
      '#description' => $this->t('Set the gallery height in a standard numeric format (such as 100% or 300px).'),
      '#element_validate' => ['juicebox_element_validate_dimension'],
    ];
    $form['jlib_backgroundColor'] = [
      '#jb_fieldset' => 'juicebox_config',
      '#type' => 'textfield',
      '#title' => $this->t('Background Color'),
      '#description' => $this->t('Set the gallery background color as a CSS3 color value (such as rgba(10,50,100,0.7) or #FF00FF).'),
    ];
    $form['jlib_textColor'] = [
      '#jb_fieldset' => 'juicebox_config',
      '#type' => 'textfield',
      '#title' => $this->t('Text Color'),
      '#description' => $this->t('Set the color of all gallery text as a CSS3 color value (such as rgba(255,255,255,1) or #FF00FF).'),
    ];
    $form['jlib_thumbFrameColor'] = [
      '#jb_fieldset' => 'juicebox_config',
      '#type' => 'textfield',
      '#title' => $this->t('Thumbnail Frame Color'),
      '#description' => $this->t('Set the color of the thumbnail frame as a CSS3 color value (such as rgba(255,255,255,.5) or #FF00FF).'),
    ];
    $form['jlib_showOpenButton'] = [
      '#jb_fieldset' => 'juicebox_config',
      '#type' => 'checkbox',
      '#title' => $this->t('Show Open Image Button'),
      '#description' => $this->t('Whether to show the "Open Image" button. This will link to the full size version of the image within a new tab to facilitate downloading.'),
    ];
    $form['jlib_showExpandButton'] = [
      '#jb_fieldset' => 'juicebox_config',
      '#type' => 'checkbox',
      '#title' => $this->t('Show Expand Button'),
      '#description' => $this->t('Whether to show the "Expand" button. Clicking this button expands the gallery to fill the browser window.'),
    ];
    $form['jlib_useFullscreenExpand'] = [
      '#jb_fieldset' => 'juicebox_config',
      '#type' => 'checkbox',
      '#title' => $this->t('Use Fullscreen Expand'),
      '#description' => $this->t('Whether to trigger fullscreen mode when clicking the expand button (for supported browsers).'),
    ];
    $form['jlib_showThumbsButton'] = [
      '#jb_fieldset' => 'juicebox_config',
      '#type' => 'checkbox',
      '#title' => $this->t('Show Thumbs Button'),
      '#description' => $this->t('Whether to show the "Toggle Thumbnails" button.'),
    ];
    $form['jlib_useThumbDots'] = [
      '#jb_fieldset' => 'juicebox_config',
      '#type' => 'checkbox',
      '#title' => $this->t('Show Thumbs Dots'),
      '#description' => $this->t('Whether to replace the thumbnail images with small dots.'),
    ];
    $form['juicebox_manual_config'] = [
      '#type' => 'details',
      '#title' => $this->t('Juicebox Library - Pro / Manual Config'),
      '#open' => FALSE,
      '#description' => $this->t('Specify any additional Juicebox library configuration options (such as "Pro" options) here.<br/>Options set here always take precedence over those set in the "Lite" options above if there is a conflict.'),
      '#weight' => 20,
    ];
    $form['manual_config'] = [
      '#jb_fieldset' => 'juicebox_manual_config',
      '#type' => 'textarea',
      '#title' => $this->t('Pro / Manual Configuraton Options'),
      '#description' => $this->t('Add one option per line in the format <strong>optionName="optionValue"</strong><br/>See also: http://www.juicebox.net/support/config_options'),
      '#element_validate' => ['juicebox_element_validate_config'],
    ];
    $form['advanced'] = [
      '#type' => 'details',
      '#title' => $this->t('Juicebox - Advanced Options'),
      '#open' => FALSE,
      '#weight' => 30,
    ];
    $form['incompatible_file_action'] = [
      '#jb_fieldset' => 'advanced',
      '#type' => 'select',
      '#title' => $this->t('Incompatible File Type Handling'),
      '#options' => [
        'skip' => $this->t('Bypass incompatible files'),
        'show_icon' => $this->t('Show mimetype icon placehoder'),
        'show_icon_and_link' => $this->t('Show mimetype icon placholder and link to file'),
      ],
      '#empty_option' => $this->t('Do nothing'),
      '#description' => $this->t('Specify any special handling that should be applied to files that Juicebox cannot display (non-images).'),
    ];
    $form['linkurl_source'] = [
      '#jb_fieldset' => 'advanced',
      '#type' => 'select',
      '#title' => $this->t("LinkURL Source"),
      '#description' => $this->t('The linkURL is an image-specific path for accessing each image outside the gallery. This is used by features such as the "Open Image Button".'),
      '#options' => ['image_styled' => 'Main Image - Styled (use this gallery\'s main image style setting)'],
      '#empty_option' => $this->t('Main Image - Unstyled (original image)'),
    ];
    $form['linkurl_target'] = [
      '#jb_fieldset' => 'advanced',
      '#type' => 'select',
      '#title' => $this->t('LinkURL Target'),
      '#options' => [
        '_blank' => $this->t('_blank'),
        '_self' => $this->t('_self'),
        '_parent' => $this->t('_parent'),
        '_top' => $this->t('_top'),
      ],
      '#description' => $this->t('Specify a target for any links that make user of the image linkURL.'),
    ];
    $form['custom_parent_classes'] = [
      '#jb_fieldset' => 'advanced',
      '#type' => 'textfield',
      '#title' => $this->t('Custom Classes for Parent Container'),
      '#description' => $this->t('Define any custom classes that should be added to the parent container within the Juicebox embed markup.<br/>This can be handy if you want to apply more advanced styling or dimensioning rules to this gallery via CSS. Enter as space-separated values.'),
    ];
    // Set values that are directly related to each key.
    foreach ($form as $conf_key => &$conf_value) {
      if (!empty($conf_value['#type']) && $conf_value['#type'] != 'details') {
        $conf_value['#default_value'] = $settings[$conf_key];
        if (in_array($conf_key, $disallowed_conf)) {
          $conf_value['#title'] .= $notification_label;
          $conf_value['#disabled'] = TRUE;
        }
      }
    }
    // Add a pre render callback that will ensure that the items are nested
    // correctly into fieldsets just before display.
    $form['#pre_render'] = [static::class . '::preRenderFieldsets'];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function confBaseStylePresets($allow_multisize = TRUE) {
    $library = $this->getLibrary();
    // Get available image style presets.
    $presets = image_style_options(FALSE);
    // If multisize is allowed, include it with the normal styles.
    if ($allow_multisize && !in_array('juicebox_multisize_image_style', $library['disallowed_conf'])) {
      $presets = ['juicebox_multisize' => $this->t('Juicebox PRO multi-size (adaptive)')] + $presets;
    }
    return $presets;
  }

}
