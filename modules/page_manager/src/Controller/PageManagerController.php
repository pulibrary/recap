<?php

/**
 * @file
 * Contains \Drupal\page_manager\Controller\PageManagerController.
 */

namespace Drupal\page_manager\Controller;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Block\BlockManagerInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Plugin\Context\ContextHandlerInterface;
use Drupal\Core\Url;
use Drupal\page_manager\PageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides route controllers for Page Manager.
 */
class PageManagerController extends ControllerBase {

  /**
   * The block manager.
   *
   * @var \Drupal\Core\Block\BlockManagerInterface
   */
  protected $blockManager;

  /**
   * The condition manager.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $conditionManager;

  /**
   * The variant manager.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $variantManager;

  /**
   * The context handler.
   *
   * @var \Drupal\Core\Plugin\Context\ContextHandlerInterface
   */
  protected $contextHandler;

  /**
   * Constructs a new DisplayVariantEditForm.
   *
   * @param \Drupal\Core\Block\BlockManagerInterface $block_manager
   *   The block manager.
   * @param \Drupal\Component\Plugin\PluginManagerInterface $condition_manager
   *   The condition manager.
   * @param \Drupal\Component\Plugin\PluginManagerInterface $variant_manager
   *   The variant manager.
   * @param \Drupal\Core\Plugin\Context\ContextHandlerInterface $context_handler
   *   The context handler.
   */
  public function __construct(BlockManagerInterface $block_manager, PluginManagerInterface $condition_manager, PluginManagerInterface $variant_manager, ContextHandlerInterface $context_handler) {
    $this->blockManager = $block_manager;
    $this->conditionManager = $condition_manager;
    $this->variantManager = $variant_manager;
    $this->contextHandler = $context_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.block'),
      $container->get('plugin.manager.condition'),
      $container->get('plugin.manager.display_variant'),
      $container->get('context.handler')
    );
  }

  /**
   * Route title callback.
   *
   * @param \Drupal\page_manager\PageInterface $page
   *   The page entity.
   *
   * @return string
   *   The title for the page edit form.
   */
  public function editPageTitle(PageInterface $page) {
    return $this->t('Edit %label page', ['%label' => $page->label()]);
  }

  /**
   * Route title callback.
   *
   * @param \Drupal\page_manager\PageInterface $page
   *   The page entity.
   * @param string $display_variant_id
   *   The display variant ID.
   *
   * @return string
   *   The title for the display variant edit form.
   */
  public function editDisplayVariantTitle(PageInterface $page, $display_variant_id) {
    $display_variant = $page->getVariant($display_variant_id);
    return $this->t('Edit %label display variant', ['%label' => $display_variant->label()]);
  }

  /**
   * Route title callback.
   *
   * @param \Drupal\page_manager\PageInterface $page
   *   The page entity.
   * @param string $condition_id
   *   The access condition ID.
   *
   * @return string
   *   The title for the access condition edit form.
   */
  public function editAccessConditionTitle(PageInterface $page, $condition_id) {
    $access_condition = $page->getAccessCondition($condition_id);
    return $this->t('Edit %label access condition', ['%label' => $access_condition->getPluginDefinition()['label']]);
  }

  /**
   * Route title callback.
   *
   * @param \Drupal\page_manager\PageInterface $page
   *   The page entity.
   * @param string $display_variant_id
   *   The display variant ID.
   * @param string $condition_id
   *   The selection condition ID.
   *
   * @return string
   *   The title for the selection condition edit form.
   */
  public function editSelectionConditionTitle(PageInterface $page, $display_variant_id, $condition_id) {
    /** @var \Drupal\page_manager\Plugin\ConditionVariantInterface $display_variant */
    $display_variant = $page->getVariant($display_variant_id);
    $selection_condition = $display_variant->getSelectionCondition($condition_id);
    return $this->t('Edit %label selection condition', ['%label' => $selection_condition->getPluginDefinition()['label']]);
  }

  /**
   * Route title callback.
   *
   * @param \Drupal\page_manager\PageInterface $page
   *   The page entity.
   * @param string $name
   *   The static context name.
   *
   * @return string
   *   The title for the static context edit form.
   */
  public function editStaticContextTitle(PageInterface $page, $name) {
    $static_context = $page->getStaticContext($name);
    return $this->t('Edit @label static context', ['@label' => $static_context['label']]);
  }

  /**
   * Enables or disables a Page.
   *
   * @param \Drupal\page_manager\PageInterface $page
   *   The page entity.
   * @param string $op
   *   The operation to perform, usually 'enable' or 'disable'.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirect back to the pages list page.
   */
  public function performPageOperation(PageInterface $page, $op) {
    $page->$op()->save();

    if ($op == 'enable') {
      drupal_set_message($this->t('The %label page has been enabled.', ['%label' => $page->label()]));
    }
    elseif ($op == 'disable') {
      drupal_set_message($this->t('The %label page has been disabled.', ['%label' => $page->label()]));
    }

    return $this->redirect('page_manager.page_list');
  }

  /**
   * Presents a list of display variants to add to the page entity.
   *
   * @param \Drupal\page_manager\PageInterface $page
   *   The page entity.
   *
   * @return array
   *   The display variant selection page.
   */
  public function selectDisplayVariant(PageInterface $page) {
    $build = [
      '#theme' => 'links',
      '#links' => [],
    ];
    foreach ($this->variantManager->getDefinitions() as $display_variant_id => $display_variant) {
      $build['#links'][$display_variant_id] = [
        'title' => $display_variant['admin_label'],
        'url' => Url::fromRoute('page_manager.display_variant_add', [
          'page' => $page->id(),
          'display_variant_id' => $display_variant_id,
        ]),
        'attributes' => [
          'class' => ['use-ajax'],
          'data-dialog-type' => 'modal',
          'data-dialog-options' => Json::encode([
            'width' => 'auto',
          ]),
        ],
      ];
    }
    return $build;
  }

  /**
   * Presents a list of access conditions to add to the page entity.
   *
   * @param \Drupal\page_manager\PageInterface $page
   *   The page entity.
   *
   * @return array
   *   The access condition selection page.
   */
  public function selectAccessCondition(PageInterface $page) {
    $build = [
      '#theme' => 'links',
      '#links' => [],
    ];
    $available_plugins = $this->conditionManager->getDefinitionsForContexts($page->getContexts());
    foreach ($available_plugins as $access_id => $access_condition) {
      $build['#links'][$access_id] = [
        'title' => $access_condition['label'],
        'url' => Url::fromRoute('page_manager.access_condition_add', [
          'page' => $page->id(),
          'condition_id' => $access_id,
        ]),
        'attributes' => [
          'class' => ['use-ajax'],
          'data-dialog-type' => 'modal',
          'data-dialog-options' => Json::encode([
            'width' => 'auto',
          ]),
        ],
      ];
    }
    return $build;
  }

  /**
   * Presents a list of selection conditions to add to the page entity.
   *
   * @param \Drupal\page_manager\PageInterface $page
   *   The page entity.
   * @param string $display_variant_id
   *   The display variant ID.
   *
   * @return array
   *   The selection condition selection page.
   */
  public function selectSelectionCondition(PageInterface $page, $display_variant_id) {
    $build = [
      '#theme' => 'links',
      '#links' => [],
    ];
    $available_plugins = $this->conditionManager->getDefinitionsForContexts($page->getContexts());
    foreach ($available_plugins as $selection_id => $selection_condition) {
      $build['#links'][$selection_id] = [
        'title' => $selection_condition['label'],
        'url' => Url::fromRoute('page_manager.selection_condition_add', [
          'page' => $page->id(),
          'display_variant_id' => $display_variant_id,
          'condition_id' => $selection_id,
        ]),
        'attributes' => [
          'class' => ['use-ajax'],
          'data-dialog-type' => 'modal',
          'data-dialog-options' => Json::encode([
            'width' => 'auto',
          ]),
        ],
      ];
    }
    return $build;
  }

  /**
   * Presents a list of blocks to add to the display variant.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   * @param \Drupal\page_manager\PageInterface $page
   *   The page entity.
   * @param string $display_variant_id
   *   The display variant ID.
   *
   * @return array
   *   The block selection page.
   */
  public function selectBlock(Request $request, PageInterface $page, $display_variant_id) {
    // Add a section containing the available blocks to be added to the variant.
    $build = [
      '#type' => 'container',
      '#attached' => [
        'library' => [
          'core/drupal.ajax',
        ],
      ],
    ];
    $available_plugins = $this->blockManager->getDefinitionsForContexts($page->getContexts());
    foreach ($available_plugins as $plugin_id => $plugin_definition) {
      // Make a section for each region.
      $category = SafeMarkup::checkPlain($plugin_definition['category']);
      $category_key = 'category-' . $category;
      if (!isset($build[$category_key])) {
        $build[$category_key] = [
          '#type' => 'fieldgroup',
          '#title' => $category,
          'content' => [
            '#theme' => 'links',
          ],
        ];
      }
      // Add a link for each available block within each region.
      $build[$category_key]['content']['#links'][$plugin_id] = [
        'title' => $plugin_definition['admin_label'],
        'url' => Url::fromRoute('page_manager.display_variant_add_block', [
          'page' => $page->id(),
          'display_variant_id' => $display_variant_id,
          'block_id' => $plugin_id,
          'region' => $request->query->get('region'),
        ]),
        'attributes' => [
          'class' => ['use-ajax'],
          'data-dialog-type' => 'modal',
          'data-dialog-options' => Json::encode([
            'width' => 'auto',
          ]),
        ],
      ];
    }
    return $build;
  }

}
