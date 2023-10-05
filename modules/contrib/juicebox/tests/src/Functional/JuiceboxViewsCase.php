<?php

namespace Drupal\Tests\juicebox\Functional;

use Drupal\Core\Url;
use Drupal\file\Entity\File;
use Drupal\Component\Utility\Html;
use Drupal\image\Entity\ImageStyle;

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
  protected static $modules = [
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
  public function setUp(): void {
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
    $xml_url = Url::fromRoute('juicebox.xml_viewsstyle',
     ['viewName' => 'juicebox_views_test', 'displayName' => 'page_2']);
    // Get the urls to the test image and thumb derivative used by default.
    $uri = File::load($node->{$this->instFieldName}[0]->target_id)->getFileUri();
    $test_image_url = ImageStyle::load('juicebox_medium')->buildUrl($uri);
    $test_thumb_url = ImageStyle::load('juicebox_square_thumb')->buildUrl($uri);
    // Check for correct embed markup.
    $this->drupalGet('juicebox-views-test-advanced');
    $this->assertSession()->responseContains(trim(json_encode(['configUrl' => $xml_url]), '{}"'));
    $this->assertSession()->responseContains('juicebox-views-test--page-2');
    $this->assertSession()->responseContains(Html::escape(\Drupal::service('file_url_generator')->generateString($test_image_url)));
    // Check for correct XML.
    $this->drupalGet($xml_path);
    $this->assertSession()->responseContains('<?xml version="1.0" encoding="UTF-8"?>');
    $this->assertSession()->responseContains('imageURL="' . Html::escape($test_image_url));
    $this->assertSession()->responseContains('thumbURL="' . Html::escape($test_thumb_url));
    // Logout and test that XML access is restricted. Note that this test view
    // is setup to limit view access only to admins.
    $this->drupalLogout();
    $this->drupalGet('juicebox/xml/viewsstyle/juicebox_views_test/page_2');
    $this->assertSession()->statusCodeEquals(403);
  }

}
