<?php

/**
 * @file
 * Contains \Drupal\page_manager\Form\SelectionConditionDeleteForm.
 */

namespace Drupal\page_manager\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\page_manager\PageInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Url;

/**
 * @todo.
 */
class SelectionConditionDeleteForm extends ConfirmFormBase {

  /**
   * The page entity this selection condition belongs to.
   *
   * @var \Drupal\page_manager\PageInterface
   */
  protected $page;

  /**
   * The display variant.
   *
   * @var \Drupal\page_manager\Plugin\ConditionVariantInterface
   */
  protected $displayVariant;

  /**
   * The selection condition used by this form.
   *
   * @var \Drupal\Core\Condition\ConditionInterface
   */
  protected $selectionCondition;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'page_manager_selection_condition_delete_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete the selection condition %name?', ['%name' => $this->selectionCondition->getPluginDefinition()['label']]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('page_manager.display_variant_edit', [
      'page' => $this->page->id(),
      'display_variant_id' => $this->displayVariant->id(),
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
  public function buildForm(array $form, FormStateInterface $form_state, PageInterface $page = NULL, $display_variant_id = NULL, $condition_id = NULL) {
    $this->page = $page;
    $this->displayVariant = $this->page->getVariant($display_variant_id);
    $this->selectionCondition = $this->displayVariant->getSelectionCondition($condition_id);
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->displayVariant->removeSelectionCondition($this->selectionCondition->getConfiguration()['uuid']);
    $this->page->save();
    drupal_set_message($this->t('The selection condition %name has been removed.', ['%name' => $this->selectionCondition->getPluginDefinition()['label']]));
    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
