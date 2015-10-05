<?php

/**
 * @file
 * Contains \Drupal\page_manager\Form\DisplayVariantDeleteBlockForm.
 */

namespace Drupal\page_manager\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\page_manager\PageInterface;

/**
 * Provides a form for deleting an access condition.
 */
class DisplayVariantDeleteBlockForm extends ConfirmFormBase {

  /**
   * The page entity.
   *
   * @var \Drupal\page_manager\PageInterface
   */
  protected $page;

  /**
   * The display variant.
   *
   * @var \Drupal\page_manager\Plugin\BlockVariantInterface
   */
  protected $displayVariant;

  /**
   * The plugin being configured.
   *
   * @var \Drupal\Core\Block\BlockPluginInterface
   */
  protected $block;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'page_manager_display_variant_delete_block_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete the block %label?', ['%label' => $this->block->label()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('page_manager.display_variant_edit', [
      'page' => $this->page->id(),
      'display_variant_id' => $this->displayVariant->id()
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, PageInterface $page = NULL, $display_variant_id = NULL, $block_id = NULL) {
    $this->page = $page;
    $this->displayVariant = $this->page->getVariant($display_variant_id);
    $this->block = $this->displayVariant->getBlock($block_id);
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->displayVariant->removeBlock($this->block->getConfiguration()['uuid']);
    $this->page->save();
    drupal_set_message($this->t('The block %label has been removed.', ['%label' => $this->block->label()]));

    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
