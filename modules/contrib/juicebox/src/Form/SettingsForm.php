<?php

namespace Drupal\juicebox\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\juicebox\JuiceboxFormatter;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a form that configures global Juicebox settings.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * The Juicebox formatter service.
   *
   * @var Drupal\juicebox\JuiceboxFormatter\JuiceboxFormatterInterface
   */
  protected $juiceboxFormatter;

  /**
   * Constructs a new SettingsForm object.
   *
   * @param \Drupal\juicebox\JuiceboxFormatter $juicebox_formatter
   *   The Juicebox formatter service.
   */
  public function __construct(JuiceboxFormatter $juicebox_formatter) {
    $this->juiceboxFormatter = $juicebox_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Instantiates this form class.
    return new static(
        // Load the service required to construct this class.
        $container->get('juicebox.formatter')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'juicebox_admin_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'juicebox.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $library = $this->juiceboxFormatter->getLibrary(TRUE, TRUE);
    $version = !empty($library['version']) ? $library['version'] : $this->t('Unknown');
    // Get all settings.
    $settings = $this->config('juicebox.settings')->get();
    // If the base language list is not officially saved yet, we can get the
    // default value from the library settings.
    if (empty($settings['base_languagelist'])) {
      $settings['base_languagelist'] = $library['base_languagelist'];
    }
    $form['juicebox_admin_intro'] = [
      '#markup' => $this->t("The options below apply to all Juicebox galleries. Note that most Juicebox configuration options are set within each gallery's unique configuration form and not applied on a global scope like the values here."),
    ];
    $form['apply_markup_filter'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Filter all title and caption output for compatibility with Juicebox javascript (recommended)'),
      '#default_value' => $settings['apply_markup_filter'],
      '#description' => $this->t('This option helps ensure title/caption output is syntactically compatible with the Juicebox javascript library by removing block-level tags.'),
    ];
    $form['enable_cors'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow galleries to be embedded remotely (CORS support)'),
      '#default_value' => $settings['enable_cors'],
      '#description' => $this->t('Enable cross-origin resource sharing (CORS) for all generated Juicebox XML. This will allow all origins/domains to use any Juicebox XML requested from this site for embedding purposes (adds a <em>Access-Control-Allow-Origin: *</em> header to all Juicebox XML responses).'),
    ];
    $form['multilingual'] = [
      '#type' => 'details',
      '#title' => $this->t('Multilingual options'),
      '#open' => TRUE,
    ];
    $form['multilingual']['translate_interface'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Translate the Juicebox javascript interface'),
      '#default_value' => $settings['translate_interface'],
      '#description' => $this->t('Send interface strings to the Juicebox javascript after passing them through the Drupal translation system.'),
    ];
    $form['multilingual']['base_languagelist'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Base string for interface translation'),
      '#default_value' => $settings['base_languagelist'],
      '#description' => $this->t('The base <strong>English</strong> interface text that Drupal should attempt to translate and pass to the Juicebox javascript for display (using the "languageList" configuration option). This text will be treated as a <strong>single string</strong> by Drupal and must be translated with a tool such as the Locale module. Note that this base string value will rarely change, and any changes made to it will break existing translations.'),
      '#wysiwyg' => FALSE,
      '#states' => [
        // Hide the settings when the translate option is disabled.
        'invisible' => [
          ':input[name="translate_interface"]' => ['checked' => FALSE],
        ],
      ],
    ];
    $form['multilingual']['base_languagelist_suggestion'] = [
      '#type' => 'item',
      '#title' => $this->t('Suggested base string for currently detected Juicebox version (@version)', ['@version' => $version]),
      '#description' => new FormattableMarkup('<pre>' . $library['base_languagelist'] . '</pre>', []),
      '#states' => [
        // Hide the settings when the translate option is disabled.
        'invisible' => [
          ':input[name="translate_interface"]' => ['checked' => FALSE],
        ],
      ],
    ];
    $multisize_disallowed = in_array('juicebox_multisize_image_style', $library['disallowed_conf']);
    $multisize_description = '<p>' . $this->t('Some versions of the Juicebox javascript library support "multi-size" (adaptive) image delivery. Individual galleries configured to use the <strong>Juicebox PRO multi-size (adaptive)</strong> image style allow for three different source derivatives to be defined per image, each of which can be configured below. The Juicebox javascript library will then choose between these depending on the active screen mode (i.e. viewport size) of each user. See the Juicebox javascript library documentation for more information.') . '</p>';
    if ($multisize_disallowed) {
      $multisize_description .= '<p><strong>' . $this->t('Your currently detected Juicebox version (@version) is not compatible with multi-size features, so the options below have been disabled.', ['@version' => $version]) . '</strong></p>';
    }
    // Mark our description, and its markup, as safe for unescaped display.
    $multisize_description = new FormattableMarkup($multisize_description, []);
    $form['juicebox_multisize'] = [
      '#type' => 'details',
      '#title' => $this->t('Juicebox PRO multi-size style configuration'),
      '#description' => $multisize_description,
      '#open' => !$multisize_disallowed,
    ];
    // Get available image style presets.
    $presets = image_style_options(FALSE);
    $form['juicebox_multisize']['juicebox_multisize_small'] = [
      '#title' => $this->t('Small mode image style'),
      '#default_value' => $settings['juicebox_multisize_small'],
      '#description' => $this->t('The style formatter to use in small screen mode (e.g., non-retina mobile devices).'),
    ];
    $form['juicebox_multisize']['juicebox_multisize_medium'] = [
      '#title' => $this->t('Medium mode image style'),
      '#default_value' => $settings['juicebox_multisize_medium'],
      '#description' => $this->t('The style formatter to use in medium screen mode (e.g., desktops and retina mobile devices).'),
    ];
    $form['juicebox_multisize']['juicebox_multisize_large'] = [
      '#title' => $this->t('Large mode image style'),
      '#default_value' => $settings['juicebox_multisize_large'],
      '#description' => $this->t('The style formatter to use in large screen mode (e.g., expanded view and retina laptops).'),
    ];
    foreach ($form['juicebox_multisize'] as &$options) {
      if (is_array($options)) {
        $options += [
          '#type' => 'select',
          '#options' => $presets,
          '#empty_option' => $this->t('None (original image)'),
          '#disabled' => $multisize_disallowed,
        ];
      }
    }
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $library = $this->juiceboxFormatter->getLibrary(TRUE, TRUE);
    if ($form_state->getvalue('translate_interface') && !empty($library['installed']) && $form_state->getvalue('base_languagelist') != $library['base_languagelist']) {
      $this->messenger()->addWarning($this->t('Interface translations are enabled but the base translation string does not match the suggested value for your version of the Juicebox javascript library. If some parts of the Juicebox interface do not appear translated correctly please verify that your base translation string is correct.'));
    }
    $this->config('juicebox.settings')
      ->set('apply_markup_filter', $form_state->getvalue('apply_markup_filter'))
      ->set('enable_cors', $form_state->getvalue('enable_cors'))
      ->set('translate_interface', $form_state->getvalue('translate_interface'))
      ->set('base_languagelist', $form_state->getvalue('base_languagelist'))
      ->set('juicebox_multisize_small', $form_state->getvalue('juicebox_multisize_small'))
      ->set('juicebox_multisize_medium', $form_state->getvalue('juicebox_multisize_medium'))
      ->set('juicebox_multisize_large', $form_state->getvalue('juicebox_multisize_large'))
      ->save();
    // These settings are global and may affect any gallery embed or XML code,
    // so we need to clear everything tagged with juicebox_gallery cache tag.
    Cache::invalidateTags(['juicebox_gallery']);
    $this->messenger()->addMessage($this->t('The Juicebox configuration options have been saved'));
  }

}
