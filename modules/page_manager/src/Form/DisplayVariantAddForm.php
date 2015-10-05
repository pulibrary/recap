<?php

/**
 * @file
 * Contains \Drupal\page_manager\Form\DisplayVariantAddForm.
 */

namespace Drupal\page_manager\Form;

use Drupal\Core\Display\VariantManager;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for adding a new display variant.
 */
class DisplayVariantAddForm extends DisplayVariantFormBase {

  /**
   * The variant manager.
   *
   * @var \Drupal\Core\Display\VariantManager
   */
  protected $variantManager;

  /**
   * Constructs a new DisplayVariantAddForm.
   *
   * @param \Drupal\Core\Display\VariantManager $variant_manager
   *   The variant manager.
   */
  public function __construct(VariantManager $variant_manager) {
    $this->variantManager = $variant_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.display_variant')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'page_manager_display_variant_add_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function submitText() {
    return $this->t('Add display variant');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    // If this display variant is new, add it to the page.
    $display_variant_id = $this->page->addVariant($this->displayVariant->getConfiguration());

    // Save the page entity.
    $this->page->save();
    drupal_set_message($this->t('The %label display variant has been added.', ['%label' => $this->displayVariant->label()]));
    $form_state->setRedirect('page_manager.display_variant_edit', [
      'page' => $this->page->id(),
      'display_variant_id' => $display_variant_id,
    ]);
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareDisplayVariant($display_variant_id) {
    // Create a new display variant instance.
    return $this->variantManager->createInstance($display_variant_id);
  }

}
