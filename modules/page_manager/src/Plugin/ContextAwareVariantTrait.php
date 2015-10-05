<?php

/**
 * @file
 * Contains \Drupal\page_manager\Plugin\ContextAwareVariantTrait.
 */

namespace Drupal\page_manager\Plugin;

/**
 * Provides methods for \Drupal\page_manager\Plugin\ContextAwareVariantInterface.
 */
trait ContextAwareVariantTrait {

  /**
   * An array of collected contexts.
   *
   * This is only used on runtime, and is not stored.
   *
   * @var \Drupal\Component\Plugin\Context\ContextInterface[]
   */
  protected $contexts = [];

  /**
   * {@inheritdoc}
   *
   * @see \Drupal\page_manager\Plugin\ContextAwareVariantInterface
   */
  public function getContexts() {
    return $this->contexts;
  }

  /**
   * {@inheritdoc}
   */
  public function setContexts(array $contexts) {
    $this->contexts = $contexts;
    return $this;
  }

}
