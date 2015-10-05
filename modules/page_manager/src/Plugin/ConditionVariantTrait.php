<?php

/**
 * @file
 * Contains \Drupal\page_manager\Plugin\ConditionVariantTrait.
 */

namespace Drupal\page_manager\Plugin;

use Drupal\Core\Condition\ConditionAccessResolverTrait;
use Drupal\Core\Condition\ConditionPluginCollection;
use Drupal\Core\Plugin\ContextAwarePluginInterface;

/**
 * Provides methods for \Drupal\page_manager\Plugin\ConditionVariantInterface.
 */
trait ConditionVariantTrait {

  use ConditionAccessResolverTrait;

  /**
   * The plugin collection that holds the selection condition plugins.
   *
   * @var \Drupal\Component\Plugin\LazyPluginCollection
   */
  protected $selectionConditionCollection;

  /**
   * @see \Drupal\page_manager\Plugin\ConditionVariantInterface::getSelectionConditions()
   */
  public function getSelectionConditions() {
    if (!$this->selectionConditionCollection) {
      $this->selectionConditionCollection = new ConditionPluginCollection(\Drupal::service('plugin.manager.condition'), $this->getSelectionConfiguration());
    }
    return $this->selectionConditionCollection;
  }

  /**
   * @see \Drupal\page_manager\Plugin\ConditionVariantInterface::addSelectionCondition()
   */
  public function addSelectionCondition(array $configuration) {
    $configuration['uuid'] = $this->uuidGenerator()->generate();
    $this->getSelectionConditions()->addInstanceId($configuration['uuid'], $configuration);
    return $configuration['uuid'];
  }

  /**
   * @see \Drupal\page_manager\Plugin\ConditionVariantInterface::getSelectionCondition()
   */
  public function getSelectionCondition($condition_id) {
    return $this->getSelectionConditions()->get($condition_id);
  }

  /**
   * @see \Drupal\page_manager\Plugin\ConditionVariantInterface::removeSelectionCondition()
   */
  public function removeSelectionCondition($condition_id) {
    $this->getSelectionConditions()->removeInstanceId($condition_id);
    return $this;
  }

  /**
   * Determines if the selection conditions will pass given a set of contexts.
   *
   * @param \Drupal\Component\Plugin\Context\ContextInterface[] $contexts
   *   An array of set contexts, keyed by context name.
   *
   * @return bool
   *   TRUE if access is granted, FALSE otherwise.
   */
  protected function determineSelectionAccess(array $contexts) {
    $conditions = $this->getSelectionConditions();
    foreach ($conditions as $condition) {
      if ($condition instanceof ContextAwarePluginInterface) {
        $this->contextHandler()->applyContextMapping($condition, $contexts);
      }
    }
    return $this->resolveConditions($conditions, $this->getSelectionLogic());
  }

  /**
   * @see \Drupal\page_manager\Plugin\ConditionVariantInterface::getSelectionLogic()
   */
  abstract public function getSelectionLogic();

  /**
   * Returns the configuration for stored selection conditions.
   *
   * @return array
   *   An array of condition configuration, keyed by the unique condition ID.
   */
  abstract protected function getSelectionConfiguration();

  /**
   * Returns the UUID generator.
   *
   * @return \Drupal\Component\Uuid\UuidInterface
   */
  abstract protected function uuidGenerator();

  /**
   * Returns the context handler.
   *
   * @return \Drupal\Core\Plugin\Context\ContextHandlerInterface
   */
  abstract protected function contextHandler();

}
