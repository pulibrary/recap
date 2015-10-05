<?php

/**
 * @file
 * Contains \Drupal\page_manager\Form\DisplayVariantDeleteForm.
 */

namespace Drupal\page_manager\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\page_manager\PageInterface;
use Drupal\Core\Form\ConfirmFormBase;

/**
 * Provides a form for deleting a display variant.
 */
class DisplayVariantDeleteForm extends ConfirmFormBase {

  /**
   * The page entity this display variant belongs to.
   *
   * @var \Drupal\page_manager\PageInterface
   */
  protected $page;

  /**
   * The display variant.
   *
   * @var \Drupal\Core\Display\VariantInterface
   */
  protected $displayVariant;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'page_manager_display_variant_delete_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete the display variant %name?', ['%name' => $this->displayVariant->label()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return $this->page->urlInfo('edit-form');
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
  public function buildForm(array $form, FormStateInterface $form_state, PageInterface $page = NULL, $display_variant_id = NULL) {
    $this->page = $page;
    $this->displayVariant = $page->getVariant($display_variant_id);
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->page->removeVariant($this->displayVariant->id());
    $this->page->save();
    drupal_set_message($this->t('The display variant %name has been removed.', ['%name' => $this->displayVariant->label()]));
    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
