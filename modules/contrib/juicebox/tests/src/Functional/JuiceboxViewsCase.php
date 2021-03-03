<?php

namespace Drupal\Tests\juicebox\Functional;

use Drupal\file\Entity\File;
use Drupal\Component\Utility\Html;

/**
 * Tests integration with Views module.
 *
 * @group Juicebox
 */
class JuiceboxViewsCase extends JuiceboxCaseTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = [
    'node',
    'text',
    'field',
    'image',
    'editor',
    'juicebox',
    'views',
    'contextual',
    'juicebox_mimic_article',
    'juicebox_test_views',
  ];

  /**
   * Bundle name.
   *
   * @var string
   */
  protected $instBundle = 'article';

  /**
   * Field name.
   *
   * @var string
   */
  protected $instFieldName = 'field_image';
  // Uncomment the line below, and remove juicebox_mimic_article from the module
  // list above, to use the "standard" profile's article type for this test
  // instead of the one we create manually (should also work, but will be slow).
  // $profile = 'standard;'.
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
      'bypass node access',
      'access contextual links',
      'use text format basic_html',
    ]);
    $this->drupalLogin($this->webUser);
    // Create a test node. Note that we don't need to initiate a node and field
    // structure before this because that's been handled for us by
    // juicebox_mimic_article.
    $this->createNodeWithFile('image', FALSE, FALSE);
    // Start all cases as an anon user.
    $this->drupalLogout();
  }

  /**
   * Test using pre-packaged advanced Juicebox view.
   *
   * The view tested here is largely the same as the "base" one tested above
   * but it includes tight access restrictions relationships.
   */
  public function testViewsAdvanced() {
    // Start as an real user as.
    $this->drupalLogin($this->webUser);
    $node = $this->node;
    $xml_path = 'juicebox/xml/viewsstyle/juicebox_views_test/page_2';
    $xml_url = \Drupal::url('juicebox.xml_viewsstyle',
     ['viewName' => 'juicebox_views_test', 'displayName' => 'page_2']);
    // Get the urls to the test image and thumb derivative used by default.
    $uri = File::load($node->{$this->instFieldName}[0]->target_id)->getFileUri();
    $test_image_url = entity_load('image_style', 'juicebox_medium')->buildUrl($uri);
    $test_thumb_url = entity_load('image_style', 'juicebox_square_thumb')->buildUrl($uri);
    // Check for correct embed markup.
    $this->drupalGet('juicebox-views-test-advanced');
    $this->assertRaw(trim(json_encode(['configUrl' => $xml_url]), '{}"'), 'Gallery setting found in Drupal.settings.');
    $this->assertRaw('juicebox-views-test--page-2', 'Embed code wrapper found.');
    $this->assertRaw(Html::escape(file_url_transform_relative($test_image_url)), 'Test image found in embed code');
    // Check for correct XML.
    $this->drupalGet($xml_path);
    $this->assertRaw('<?xml version="1.0" encoding="UTF-8"?>', 'Valid XML detected.');
    $this->assertRaw('imageURL="' . Html::escape($test_image_url), 'Test image found in XML.');
    $this->assertRaw('thumbURL="' . Html::escape($test_thumb_url), 'Test thumbnail found in XML.');
    // Logout and test that XML access is restricted. Note that this test view
    // is setup to limit view access only to admins.
    $this->drupalLogout();
    $this->drupalGet('juicebox/xml/viewsstyle/juicebox_views_test/page_2');
    $this->assertResponse(403, 'XML access blocked for access-restricted view.');
  }

}
