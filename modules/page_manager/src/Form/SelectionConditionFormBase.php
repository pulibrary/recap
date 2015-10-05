<?php

/**
 * @file
 * Contains \Drupal\page_manager\Form\SelectionConditionFormBase.
 */

namespace Drupal\page_manager\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\page_manager\PageInterface;

/**
 * Provides a base form for editing and adding a selection condition.
 */
abstract class SelectionConditionFormBase extends ConditionFormBase {

  /**
   * The display variant.
   *
   * @var \Drupal\page_manager\Plugin\ConditionVariantInterface
   */
  protected $displayVariant;

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, PageInterface $page = NULL, $display_variant_id = NULL, $condition_id = NULL) {
    $this->displayVariant = $page->getVariant($display_variant_id);
    return parent::buildForm($form, $form_state, $page, $condition_id);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $configuration = $this->condition->getConfiguration();
    // If this selection condition is new, add it to the page.
    if (!isset($configuration['uuid'])) {
      $this->displayVariant->addSelectionCondition($configuration);
    }

    // Save the page entity.
    $this->page->save();

    $form_state->setRedirect('page_manager.display_variant_edit', [
      'page' => $this->page->id(),
      'display_variant_id' => $this->displayVariant->id(),
    ]);
  }

}
