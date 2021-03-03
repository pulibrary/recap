<?php

namespace Drupal\juicebox\Plugin\views\style;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\views\Plugin\views\style\StylePluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Utility\LinkGeneratorInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Component\Utility\Html;
use Drupal\juicebox\JuiceboxFormatterInterface;
use Drupal\juicebox\JuiceboxGalleryInterface;

/**
 * Plugin implementation of the 'juicebox' display style.
 *
 * @ingroup views_style_plugins
 *
 * @ViewsStyle(
 *   id = "juicebox",
 *   title = @Translation("Juicebox Gallery"),
 *   help = @Translation("Display rows as a Juicebox Gallery."),
 *   theme = "views_view_list",
 *   display_types = {"normal"}
 * )
 */
class JuiceboxDisplayStyle extends StylePluginBase {

  /**
   * A Juicebox formatter service.
   *
   * @var \Drupal\juicebox\JuiceboxFormatterInterface
   */
  protected $juicebox;

  /**
   * A Drupal entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * A Drupal entity field manager service.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * A Drupal string translation service.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface
   */
  protected $stringTranslation;

  /**
   * A Symfony request object for the current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * {@inheritdoc}
   */
  protected $usesGrouping = FALSE;

  /**
   * {@inheritdoc}
   */
  protected $usesFields = TRUE;

  /**
   * {@inheritdoc}
   */
  protected $usesRowPlugin = FALSE;

  /**
   * {@inheritdoc}
   */
  protected $usesRowClass = FALSE;

  /**
   * {@inheritdoc}
   */
  protected $usesOptions = TRUE;

  /**
   * Factory to fetch required dependencies from container.
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    // Create a new instance of the plugin. This also allows us to extract
    // services from the container and inject them into our plugin via its own
    // constructor as needed.
    return new static($configuration, $plugin_id, $plugin_definition, $container->get('entity_type.manager'), $container->get('entity_field.manager'), $container->get('link_generator'), $container->get('string_translation'), $container->get('request_stack'), $container->get('juicebox.formatter'));
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, EntityFieldManagerInterface $entity_field_manager, LinkGeneratorInterface $link_generator, TranslationInterface $translation, RequestStack $request_stack, JuiceboxFormatterInterface $juicebox) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFieldManager = $entity_field_manager;
    $this->stringTranslation = $translation;
    $this->request = $request_stack->getCurrentRequest();
    $this->juicebox = $juicebox;
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $library = $this->juicebox->getLibrary();
    $base_settings = $this->juicebox->confBaseOptions();
    // Structure the base settings in the "default" format that views wants.
    foreach ($base_settings as $setting => $value) {
      $base_settings_default[$setting] = ['default' => $value];
    }
    $options = array_merge($base_settings_default, [
      'image_field' => ['default' => ''],
      // If the library supports multi-size we can default to that for the main
      // image, otherwise use the "medium" style.
      'image_field_style' => [
        'default' => (!empty($library['version']) &&
      !in_array('juicebox_multisize_image_style', $library['disallowed_conf']))
        ? 'juicebox_multisize' : 'juicebox_medium',
      ],
      'thumb_field' => ['default' => ''],
      'thumb_field_style' => ['default' => 'juicebox_square_thumb'],
      'title_field' => ['default' => ''],
      'caption_field' => ['default' => ''],
      'show_title' => ['default' => 0],
    ]);
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    $settings = $this->options;
    // Get the active field options.
    $options = $this->confGetFieldSources();
    $missing_field_warning = '';
    if (empty($options['field_options_images'])) {
      $missing_field_warning = $this->t('<strong>You must add a field of type image, file or file ID to your view display before this value can be set.</strong><br/>');
    }
    // Add the view-specific elements.
    $form['image_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Image Source'),
      '#default_value' => $settings['image_field'],
      '#description' => $this->t('The field source to use for each image in the gallery. Must be an image field, file field or a file ID. If using a multivalued field (*) only the <em>first</em> value from each entity will be used.'),
      '#suffix' => $missing_field_warning,
      '#options' => $options['field_options_images'],
      '#empty_option' => $this->t('- Select -'),
    ];
    $form['thumb_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Thumbnail Source'),
      '#default_value' => $settings['thumb_field'],
      '#description' => $this->t('The field source to use for each thumbnail in the gallery. Must be an image field, file field or a file ID. Typically you will choose the same value that was set in the "Image Source" option above.'),
      '#suffix' => $missing_field_warning,
      '#options' => $options['field_options_images'],
      '#empty_option' => $this->t('- Select -'),
    ];
    $form['image_field_style'] = [
      '#type' => 'select',
      '#title' => $this->t('Image Field Style'),
      '#default_value' => $settings['image_field_style'],
      '#description' => $this->t('The style formatter for the image. Any formatting settings configured on the field itself will be ignored and this style setting will always be used.'),
      '#options' => $this->juicebox->confBaseStylePresets(),
      '#empty_option' => $this->t('None (original image)'),
    ];
    $form['thumb_field_style'] = [
      '#type' => 'select',
      '#title' => $this->t('Thumbnail Field Style'),
      '#default_value' => $settings['thumb_field_style'],
      '#description' => $this->t('The style formatter for the thumbnail. Any formatting settings configured on the field itself will be ignored and this style setting will always be used.'),
      '#options' => $this->juicebox->confBaseStylePresets(FALSE),
      '#empty_option' => $this->t('None (original image)'),
    ];
    $form['title_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Title Field'),
      '#default_value' => $settings['title_field'],
      '#description' => $this->t("The view's field that should be used for the title of each image in the gallery. Any formatting settings configured on the field itself will be respected."),
      '#options' => $options['field_options'],
      '#empty_option' => $this->t('None'),
    ];
    $form['caption_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Caption Field'),
      '#default_value' => $settings['caption_field'],
      '#description' => $this->t("The view's field that should be used for the caption of each image in the gallery. Any formatting settings configured on the field itself will be respected."),
      '#options' => $options['field_options'],
      '#empty_option' => $this->t('None'),
    ];
    $form['show_title'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show Gallery Title'),
      '#default_value' => $settings['show_title'],
      '#description' => $this->t('Show the view display title as the gallery title.'),
    ];
    // Add the common form elements.
    $form = $this->juicebox->confBaseForm($form, $settings);
    // Add view-sepcific field options for the linkURL setting.
    $linkurl_field_options = [];
    foreach ($options['field_options'] as $field_key => $field_name) {
      $linkurl_field_options[$field_key] = $this->t('Field') . ' - ' . $field_name;
    }
    $form['linkurl_source']['#description'] = $form['linkurl_source']['#description'] . '</br><strong>' . $this->t('If using a field source it must render a properly formatted URL and nothing else.') . '</strong>';
    $form['linkurl_source']['#options'] = array_merge($form['linkurl_source']['#options'], $linkurl_field_options);
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $element = [];
    $view = $this->view;
    $settings = $this->options;
    $display_name = isset($view->current_display) ? $view->current_display : 'default';
    // Generate xml details.
    $xml_route_info = [
      'route_name' => 'juicebox.xml_viewsstyle',
      'route_parameters' => [
        'viewName' => $view->id(),
        'displayName' => $display_name,
      ],
      'options' => [
        'query' => $this->argsToQuery() +
        $this->request->query->all(),
      ],
    ];
    // If we are previewing the view in the admin interface any changes made
    // will not be propogated through to the XML until the view is saved. This
    // can be very confusing as the preview will appear to be broken, so we
    // simply hide the preview output.
    if ($this->request->get('_route') == 'entity.view.preview_form') {
      $message = $this->stringTranslation->translate("Juicebox galleries cannot be viewed as a live preview. Please save your view and visit the full page URL for this display to preview this gallery.");
      $this->messenger()->addWarning($message);
      return ['#markup' => $message];
    }
    // Try building the gallery and its XML.
    try {
      // Initialize the gallery.
      $gallery = $this->juicebox->newGallery($xml_route_info['route_parameters']);
      // Build the gallery.
      $this->buildGallery($gallery);
      // Build field-specific contextual links.
      $contextual = $this->buildContextualLinks($xml_route_info);
      // Create a render array with the gallery markup.
      $element = $this->juicebox->buildEmbed($gallery, $settings, $xml_route_info, TRUE, FALSE, $contextual);
    }
    catch (\Exception $e) {
      $message = 'Exception building Juicebox embed code for view: !message in %function (line %line of %file).';
      watchdog_exception('juicebox', $e, $message);
    }
    return $element;
  }

  /**
   * Build the gallery based on loaded Drupal views data.
   *
   * @param Drupal\juicebox\JuiceboxGalleryInterface $gallery
   *   An initialized Juicebox gallery object.
   */
  protected function buildGallery(JuiceboxGalleryInterface $gallery) {
    $view = $this->view;
    $settings = $this->options;
    // Populate $this->rendered_fields.
    $this->renderFields($view->result);
    // Get all row image data in the format of Drupal file field items.
    $image_items = $thumb_items = $this->getItems($settings['image_field']);
    if ($settings['image_field'] != $settings['thumb_field']) {
      $thumb_items = $this->getItems($settings['thumb_field']);
    }
    // Iterate through each view row and calculate the gallery-specific details.
    foreach ($image_items as $row_index => $image_item) {
      // Make sure each main image has a thumb item.
      $thumb_item = !empty($thumb_items[$row_index]) ? $thumb_items[$row_index] : $image_item;
      // Calculate the source data that Juicebox requires.
      $src_data = $this->juicebox->styleImageSrcData($image_item, $settings['image_field_style'], $thumb_item, $settings['thumb_field_style'], $settings);
      // Short-circut this iteration if skipping an incompatible file.
      if (!$src_data['juicebox_compatible'] && $settings['incompatible_file_action'] == 'skip') {
        continue;
      }
      // Check if the linkURL should be customized based on view settings.
      if (!empty($settings['linkurl_source']) && !empty($this->rendered_fields[$row_index][$settings['linkurl_source']])) {
        $src_data['linkURL'] = (string) $this->rendered_fields[$row_index][$settings['linkurl_source']];
      }
      // Set the image title.
      $title = '';
      // If we have an incompatible file the title may need special handeling.
      if (!$src_data['juicebox_compatible'] && $settings['incompatible_file_action'] == 'show_icon_and_link') {
        $anchor = !empty($image_item->description) ? $image_item->description : $image_item->filename;
        $title = $this->linkGenerator->generate($anchor, Url::fromUri($src_data['linkURL']));
      }
      elseif (!empty($settings['title_field']) && !empty($this->rendered_fields[$row_index][$settings['title_field']])) {
        $title = (string) $this->rendered_fields[$row_index][$settings['title_field']];
      }
      // Set the image caption.
      $caption = '';
      if (!empty($settings['caption_field']) && !empty($this->rendered_fields[$row_index][$settings['caption_field']])) {
        $caption = (string) $this->rendered_fields[$row_index][$settings['caption_field']];
      }
      // Add this image to the gallery.
      $gallery->addImage($src_data, $title, $caption);
    }
    if ($settings['show_title']) {
      $gallery->addOption('gallerytitle', Html::escape($view->getTitle()));
    }
    // Run common build tasks.
    $this->juicebox->runCommonBuild($gallery, $settings, $view);
  }

  /**
   * Utility to build contextual links for a viewstyle-based gallery display.
   *
   * @param array $xml_route_info
   *   Associative array of route info used to generate the XML.
   *
   * @return array
   *   An associated array of calculated contextual link information.
   */
  protected function buildContextualLinks(array $xml_route_info) {
    $contextual = [];
    // Add a contextual link to view the XML. Note that we include any query
    // params as route paramaters. These won't be used in the actual route
    // but they will be preserved as query paramaters on the contextual link
    // (which may be needed during the XML request).
    $xml_query = !empty($xml_route_info['options']['query']) ? $xml_route_info['options']['query'] : [];
    // Add a contextual link to view the XML.
    $contextual['juicebox_xml_viewsstyle'] = [
      'route_parameters' => $xml_route_info['route_parameters'] + $xml_query,
    ];
    return $contextual;
  }

  /**
   * Utility to get the item arrays that contain image data from view rows.
   *
   * @param string $source_field
   *   The view field source that will contain a file identifer. The exact part
   *   of the row data to get the file identifer from will depend on the field
   *   type, and this method will resolve that based on the view's field handler
   *   details.
   *
   * @return array
   *   An indexed array, keyed by row id, of file field entities that were
   *   extracted based on row data.
   *
   * @see JuiceboxDiplayStyle::confGetFieldSources()
   */
  protected function getItems($source_field) {
    $view = $this->view;
    // Get the field source options and make sure the passed-source is valid.
    $source_options = $this->confGetFieldSources();
    if (empty($source_options['field_options_images_type'][$source_field])) {
      throw new \Exception('Empty or invalid field source @source detected for Juicebox view-based gallery.', ['@source' => $source_field]);
    }
    else {
      $source_type = $source_options['field_options_images_type'][$source_field];
    }
    $fids = [];
    $items = [];
    // Pass 1 - get the fids based on the source type.
    foreach ($view->result as $row_index => $row) {
      switch ($source_type) {
        case 'file_id_field':
          // The source is a file ID field so we can fetch the fid from row
          // data directly.
          $target_id = $view->field[$source_field]->getValue($row);
          if (!empty($target_id) && is_numeric($target_id)) {
            $fids[$row_index] = $target_id;
          }
          continue 2;

        case 'file_field':
          // The source is a file field so we fetch the fid through the
          // target_id property if the field item.
          $target_ids = $view->field[$source_field]->getValue($row, 'target_id');
          // The target IDs value comes in a mixed format depending on
          // cardinality. We can only use one ID as each view row can only
          // reference one image (to ensure appropriate matching with the
          // thumb/title/caption data already specified on the row).
          $target_id = is_array($target_ids) ? reset($target_ids) : $target_ids;
          if (!empty($target_id) && is_numeric($target_id)) {
            $fids[$row_index] = $target_id;
          }
      }
    }
    if (empty($items)) {
      // Bulk load all file entities.
      $file_entities = $this->entityTypeManager->getStorage('file')->loadMultiple($fids);
      // Pass 2 - Ensure the file entities are keyed by row.
      foreach ($fids as $row_index => $fid) {
        $items[$row_index] = $file_entities[$fid];
      }
    }
    return $items;
  }

  /**
   * Utility to determine which view fields can be used for image data.
   *
   * This method will extract a list of fields that can be used as "sources"
   * for a Juicebox gallery along with other useful field information.
   *
   * @return array
   *   An associative array containing a breakdown of field data that can be
   *   referenced by other build methods, including:
   *   - field_options_image: An associative array, keyed by field id, of fields
   *     that can be used as Juicebox gallery image sources.
   *   - field_options_image_type: An associative array, keyed by field id, of
   *     field "types" for all fields listed in 'field_options_image' above.
   *   - field_options: An associative array, keyed by field id, of fields that
   *     cannot be used as Juicebox gallery image sources, but may be useful
   *     for other purposes (text and caption sorces, etc.)
   */
  public function confGetFieldSources() {
    $options = [
      'field_options_images' => [],
      'field_options_images_type' => [],
      'field_options' => [],
    ];
    $view = $this->view;
    $field_handlers = $view->display_handler->getHandlers('field');
    $field_labels = $view->display_handler->getFieldLabels();
    // Separate image fields from non-image fields. For image fields we can
    // work with fids and fields of type image or file.
    foreach ($field_handlers as $viewfield => $handler) {
      $is_image = FALSE;
      $id = $handler->getPluginId();
      $name = $field_labels[$viewfield];
      if ($id == 'field') {
        // The field definition is on the handler, it's right bloody there, but
        // it's protected so we can't access it. This means we have to take the
        // long road (via our own injected entity manager) to get the field type
        // info.
        $entity = $this->entityFieldManager->getFieldStorageDefinitions($handler->getEntityType());
        if (isset($handler->field) && array_key_exists($handler->field, $entity)) {
          $field_definition = $entity[$handler->field];
          $field_type = $field_definition->getType();
          if ($field_type == 'image' || $field_type == 'file' || $field_type == 'entity_reference') {
            $field_cardinality = $field_definition->get('cardinality');
            $options['field_options_images'][$viewfield] = $name . ($field_cardinality == 1 ? '' : '*');
            $options['field_options_images_type'][$viewfield] = 'file_field';
            $is_image = TRUE;
          }
          elseif ($field_type == 'integer' && $handler->field == 'fid') {
            $options['field_options_images'][$viewfield] = $name;
            $options['field_options_images_type'][$viewfield] = 'file_id_field';
            $is_image = TRUE;
          }
        }
      }
      // Previous D8 betas listed files differently, so we still try to support
      // that case for legacy purposes.
      elseif ($id == 'file' && $viewfield == 'fid') {
        $options['field_options_images'][$viewfield] = $name;
        $options['field_options_images_type'][$viewfield] = 'file_id_field';
        $is_image = TRUE;
      }
      if (!$is_image) {
        $options['field_options'][$viewfield] = $name;
      }
    }
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function validate() {
    $errors = parent::validate();
    $pager_options = $this->displayHandler->getOption('pager');
    if (isset($pager_options['type']) && !($pager_options['type'] == 'none' || $pager_options['type'] == 'some')) {
      // @todo Re-enable this error once issue #2579931 is resolved.
      // $errors[] = $this->stringTranslation->translate('The Juicebox
      // style cannot be used with a pager. Please disable the "Use a pager"
      // option for this display.');
    }
    $style = $this->displayHandler->getOption('style');
    // We want to somewhat "nag" the user if they have not yet configured the
    // Juicebox-specific plugin settings (because things won't work until they
    // do). However, we do NOT want to formally set an error. This is because
    // this validate method can run on pages where the user can't actaully touch
    // the Juicebox-specific plugin settings (such as
    // admin/structure/views/add).
    if (empty($style['options']['image_field']) || empty($style['options']['thumb_field'])) {
      $this->messenger()->addWarning($this->stringTranslation->translate("To ensure a fully functional Juicebox gallery please remember to add at least one field of type Image, File or File ID to your Juicebox view display, and to configure all Juicebox Gallery format settings. Once you have completed these steps, re-save your view to remove this warning."), FALSE);
    }
    return $errors;
  }

  /**
   * Utility to extract current set of view args into list of query params.
   *
   * @return array
   *   An array of items that can be used directly as part of the 'query' array
   *   in core URL-building methods. The keys will be numbered numerically as
   *   arg_0, arg_1,... arg_N with the same indexed order of the view args.
   */
  protected function argsToQuery() {
    $query = [];
    foreach ($this->view->args as $key => $arg) {
      $query['arg_' . $key] = $arg;
    }
    return $query;
  }

}
