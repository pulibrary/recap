<?php

/**
 * @file
 * Contains \Drupal\page_manager\Plugin\ContextAwareVariantInterface.
 */

namespace Drupal\page_manager\Plugin;

/**
 * Provides an interface for variant plugins that are context-aware.
 */
interface ContextAwareVariantInterface {

  /**
   * Gets the values for all defined contexts.
   *
   * @return \Drupal\Component\Plugin\Context\ContextInterface[]
   *   An array of set contexts, keyed by context name.
   */
  public function getContexts();

  /**
   * Sets the context values for this display variant.
   *
   * @param \Drupal\Component\Plugin\Context\ContextInterface[] $contexts
   *   An array of contexts, keyed by context name.
   *
   * @return $this
   */
  public function setContexts(array $contexts);

}
