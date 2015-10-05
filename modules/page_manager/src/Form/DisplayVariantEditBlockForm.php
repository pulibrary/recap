<?php

/**
 * @file
 * Contains \Drupal\page_manager\Form\DisplayVariantEditBlockForm.
 */

namespace Drupal\page_manager\Form;

/**
 * Provides a form for editing a block plugin of a display variant.
 */
class DisplayVariantEditBlockForm extends DisplayVariantConfigureBlockFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'page_manager_display_variant_edit_block_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareBlock($block_id) {
    return $this->displayVariant->getBlock($block_id);
  }

  /**
   * {@inheritdoc}
   */
  protected function submitText() {
    return $this->t('Update block');
  }

}
