<?php

namespace Drupal\Tests\libraries\Kernel\ExternalLibrary\Asset;

use Drupal\Tests\libraries\Kernel\LibraryTypeKernelTestBase;

/**
 * Provides a base test class for asset library type tests.
 */
abstract class AssetLibraryTestBase extends LibraryTypeKernelTestBase {

  /**
   * {@inheritdoc}
   *
   * LibraryManager requires system_get_info() which is in system.module.
   *
   * @see \Drupal\libraries\ExternalLibrary\LibraryManager::getRequiredLibraryIds()
   */
  protected static $modules = ['system'];

  /**
   * The Drupal core library discovery.
   *
   * @var \Drupal\Core\Asset\LibraryDiscoveryInterface
   */
  protected $coreLibraryDiscovery;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->coreLibraryDiscovery = $this->container->get('library.discovery');
  }

  /**
   * Get license name.
   */
  public function getLicenseName(): string {
    return version_compare(\Drupal::VERSION, '10.3.0', '<') ? 'GNU-GPL-2.0-or-later' : 'GPL-2.0-or-later';
  }

}
