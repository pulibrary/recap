<?php

/**
 * @file
 * Contains \Drupal\page_manager\Form\DisplayVariantConfigureBlockFormBase.
 */

namespace Drupal\page_manager\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContextAwarePluginAssignmentTrait;
use Drupal\Core\Plugin\ContextAwarePluginInterface;
use Drupal\page_manager\PageInterface;

/**
 * Provides a base form for configuring a block as part of a display variant.
 */
abstract class DisplayVariantConfigureBlockFormBase extends FormBase {

  use ContextAwarePluginAssignmentTrait;

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
   * Prepares the block plugin based on the block ID.
   *
   * @param string $block_id
   *   Either a block ID, or the plugin ID used to create a new block.
   *
   * @return \Drupal\Core\Block\BlockPluginInterface
   *   The block plugin.
   */
  abstract protected function prepareBlock($block_id);

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
  public function buildForm(array $form, FormStateInterface $form_state, PageInterface $page = NULL, $display_variant_id = NULL, $block_id = NULL) {
    $this->page = $page;
    $this->displayVariant = $page->getVariant($display_variant_id);
    $this->block = $this->prepareBlock($block_id);
    $form_state->set('display_variant_id', $display_variant_id);
    $form_state->set('block_id', $this->block->getConfiguration()['uuid']);

    $form['#tree'] = TRUE;
    $form['settings'] = $this->block->buildConfigurationForm([], $form_state);
    $form['settings']['id'] = [
      '#type' => 'value',
      '#value' => $this->block->getPluginId(),
    ];
    $form['region'] = [
      '#title' => $this->t('Region'),
      '#type' => 'select',
      '#options' => $this->displayVariant->getRegionNames(),
      '#default_value' => $this->displayVariant->getRegionAssignment($this->block->getConfiguration()['uuid']),
      '#required' => TRUE,
    ];

    if ($this->block instanceof ContextAwarePluginInterface) {
      $form['context_mapping'] = $this->addContextAssignmentElement($this->block, $this->page->getContexts());
    }

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
    // The page might have been serialized, resulting in a new display variant
    // collection. Refresh the display variant and block objects.
    $this->displayVariant = $this->page->getVariant($form_state->get('display_variant_id'));
    $this->block = $this->displayVariant->getBlock($form_state->get('block_id'));

    $settings = (new FormState())->setValues($form_state->getValue('settings'));
    // Call the plugin validate handler.
    $this->block->validateConfigurationForm($form, $settings);
    // Update the original form values.
    $form_state->setValue('settings', $settings->getValues());
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $settings = (new FormState())->setValues($form_state->getValue('settings'));

    // Call the plugin submit handler.
    $this->block->submitConfigurationForm($form, $settings);
    // Update the original form values.
    $form_state->setValue('settings', $settings->getValues());

    if ($this->block instanceof ContextAwarePluginInterface) {
      $this->block->setContextMapping($form_state->getValue('context_mapping', []));
    }

    $this->displayVariant->updateBlock($this->block->getConfiguration()['uuid'], ['region' => $form_state->getValue('region')]);
    $this->page->save();

    $form_state->setRedirect('page_manager.display_variant_edit', [
      'page' => $this->page->id(),
      'display_variant_id' => $this->displayVariant->id(),
    ]);
  }

}
