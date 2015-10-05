<?php

/**
 * @file
 * Contains \Drupal\page_manager\Form\DisplayVariantFormInterface.
 */

namespace Drupal\page_manager\Form;

use Drupal\Core\Form\BaseFormIdInterface;

/**
 * Provides an interface for forms used for editing or adding a display variant.
 */
interface DisplayVariantFormInterface extends BaseFormIdInterface {

  /**
   * Returns the page entity used by the form.
   *
   * @return \Drupal\page_manager\PageInterface
   */
  public function getPage();

  /**
   * Returns the display variant used by the form.
   *
   * @return \Drupal\Core\Display\VariantInterface
   */
  public function getDisplayVariant();

}
