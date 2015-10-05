<?php

/**
 * @file
 * Contains \Drupal\page_manager\Form\DisplayVariantFormBase.
 */

namespace Drupal\page_manager\Form;

use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\page_manager\PageInterface;
use Drupal\Core\Form\FormBase;
use Drupal\page_manager\Plugin\PageAwareVariantInterface;

/**
 * Provides a base form for editing and adding a display variant.
 */
abstract class DisplayVariantFormBase extends FormBase implements DisplayVariantFormInterface {

  /**
   * The page entity this display variant belongs to.
   *
   * @var \Drupal\page_manager\PageInterface
   */
  protected $page;

  /**
   * The display variant used by this form.
   *
   * @var \Drupal\Core\Display\VariantInterface
   */
  protected $displayVariant;

  /**
   * Prepares the display variant used by this form.
   *
   * @param string $display_variant_id
   *   Either a display variant ID, or the plugin ID used to create a new
   *   variant.
   *
   * @return \Drupal\Core\Display\VariantInterface
   *   The display variant object.
   */
  abstract protected function prepareDisplayVariant($display_variant_id);

  /**
   * {@inheritdoc}
   */
  public function getPage() {
    return $this->page;
  }

  /**
   * {@inheritdoc}
   */
  public function getDisplayVariant() {
    return $this->displayVariant;
  }

  /**
   * Returns the text to use for the submit button.
   *
   * @return string
   *   The submit button text.
   */
  abstract protected function submitText();

  /**
   * {@inheritdoc}
   */
  public function getBaseFormId() {
    return 'page_manager_display_variant_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, PageInterface $page = NULL, $display_variant_id = NULL) {
    $this->page = $page;
    $this->displayVariant = $this->prepareDisplayVariant($display_variant_id);
    if ($this->displayVariant instanceof PageAwareVariantInterface) {
      $this->displayVariant->setExecutable($this->page->getExecutable());
    }

    // Allow the display variant to add to the form.
    $form['display_variant'] = $this->displayVariant->buildConfigurationForm([], $form_state);
    $form['display_variant']['#tree'] = TRUE;

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->submitText(),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Allow the display variant to validate the form.
    $display_variant_values = (new FormState())->setValues($form_state->getValue('display_variant'));
    $this->displayVariant->validateConfigurationForm($form, $display_variant_values);
    // Update the original form values.
    $form_state->setValue('display_variant', $display_variant_values->getValues());
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Allow the display variant to submit the form.
    $display_variant_values = (new FormState())->setValues($form_state->getValue('display_variant'));
    $this->displayVariant->submitConfigurationForm($form, $display_variant_values);
    // Update the original form values.
    $form_state->setValue('display_variant', $display_variant_values->getValues());
  }

}
