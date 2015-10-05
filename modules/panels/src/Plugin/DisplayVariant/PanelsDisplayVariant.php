<?php

/**
 * @file
 * Contains \Drupal\page_manager\Plugin\DisplayVariant\PanelsDisplayVariant.
 */

namespace Drupal\panels\Plugin\DisplayVariant;

use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Component\Utility\SafeMarkup;
use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Display\VariantBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Component\Plugin\ContextAwarePluginInterface;
use Drupal\Core\Plugin\Context\ContextHandlerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\page_manager\PageExecutableInterface;
use Drupal\Core\Utility\Token;
use Drupal\page_manager\Plugin\BlockVariantInterface;
use Drupal\page_manager\Plugin\BlockVariantTrait;
use Drupal\page_manager\Plugin\ConditionVariantInterface;
use Drupal\page_manager\Plugin\ConditionVariantTrait;
use Drupal\page_manager\Plugin\ContextAwareVariantInterface;
use Drupal\page_manager\Plugin\ContextAwareVariantTrait;
use Drupal\page_manager\Plugin\PageAwareVariantInterface;
use Drupal\layout_plugin\Layout;
use Drupal\layout_plugin\Plugin\Layout\LayoutInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a display variant that simply contains blocks.
 *
 * @DisplayVariant(
 *   id = "panels_variant",
 *   admin_label = @Translation("Panels")
 * )
 */
class PanelsDisplayVariant extends VariantBase implements ContextAwareVariantInterface, ConditionVariantInterface, ContainerFactoryPluginInterface, PageAwareVariantInterface, BlockVariantInterface {

  use BlockVariantTrait;
  use ContextAwareVariantTrait;
  use ConditionVariantTrait;

  /**
   * The layout handler.
   *
   * @var \Drupal\layout_plugin\Plugin\Layout\LayoutInterface
   */
  protected $layout;

  /**
   * The context handler.
   *
   * @var \Drupal\Core\Plugin\Context\ContextHandlerInterface
   */
  protected $contextHandler;

  /**
   * The UUID generator.
   *
   * @var \Drupal\Component\Uuid\UuidInterface
   */
  protected $uuidGenerator;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * The page executable.
   *
   * @var \Drupal\page_manager\PageExecutable
   */
  protected $executable;

  /**
   * The token service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $token;

  /**
   * Constructs a new PanelsDisplayVariant.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Plugin\Context\ContextHandlerInterface $context_handler
   *   The context handler.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user.
   * @param \Drupal\Component\Uuid\UuidInterface $uuid_generator
   *   The UUID generator.
   * @param \Drupal\Core\Utility\Token $token
   *   The token service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ContextHandlerInterface $context_handler, AccountInterface $account, UuidInterface $uuid_generator, Token $token) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->contextHandler = $context_handler;
    $this->account = $account;
    $this->uuidGenerator = $uuid_generator;
    $this->token = $token;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('context.handler'),
      $container->get('current_user'),
      $container->get('uuid'),
      $container->get('token')
    );
  }

  /**
   * Returns instance of the layout plugin used by this page variant.
   *
   * @return \Drupal\layout_plugin\Plugin\Layout\LayoutInterface
   *   Layout plugin instance.
   */
  public function getLayout() {
    if (!isset($this->layout)) {
      $this->layout = Layout::layoutPluginManager()->createInstance($this->configuration['layout'], []);
    }
    return $this->layout;
  }

  /**
   * {@inheritdoc}
   */
  public function getRegionNames() {
    return $this->getLayout()->getPluginDefinition()['region_names'];
  }

  /**
   * Build render arrays for each of the regions.
   *
   * @return
   *   An associative array keyed by region id, containing the render array
   *   representing the content of each region.
   */
  protected function buildRegions() {
    $build = [];
    $contexts = $this->getContexts();
    foreach ($this->getRegionAssignments() as $region => $blocks) {
      if (!$blocks) {
        continue;
      }

      $region_name = Html::getClass("block-region-$region");
      $build[$region]['#prefix'] = '<div class="' . $region_name . '">';
      $build[$region]['#suffix'] = '</div>';

      /** @var $blocks \Drupal\block\BlockPluginInterface[] */
      $weight = 0;
      foreach ($blocks as $block_id => $block) {
        if ($block instanceof ContextAwarePluginInterface) {
          $this->contextHandler()->applyContextMapping($block, $contexts);
        }
        if ($block->access($this->account)) {
          $block_render_array = [
            '#theme' => 'block',
            '#attributes' => [],
            '#weight' => $weight++,
            '#configuration' => $block->getConfiguration(),
            '#plugin_id' => $block->getPluginId(),
            '#base_plugin_id' => $block->getBaseId(),
            '#derivative_plugin_id' => $block->getDerivativeId(),
          ];
          if (!empty($block_render_array['#configuration']['label'])) {
            $block_render_array['#configuration']['label'] = SafeMarkup::checkPlain($block_render_array['#configuration']['label']);
          }
          $block_render_array['content'] = $block->build();

          $build[$region][$block_id] = $block_render_array;
        }
      }
    }
    $build['#title'] = $this->renderPageTitle($this->configuration['page_title']);
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $regions = $this->buildRegions();
    if ($layout = $this->getLayout()) {
      return $layout->build($regions);
    }
    return $regions;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    // Allow to configure the page title, even when adding a new display.
    // Default to the page label in that case.
    $form['page_title'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Page title'),
      '#description' => $this->t('Configure the page title that will be used for this display.'),
      '#default_value' => !$this->id() ? $this->executable->getPage()->label() : $this->configuration['page_title'],
    );

    // Do not allow blocks to be added until the display variant has been saved.
    if (!$this->id()) {
      $form['layout'] = [
        '#title' => $this->t('Layout'),
        '#type' => 'select',
        '#options' => Layout::getLayoutOptions(['group_by_category' => TRUE]),
        '#default_value' => NULL
      ];

      return $form;
    }

    // Determine the page ID, used for links below.
    $page_id = $this->executable->getPage()->id();

    // Set up the attributes used by a modal to prevent duplication later.
    $attributes = [
      'class' => ['use-ajax'],
      'data-dialog-type' => 'modal',
      'data-dialog-options' => Json::encode([
        'width' => 'auto',
      ]),
    ];
    $add_button_attributes = NestedArray::mergeDeep($attributes, [
      'class' => [
        'button',
        'button--small',
        'button-action',
      ],
    ]);

    if ($block_assignments = $this->getRegionAssignments()) {
      // Build a table of all blocks used by this display variant.
      $form['block_section'] = [
        '#type' => 'details',
        '#title' => $this->t('Blocks'),
        '#open' => TRUE,
      ];
      $form['block_section']['add'] = [
        '#type' => 'link',
        '#title' => $this->t('Add new block'),
        '#url' => Url::fromRoute('page_manager.display_variant_select_block', [
          'page' => $page_id,
          'display_variant_id' => $this->id(),
        ]),
        '#attributes' => $add_button_attributes,
        '#attached' => [
          'library' => [
            'core/drupal.ajax',
          ],
        ],
      ];
      $form['block_section']['blocks'] = [
        '#type' => 'table',
        '#header' => [
          $this->t('Label'),
          $this->t('Plugin ID'),
          $this->t('Region'),
          $this->t('Weight'),
          $this->t('Operations'),
        ],
        '#empty' => $this->t('There are no regions for blocks.'),
        // @todo This should utilize https://drupal.org/node/2065485.
        '#parents' => ['display_variant', 'blocks'],
      ];
      // Loop through the blocks per region.
      foreach ($block_assignments as $region => $blocks) {
        // Add a section for each region and allow blocks to be dragged between
        // them.
        $form['block_section']['blocks']['#tabledrag'][] = [
          'action' => 'match',
          'relationship' => 'sibling',
          'group' => 'block-region-select',
          'subgroup' => 'block-region-' . $region,
          'hidden' => FALSE,
        ];
        $form['block_section']['blocks']['#tabledrag'][] = [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'block-weight',
          'subgroup' => 'block-weight-' . $region,
        ];
        $form['block_section']['blocks'][$region] = [
          '#attributes' => [
            'class' => ['region-title', 'region-title-' . $region],
            'no_striping' => TRUE,
          ],
        ];
        $form['block_section']['blocks'][$region]['title'] = [
          '#markup' => $this->getRegionName($region),
          '#wrapper_attributes' => [
            'colspan' => 5,
          ],
        ];
        $form['block_section']['blocks'][$region . '-message'] = [
          '#attributes' => [
            'class' => [
              'region-message',
              'region-' . $region . '-message',
              empty($blocks) ? 'region-empty' : 'region-populated',
            ],
          ],
        ];
        $form['block_section']['blocks'][$region . '-message']['message'] = [
          '#markup' => '<em>' . t('No blocks in this region') . '</em>',
          '#wrapper_attributes' => [
            'colspan' => 5,
          ],
        ];

        /** @var $blocks \Drupal\block\BlockPluginInterface[] */
        foreach ($blocks as $block_id => $block) {
          $row = [
            '#attributes' => [
              'class' => ['draggable'],
            ],
          ];
          $row['label']['#markup'] = $block->label();
          $row['id']['#markup'] = $block->getPluginId();
          // Allow the region to be changed for each block.
          $row['region'] = [
            '#title' => $this->t('Region'),
            '#title_display' => 'invisible',
            '#type' => 'select',
            '#options' => $this->getRegionNames(),
            '#default_value' => $this->getRegionAssignment($block_id),
            '#attributes' => [
              'class' => ['block-region-select', 'block-region-' . $region],
            ],
          ];
          // Allow the weight to be changed for each block.
          $configuration = $block->getConfiguration();
          $row['weight'] = [
            '#type' => 'weight',
            '#default_value' => isset($configuration['weight']) ? $configuration['weight'] : 0,
            '#title' => t('Weight for @block block', ['@block' => $block->label()]),
            '#title_display' => 'invisible',
            '#attributes' => [
              'class' => ['block-weight', 'block-weight-' . $region],
            ],
          ];
          // Add the operation links.
          $operations = [];
          $operations['edit'] = [
            'title' => $this->t('Edit'),
            'url' => Url::fromRoute('page_manager.display_variant_edit_block', [
              'page' => $page_id,
              'display_variant_id' => $this->id(),
              'block_id' => $block_id,
            ]),
            'attributes' => $attributes,
          ];
          $operations['delete'] = [
            'title' => $this->t('Delete'),
            'url' => Url::fromRoute('page_manager.display_variant_delete_block', [
              'page' => $page_id,
              'display_variant_id' => $this->id(),
              'block_id' => $block_id,
            ]),
            'attributes' => $attributes,
          ];

          $row['operations'] = [
            '#type' => 'operations',
            '#links' => $operations,
          ];
          $form['block_section']['blocks'][$block_id] = $row;
        }
      }
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    if ($form_state->hasValue('layout')) {
      $this->configuration['layout'] = $form_state->getValue('layout');
    }

    if ($form_state->hasValue('page_title')) {
      $this->configuration['page_title'] = $form_state->getValue('page_title');
    }

    // If the blocks were rearranged, update their values.
    if ($form_state->hasValue('blocks')) {
      foreach ($form_state->getValue('blocks') as $block_id => $block_values) {
        $this->updateBlock($block_id, $block_values);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function access(AccountInterface $account = NULL) {
    // If no blocks are configured for this variant, deny access.
    if (empty($this->configuration['blocks'])) {
      return FALSE;
    }

    // Delegate to the conditions.
    if ($this->determineSelectionAccess($this->getContexts()) === FALSE) {
      return FALSE;
    }

    return parent::access($account);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return parent::defaultConfiguration() + [
      'blocks' => [],
      'selection_conditions' => [],
      'selection_logic' => 'and',
      'page_title' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    foreach ($this->getBlockCollection() as $instance) {
      $this->calculatePluginDependencies($instance);
    }
    foreach ($this->getSelectionConditions() as $instance) {
      $this->calculatePluginDependencies($instance);
    }
    return $this->dependencies;
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return [
      'selection_conditions' => $this->getSelectionConditions()->getConfiguration(),
      'blocks' => $this->getBlockCollection()->getConfiguration(),
    ] + parent::getConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function getSelectionLogic() {
    return $this->configuration['selection_logic'];
  }

  /**
   * Renders the page title and replaces tokens.
   *
   * @param string $page_title
   *   The page title that should be rendered.
   *
   * @return string
   *   The page title after replacing any tokens.
   */
  protected function renderPageTitle($page_title) {
    $data = $this->getContextAsTokenData();
    return $this->token->replace($page_title, $data);
  }

  /**
   * Returns available context as token data.
   *
   * @return array
   *   An array with token data values keyed by token type.
   */
  protected function getContextAsTokenData() {
    $data = array();
    foreach ($this->executable->getContexts() as $context) {
      // @todo Simplify this when token and typed data types are unified in
      //   https://drupal.org/node/2163027.
      if (strpos($context->getContextDefinition()->getDataType(), 'entity:') === 0) {
        $token_type = substr($context->getContextDefinition()->getDataType(), 7);
        if ($token_type == 'taxonomy_term') {
          $token_type = 'term';
        }
        $data[$token_type] = $context->getContextValue();
      }
    }
    return $data;
  }

  /**
   * Wraps drupal_html_class().
   *
   * @return string
   */
  protected function drupalHtmlClass($class) {
    return drupal_html_class($class);
  }

  /**
   * {@inheritdoc}
   */
  protected function contextHandler() {
    return $this->contextHandler;
  }

  /**
   * {@inheritdoc}
   */
  protected function getSelectionConfiguration() {
    return $this->configuration['selection_conditions'];
  }

  /**
   * {@inheritdoc}
   */
  public function setExecutable(PageExecutableInterface $executable) {
    $this->executable = $executable;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  protected function getBlockConfig() {
    return $this->configuration['blocks'];
  }

  /**
   * {@inheritdoc}
   */
  protected function uuidGenerator() {
    return $this->uuidGenerator;
  }

}
