<?php

/**
 * @file
 * Contains \Drupal\page_manager\Plugin\BlockVariantTrait.
 */

namespace Drupal\page_manager\Plugin;

/**
 * Provides methods for \Drupal\page_manager\Plugin\BlockVariantInterface.
 */
trait BlockVariantTrait {

  /**
   * The plugin collection that holds the block plugins.
   *
   * @var \Drupal\page_manager\Plugin\BlockPluginCollection
   */
  protected $blockPluginCollection;

  /**
   * @see \Drupal\page_manager\Plugin\BlockVariantInterface::getRegionNames()
   */
  public function getRegionNames() {
    return [
      'top' => 'Top',
      'bottom' => 'Bottom',
    ];
  }

  /**
   * @see \Drupal\page_manager\Plugin\BlockVariantInterface::getBlock()
   */
  public function getBlock($block_id) {
    return $this->getBlockCollection()->get($block_id);
  }

  /**
   * @see \Drupal\page_manager\Plugin\BlockVariantInterface::addBlock()
   */
  public function addBlock(array $configuration) {
    $configuration['uuid'] = $this->uuidGenerator()->generate();
    $this->getBlockCollection()->addInstanceId($configuration['uuid'], $configuration);
    return $configuration['uuid'];
  }

  /**
   * @see \Drupal\page_manager\Plugin\BlockVariantInterface::removeBlock()
   */
  public function removeBlock($block_id) {
    $this->getBlockCollection()->removeInstanceId($block_id);
    return $this;
  }

  /**
   * @see \Drupal\page_manager\Plugin\BlockVariantInterface::updateBlock()
   */
  public function updateBlock($block_id, array $configuration) {
    $existing_configuration = $this->getBlock($block_id)->getConfiguration();
    $this->getBlockCollection()->setInstanceConfiguration($block_id, $configuration + $existing_configuration);
    return $this;
  }

  /**
   * @see \Drupal\page_manager\Plugin\BlockVariantInterface::getRegionAssignment()
   */
  public function getRegionAssignment($block_id) {
    $configuration = $this->getBlock($block_id)->getConfiguration();
    return isset($configuration['region']) ? $configuration['region'] : NULL;
  }

  /**
   * @see \Drupal\page_manager\Plugin\BlockVariantInterface::getRegionAssignments()
   */
  public function getRegionAssignments() {
    // Build an array of the region names in the right order.
    $empty = array_fill_keys(array_keys($this->getRegionNames()), []);
    $full = $this->getBlockCollection()->getAllByRegion();
    // Merge it with the actual values to maintain the ordering.
    return array_intersect_key(array_merge($empty, $full), $empty);
  }

  /**
   * @see \Drupal\page_manager\Plugin\BlockVariantInterface::getRegionName()
   */
  public function getRegionName($region) {
    $regions = $this->getRegionNames();
    return isset($regions[$region]) ? $regions[$region] : '';
  }

  /**
   * Returns the block plugins used for this display variant.
   *
   * @return \Drupal\Core\Block\BlockPluginInterface[]|\Drupal\page_manager\Plugin\BlockPluginCollection
   *   An array or collection of configured block plugins.
   */
  protected function getBlockCollection() {
    if (!$this->blockPluginCollection) {
      $this->blockPluginCollection = new BlockPluginCollection(\Drupal::service('plugin.manager.block'), $this->getBlockConfig());
    }
    return $this->blockPluginCollection;
  }

  /**
   * Returns the UUID generator.
   *
   * @return \Drupal\Component\Uuid\UuidInterface
   */
  abstract protected function uuidGenerator();

  /**
   * Returns the configuration for stored blocks.
   *
   * @return array
   *   An array of block configuration, keyed by the unique block ID.
   */
  abstract protected function getBlockConfig();

}
