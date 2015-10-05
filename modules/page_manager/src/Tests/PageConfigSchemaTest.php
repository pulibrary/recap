<?php

/**
 * @file
 * Contains \Drupal\page_manager\Tests\PageConfigSchemaTest.
 */

namespace Drupal\page_manager\Tests;

use Drupal\config\Tests\SchemaCheckTestTrait;
use Drupal\page_manager\Entity\Page;
use Drupal\simpletest\KernelTestBase;

/**
 * Ensures that page entities have valid config schema.
 *
 * @group page_manager
 */
class PageConfigSchemaTest extends KernelTestBase {

  use SchemaCheckTestTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['page_manager', 'block', 'node', 'user'];

  /**
   * Tests whether the page entity config schema is valid.
   */
  public function testValidPageConfigSchema() {
    $id = strtolower($this->randomMachineName());
    /** @var $page \Drupal\page_manager\PageInterface */
    $page = Page::create([
      'id' => $id,
      'label' => $this->randomMachineName(),
      'path' => '/node/{node}',
    ]);

    // Add an access condition.
    $page->addAccessCondition([
      'id' => 'node_type',
      'bundles' => [
        'article' => 'article',
      ],
      'negate' => TRUE,
      'context_mapping' => [
        'node' => 'node',
      ],
    ]);

    // Add a block display variant.
    $display_variant_id = $page->addVariant([
      'id' => 'block_display',
      'label' => 'Block page',
    ]);
    /** @var $display_variant \Drupal\page_manager\Plugin\DisplayVariant\BlockDisplayVariant */
    $display_variant = $page->getVariant($display_variant_id);

    // Add a selection condition.
    $display_variant->addSelectionCondition([
      'id' => 'node_type',
      'bundles' => [
        'page' => 'page',
      ],
      'context_mapping' => [
        'node' => 'node',
      ],
    ]);

    // Add a block.
    $display_variant->addBlock([
      'id' => 'entity_view:node',
      'label' => 'View the node',
      'provider' => 'page_manager',
      'label_display' => 'visible',
      'view_mode' => 'default',
    ]);
    $page->save();

    $config = \Drupal::config("page_manager.page.$id");
    $this->assertEqual($config->get('id'), $id);
    $this->assertConfigSchema(\Drupal::service('config.typed'), $config->getName(), $config->get());
  }

}
