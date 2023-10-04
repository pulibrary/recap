<?php

namespace Drupal\Tests\juicebox\Functional;

use Drupal\file\Entity\File;
use Drupal\Component\Utility\Html;
use Drupal\image\Entity\ImageStyle;

/**
 * Tests gallery-specific configuration logic for Juicebox galleries.
 *
 * @group Juicebox
 */
class JuiceboxConfCase extends JuiceboxCaseTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  protected static $modules = ['node', 'field_ui', 'image', 'juicebox'];

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
      'administer node fields',
      'administer node display',
      'bypass node access',
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
   * Test common Lite configuration logic for a Juicebox formatter.
   */
  public function testConfigLite() {
    $node = $this->node;
    // Check control case as anon user without custom configuration. This will
    // also prime the cache in order to test cache tag invalidation once the
    // settings are altered.
    $this->drupalGet('node/' . $node->id());
    $this->assertSession()->responseContains(trim(json_encode([
      'gallerywidth' => '100%',
      'galleryheight' => '100%',
      'backgroundcolor' => '#222222',
    ]), '{}'));
    $this->drupalGet('juicebox/xml/field/node/' . $node->id() . '/' . $this->instFieldName . '/full');
    $this->assertSession()->responseContains('<juicebox gallerywidth="100%" galleryheight="100%" backgroundcolor="#222222" textcolor="rgba(255,255,255,1)" thumbframecolor="rgba(255,255,255,.5)" showopenbutton="TRUE" showexpandbutton="TRUE" showthumbsbutton="TRUE" usethumbdots="FALSE" usefullscreenexpand="FALSE">');
    // Alter settings to contain custom values.
    $this->drupalLogin($this->webUser);
    $this->drupalGet('admin/structure/types/manage/' . $this->instBundle . '/display');
    $this->submitForm([], $this->instFieldName . '_settings_edit', 'entity-view-display-edit-form');
    $edit = [
      'fields[' . $this->instFieldName . '][settings_edit_form][settings][jlib_galleryWidth]' => '50%',
      'fields[' . $this->instFieldName . '][settings_edit_form][settings][jlib_galleryHeight]' => '200px',
      'fields[' . $this->instFieldName . '][settings_edit_form][settings][jlib_backgroundColor]' => 'red',
      'fields[' . $this->instFieldName . '][settings_edit_form][settings][jlib_textColor]' => 'green',
      'fields[' . $this->instFieldName . '][settings_edit_form][settings][jlib_thumbFrameColor]' => 'blue',
      'fields[' . $this->instFieldName . '][settings_edit_form][settings][jlib_showOpenButton]' => FALSE,
      'fields[' . $this->instFieldName . '][settings_edit_form][settings][jlib_showExpandButton]' => FALSE,
      'fields[' . $this->instFieldName . '][settings_edit_form][settings][jlib_showThumbsButton]' => FALSE,
      'fields[' . $this->instFieldName . '][settings_edit_form][settings][jlib_useThumbDots]' => TRUE,
      'fields[' . $this->instFieldName . '][settings_edit_form][settings][jlib_useFullscreenExpand]' => TRUE,
    ];
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains('Your settings have been saved.');
    // Now check the resulting XML again as an anon user.
    $this->drupalLogout();
    // Check for correct embed markup.
    $this->drupalGet('node/' . $node->id());
    $this->assertSession()->responseContains(trim(json_encode([
      'gallerywidth' => '50%',
      'galleryheight' => '200px',
      'backgroundcolor' => 'red',
    ]), '{}'));
    // Check for correct XML.
    $this->drupalGet('juicebox/xml/field/node/' . $node->id() . '/' . $this->instFieldName . '/full');
    $this->assertSession()->responseContains('<juicebox gallerywidth="50%" galleryheight="200px" backgroundcolor="red" textcolor="green" thumbframecolor="blue" showopenbutton="FALSE" showexpandbutton="FALSE" showthumbsbutton="FALSE" usethumbdots="TRUE" usefullscreenexpand="TRUE">');
  }

  /**
   * Test common Pro configuration logic for a Juicebox formatter.
   */
  public function testConfigPro() {
    $node = $this->node;
    // Do a set of control requests as an anon user that will also prime any
    // caches.
    $this->drupalGet('node/' . $node->id());
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet('juicebox/xml/field/node/' . $node->id() . '/' . $this->instFieldName . '/full');
    $this->assertSession()->statusCodeEquals(200);
    // Set new manual options and also add a manual customization that's
    // intended to override a custom Lite option.
    $this->drupalLogin($this->webUser);
    $this->drupalGet('admin/structure/types/manage/' . $this->instBundle . '/display');
    $this->submitForm([], $this->instFieldName . '_settings_edit', 'entity-view-display-edit-form');
    $edit = [
      'fields[' . $this->instFieldName . '][settings_edit_form][settings][jlib_showExpandButton]' => FALSE,
      'fields[' . $this->instFieldName . '][settings_edit_form][settings][manual_config]' => "sHoWoPeNbUtToN=\"FALSE\"\nshowexpandbutton=\"TRUE\"\ngallerywidth=\"50%\"\nmyCustomSetting=\"boomsauce\"",
    ];
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains('Your settings have been saved.');
    $this->drupalLogout();
    // Check for correct embed markup.
    $this->drupalGet('node/' . $node->id());
    $this->assertSession()->responseContains(trim(json_encode([
      'gallerywidth' => '50%',
      'galleryheight' => '100%',
      'backgroundcolor' => '#222222',
    ]), '{}'));
    // Check for correct XML.
    $this->drupalGet('juicebox/xml/field/node/' . $node->id() . '/' . $this->instFieldName . '/full');
    $this->assertSession()->responseContains('<juicebox gallerywidth="50%" galleryheight="100%" backgroundcolor="#222222" textcolor="rgba(255,255,255,1)" thumbframecolor="rgba(255,255,255,.5)" showopenbutton="FALSE" showexpandbutton="TRUE" showthumbsbutton="TRUE" usethumbdots="FALSE" usefullscreenexpand="FALSE" mycustomsetting="boomsauce">');
  }

  /**
   * Test common Advanced configuration logic for a Juicebox formatter.
   */
  public function testConfigAdvanced() {
    $node = $this->node;
    // Get the urls to the main image with and without "large" styling.
    $uri = File::load($node->{$this->instFieldName}[0]->target_id)->getFileUri();
    $test_image_url = \Drupal::service('file_url_generator')->generateAbsoluteString($uri);
    $test_image_url_formatted = ImageStyle::load('juicebox_medium')->buildUrl($uri);
    // Check control case without custom configuration.
    $this->drupalGet('juicebox/xml/field/node/' . $node->id() . '/' . $this->instFieldName . '/full');
    $this->assertSession()->responseContains('linkTarget="_blank"');
    $this->assertSession()->responseContains('linkURL="' . $test_image_url);
    // Set new advanced options.
    $this->drupalLogin($this->webUser);
    $this->drupalGet('admin/structure/types/manage/' . $this->instBundle . '/display');
    $this->submitForm([], $this->instFieldName . '_settings_edit', 'entity-view-display-edit-form');
    $edit = [
      'fields[' . $this->instFieldName . '][settings_edit_form][settings][image_style]' => 'juicebox_medium',
      'fields[' . $this->instFieldName . '][settings_edit_form][settings][linkurl_source]' => 'image_styled',
      'fields[' . $this->instFieldName . '][settings_edit_form][settings][linkurl_target]' => '_self',
      'fields[' . $this->instFieldName . '][settings_edit_form][settings][custom_parent_classes]' => 'my-custom-wrapper',
    ];
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains('Your settings have been saved.');
    $this->drupalLogout();
    // Check case with custom configuration.
    $this->drupalGet('juicebox/xml/field/node/' . $node->id() . '/' . $this->instFieldName . '/full');
    $this->assertSession()->responseContains('linkTarget="_self"');
    $this->assertSession()->responseContains('linkURL="' . Html::escape($test_image_url_formatted));
    // Also check for custom class in embed code.
    $this->drupalGet('node/' . $node->id());
    $this->assertSession()->responseContains('class="juicebox-parent my-custom-wrapper"');
  }

}
