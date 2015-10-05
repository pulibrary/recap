<?php

/**
 * @file
 * Contains \Drupal\page_manager\Plugin\PageAwareVariantInterface.
 */

namespace Drupal\page_manager\Plugin;

use Drupal\Core\Display\VariantInterface;
use Drupal\page_manager\PageExecutableInterface;

/**
 * Provides an interface for variant plugins that are Page-aware.
 */
interface PageAwareVariantInterface extends VariantInterface {

  /**
   * Initializes the display variant.
   *
   * Only used during runtime.
   *
   * @param \Drupal\page_manager\PageExecutableInterface $executable
   *   The page executable.
   *
   * @return $this
   */
  public function setExecutable(PageExecutableInterface $executable);

}
