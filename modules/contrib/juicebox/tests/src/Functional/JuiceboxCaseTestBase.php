<?php

namespace Drupal\Tests\juicebox\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Tests\TestFileCreationTrait;

/**
 * Common helper class for Juicebox module tests.
 *
 * @group juicebox
 */
abstract class JuiceboxCaseTestBase extends BrowserTestBase {

  use TestFileCreationTrait;

  /**
   * Common variables.
   *
   * @var mixed
   */
  protected $webUser;
  // Properties to store details of the field that will be use in a field.
  /**
   * Formatter test.
   *
   * @var mixed
   */
  protected $node;

  /**
   * Bundle name.
   *
   * @var string
   */
  protected $instBundle = 'juicebox_gallery';

  /**
   * Field name.
   *
   * @var string
   */
  protected $instFieldName = 'field_juicebox_image';

  /**
   * Field type.
   *
   * @var string
   */
  protected $instFieldType = 'image';

  /**
   * Setup a new content type, with a image/file field.
   */
  protected function initNode() {
    // Create a new content type.
    $this->drupalCreateContentType([
      'type' => $this->instBundle,
      'name' => $this->instBundle,
    ]);
    // Prep a field base.
    $field_storage_settings = [
      'display_field' => TRUE,
      'display_default' => TRUE,
      'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
    ];
    $field_storage = [
      'entity_type' => 'node',
      'field_name' => $this->instFieldName,
      'type' => $this->instFieldType,
      'settings' => $field_storage_settings,
      'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
    ];
    entity_create('field_storage_config', $field_storage)->save();
    // Prep a field instance.
    $field_settings = [];
    if ($this->instFieldType == 'image') {
      $field_settings['alt_field'] = TRUE;
      $field_settings['alt_field_required'] = FALSE;
      $field_settings['title_field'] = TRUE;
      $field_settings['title_field_required'] = FALSE;
    }
    if ($this->instFieldType == 'file') {
      $field_settings['description_field'] = TRUE;
      $field_settings['file_extensions'] = 'txt jpg png mp3 rtf docx pdf';
    }
    $field = [
      'field_name' => $this->instFieldName,
      'label' => $this->randomString(),
      'entity_type' => 'node',
      'bundle' => $this->instBundle,
      'required' => FALSE,
      'settings' => $field_settings,
    ];
    entity_create('field_config', $field)->save();
    // Setup widget.
    entity_get_form_display('node', $this->instBundle, 'default')
      ->setComponent($this->instFieldName, [
        'type' => 'file_generic',
        'settings' => [],
      ])
      ->save();
    // Clear some caches for good measure.
    $entity_manager = $this->container->get('entity.manager');
    $entity_manager->getStorage('field_storage_config')->resetCache();
    $entity_manager->getStorage('field_config')->resetCache();
  }

  /**
   * Helper to activate a Juicebox field formatter on a field.
   */
  protected function activateJuiceboxFieldFormatter() {
    entity_get_display('node', $this->instBundle, 'default')
      ->setComponent($this->instFieldName, [
        'type' => 'juicebox_formatter',
        'settings' => [],
      ])
      ->save();
  }

  /**
   * Helper to create a node and upload a file to it.
   */
  protected function createNodeWithFile($file_type = 'image', $multivalue = TRUE, $add_title_caption = TRUE) {
    $file = current($this->getTestFiles($file_type));
    $edit = [
      'title[0][value]' => 'Test Juicebox Gallery Node',
      'files[' . $this->instFieldName . '_0]' . ($multivalue ? '[]' : '') => \Drupal::service('file_system')->realpath($file->uri),
    ];
    $this->drupalGet('node/add/' . $this->instBundle);
    $this->submitForm($edit, 'Save');
    // Get ID of the newly created node from the current URL.
    $matches = [];
    preg_match('/node\/([0-9]+)/', $this->getUrl(), $matches);
    if (isset($matches[1])) {
      $nid = $matches[1];
      // Now re-edit the node to add title and caption values for the newly
      // uploaded image. This could probably also be done above with
      // DrupalWebTestCase::drupalPostAJAX(), but this works too.
      $edit = [
        'body[0][value]' => 'Some body content on node ' . $nid . ' <strong>with formatting</strong>',
      ];
      if ($add_title_caption) {
        if ($this->instFieldType == 'image') {
          $edit[$this->instFieldName . '[0][title]'] = 'Some title text for field ' . $this->instFieldName . ' on node ' . $nid;
          $edit[$this->instFieldName . '[0][alt]'] = 'Some alt text for field ' . $this->instFieldName . ' on node ' . $nid . ' <strong>with formatting</strong>';
        }
        if ($this->instFieldType == 'file') {
          $edit[$this->instFieldName . '[0][description]'] = 'Some description text for field ' . $this->instFieldName . ' on node ' . $nid . ' <strong>with formatting</strong>';
        }
      }
      $this->drupalGet('node/' . $nid . '/edit');
      $this->submitForm($edit, 'Save');
      // Clear some caches for good measure and save the node object for
      // reference during tests.
      $node_storage = $this->container->get('entity.manager')->getStorage('node');
      $node_storage->resetCache([$nid]);
      $this->node = $node_storage->load($nid);
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Get server-rendered contextual links for the given contextual link ids.
   *
   * @param array $ids
   *   An array of contextual link ids.
   * @param string $current_path
   *   The Drupal path for the page for which the contextual links are rendered.
   *
   * @return string
   *   The response body.
   */
  protected function renderContextualLinks(array $ids, $current_path) {
    $post = [];
    for ($i = 0; $i < count($ids); $i++) {
      $post['ids[' . $i . ']'] = $ids[$i];
    }
    return $this->drupalPost('contextual/render', 'application/json', $post, ['query' => ['destination' => $current_path]]);
  }

}
