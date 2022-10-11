<?php

declare(strict_types = 1);

namespace Drupal\Tests\cas\Functional;

use Drupal\contact\Entity\ContactForm;
use Drupal\Tests\cas\Traits\CasTestTrait;

/**
 * Tests the post-login destination.
 *
 * @group cas
 */
class CasPostLoginDestinationTest extends CasBrowserTestBase {

  use CasTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'cas',
    'cas_mock_server',
    'contact',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create a local user.
    $account = $this->createUser(['access site-wide contact form'], 'beavis');
    // Create a CAS user.
    $this->createCasUser('beavis', 'beavis@example.com', 'needtp', [
      'firstname' => 'Beavis',
      'lastname' => 'El Gran Cornholio',
    ], $account);

    // Create a contact form to redirect to after a successful login.
    ContactForm::create(['id' => 'feedback'])->save();
  }

  /**
   * Tests post-login destination.
   *
   * @group legacy
   */
  public function testDestination(): void {
    $this->casLogin('beavis@example.com', 'needtp', [
      'destination' => 'contact',
    ]);
    $this->assertSession()->addressEquals('/contact');
  }

}
