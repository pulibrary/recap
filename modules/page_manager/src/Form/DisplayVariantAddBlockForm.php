<?php

/**
 * @file
 * Contains \Drupal\page_manager\Form\DisplayVariantAddBlockForm.
 */

namespace Drupal\page_manager\Form;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\page_manager\PageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides a form for adding a block plugin to a display variant.
 */
class DisplayVariantAddBlockForm extends DisplayVariantConfigureBlockFormBase {

  /**
   * The block manager.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $blockManager;

  /**
   * Constructs a new DisplayVariantFormBase.
   */
  public function __construct(PluginManagerInterface $block_manager) {
    $this->blockManager = $block_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.block')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'page_manager_display_variant_add_block_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareBlock($plugin_id) {
    $block = $this->blockManager->createInstance($plugin_id);
    $block_id = $this->displayVariant->addBlock($block->getConfiguration());
    return $this->displayVariant->getBlock($block_id);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, Request $request = NULL, PageInterface $page = NULL, $display_variant_id = NULL, $block_id = NULL) {
    $form = parent::buildForm($form, $form_state, $page, $display_variant_id, $block_id);
    $form['region']['#default_value'] = $request->query->get('region');
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function submitText() {
    return $this->t('Add block');
  }

}
