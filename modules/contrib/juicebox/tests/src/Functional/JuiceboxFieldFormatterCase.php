<?php

namespace Drupal\Tests\juicebox\Functional;

use Drupal\file\Entity\File;
use Drupal\Component\Utility\Html;
use Drupal\Tests\Traits\Core\CronRunTrait;

/**
 * Tests the Juicebox field formatter.
 *
 * @group Juicebox
 */
class JuiceboxFieldFormatterCase extends JuiceboxCaseTestBase {

  use CronRunTrait;

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = [
    'node',
    'field_ui',
    'image',
    'juicebox',
    'search',
    'contextual',
  ];

  /**
   * Define setup tasks.
   */
  public function setUp() {
    parent::setUp();
    // Create and login user.
    $this->webUser = $this->drupalCreateUser([
      'access content',
      'access administration pages',
      'administer site configuration',
      'administer content types',
      'administer nodes',
      'administer node fields',
      'administer node display',
      'bypass node access',
      'search content',
      'access contextual links',
    ]);
    $this->drupalLogin($this->webUser);
    // Prep a node with an image/file field and create a test entity.
    $this->initNode();
    // Activte the field formatter for our new node instance.
    $this->activateJuiceboxFieldFormatter();
    // Create a test node.
    $this->createNodeWithFile();
    // Start all cases as an anon user.
    $this->drupalLogout();
  }

  /**
   * Test configuration options that are specific to Juicebox field formatter.
   */
  public function testFieldFormatterConf() {
    $node = $this->node;
    // Do a set of control requests as an anon user that will also prime any
    // caches.
    $this->drupalGet('node/' . $node->id());
    $this->assertResponse(200, 'Control request of test node was successful.');
    $this->drupalGet('juicebox/xml/field/node/' . $node->id() . '/' . $this->instFieldName . '/full');
    $this->assertResponse(200, 'Control request of XML was successful.');
    // Alter field formatter specific settings to contain custom values.
    $this->drupalLogin($this->webUser);
    $this->drupalGet('admin/structure/types/manage/' . $this->instBundle . '/display');
    $this->submitForm([], $this->instFieldName . '_settings_edit', 'entity-view-display-edit-form');
    $edit = [
      'fields[' . $this->instFieldName . '][settings_edit_form][settings][image_style]' => '',
      'fields[' . $this->instFieldName . '][settings_edit_form][settings][thumb_style]' => 'thumbnail',
      'fields[' . $this->instFieldName . '][settings_edit_form][settings][caption_source]' => 'alt',
      'fields[' . $this->instFieldName . '][settings_edit_form][settings][title_source]' => 'title',
    ];
    $this->submitForm($edit, 'Save');
    $this->assertText($this->t('Your settings have been saved.'), 'Gallery configuration changes saved.');
    // Get the urls to the image and thumb derivatives expected.
    $uri = File::load($node->{$this->instFieldName}[0]->target_id)->getFileUri();
    $test_formatted_image_url = file_create_url($uri);
    $test_formatted_thumb_url = entity_load('image_style', 'thumbnail')->buildUrl($uri);
    // Check for correct embed markup as anon user.
    $this->drupalLogout();
    $this->drupalGet('node/' . $node->id());
    $this->assertRaw(Html::escape(file_url_transform_relative($test_formatted_image_url)), 'Test styled image found in embed code');
    // Check for correct XML.
    $this->drupalGet('juicebox/xml/field/node/' . $node->id() . '/' . $this->instFieldName . '/full');
    $this->assertRaw('imageURL="' . Html::escape($test_formatted_image_url), 'Test styled image found in XML.');
    $this->assertRaw('thumbURL="' . Html::escape($test_formatted_thumb_url), 'Test styled thumbnail found in XML.');
    // Note the intended title and caption text does not contain any block-level
    // tags as long as the global title and caption output filter is working.
    // So this acts as a test for that feature as well.
    $this->assertRaw('<title><![CDATA[Some title text for field ' . $this->instFieldName . ' on node ' . $node->id() . ']]></title>', 'Image title text found in XML');
    $this->assertRaw('<caption><![CDATA[Some alt text for field ' . $this->instFieldName . ' on node ' . $node->id() . ' &lt;strong&gt;with formatting&lt;/strong&gt;]]></caption>', 'Image caption text found in XML');
    // Now that we have title and caption data set, also ensure this text can
    // be found in search results. First we update the search index by marking
    // our test node as dirty and running cron.
    $this->drupalLogin($this->webUser);
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->submitForm([], 'Save');
    $this->cronRun();
    $this->drupalGet('search');
    $this->submitForm(['keys' => '"Some title text"'], 'Search');
    $this->assertText('Test Juicebox Gallery Node', 'Juicebox node found in search for title text.');
    // The Juicebox javascript should have been excluded from the search results
    // page.
    $this->assertNoRaw('"configUrl":"', 'Juicebox Drupal.settings vars not included on search result page.');
  }

  /**
   * Test access to the Juicebox XML for the field formatter.
   */
  public function testFieldFormatterAccess() {
    $node = $this->node;
    // The node and XML should be initially accessible (control test).
    $this->drupalGet('node/' . $node->id());
    $this->assertResponse(200, 'Access allowed for published node.');
    $this->drupalGet('juicebox/xml/field/node/' . $node->id() . '/' . $this->instFieldName . '/full');
    $this->assertRaw('<?xml version="1.0" encoding="UTF-8"', 'XML access allowed to published node (valid XML detected).');
    // Now unpublish the node as a way of making it inaccessible to
    // non-privileged users. There are unlimited ways that access can be
    // restricted, such as other perm settings, contrb module controls for
    // entities (node_access, tac, etc.), contrb module controls for fields
    // (field_permissions), etc. We can't test them all here, but we can run
    // this basic check to ensure that XML access restrictions kick-in.
    $node->status = NODE_NOT_PUBLISHED;
    $node->save();
    // Re-check access.
    $this->drupalGet('node/' . $node->id());
    $this->assertResponse(403, 'Access blocked for unpublished node.');
    $this->drupalGet('juicebox/xml/field/node/' . $node->id() . '/' . $this->instFieldName . '/full');
    $this->assertResponse(403, 'XML access blocked for unpublished node.');
  }

}
