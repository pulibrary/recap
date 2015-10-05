<?php

/**
 * @file
 * Contains \Drupal\page_manager\Tests\PageManagerTranslationIntegrationTest.
 */

namespace Drupal\page_manager\Tests;

use Drupal\content_translation\Tests\ContentTranslationTestBase;

/**
 * Tests that overriding the entity page does not affect content translation.
 *
 * @group page_manager
 */
class PageManagerTranslationIntegrationTest extends ContentTranslationTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['page_manager', 'node', 'content_translation'];

  /**
   * {@inheritdoc}
   */
  protected $entityTypeId = 'node';

  /**
   * {@inheritdoc}
   */
  protected $bundle = 'article';

  /**
   * {@inheritdoc}
   */
  protected function setupBundle() {
    parent::setupBundle();
    $this->drupalCreateContentType(['type' => 'article', 'name' => 'Article']);
  }

  /**
   * {@inheritdoc}
   */
  protected function getTranslatorPermissions() {
    return array_merge(parent::getTranslatorPermissions(), ['administer pages', 'administer pages']);
  }

  /**
   * Tests that overriding the node page does not prevent translation.
   */
  public function testNode() {
    $node = $this->drupalCreateNode(['type' => 'article']);
    $this->drupalGet('node/' . $node->id());
    $this->assertResponse(200);
    $this->assertText($node->label());
    $this->clickLink('Translate');
    $this->assertResponse(200);

    // Create a new page entity to take over node pages.
    $edit = [
      'label' => 'Node View',
      'id' => 'node_view',
      'path' => 'node/%',
    ];
    $this->drupalPostForm('admin/structure/page_manager/add', $edit, 'Save');
    $this->clickLink('Edit');
    $this->drupalPostForm(NULL, ['display_variant[status_code]' => 200], 'Update display variant');

    $this->drupalGet('node/' . $node->id());
    $this->assertResponse(200);
    $this->clickLink('Translate');
    $this->assertResponse(200);
  }

}
