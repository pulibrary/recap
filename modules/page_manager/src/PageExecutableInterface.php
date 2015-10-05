<?php

/**
 * @file
 * Contains \Drupal\page_manager\PageExecutableInterface.
 */

namespace Drupal\page_manager;

use Drupal\Component\Plugin\Context\ContextInterface;

/**
 * Interface implemented by page executables.
 *
 * A page executable represents a page entity during runtime execution.
 *
 * @see \Drupal\page_manager\PageInterface
 */
interface PageExecutableInterface {

  /**
   * Retrieves the underlying page entity.
   *
   * @return \Drupal\page_manager\PageInterface
   *   The page entity.
   */
  public function getPage();

  /**
   * Selects the display variant to use for the page entity.
   *
   * This loops through the available display variants and checks each for
   * access, returning the first one that is accessible.
   *
   * @return \Drupal\Core\Display\VariantInterface|null
   *   Either the first accessible display variant, or NULL if none are
   *   accessible.
   */
  public function selectDisplayVariant();

  /**
   * Gets the values for all defined contexts.
   *
   * @return \Drupal\Component\Plugin\Context\ContextInterface[]
   *   An array of set context values, keyed by context name.
   */
  public function getContexts();

  /**
   * Sets the context for a given name.
   *
   * @param string $name
   *   The name of the context.
   * @param \Drupal\Component\Plugin\Context\ContextInterface $value
   *   The context to add.
   *
   * @return $this
   */
  public function addContext($name, ContextInterface $value);

}
